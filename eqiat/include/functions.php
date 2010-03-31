<?php

/*
 * Eqiat
 * Easy QTI Item Authoring Tool
 *
 * with Question Bank specific functions added
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

// Question Bank's functions----------------------------------------------------

function forbidden($message = "403: forbidden", $mimetype = "text/plain") {
	header("Content-Type: $mimetype", true, 403);
	echo $message;
	exit;
}

// return true if a user exists in the database
function userexists($username, $password = null, $ishash = false) {
	if (!$ishash)
		$password = md5($password);
	$query = "SELECT COUNT(*) FROM users WHERE username LIKE '" . $GLOBALS["db"]->escapeString($username) . "'";
	if (!is_null($password))
		$query .= " AND passwordhash='" . $GLOBALS["db"]->escapeString($password) . "'";

	return $GLOBALS["db"]->querySingle($query) === 1;
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
	return $GLOBALS["db"]->querySingle("SELECT COUNT(*) FROM items WHERE identifier='" . $GLOBALS["db"]->escapeString($qtiid) . "';") === 1;
}

// return the owner of an item with the given identifier
function itemowner($qtiid) {
	return $GLOBALS["db"]->querySingle("SELECT user FROM items WHERE identifier='" . $GLOBALS["db"]->escapeString($qtiid) . "';");
}

// Eqiat's functions------------------------------------------------------------

// array_filter recursively
function array_filter_recursive($input, $callback) {
	$output = array();
	foreach ($input as $key => $value) {
		if (is_array($value))
			$output[$key] = array_filter_recursive($value, $callback);
		else
			$output[$key] = call_user_func($callback, $value);
	}
	return $output;
}

// if magic quotes get/post/cookie is on, undo it by stripping slashes from each
function unmagic() {
	if (get_magic_quotes_gpc()) {
		$_GET = array_filter_recursive($_GET, "stripslashes");
		$_POST = array_filter_recursive($_POST, "stripslashes");
		$_COOKIE = array_filter_recursive($_COOKIE, "stripslashes");
	}
}

//exit with various HTTP statuses, most useful for Ajax-------------------------
function servererror($message = "server error") {
	header("Content-Type: text/plain", true, 500);
	if (is_array($message))
		foreach ($message as $m)
			echo "- " . $m . "\n";
	else
		echo $message . "\n";
	exit;
}
function badrequest($message = "bad request", $mimetype = "text/plain") {
	header("Content-Type: $mimetype", true, 400);
	if (is_array($message))
		foreach ($message as $m)
			echo "- " . $m . "\n";
	else
		echo $message . "\n";
	exit;
}
function ok($message = null, $mimetype = "text/plain") {
	if (is_null($message))
		header("Content-Type: text/plain", true, 204);
	else {
		header("Content-Type: $mimetype", true, 200);
		echo $message;
	}
	exit;
}
function notfound($message = "404: not found", $mimetype = "text/plain") {
	header("Content-Type: $mimetype", true, 404);
	echo $message;
	exit;
}

// make a string safe for XML
function xmlspecialchars($text) {
	return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
}

// add one SimpleXML tree to another
function simplexml_append(SimpleXMLElement $parent, SimpleXMLElement $new_child) {
	$node1 = dom_import_simplexml($parent);
	$dom_sxe = dom_import_simplexml($new_child);
	$node2 = $node1->ownerDocument->importNode($dom_sxe, true);
	$node1->appendChild($node2);
}

// return indented XML string from SimpleXML object
function simplexml_indented_string(SimpleXMLElement $xml) {
	$dom = dom_import_simplexml($xml)->ownerDocument;
	$dom->formatOutput = true;
	return $dom->saveXML();
}

// show an array of messages as HTML
function showmessages($messages, $title = "Message", $class = null) {
	if (!empty($messages)) { ?>
		<div<?php if (!is_null($class)) { ?> class="<?php echo $class; ?>"<?php } ?>>
			<h3><?php echo htmlspecialchars($title); ?></h3>
			<ul>
				<?php foreach ($messages as $message) { ?>
					<li><?php echo htmlspecialchars($message); ?></li>
				<?php } ?>
			</ul>
		</div>
	<?php }
}

// validate a string of QTI XML or SimpleXML element
// $errors, $warnings and $messages should be arrays
function validateQTI($xml, &$errors, &$warnings, &$messages) {
	if ($xml instanceof SimpleXMLElement)
		$xml = $xml->asXML();

	$pipes = null;
	$validate = proc_open("./run.sh", array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes, SITEROOT_LOCAL . "validate");
	if (!is_resource($validate)) {
		$errors[] = "Failed to start validator";
		return false;
	}

	// give QTI on stdin and close the pipe
	fwrite($pipes[0], $xml);
	fclose($pipes[0]);

	// get contents of stdout and stderr
	$stdout = trim(stream_get_contents($pipes[1]));
	fclose($pipes[1]);
	$stderr = trim(stream_get_contents($pipes[2]));
	fclose($pipes[2]);

	$exitcode = proc_close($validate);

	if (!empty($stderr))
		$errors = array_merge($errors, explode("\n", $stderr));
	if (!empty($stdout)) {
		$stdout = explode("\n", $stdout);
		foreach ($stdout as $message) {
			$parts = explode("\t", $message);
			switch ($parts[0]) {
				case "Error":
					$errors[] = "Validator error: {$parts[1]} ({$parts[2]})";
					break;
				case "Warning":
					$warnings[] = "Validator warning: {$parts[1]} ({$parts[2]})";
					break;
				default:
					$messages[] = "Validator message: {$parts[1]} ({$parts[2]})";
			}
		}
	}

	if (empty($errors) && $exitcode != 0)
		$errors[] = "Validator exited with code $exitcode";

	return $exitcode == 0;
}

// redirect to another URL
function redirect($destination = null, $anal = true, $permanent = false, $textonly = false) {
	session_write_close();
	header("HTTP/1.1 " . ($permanent ? "301 Moved Permamently" : "302 Moved Temporarily"));

	if (is_null($destination))
		$destination = $_SERVER["REQUEST_URI"];

	// HTTP spec says location has to be absolute. If we started with a slash, 
	// assume it started with the siteroot and so we can prepend the site's 
	// domain.
	// Otherwise if it doesn't start with http:// or https:// prepend the 
	// hostname and the directory of the current request URI
	if ($destination[0] == "/")
		$destination = "http://" . $_SERVER["HTTP_HOST"] . $destination;
	else if (!preg_match('%^https?://%', $destination))
		$destination = "http://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["REQUEST_URI"]) . "/" . $destination;

	header("Location: " . $destination);
	if ($anal)
		die("Tried and failed to redirect you. No worries – just follow this link: $destination\n");
}

// remove the XML declaration if it exists and the outer element from a string 
// of XML
function xml_remove_wrapper_element($xml) {
	return preg_replace(array('%^<\?xml[^>]*\?>\s*%', '%^<[^>]*/>$%s', '%^<[^>]*>(.*)</[^>]*>$%s'), array('', '', '$1'), trim($xml));
}

// get non-interaction XML from a QTI itemBody node (that is, the stimulus)
function qti_get_stimulus(SimpleXMLElement $ib) {
	$itemBodyIgnore = array(
		// subclasses of block:
		"customInteraction", "positionObjectStage",
		// subclasses of blockInteraction, which is an abstract subclass of 
		// block:
		"associateInteraction", "choiceInteraction", "drawingInteraction", 
		"extendedTextInteraction", "gapMatchInteraction", 
		"hottextInteraction", "matchInteraction", "mediaInteraction", 
		"orderInteraction", "sliderInteraction", "uploadInteraction",
		// subclasses of graphicInteraction, which is an abstract subclass 
		// of blockInteraction:
		"graphicAssociateInteraction", "graphicGapMatchInteraction", 
		"graphicOrderInteraction", "hotspotInteraction", 
		"selectPointInteraction",
	);

	$stimulus = simplexml_load_string('<stimulus xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"/>', null);
	foreach ($ib->children() as $child) {
		if (in_array($child->getName(), $itemBodyIgnore))
			continue;
		simplexml_append($stimulus, $child);
	}

	return xml_remove_wrapper_element($stimulus->asXML());
}

// get array of items, one of each type
function item_types() {
	// look for item type classes
	$dh = opendir(SITEROOT_LOCAL . "classes/itemtypes") or servererror("Couldn't open item types dir");
	$items = array();
	while (($file = readdir($dh)) !== false) {
		if (!preg_match('%^QTI.*\.class\.php$%', $file))
			continue;

		$classname = substr($file, 0, -10);

		// skip classes which aren't QTIAssessmentItems
		if (!is_subclass_of($classname, "QTIAssessmentItem"))
			continue;

		// skip abstract classes
		$rc = new ReflectionClass($classname);
		if ($rc->isAbstract())
			continue;

		$items[] = new $classname(false);
	}
	closedir($dh);

	usort($items, "compare_item_alpha");

	return $items;
}

// compare item types alphabetically by name
function compare_item_alpha(QTIAssessmentItem $a, QTIAssessmentItem $b) {
	return strcasecmp($a->itemTypePrint(), $b->itemTypePrint());
}

// get array of item action class objects
function item_actions() {
	// look for item type classes
	$dh = opendir(SITEROOT_LOCAL . "classes/itemactions") or servererror("Couldn't open item actions dir");
	$types = array();
	while (($file = readdir($dh)) !== false) {
		if (!preg_match('%^.+Action\.class\.php$%', $file))
			continue;

		$classname = substr($file, 0, -10);

		// skip classes which aren't ItemActions
		if (!is_subclass_of($classname, "ItemAction"))
			continue;

		// skip abstract classes
		$rc = new ReflectionClass($classname);
		if ($rc->isAbstract())
			continue;

		$types[] = new $classname;
	}
	closedir($dh);

	usort($types, "compare_action_alpha");

	return $types;
}

// compare item actions alphabetically by name
function compare_action_alpha(ItemAction $a, ItemAction $b) {
	return strcasecmp($a->name(), $b->name());
}

// return plural ending if appropriate
function plural($input, $pluralsuffix = "s", $singularsuffix = "") {
	if (is_array($input) && count($input) != 1 || is_numeric($input) && $input != 1)
		return $pluralsuffix;
	return $singularsuffix;
}

// return Javascript for item action links
function item_action_js() {
	$actions = item_actions();
	$jsparts = array();
	foreach ($actions as $action) {
		$js = $action->clickJS();
		if (is_null($js))
			continue;

		ob_start();
		?>
		$(".itemaction_<?php echo $action->actionString(); ?>").click(function(e) {
			<?php echo $js; ?>
		});
		<?php
		$jsparts[] = ob_get_clean();
	}
	if (empty($jsparts))
		return "";

	ob_start();
	?>
	$(document).ready(function() {
		<?php echo implode("\n", $jsparts); ?>
	});
	<?php
	return ob_get_clean();
}

// return a readable date in HTML form
function friendlydate_html($timestamp, $dayofweek = false, $html = true) {
	$diff = time() - $timestamp;
	if ($diff < 0 || $timestamp < strtotime("January 1 00:00")) {
		// future or not this year -- give full date
		$datestring = date("Y M j, H:i", $timestamp);
	} else if ($timestamp < strtotime("today")) {
		// yesterday or before
		$datestring = date("D, M j, H:i", $timestamp);
		if ($timestamp < strtotime("-6 days 00:00")) {
			// a week or more ago -- leave at month and day
		} else if ($timestamp < strtotime("-1 day 00:00")) {
			// before yesterday -- additionally give number of days ago
			$datestring .= " (" . round((strtotime("00:00") - strtotime("00:00", $timestamp)) /24/60/60) . "&nbsp;days&nbsp;ago)";
		} else {
			// yesterday -- say so
			$datestring .= " (yesterday)";
		}
	} else if ($diff >= 60*60) {
		// an hour or more ago -- give rough number of hours
		$hours = round($diff / 60 / 60);
		$datestring = $hours . " hour" . plural($hours) . " ago";
	} else if ($diff >= 60) {
		// a minute or more ago -- give rough number of minutes
		$minutes = round($diff / 60);
		$datestring = $minutes . " minute" . plural($minutes) . " ago";
	} else if ($diff > 20) {
		// 20 seconds or more ago -- give number of seconds
		$datestring = $diff . " seconds ago";
	} else
		$datestring = "just now";

	if ($html)
		return "<span class=\"date\" title=\"" . date("Y-m-d H:i:s T", $timestamp) . "\">$datestring</span>";
	return str_replace("&nbsp;", " ", $datestring);
}
// same in plain text
function friendlydate($timestamp, $dayofweek = false) {
	return friendlydate_html($timestamp, $dayofweek, false);
}

// parse text as XML, return SimpleXML if fine or array of errors if not
function stringtoxml($string, $title = "XML") {
	// switch on internal error handling, remember old value
	$internalerrors = libxml_use_internal_errors(true);

	// parse as XML
	$xml = simplexml_load_string($string);
	if ($xml === false) {
		// collect and return errors
		$errors = array();
		foreach (libxml_get_errors() as $error)
			$errors[] = ucfirst($title) . " line {$error->line}, column {$error->column}: {$error->message}";
		libxml_clear_errors();
		return $errors;
	} else
		return $xml;

	// reset internal errors to original value
	libxml_use_internal_errors($internalerrors);
}

// trim, then wrap XML in a div if it doesn't already start with one
function wrapindiv($string) {
	$string = trim($string);
	if (substr($string, 0, 4) != "<div")
		$string = "<div>" . $string . "</div>";
	return $string;
}

// return a string with its first letter changed to lowercase (this already 
// exists in PHP >= 5.3.0 which is not yet standard)
if (!function_exists("lcfirst")) {
	function lcfirst($string) {
		$string[0] = strtolower($string[0]);
		return $string;
	}
}

// return true if the user's on IE (of any version)
function usingIE() {
	return isset($_SERVER["HTTP_USER_AGENT"]) && (strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== false);
}

// return an associative array of HTTP response headers given an HTTP response 
// string
function response_headers($response) {
	$responseparts = explode("\r\n\r\n", $response);
	$headerstrings = explode("\r\n", $responseparts[0]);
	$headers = array();
	foreach ($headerstrings as $headerstring) {
		$headerparts = preg_split('%:\s*%', $headerstring);
		if (isset($headerparts[1]))
			$headers[$headerparts[0]] = $headerparts[1];
	}
	return $headers;
}

// return the body of an HTTP response which includes headers
function response_body($response) {
	if (strpos($response, "\r\n\r\n") === false)
		return "";
	return substr($response, strpos($response, "\r\n\r\n") + 4);
}

?>
