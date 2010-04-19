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

stars = {
	starsize: 16,
	calcstars: function(e) {
		if ($j.browser.msie) {
			var x = e.originalEvent.x;
		} else {
			var x = e.originalEvent.layerX;
		}
		if ($j(e.originalEvent.target).is(".off")) {
			x += e.originalEvent.target.offsetLeft;
		}
		if (x < stars.starsize / 2) {
			return 0;
		} else {
			var score = 1;
			while (x > stars.starsize && score < 5) {
				score++;
				x -= stars.starsize;
			}
			return score;
		}
	},
	lightstars: function(el, score) {
		var on = score / 5 * 100;
		$j(el).find(".on").width(on + "%").end().find(".off").width((100 - on) + "%");
	},
	mouseout: function(e) {
		if ($j(e.relatedTarget).parents(".stars").length == 0)
			stars.lightstars(this, $j(this).find("input.rating").val());
	},
	mousemove: function(e) {
		stars.lightstars(this, stars.calcstars(e));
	},
	click: function(e) {
		var rating = stars.calcstars(e);
		$j(this).find("input.rating").val(rating);
		stars.lightstars(this, rating);
	}
}

$j(document).ready(function() {
	$j("#logoutlink").click(logout);
	$j(".stars.settable").mouseout(stars.mouseout).mousemove(stars.mousemove).click(stars.click);
});

<?php
// vim: ft=javascript
?>
