<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

function forbidden($message = "403: forbidden", $mimetype = "text/plain") {
	header("Content-Type: $mimetype", true, 403);
	echo $message;
	exit;
}

// return the database object, connecting and setting up the schema first if 
// necessary
function db() {
	if (array_key_exists("db", $GLOBALS))
		return $GLOBALS["db"];

	$GLOBALS["db"] = new SQLite3((basename(SITEROOT_LOCAL) == "eqiat" ? dirname(SITEROOT_LOCAL) . "/" : SITEROOT_LOCAL) . "db/db.sqlite");
	$GLOBALS["db"]->exec("
		BEGIN TRANSACTION;

		CREATE TABLE IF NOT EXISTS items (
			identifier TEXT PRIMARY KEY ASC NOT NULL,
			uploaded INTEGER NOT NULL,
			modified INTEGER NULL,
			user TEXT NOT NULL,
			title TEXT NOT NULL,
			description TEXT NULL,
			xml BLOB NOT NULL
		);
		CREATE INDEX IF NOT EXISTS items_user ON items (user ASC);

		CREATE TABLE IF NOT EXISTS keywords (
			item TEXT NOT NULL,
			keyword TEXT NOT NULL
		);
		CREATE INDEX IF NOT EXISTS keywords_item ON keywords (item ASC);
		CREATE INDEX IF NOT EXISTS keywords_keyword ON keywords (keyword ASC);

		CREATE TABLE IF NOT EXISTS users (
			username TEXT PRIMARY KEY ASC NOT NULL,
			passwordhash TEXT NOT NULL,
			registered INTEGER NOT NULL,
			privileges INTEGER NOT NULL DEFAULT 0,
			deleted INTEGER NOT NULL DEFAULT 0
		);

		CREATE TABLE IF NOT EXISTS ratings (
			user TEXT NOT NULL,
			item TEXT NOT NULL,
			rating INTEGER NOT NULL,
			posted INTEGER NOT NULL
		);
		CREATE INDEX IF NOT EXISTS ratings_item ON ratings (item ASC);
		CREATE INDEX IF NOT EXISTS ratings_posted ON ratings (posted ASC);

		CREATE TABLE IF NOT EXISTS comments (
			user TEXT NOT NULL,
			item TEXT NOT NULL,
			comment TEXT NOT NULL,
			posted INTEGER NOT NULL
		);
		CREATE INDEX IF NOT EXISTS comments_item ON comments (item ASC);

		COMMIT;
	");
	return $GLOBALS["db"];
}

// return true if a user exists in the database
// if checking a password, deleted users do not exist (therefore a deleted user 
// can't log in)
// otherwise, deleted users do exist (therefore a new user can't register with a 
// previously used username)
function userexists($username, $password = null, $ishash = false) {
	if (!is_null($password) && !$ishash)
		$password = md5($password);
	$query = "SELECT COUNT(*) FROM users WHERE username LIKE '" . db()->escapeString($username) . "'";
	if (!is_null($password))
		$query .= " AND passwordhash='" . db()->escapeString($password) . "' AND deleted=0";

	return db()->querySingle($query) === 1;
}

// return true if a user exists but has been deleted
function userdeleted($username) {
	return (boolean) db()->querySingle("SELECT COUNT(*) FROM users WHERE username LIKE '" . db()->escapeString($username) . "' AND deleted=1;");
}

// return true if the user (named or current) has raised privileges
function userhasprivileges($user = null) {
	if (is_null($user)) {
		if (!loggedin())
			return false;
		$user = username();
	}
	if (!userexists($user)) {
		echo "user doesn't exist";
		return false;
	}
	return (boolean) db()->querySingle("SELECT privileges FROM users WHERE username='" . db()->escapeString($user) . "';");
}

// return a count of privileged users
function privilegedusers() {
	return db()->querySingle("SELECT COUNT(*) FROM users WHERE privileges=1;");
}

// attempt to log in
function login($username, $password, $ishash = false) {
	if (userexists($username, $password, $ishash)) {
		$_SESSION[SITE_TITLE . "_username"] = $username;
		$_SESSION[SITE_TITLE . "_passwordhash"] = $ishash ? $password : md5($password);
		return true;
	}
	return false;
}

// log out
function logout() {
	unset($_SESSION[SITE_TITLE . "_username"], $_SESSION[SITE_TITLE . "_passwordhash"]);
}

// user is logged in
function loggedin() {
	return isset($_SESSION[SITE_TITLE . "_username"]) && isset($_SESSION[SITE_TITLE . "_passwordhash"]) && userexists($_SESSION[SITE_TITLE . "_username"], $_SESSION[SITE_TITLE . "_passwordhash"], true);
}

// return username or false if not logged in
function username() {
	if (loggedin())
		return $_SESSION[SITE_TITLE . "_username"];
	return false;
}

// if a user is not logged in, show a login form and exit or, if async, send 403 forbidden
function requirelogin() {
	if (loggedin())
		return;
	if (isset($_REQUEST["async"]))
		forbidden();

	$_SESSION["nextpage"] = $_SERVER["REQUEST_URI"];
	include "content/login.php";
	exit;
}

// return true if an item with the given identifier exists in the database
function itemexists($qtiid) {
	return db()->querySingle("SELECT COUNT(*) FROM items WHERE identifier='" . db()->escapeString($qtiid) . "';") === 1;
}

// return the owner of an item with the given identifier
function itemowner($qtiid) {
	return db()->querySingle("SELECT user FROM items WHERE identifier='" . db()->escapeString($qtiid) . "';");
}

// return the item with the given identifier from the database
function getitem($qtiid) {
	if (!itemexists($qtiid))
		return false;

	// get item
	$item = db()->querySingle("SELECT * FROM items WHERE identifier='" . db()->escapeString($qtiid) . "';", true);

	// get keywords
	$item["keywords"] = array();
	$result = db()->query("SELECT keyword FROM keywords WHERE item='" . db()->escapeString($qtiid) . "' ORDER BY keyword ASC;");
	while ($row = $result->fetchArray(SQLITE3_NUM))
		$item["keywords"][] = $row[0];

	// get rating (since last modification)
	$rating = db()->querySingle("
		SELECT AVG(rating) AS rating, COUNT(rating) AS ratingcount
		FROM ratings
		WHERE item='" . db()->escapeString($qtiid) . "'
		AND posted > " . max($item["uploaded"], is_null($item["modified"]) ? 0 : $item["modified"]) . "
	;", true);
	$item["ratingcount"] = $rating["ratingcount"];
	$item["rating"] = $rating["ratingcount"] > 0 ? $rating["rating"] : null;

	// get comments
	$item["comments"] = array();
	$result = db()->query("
		SELECT
			comments.user AS user,
			comments.comment AS comment,
			comments.posted AS posted,
			ratings.rating AS rating,
			users.deleted AS userdeleted
		FROM comments
		LEFT JOIN ratings ON comments.user=ratings.user AND comments.item=ratings.item AND comments.posted=ratings.posted
		LEFT JOIN users ON comments.user=users.username
		WHERE comments.item='" . db()->escapeString($qtiid) . "'
		ORDER BY posted ASC;
	");
	while ($row = $result->fetchArray(SQLITE3_ASSOC))
		$item["comments"][] = $row;

	return $item;
}

// return the current user's rating of a given item
function itemrating($qtiid) {
	if (!loggedin() || !itemexists($qtiid))
		return false;

	$item = getitem($qtiid);
	$result = db()->query("
		SELECT rating
		FROM ratings
		WHERE item='" . db()->escapeString($qtiid) . "'
		AND posted > " . max($item["uploaded"], is_null($item["modified"]) ? 0 : $item["modified"]) . "
		AND user='" . db()->escapeString(username()) . "'
	;");
	if ($row = $result->fetchArray(SQLITE3_NUM))
		return $row[0];
	return null;
}

// turn a string of xhtml into html
function xhtml_to_html($xhtml) {
	$selfclosing = array(
		"area",
		"base",
		"basefont",
		"br",
		"col",
		"frame",
		"hr",
		"img",
		"input",
		"link",
		"meta",
		"param",
	);

	// HTML's self closing tags don't need to be closed
	// (catch both <tag.../> and <tag...></tag>)
	$html = preg_replace('%<(' . implode("|", $selfclosing) . ')\b([^>]*?)\s*(/>|>\s*</\1>)%i', '<\1\2>', $xhtml);

	// other empty tags in the short style (eg <div/>) need to be opened and 
	// closed
	$html = preg_replace('%<(.+?)\b([^>]*?)\s*/>%', '<\1\2></\1>', $html);

	// get rid of any xhtml namespace tags
	$html = preg_replace('%\s+xmlns=(["\'])http://www.w3.org/1999/xhtml\1%i', '', $html);

	return $html;
}

// given the page SimpleXML element of a response from QTIEngine, extract the 
// important bits of the header (javascript and stylesheet links) and return 
// them as an HTML string to be put in the header
function qtiengine_header_html(SimpleXMLElement $page) {
	$headerextra = "";

	// javascript
	foreach ($page->html->head->script as $script) {
		if (isset($script["src"]) && isset($script["type"]) && ((string) $script["type"] == "text/javascript" || (string) $script["type"] == "application/javascript")) {
			// TODO: cater for inline scripts as well as included ones
			ob_start();
			?>
			<script type="text/javascript" src="<?php echo (string) $script["src"]; ?>"></script>
			<?php
			$headerextra .= ob_get_clean();
		}
	}

	// stylesheets
	foreach ($page->html->head->link as $link) {
		if (isset($link["rel"]) && (string) $link["rel"] == "stylesheet" && isset($link["href"]) && isset($link["type"]) && (string) $link["type"] == "text/css") {
			// TODO: cater for inline styles as well as included ones
			ob_start();
			?>
			<link rel="stylesheet" type="text/css"<?php if (isset($link["media"])) { ?> media="<?php echo (string) $link["media"]; ?>"<?php } ?> href="<?php echo (string) $link["href"]; ?>">
			<?php
			$headerextra .= ob_get_clean();
		}
	}

	return $headerextra;
}

// given the page SimpleXML element of a response from QTIEngine, extract the 
// div with id "body" and convert to HTML
// the default QTIEngine XSL transformation has a div with everything we want in 
// it with id "body" (it doesn't include the internal state etc)
// also replace h2 tags with h3 and strip hr tags
function qtiengine_bodydiv_html(SimpleXMLElement $page, $divid = "qtienginebodydiv") {
	// php5's support for default namespace is useless so we have to define it 
	// manually
	$namespaces = $page->html->getNamespaces();
	$defaultnamespace = $namespaces[""];
	$page->registerXPathNamespace("n", $defaultnamespace);

	$bodydivs = $page->xpath("//n:div[@id='body']");
	if (count($bodydivs) != 1)
		servererror("didn't get expected HTML output from QTIEngine");
	$bodydiv = $bodydivs[0];
	$bodydiv["id"] = $divid;

	return preg_replace(array('%<(/?)h2\b%', '%<hr.*?>%'), array('<\1h3', ''), xhtml_to_html(simplexml_indented_string($bodydiv)));
}

function callstack($html = true) {
	$bt = debug_backtrace();

	// lose the call to this function
	array_shift($bt);

	echo "backtrace:\n";
	if ($html) echo "<ul>";
	foreach ($bt as $call) {
		if ($html) echo "<li>";
		echo $call["file"] . ":" . $call["line"] . " calls " . $call["function"] . "(" . implode(", ", $call["args"]) . ")";
		if ($html)
			echo "</li>";
		else
			echo "\n";
	}
	if ($html) echo "</ul>";
}

?>
