<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

include "constants.php";
header("Content-Type: text/javascript");
?>

// in case Prototype has been included by QTIEngine we set Jquery to $j rather 
// than the usual $
$j = jQuery;

logout = function(e) {
	e.preventDefault();
	jQuery.ajax({
		"cache": false,
		"data": { "async": true },
		"success": function() {
			$j("#logoutlink").parents("li:first").html("Logged out");
		},
		"type": "POST",
		"url": "<?php echo SITEROOT_WEB; ?>?page=logout"
	});
};

$j(document).ready(function() {
	$j("#logoutlink").click(logout);
});

<?php
// vim: ft=javascript
?>
