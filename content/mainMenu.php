<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

include "htmlheader.php";
?>

<dl id="mainmenu">
	<dt><a href="<?php echo SITEROOT_WEB; ?>?page=itemList">Item list</a></dt>
	<dd>A filterable list of all items currently in <?php echo 
	htmlspecialchars(SITE_TITLE); ?></dd>
</dl>

<?php include "htmlfooter.php"; ?>
