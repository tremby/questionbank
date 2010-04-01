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
	$ai->sessionStore();

	$action = new EditAssessmentItemAction();
	redirect($action->actionURL($ai, false));
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
			<a href="<?php echo SITEROOT_WEB; ?>?page=newAssessmentItem&amp;itemtype=<?php echo $item->itemType(); ?>">
				<?php echo htmlspecialchars(ucfirst($item->itemTypePrint())); ?>
			</a>
		</dt>
		<dd><?php echo htmlspecialchars(ucfirst($item->itemTypeDescription())); ?></dd>
	<?php } ?>
</dl>
<?php
include "htmlfooter.php";

?>
