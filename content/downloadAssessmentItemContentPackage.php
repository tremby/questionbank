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

if (!isset($_REQUEST["qtiid"])) badrequest("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) badrequest("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

// build the manifest

$manifest = simplexml_load_string('<manifest
	xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
	xmlns:imsmd="' . NS_IMSMD . '"
	xmlns:imsqti="' . NS_IMSQTI . '"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://www.imsglobal.org/xsd/imsmd_v1p2 imsmd_v1p2p4.xsd http://www.imsglobal.org/xsd/imsqti_v2p1  http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
/>');
$manifest->addAttribute("identifier", $ai->getMID());

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
$qmd = $md->addChild("qtiMetadata", null, NS_IMSQTI);
$qmd->addChild("timeDependent", "false", NS_IMSQTI);
foreach ($ai->interactionTypes() as $it)
	$qmd->addChild("interactionType", $it, NS_IMSQTI);
$qmd->addChild("feedbackType", is_null($ai->data("feedback")) ? "none" : "nonadaptive", NS_IMSQTI);
$qmd->addChild("solutionAvailable", "true", NS_IMSQTI);

// resource LOM metadata
$lom = $md->addChild("lom", null, NS_IMSMD);
$g = $lom->addChild("general", null, NS_IMSMD);
$g->addChild("title", null, NS_IMSMD)->addChild("langstring", $ai->data("data"), NS_IMSMD);
if (!is_null($ai->data("description")))
	$g->addChild("description", null, NS_IMSMD)->addChild("langstring", $ai->data("description"), NS_IMSMD);
foreach ($ai->getKeywords() as $keyword)
	$g->addChild("keyword", null, NS_IMSMD)->addChild("langstring", $keyword, NS_IMSMD);

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
