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
	<dt>Item list</a></dt>
	<dd>
		A filterable list of all items currently in <?php echo htmlspecialchars(SITE_TITLE); ?>
		<ul>
			<li><a href="<?php echo Uri::construct(SITEROOT_WEB . "?page=itemList", true)->addvars("clear", "true")->geturi(); ?>">All items</a> (or start a new search)</li>
			<?php if (isset($_SESSION["search"])) { ?>
				<li><a href="<?php echo Uri::construct(SITEROOT_WEB . "?page=itemList", true)->geturi(); ?>">Previous search results</a>
			<?php } ?>
			<?php if (loggedin()) { ?>
				<li><a href="<?php echo Uri::construct(SITEROOT_WEB . "?page=itemList", true)->addvars("user", username())->geturi(); ?>">Your items</a>
			<?php } ?>
		</ul>
	</dd>
</dl>

<?php include "htmlfooter.php"; ?>
