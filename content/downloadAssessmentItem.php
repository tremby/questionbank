<?php

if (!isset($_SESSION["qti"])) die("No QTI in session data");

// parse the QTI to get the title
$ai = simplexml_load_string($_SESSION["qti"]);
$title = preg_replace('%[^A-Za-z0-9._ -]%', "_", $ai["title"]);

// download the QTI
//header("Content-Type: application/qti+xml"); // proposed Mime type
header("Content-Type: application/xml");
header("Content-Disposition: attachment; filename=\"$title.qti.xml\"");
echo $_SESSION["qti"];
exit;

?>
