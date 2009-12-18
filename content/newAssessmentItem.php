<?php

if (isset($_REQUEST["itemtype"])) {
	// item type chosen
	// look for a item type class with this name

	$classname = "QTI" . ucfirst($_REQUEST["itemtype"]);

	if (!@class_exists($classname) || !is_subclass_of($classname, "QTIAssessmentItem"))
		die("Item type doesn't exist or not implemented");

	$ai = new $classname;

	// store new item in session data
	if (!isset($_SESSION["items"]) || !is_array($_SESSION["items"]))
		$_SESSION["items"] = array();
	$_SESSION["items"][$ai->getQTIID()] = $ai;

	redirect("?page=editAssessmentItem&qtiid=" . $ai->getQTIID());
}

// choose from a list of item types

$items = item_types();

include "htmlheader.php";
?>
<h2>Make a new item</h2>
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
