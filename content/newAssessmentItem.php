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

if (isset($_REQUEST["itemtype"])) {
	// item type chosen
	// look for a item type class with this name

	$classname = "QTI" . ucfirst($_REQUEST["itemtype"]);

	if (!@class_exists($classname) || !is_subclass_of($classname, "QTIAssessmentItem"))
		servererror("Item type doesn't exist or not implemented");

	$ai = new $classname;

	// store new item in session data
	if (!isset($_SESSION["items"]) || !is_array($_SESSION["items"]))
		$_SESSION["items"] = array();
	$_SESSION["items"][$ai->getQTIID()] = $ai;

	redirect("?page=editAssessmentItem&qtiid=" . $ai->getQTIID());
}

// choose from a list of item types

$items = item_types();

$GLOBALS["title"] = "New assessment item";
include "htmlheader.php";
?>
<h2><?php echo $GLOBALS["title"]; ?></h2>
<p>The first stage is to choose an item type.</p>
<dl>
	<?php foreach ($items as $item) { ?>
		<dt>
			<a href="?page=newAssessmentItem&amp;itemtype=<?php echo $item->itemType(); ?>">
				<?php echo htmlspecialchars(ucfirst($item->itemTypePrint())); ?>
			</a>
		</dt>
		<dd><?php echo htmlspecialchars(ucfirst($item->itemTypeDescription())); ?></dd>
	<?php } ?>
</dl>
<?php
include "htmlfooter.php";

?>
