<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (!isset($_REQUEST["async"]) && !loggedin()) {
	$title = "Not logged in";
	include "htmlheader.php";
	?>
	<h2><?php echo htmlspecialchars($title); ?></h2>
	<p>You're not logged in and so can't log out</p>
	<?php
	include "htmlfooter.php";
	exit;
}

$errors = array();

logout();
if (isset($_REQUEST["async"]))
	ok();

$title = "Successfully logged out";
include "htmlheader.php";
?>
<h2><?php echo htmlspecialchars($title); ?></h2>
<p>You have logged out</p>
<?php
include "htmlfooter.php";
