<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

requirelogin();

if (!isset($_REQUEST["qtiid"]))
	redirect("eqiat/");

$ai = QTIAssessmentItem::fromQTIID($_REQUEST["qtiid"]);
if (!$ai)
	badrequest("No QTI found in session data for specified QTI ID");
if (!$ai->getQTI() || count($ai->getErrors()))
	badrequest("Specified QTI item is unfinished or has errors");
if (($exists = itemexists($ai->getQTIID())) && itemowner($ai->getQTIID()) != username())
	badrequest("The item you are trying to deposit was already uploaded by a different user. You should clone it so it gets a new identifier and then try again.");

db()->exec("BEGIN TRANSACTION;");
if ($exists) {
	// item exists and was uploaded by this user -- update it
	db()->exec("
		DELETE FROM keywords WHERE item='" . db()->escapeString($ai->getQTIID()) . "';
		UPDATE items SET
			modified=" . time() . ",
			title='" . db()->escapeString($ai->data("title")) . "',
			description='" . db()->escapeString($ai->data("description")) . "',
			xml='" . db()->escapeString($ai->getQTIIndentedString()) . "'
		WHERE identifier='" . db()->escapeString($ai->getQTIID()) . "';
	");

	// add a comment to the item to show it has been updated
	db()->exec("
		INSERT INTO comments VALUES (
			'" . db()->escapeString(username()) . "',
			'" . db()->escapeString($ai->getQTIID()) . "',
			'" . db()->escapeString("Automatic comment: this item has been updated") . "',
			" . time() . "
		);
	");
} else {
	// new item -- insert it
	db()->exec("
		INSERT INTO items VALUES (
			'" . db()->escapeString($ai->getQTIID()) . "',
			" . time() . ",
			NULL,
			'" . db()->escapeString(username()) . "',
			'" . db()->escapeString($ai->data("title")) . "',
			'" . db()->escapeString($ai->data("description")) . "',
			'" . db()->escapeString($ai->getQTIIndentedString()) . "'
		);
	");
}

// add keywords
foreach ($ai->getKeywords() as $keyword) {
	db()->exec("
		INSERT INTO keywords VALUES (
			'" . db()->escapeString($ai->getQTIID()) . "',
			'" . db()->escapeString($keyword) . "'
		);
	");
}

// commit changes
db()->exec("COMMIT;");

// remove from session memory to remove from Eqiat view
$ai->sessionRemove();

$title = "Item " . ($exists ? "updated" : "deposited");
include "htmlheader.php";
?>
<h2><?php echo htmlspecialchars($title); ?></h2>
<p>The item "<?php echo htmlspecialchars($ai->data("title")); ?>" has been <?php echo $exists ? "updated" : "deposited"; ?>.</p>
<?php
include "htmlfooter.php";

