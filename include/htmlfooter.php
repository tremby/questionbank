<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

?>
	<div class="clearer"></div>
</div>
<div id="footer">
	<a href="http://github.com/tremby/questionbank"><?php echo PROGRAMNAME; ?></a>
	<?php echo VERSION; ?>,
	&copy; 2010
	<a href="http://www.jisc.ac.uk">JISC</a>-funded
	<a href="http://easihe.ecs.soton.ac.uk">EASiHE project</a>,
	<a href="http://www.soton.ac.uk">University of Southampton</a>
	<?php if (strpos(VERSION, "git") !== false) { $commit = `git show`; ?>
		<!-- last commit: <?php echo "\n" . str_replace("--", "–", substr($commit, 0, strpos($commit, "\n\n", strpos($commit, "\n\n") + 1))) . "\n"; ?>
		-->
	<?php } ?>
</div>
</body>
</html>
