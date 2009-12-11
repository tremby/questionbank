<?php

if (!isset($_REQUEST["qtiid"])) die("No QTI ID specified");
if (!isset($_SESSION["qti"][$_REQUEST["qtiid"]])) die("No QTI found in session data for specified QTI ID");

$qti = $_SESSION["qti"][$_REQUEST["qtiid"]];

// parse the QTI to get the title
$ai = simplexml_load_string($qti);
$title = preg_replace('%[^A-Za-z0-9._ -]%', "_", $ai["title"]);

// download the QTI
//header("Content-Type: application/qti+xml"); //proposed Mime type -- http://www.imsglobal.org/question/ims-qti-archives/2003-January/000502.html
header("Content-Type: application/xml");
header("Content-Disposition: attachment; filename=\"$title.qti.xml\"");
echo $qti;
exit;

?>
