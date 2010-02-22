<?php

/*
 * Eqiat
 * Easy QTI Item Authoring Tool
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (!isset($_REQUEST["qtiid"])) badrequest("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) badrequest("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

if (isset($_POST["edititem"])) {
	// form submitted -- try to build QTI

	// if posted itemtype is different to the current one, make a new object
	if (isset($_POST["itemtype"]) && $_POST["itemtype"] != $ai->itemType()) {
		$olditem = $ai;

		$classname = "QTI" . ucfirst($_POST["itemtype"]);

		if (!@class_exists($classname) || !is_subclass_of($classname, "QTIAssessmentItem"))
			badrequest("Item type doesn't exist or not implemented");

		$ai = new $classname;

		// keep the old identifier
		$ai->setQTIID($olditem->getQTIID());

		// replace old object in session data
		if (!isset($_SESSION["items"]))
			$_SESSION["items"] = array();
		else
			foreach($_SESSION["items"] as $id => $item)
				if ($olditem == $_SESSION["items"][$id])
					$_SESSION["items"][$id] = $ai;

		unset($olditem);
	}

	if ($ai->getQTI($_POST) === false) {
		// problem of some kind, show the form again with any messages
		$ai->showForm($_POST);
		exit;
	}

	// new QTI is fine

	// display any warnings and messages
	$thingstosay = array();
	$tmp = $ai->getWarnings();
	if (!empty($tmp)) $thingstosay[] = "warnings";
	$tmp = $ai->getMessages();
	if (!empty($tmp)) $thingstosay[] = "messages";
	$title = "Item \"" . htmlspecialchars($ai->data("title")) . "\" complete";
	include "htmlheader.php";
	?>
	<h2><?php echo $title; ?></h2>
	<p>The item has been successfully validated<?php if (!empty($thingstosay)) { ?> with the following <?php echo implode(" and ", $thingstosay); ?>:<?php } ?></p>
	<?php
	$ai->showmessages();

	// show preview and download links
	?>
	<h3>QTIEngine preview</h3>
	<?php if (usingIE()) { //iframe isn't available in HTML 4 Strict but IE (tested on 8) doesn't like object elements used for embedded HTML ?>
		<iframe width="100%" height="400" src="?page=previewAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>"></iframe>
	<?php } else { ?>
		<object class="embeddedhtml" width="100%" height="400" type="text/html" data="?page=previewAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>"></object>
	<?php } ?>

	<h3>Actions</h3>
	<ul>
		<li><a href="<?php echo SITEROOT_WEB; ?>">Go back to the main menu and item list</a></li>
		<li><a href="<?php echo SITEROOT_WEB; ?>?page=previewAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Preview the item full screen</a></li>
		<li><a href="<?php echo SITEROOT_WEB; ?>?page=downloadAssessmentItemXML&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Download the QTI item as an XML file</a></li>
		<li><a href="<?php echo SITEROOT_WEB; ?>?page=downloadAssessmentItemContentPackage&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Download the QTI item as a content package</a> to include its metadata</li>
		<li><a href="<?php echo SITEROOT_WEB; ?>?page=editAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Edit this item</a> further</li>
		<li><a href="<?php echo SITEROOT_WEB; ?>?page=cloneAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Clone this item</a> to use it as a template for a new item</li>
	</ul>

	<?php
	include "htmlfooter.php";
	exit;
}

// nothing posted -- show form with data as is (possibly empty)
$ai->showForm();

?>
