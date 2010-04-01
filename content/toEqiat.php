<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

$errors = array();
$warnings = array();
$messages = array();

if (!isset($_REQUEST["qtiid"]))
	badrequest("no QTI ID was specified");

$item = getitem($_REQUEST["qtiid"]);

if (!$item)
	badrequest("no item with the given QTI ID exists in the database");

// if not cloning...
if (!isset($_REQUEST["clone"])) {
	// only the owner can edit it
	if (!loggedin())
		badrequest("you're not logged in so can't edit this item");
	if ($item["user"] != username())
		badrequest("you're not the owner of this item and so can't edit it");

	// if the item's already in session memory redirect straight to Eqiat
	if (isset($_SESSION["items"]) && array_key_exists($_REQUEST["qtiid"], $_SESSION["items"]))
		redirect(SITEROOT_WEB . "eqiat/#item_" . $_REQUEST["qtiid"]);
}

// make a QTIAssessmentItem object from the data we have and put it in session memory
$metadata = array(
	"description"	=>	$item["description"],
	"keywords"		=>	$item["keywords"],
);
$ai = xmltoqtiobject($item["xml"], $errors, $warnings, $messages, $metadata, isset($_REQUEST["clone"]));
if ($ai === false)
	servererror("Errors:\n" . implode("\n", $errors) . "\n\nWarnings:\n" . implode("\n", $warnings) . "\n\nMessages:\n" . implode("\n", $messages));

$ai->sessionStore();
redirect(SITEROOT_WEB . "eqiat/#item_" . $ai->getQTIID());

?>
