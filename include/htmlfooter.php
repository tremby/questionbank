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

?>
</div>
<div id="footer">
	<?php echo PROGRAMNAME; ?> <?php echo VERSION; ?>, &copy; 2010 JISC-funded EASiHE project, University of Southampton
	<?php if (strpos(VERSION, "git") !== false) { ?>
		<!-- output of `git show | head -3`: <?php echo "\n" . `git show | head -3`; ?>
		-->
	<?php } ?>
</div>
</body>
</html>
