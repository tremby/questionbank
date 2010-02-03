<?php

if (!isset($_REQUEST["qtiid"])) badrequest("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) badrequest("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

// build the manifest

$imsqti = "http://www.imsglobal.org/xsd/imsqti_v2p1";
$imsmd = "http://www.imsglobal.org/xsd/imsmd_v1p2";
$manifest = simplexml_load_string('<manifest
	xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
	xmlns:imsmd="' . $imsmd . '"
	xmlns:imsqti="' . $imsqti . '"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://www.imsglobal.org/xsd/imsmd_v1p2 imsmd_v1p2p4.xsd http://www.imsglobal.org/xsd/imsqti_v2p1  http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
/>');
$manifest->addAttribute("identifier", "MANIFEST-" . $ai->getQTIID());

// organizations element
$manifest->addChild("organizations");

// resources element
$rs = $manifest->addChild("resources");
$r = $rs->addChild("resource");
$r->addAttribute("identifier", $ai->getQTIID());
$r->addAttribute("type", "imsqti_item_xmlv2p1");
$r->addAttribute("href", "{$ai->getTitleFS()}.xml");
$md = $r->addChild("metadata");

// resource qti metadata
$qmd = $md->addChild("qtiMetadata", null, $imsqti);
$qmd->addChild("timeDependent", "false", $imsqti);
$qmd->addChild("interactionType", $ai->interactionType(), $imsqti);
$qmd->addChild("feedbackType", is_null($ai->data("feedback")) ? "none" : "nonadaptive", $imsqti);
$qmd->addChild("solutionAvailable", "true", $imsqti);

// resource LOM metadata
$lom = $md->addChild("lom", null, $imsmd);
$g = $lom->addChild("general", null, $imsmd);
$g->addChild("title", null, $imsmd)->addChild("langstring", $ai->getTitle(), $imsmd);
if (!is_null($ai->data("description")))
	$g->addChild("description", null, $imsmd)->addChild("langstring", $ai->data("description"), $imsmd);
if (!is_null($ai->data("keywords"))) {
	$keywords = explode(",", $ai->data("keywords"));
	$keywords = array_map("trim", $keywords);
	foreach ($keywords as $keyword) {
		if (strlen($keyword) == 0)
			continue;
		$g->addChild("keyword", null, $imsmd)->addChild("langstring", $keyword, $imsmd);
	}
}

// file element
$r->addChild("file")->addAttribute("href", "{$ai->getTitleFS()}.xml");

// make temporary zip archive
$zip = new ZipArchive();
$filename = "/tmp/" . uniqid("zip");
if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true)
	servererror("couldn't make zip file");
$zip->addFromString("imsmanifest.xml", simplexml_indented_string($manifest));
$zip->addFromString("{$ai->getTitleFS()}.xml", $ai->getQTIIndentedString());
$zip->close();

// download the content package
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"{$ai->getTitleFS()}.zip\"");
echo file_get_contents($filename);

// delete the temporary zip archive
unlink($filename);

exit;

?>
