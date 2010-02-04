<?php

function handleupload(&$errors, &$warnings, &$messages) {
	if (empty($_FILES) || empty($_FILES["file"])) {
		$errors[] = "No file was uploaded";
		return;
	}
	if ($_FILES["file"]["error"]) {
		$errors[] = "There was a problem uploading the file &mdash; try again in a short while";
		return;
	}

	$xml = file_get_contents($_FILES["file"]["tmp_name"]);

	if (!validateQTI($xml, $errors, $warnings, $messages)) {
		$errors[] = "The uploaded file is not valid QTI";
		return;
	}

	$xml = simplexml_load_string($xml);

	$items = item_types();

	$scores = array();
	$ai = null;
	foreach ($items as $item) {
		$score = $item->fromXML($xml);
		$scores[] = $score;

		if ($score == 255) {
			$ai = $item;
			break;
		}
	}
	if (is_null($ai)) {
		arsort($scores);
		$keys = array_keys($scores);

		if ($scores[$keys[0]] == 0) {
			$errors[] = "The uploaded item was not of a recognized type";
			return;
		} else {
			$ai = $items[$keys[0]];
			$ai->addWarning("Item did not exactly match any of the implemented types. The closest match ({$ai->itemTypePrint()}, " . round($scores[$keys[0]] / 2.55) . "% match) was chosen.");
		}
	}

	// give it a new identifier if appropriate
	if (isset($_POST["newidentifier"]))
		$ai->setQTIID();

	// put it in session data
	if (!isset($_SESSION["items"]))
		$_SESSION["items"] = array();
	$_SESSION["items"][$ai->getQTIID()] = $ai;

	// take the user to the main menu with the uploaded item highlighted
	redirect("?#item_" . $ai->getQTIID());
}

$errors = array();
$warnings = array();
$messages = array();

if (isset($_POST["uploaditem"]))
	handleupload($errors, $warnings, $messages);

include "htmlheader.php";
?>
<h2>Upload an assessment item</h2>

<p>This form allows you to upload an existing assessment item so it can be 
edited or packaged. At present content packages are not accepted, only QTI 
XML.</p>

<?php
foreach(array("error" => $errors, "warning" => $warnings, "message" => $messages) as $type => $messages)
	showmessages($messages, ucfirst($type), $type);
?>

<form id="uploaditem" action="?page=uploadAssessmentItem" method="post" enctype="multipart/form-data">
	<dl>
		<dt><label for="file">File</label></dt>
		<dd><input id="file" type="file" name="file"></dd>

		<dt><label for="newidentifier">Give the item a new identifier</label></dt>
		<dd>
			<input type="checkbox" id="newidentifier" name="newidentifier" value="true">
			<span class="hint">Leave this box clear if you are updating or correcting an item; check it if you are making a new item using the uploaded one as a template</span>
		<dd>
	</dl>
	<div>
		<input type="hidden" name="MAX_FILE_SIZE" value="262144">
		<input id="submit" type="submit" name="uploaditem" value="Submit">
	</div>
</form>

<?php include "htmlfooter.php"; ?>
