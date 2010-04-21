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

$errors = array();
$warnings = array();
$messages = array();

if (isset($_POST["uploaditem"])) {
	$output = handleupload($errors, $warnings, $messages);
	if ($output !== false) {
		list($xml, $metadata) = $output;
		$ai = xmltoqtiobject($xml, $errors, $warnings, $messages, $metadata, isset($_POST["newidentifier"]));
		if ($ai !== false) {
			// save the item in session data
			$ai->sessionStore();

			// redirect to the main menu with the new item highlighted
			redirect(SITEROOT_WEB . "#item_" . $ai->getQTIID());
		}
	}
}

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
