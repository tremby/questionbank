<?php

if (!isset($_REQUEST["qtiid"])) die("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) die("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

// download the QTI
//header("Content-Type: application/qti+xml"); //proposed Mime type -- http://www.imsglobal.org/question/ims-qti-archives/2003-January/000502.html
header("Content-Type: application/xml");
header("Content-Disposition: attachment; filename=\"{$ai->getTitleFS()}.qti.xml\"");
echo $ai->getQTIIndentedString();
exit;

?>
