<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

// error reporting
error_reporting(E_ALL | E_STRICT);

// constants
require_once "include/constants.php";

// class autoloader
function __autoload($classname) {
	$path = "eqiat/classes/$classname.class.php";
	if (dirname($path) == "eqiat/classes" && file_exists($path)) {
		require_once $path;
		return;
	}
	$path = "eqiat/classes/itemtypes/$classname.class.php";
	if (dirname($path) == "eqiat/classes/itemtypes" && file_exists($path)) {
		require_once $path;
		return;
	}
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

// database
$db = new SQLite3("db/db.sqlite");
$result = $db->exec("
	BEGIN TRANSACTION;
	CREATE TABLE IF NOT EXISTS items (
		identifier text not null,
		uploaded integer not null,
		modified integer null,
		user text not null,
		title text not null,
		description text null,
		xml blob not null
	);
	CREATE TABLE IF NOT EXISTS keywords (
		item text not null,
		keyword text not null
	);
	CREATE TABLE IF NOT EXISTS users (
		username text not null,
		passwordhash text not null,
		registered integer not null
	);
	CREATE TABLE IF NOT EXISTS ratings (
		user text not null,
		item text not null,
		rating integer null,
		comment integer null
	);
	COMMIT;
");

// start sessions
session_start();

// serve the page
$page = isset($_GET["page"]) ? $_GET["page"] : "mainMenu";
switch ($page) {
	default:
		if (dirname("content/$page.php") == "content" && file_exists("content/$page.php"))
			include "content/$page.php";
		else
			notfound();
}

?>
