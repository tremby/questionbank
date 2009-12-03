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
			// new QTI is fine -- display it and any warnings and messages

			$thingstosay = array();
			$tmp = $ai->getWarnings();
			if (!empty($tmp)) $thingstosay[] = "warnings";
			$tmp = $ai->getMessages();
			if (!empty($tmp)) $thingstosay[] = "messages";

			include "htmlheader.php";
			?>

			<h2>New QTI item complete</h2>
			<p>The new item has been successfully validated<?php if (!empty($thingstosay)) { ?> with the following <?php echo implode(" and ", $thingstosay); ?>:<?php } ?></p>

			<?php $ai->showmessages(); ?>

			<h3>XML</h3>
			<iframe width="80%" height="400" src="data:text/xml;base64,<?php echo base64_encode($ai->getQTI()->asXML()); ?>"></iframe>

			<h3>As plain text</h3>
			<div style="width: 80%; height: 400px; overflow: auto;">
				<pre><?php
					$dom = dom_import_simplexml($ai->getQTI())->ownerDocument;
					$dom->formatOutput = true;
					echo htmlspecialchars($dom->saveXML());
				?></pre>
			</div>

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

	// look for item type classes
	$dh = opendir(SITEROOT_LOCAL . "classes/itemtypes") or die("Couldn't open item types dir");
	$items = array();
	while (($file = readdir($dh)) !== false) {
		if (!preg_match('%^QTI.*\.class\.php$%', $file))
			continue;

		$classname = substr($file, 0, -10);

		if (!is_subclass_of($classname, "QTIAssessmentItem"))
			continue;

		$items[] = new $classname;
	}
	closedir($dh);

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
