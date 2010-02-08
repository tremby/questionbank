<?php

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (!isset($_REQUEST["qtiid"])) badrequest("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) badrequest("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

// download the QTI
//header("Content-Type: application/qti+xml"); //proposed Mime type -- http://www.imsglobal.org/question/ims-qti-archives/2003-January/000502.html
header("Content-Type: application/xml");
header("Content-Disposition: attachment; filename=\"{$ai->getTitleFS()}.xml\"");
echo $ai->getQTIIndentedString();
exit;

?>
