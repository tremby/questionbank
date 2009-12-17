<?php

if (isset($_REQUEST["itemtype"])) {
	// item type chosen
	// look for a item type class with this name

	$classname = "QTI" . ucfirst($_REQUEST["itemtype"]);

	if (!@class_exists($classname))
		die("Item type doesn't exist or not implemented");

	if (!is_subclass_of($classname, "QTIAssessmentItem"))
		die("Item type doesn't exist or not implemented");

	$ai = new $classname;

	if (isset($_POST["newitem"])) {
		// form submitted -- try to build QTI

		if ($ai->getQTI($_POST) === false) {
			// problem of some kind, show the form again with any messages
			$ai->showForm($_POST);
		} else {
			// new QTI is fine

			// store item in session data
			if (!isset($_SESSION["items"]) || !is_array($_SESSION["items"]))
				$_SESSION["items"] = array();
			$_SESSION["items"][$ai->getQTIID()] = $ai;

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

			<h3>Download the item</h3>
			<p>You can <a href="?page=downloadAssessmentItem&amp;qtiid=<?php echo $ai->getQTIID(); ?>">download the QTI item as an XML file</a>.</p>

			<h3>Make a content package</h3>
			<p>You can <a href="?page=makeContentPackage&amp;qtiid=<?php echo $ai->getQTIID(); ?>">package this item</a> to include metadata.</p>

			<?php
			include "htmlfooter.php";
			exit;
		}
	} else {
		// nothing posted -- empty form
		$ai->showform();
	}
} else if (isset($_POST["xml"])) {
	// XML submitted

	// validate it
	$errors = array();
	$warnings = array();
	$messages = array();
	if (!validateQTI($_POST["xml"], $errors, $warnings, $messages)) {
		// give error messages
		include "htmlheader.php";
		?>
		<h2>Posted string was not valid QTI</h2>
		<?php
		showmessages($errors, "Error", "error");
		showmessages($warnings, "Warning", "warning");
		showmessages($messages, "Message", "message");
		include "htmlfooter.php";
		exit;
	}

	// parse it and reform it so it's like form data
	$xml = simplexml_load_string($_POST["xml"]);
	die("validated OK");
} else {
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
}

?>
