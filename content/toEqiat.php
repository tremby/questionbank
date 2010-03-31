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

// if not cloning and the item's already in session memory redirect straight to 
// Eqiat
if (!isset($_REQUEST["clone"]) && isset($_SESSION["items"]) && array_key_exists($_REQUEST["qtiid"], $_SESSION["items"]))
	redirect(SITEROOT_WEB . "eqiat/#item_" . $_REQUEST["qtiid"]);

$item = getitem($_REQUEST["qtiid"]);

if (!$item)
	badrequest("no item with the given QTI ID exists in the database");

$metadata = array(
	"description"	=>	$item["description"],
	"keywords"		=>	$item["keywords"],
);

$ai = xmltoqtiobject($item["xml"], $errors, $warnings, $messages, $metadata, isset($_REQUEST["clone"]));
if ($ai !== false)
	redirect(SITEROOT_WEB . "eqiat/#item_" . $ai->getQTIID());

servererror("Errors:\n" . implode("\n", $errors) . "\n\nWarnings:\n" . implode("\n", $warnings) . "\n\nMessages:\n" . implode("\n", $messages));

?>
