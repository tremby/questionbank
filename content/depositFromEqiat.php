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

deposititem($ai);

// remove from session memory to remove from Eqiat view
$ai->sessionRemove();

$title = "Item " . ($exists ? "updated" : "deposited");
include "htmlheader.php";
?>
<h2><?php echo htmlspecialchars($title); ?></h2>
<p>The item "<?php echo htmlspecialchars($ai->data("title")); ?>" has been <?php echo $exists ? "updated" : "deposited"; ?>.</p>
<?php
include "htmlfooter.php";

