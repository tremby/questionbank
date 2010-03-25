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

	CREATE TABLE IF NOT EXISTS comments (
		user TEXT NOT NULL,
		item TEXT NOT NULL,
		comment TEXT NOT NULL,
		posted INTEGER NOT NULL
	);
	CREATE UNIQUE INDEX IF NOT EXISTS comments_user_item ON comments (user ASC, item ASC);

	COMMIT;
");
var_dump($result);
exit;

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
