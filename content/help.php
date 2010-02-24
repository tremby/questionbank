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

// slurp the README and pull out the USAGE section

$readme = file_get_contents(SITEROOT_LOCAL . "README");
$usage = preg_replace('%.*\n\nUSAGE\n\n(.*?)\n\n[^a-z]+\n\n.*%s', '\\1', $readme);

$usage_html = "<p>" . preg_replace(array('% \n%', '%\n\n%', '%\n%'), array(" ", "</p><p>", "<br>"), htmlspecialchars($usage)) . "</p>";

$GLOBALS["title"] = "Help";
include "htmlheader.php";
?>
<h2>Help</h2>
<p class="hint">The following is taken directly from the USAGE section of <a href="<?php echo SITEROOT_WEB; ?>README">the README file</a></p>

<?php echo $usage_html; ?>

<?php include "htmlfooter.php"; ?>
