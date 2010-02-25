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

class DeleteAssessmentItemAction extends ItemAction {
	private $ai;
	private $aititle;

	public function name() {
		return "delete";
	}

	public function description() {
		return "Remove the assessment item from session memory";
	}

	public function beforeLogic() {
		$this->ai = QTIAssessmentItem::fromQTIID($_REQUEST["qtiid"]);
		$this->aititle = $this->ai->data("title");
	}

	public function getLogic() {
		// called as get -- present confirmation
		$GLOBALS["title"] = "Confirm deletion of assessment item \"" . htmlspecialchars($this->aititle) . "\"";
		include "htmlheader.php";
		?>
		<h2>Confirm deletion</h2>
		<form action="<?php echo $this->actionURL($this->ai); ?>" method="post">
			<p>
				<input type="hidden" name="qtiid" value="<?php echo $this->ai->getQTIID(); ?>">
				<input type="submit" name="confirm" value="Delete the assessment item &quot;<?php echo htmlspecialchars($this->aititle); ?>&quot; from session memory">
			</p>
		</form>
		<?php
		include "htmlfooter.php";
	}

	public function postLogic() {
		$this->ai->sessionRemove();

		if (isset($_REQUEST["async"])) ok();

		$GLOBALS["title"] = "Item \"" . htmlspecialchars($this->aititle) . "\" deleted";
		include "htmlheader.php";
		?>
		<h2><?php echo $GLOBALS["title"]; ?></h2>
		<p>The assessment item <?php echo htmlspecialchars($this->aititle); ?> has been removed 
		from memory.</p>
		<?php
		include "htmlfooter.php";
	}

	public function clickJS() {
		ob_start();
		?>
		e.preventDefault();

		if (!confirm("Are you sure you want to delete this item?"))
			return;

		<?php if (isset($_REQUEST["qtiid"])) { ?>
			var qtiid = "<?php echo $_REQUEST["qtiid"]; ?>";
		<?php } else { ?>
			var qtiid = $(this).parents("tr:first").attr("id").split("_").splice(1).join("_");
		<?php } ?>

		jQuery.ajax({
			"cache": false,
			"context": $(this).parents("tr:first").get(0),
			"data": { "async": true, "qtiid": qtiid },
			"error": function(xhr, text, error) { console.error(error); },
			"success": function() {
				if ($("#itemlist").size() > 0)
					$(this.context).remove();
				else {
					alert("The item has been deleted");
					window.location.pathname = "<?php echo SITEROOT_WEB; ?>";
				}
			},
			"type": "POST",
			"url": "<?php echo $this->actionURL(null, false); ?>"
		});
		<?php
		return ob_get_clean();
	}

}

?>
