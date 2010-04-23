<?php

/*
 * Question Bank
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
		if (validateQTI($xml, $errors, $warnings, $messages)) {
			$xml = simplexml_load_string($xml);

			// get data from QTI
			$identifier = (string) $xml["identifier"];
			$title = (string) $xml["title"];

			// see if an item with this identifier already exists in the 
			// database
			$exists = itemexists($identifier);

			if ($exists && itemowner($identifier) != username())
				$errors[] = "The item you are trying to upload was already uploaded by a different user. You should clone it so it gets a new identifier and then try again.";
			else {
				deposititem($xml, $metadata);
				if (!authoredineqiat($xml))
					$warnings[] = "This item was not authored in Eqiat. The item will still be playable but Eqiat may not be able to import it for editing.";

				// collect any warnings and messages
				$thingstosay = array();
				if (!empty($warnings)) $thingstosay[] = "warnings";
				if (!empty($messages)) $thingstosay[] = "messages";

				$title = "Item " . ($exists ? "updated" : "deposited");
				include "htmlheader.php";
				?>
				<h2><?php echo htmlspecialchars($title); ?></h2>
				<p>The item "<?php echo htmlspecialchars((string) $xml["title"]); ?>" has been <?php echo $exists ? "updated" : "deposited"; ?><?php if (!empty($thingstosay)) { ?> with the following <?php echo implode(" and ", $thingstosay); ?>:<?php } ?></p>
				<?php
				foreach(array("warning" => $warnings, "message" => $messages) as $type => $messages)
					showmessages($messages, ucfirst($type) . plural($messages), $type);
				include "htmlfooter.php";
				exit;
			}
		}
	}
}

$GLOBALS["title"] = "Upload an assessment item";
include "htmlheader.php";
?>
<h2><?php echo $GLOBALS["title"]; ?></h2>

<p>This form allows you to upload an existing assessment item directly to <?php echo htmlspecialchars(SITE_TITLE); ?>. 
The file uploaded must be either an assessment item in QTI XML format or an IMS 
content package containing a single assessment item.</p>

<?php
foreach(array("error" => $errors, "warning" => $warnings, "message" => $messages) as $type => $messages)
	showmessages($messages, ucfirst($type), $type);
?>

<form id="uploaditem" action="?page=uploadItem" method="post" enctype="multipart/form-data">
	<dl>
		<dt><label for="file">File</label></dt>
		<dd><input id="file" type="file" name="file"></dd>
	</dl>
	<div>
		<input type="hidden" name="MAX_FILE_SIZE" value="262144">
		<input id="submit" type="submit" name="uploaditem" value="Submit">
	</div>
</form>

<?php include "htmlfooter.php"; ?>
