<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

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

// return plural ending if appropriate
function plural($input, $pluralsuffix = "s", $singularsuffix = "") {
	if (is_array($input) && count($input) != 1 || is_numeric($input) && $input != 1)
		return $pluralsuffix;
	return $singularsuffix;
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

// return true if the user's on IE (of any version)
function usingIE() {
	return isset($_SERVER["HTTP_USER_AGENT"]) && (strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== false);
}

// return true if a user exists in the database
function userexists($username, $password = null) {
	$query = "SELECT COUNT(*) FROM users WHERE username LIKE '" . $GLOBALS["db"]->escapeString($username) . "'";
	if (!is_null($password))
		$query .= " AND password = '" . $GLOBALS["db"]->escapeString(md5($password)) . "'";

	return $GLOBALS["db"]->querySingle($query) === 1;
}
?>
