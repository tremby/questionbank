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
			registered INTEGER NOT NULL
		);

		CREATE TABLE IF NOT EXISTS ratings (
			user TEXT NOT NULL,
			item TEXT NOT NULL,
			rating INTEGER NOT NULL,
			posted INTEGER NOT NULL
		);
		CREATE UNIQUE INDEX IF NOT EXISTS ratings_user_item ON ratings (user ASC, item ASC);
		CREATE INDEX IF NOT EXISTS ratings_item ON ratings (item ASC);

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
function userexists($username, $password = null, $ishash = false) {
	if (!$ishash)
		$password = md5($password);
	$query = "SELECT COUNT(*) FROM users WHERE username LIKE '" . db()->escapeString($username) . "'";
	if (!is_null($password))
		$query .= " AND passwordhash='" . db()->escapeString($password) . "'";

	return db()->querySingle($query) === 1;
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

	return $item;
}

?>
