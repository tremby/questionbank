<?php

// constants
require_once "include/constants.php";

// class autoloader
function __autoload($classname) {
	require_once "classes/$classname.class.php";
}

// set up include path
ini_set("include_path", ".:" . SITEROOT_LOCAL . "include");

// common functions
require_once "include/functions.php";

// character encoding
mb_internal_encoding("UTF-8");

// default timezone
date_default_timezone_set("Europe/London");

// undo any magic quotes
unmagic();

// serve the page
$page = isset($_GET["page"]) ? $_GET["page"] : "home";
switch($page) {
	default:
		if (dirname("content/$page.php") == "content" && file_exists("content/$page.php"))
			include "content/$page.php";
		else
			notfound();
}

?>
