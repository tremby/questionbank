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

logout = function(e) {
	e.preventDefault();
	jQuery.ajax({
		"cache": false,
		"data": { "async": true },
		"success": function() {
			$("#logoutlink").parents("li:first").html("Logged out");
		},
		"type": "POST",
		"url": "<?php echo SITEROOT_WEB; ?>?page=logout"
	});
};

$(document).ready(function() {
	$("#logoutlink").click(logout);
});

<?php
// vim: ft=javascript
?>
