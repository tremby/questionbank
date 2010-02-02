<?php

if (!isset($_REQUEST["qtiid"])) die("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) die("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

if (isset($_POST["edititem"])) {
	// form submitted -- try to build QTI

	// if posted itemtype is different to the current one, make a new object
	if (isset($_POST["itemtype"]) && $_POST["itemtype"] != $ai->itemType()) {
		$olditem = $ai;

		$classname = "QTI" . ucfirst($_POST["itemtype"]);

		if (!@class_exists($classname) || !is_subclass_of($classname, "QTIAssessmentItem"))
			die("Item type doesn't exist or not implemented");

		$ai = new $classname;

		// replace old object in session data
		if (!isset($_SESSION["items"]))
			$_SESSION["items"] = array();
		else
			foreach($_SESSION["items"] as $id => $item)
				if ($olditem == $_SESSION["items"][$id])
					$_SESSION["items"][$id] = $ai;
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
	include "htmlheader.php";
	?>
	<h2>New QTI item complete</h2>
	<p>The new item has been successfully validated<?php if (!empty($thingstosay)) { ?> with the following <?php echo implode(" and ", $thingstosay); ?>:<?php } ?></p>
	<?php
	$ai->showmessages();

	// show preview and download links
	?>
	<h3>QTIEngine preview</h3>
	<iframe width="80%" height="400" src="?page=previewAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>"></iframe>

	<h3>Actions</h3>
	<ul>
		<li><a href="?">Go back to the main menu and item list</a></li>
		<li><a href="?page=previewAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Preview the item full screen</a></li>
		<li><a href="?page=downloadAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Download the QTI item as an XML file</a></li>
		<li><a href="?page=makeContentPackage&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Download the QTI item as a content package</a> to include its metadata</li>
		<li><a href="?page=editAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">Edit this item</a> further</li>
	</ul>

	<?php
	include "htmlfooter.php";
	exit;
}

// nothing posted -- show form with data as is (possibly empty)
$ai->showForm();

?>
