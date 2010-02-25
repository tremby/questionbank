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

function handleupload(&$errors, &$warnings, &$messages) {
	if (empty($_FILES) || empty($_FILES["file"])) {
		$errors[] = "No file was uploaded";
		return;
	}
	if ($_FILES["file"]["error"]) {
		$errors[] = "There was a problem uploading the file &mdash; try again in a short while";
		return;
	}

	switch ($_FILES["file"]["type"]) {
		case "application/xml":
		case "text/xml":
		case "application/qti+xml":
			$metadata = array();
			$filename = $_FILES["file"]["name"];
			$xml = file_get_contents($_FILES["file"]["tmp_name"]);
			break;
		case "application/zip":
		case "application/x-zip-compressed":
		case "application/x-zip":
			// open zip file
			$zip = new ZipArchive();
			if ($zip->open($_FILES["file"]["tmp_name"]) !== true) {
				$errors[] = "Failed to open the zip file. Ensure it is not corrupt and try again.";
				return;
			}

			// get manifest, make sure it's valid XML
			$mxml = $zip->getfromName("imsmanifest.xml");
			if ($mxml === false) {
				$errors[] = "Error getting manifest file -- are you sure this is a content package?";
				return;
			}
			$mxml = simplexml_load_string($mxml);
			if ($mxml === false) {
				$errors[] = "The manifest file in the uploaded content package is not valid XML";
				return;
			}

			$metadata = array();

			// get manifest identifier
			$metadata["midentifier"] = (string) $mxml["identifier"];

			// ensure there's only one resource
			$resource = $mxml->resources->resource;
			if (count($resource) > 1) {
				$errors[] = "More than one resource element found in the manifest -- this is not a single-item content package";
				return;
			}
			if (count($resource) == 0) {
				$errors[] = "No resource elements found in the manifest -- this is not a single-item content package";
				return;
			}

			// ensure it's an item rather than an assessment
			if (!preg_match('%^imsqti_item_%', (string) $resource["type"])) {
				$errors[] = "This content package contains an assessment test rather than an assessment item";
				return;
			}

			// get the metadata
			$imsmd = $resource->metadata->children(NS_IMSMD);
			if (isset($imsmd->lom->general->description->langstring[0])) {
				$metadata["description"] = (string) $imsmd->lom->general->description->langstring[0];
			}
			$metadata["keywords"] = array();
			foreach ($imsmd->lom->general->keyword as $keyword)
				$metadata["keywords"][] = (string) $keyword->langstring[0];

			// get the file pointed to
			$filename = (string) $resource["href"];
			$xml = $zip->getfromName($filename);
			if ($xml === false) {
				$errors[] = "Error getting item file \"$filename\" from archive";
				return;
			}

			break;
		default:
			$errors[] = "The uploaded file (of type " . $_FILES["file"]["type"] . ") was not recognized as a content package (zip) or QTI XML file.";
			return;
	}

	// make sure it's valid XML
	$xml = simplexml_load_string($xml);
	if ($xml === false) {
		$errors[] = "The file \"$filename\" is not valid XML";
		return;
	}

	// make sure it's an assessment item
	if ($xml->getName() != "assessmentItem") {
		$errors[] = "The file \"$filename\" is not a QTI assessment item";
		return;
	}

	// make sure it's valid QTI
	if (!validateQTI($xml, $errors, $warnings, $messages)) {
		$errors[] = "The file \"$filename\" is not valid QTI";
		return;
	}

	// test against supported item types
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

	// give it a new identifier if appropriate, restore manifest identifier if 
	// no new identifier is wanted
	if (isset($_POST["newidentifier"]))
		$ai->setQTIID();
	else if (array_key_exists("midentifier", $metadata))
		$ai->setMID($metadata["midentifier"]);

	// restore the metadata taken from the manifest
	if (array_key_exists("description", $metadata))
		$ai->data("description", $metadata["description"]);
	if (array_key_exists("keywords", $metadata))
		$ai->data("keywords", implode(", ", $metadata["keywords"]));

	// take the user to the main menu with the uploaded item highlighted
	redirect(SITEROOT_WEB . "#item_" . $ai->getQTIID());
}

$errors = array();
$warnings = array();
$messages = array();

if (isset($_POST["uploaditem"]))
	handleupload($errors, $warnings, $messages);

$GLOBALS["title"] = "Upload an assessment item";
include "htmlheader.php";
?>
<h2><?php echo $GLOBALS["title"]; ?></h2>

<p>This form allows you to upload an existing assessment item so it can be 
edited or repackaged. The file uploaded must be either an assessment item in QTI 
XML format or an IMS content package containing a single assessment item.</p>

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
