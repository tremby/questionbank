<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (!loggedin())
	forbidden();

if (!isset($_REQUEST["qtiid"]))
	badrequest("no QTI ID was specified");

$item = getitem($_REQUEST["qtiid"]);

if (!$item)
	badrequest("no item with the given QTI ID exists in the database");

if ($item["user"] != username() && !userhasprivileges())
	forbidden();

// start transaction
db()->exec("BEGIN TRANSACTION;");

// delete ratings
db()->exec("DELETE FROM ratings WHERE item='" . db()->escapeString($item["identifier"]) . "';");

// delete comments
db()->exec("DELETE FROM comments WHERE item='" . db()->escapeString($item["identifier"]) . "';");

// delete keywords
db()->exec("DELETE FROM keywords WHERE item='" . db()->escapeString($item["identifier"]) . "';");

// delete item
db()->exec("DELETE FROM items WHERE identifier='" . db()->escapeString($item["identifier"]) . "';");

// commit changes
db()->exec("COMMIT;");

$title = "Item deleted";
include "htmlheader.php";
?>
<h2><?php echo htmlspecialchars($title); ?></h2>
<p>The item "<?php echo htmlspecialchars($item["title"]); ?>" has been deleted 
along with all of its ratings and comments.</p>
<?php
include "htmlfooter.php";
?>
