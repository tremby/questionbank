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
$aititle = $ai->data("title");

unset($_SESSION["items"][$_REQUEST["qtiid"]]);

if (isset($_REQUEST["async"])) ok();

$GLOBALS["title"] = "Item \"" . htmlspecialchars($aititle) . "\" deleted";
include "htmlheader.php";
?>

<h2><?php echo $GLOBALS["title"]; ?></h2>
<p>The assessment item <?php echo htmlspecialchars($aititle); ?> has been removed 
from memory.</p>

<?php include "htmlfooter.php"; ?>
