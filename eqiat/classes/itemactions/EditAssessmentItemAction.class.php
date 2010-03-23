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

class EditAssessmentItemAction extends ItemAction {
	private $ai;

	public function name() {
		return "edit";
	}

	public function description() {
		return "Edit the assessment item";
	}

	public function beforeLogic() {
		$this->ai = QTIAssessmentItem::fromQTIID($_REQUEST["qtiid"]);
	}

	public function getLogic() {
		// nothing posted -- show form with data as is (possibly empty)
		$this->ai->showForm();
	}

	public function postLogic() {
		// form submitted -- try to build QTI

		// if posted itemtype is different to the current one, make a new object
		if (isset($_POST["itemtype"]) && $_POST["itemtype"] != $this->ai->itemType()) {
			$olditem = $this->ai;

			$classname = "QTI" . ucfirst($_POST["itemtype"]);

			if (!@class_exists($classname) || !is_subclass_of($classname, "QTIAssessmentItem"))
				badrequest("Item type doesn't exist or not implemented");

			$this->ai = new $classname;

			// keep the old identifier
			$this->ai->setQTIID($olditem->getQTIID());

			unset($olditem);
		}

		if ($this->ai->getQTI($_POST) === false) {
			// problem of some kind, show the form again with any messages
			$this->ai->showForm($_POST);
			exit;
		}

		// new QTI is fine

		// collect any warnings and messages
		$thingstosay = array();
		$tmp = $this->ai->getWarnings();
		if (!empty($tmp)) $thingstosay[] = "warnings";
		$tmp = $this->ai->getMessages();
		if (!empty($tmp)) $thingstosay[] = "messages";
		$title = "Item \"" . htmlspecialchars($this->ai->data("title")) . "\" complete";

		// set up the action JS
		$GLOBALS["headerjs"] = item_action_js();

		// output the success message
		include "htmlheader.php";
		?>
		<h2><?php echo $title; ?></h2>
		<p>The item has been successfully validated<?php if (!empty($thingstosay)) { ?> with the following <?php echo implode(" and ", $thingstosay); ?>:<?php } ?></p>
		<?php
		$this->ai->showmessages();

		// show preview and download links
		?>
		<h3>QTIEngine preview</h3>
		<?php $action = new PreviewAssessmentItemAction(); ?>
		<?php if (usingIE()) { //iframe isn't available in HTML 4 Strict but IE (tested on 8) doesn't like object elements used for embedded HTML ?>
			<iframe width="100%" height="400" src="<?php echo $action->actionURL($this->ai); ?>"></iframe>
		<?php } else { ?>
			<object class="embeddedhtml" width="100%" height="400" type="text/html" data="<?php echo $action->actionURL($this->ai); ?>"></object>
		<?php } ?>

		<h3>Actions</h3>
		<ul>
			<li><a href="<?php echo SITEROOT_WEB; ?>">Go back to the main menu and item list</a></li>
			<?php
			$types = item_actions();
			$actions = array();
			foreach ($types as $type)
				if ($type->available($this->ai))
					$actions[] = $type;
				foreach ($actions as $action) { ?>
					<li><a class="itemaction_<?php echo $action->actionString(); ?>" href="<?php echo $action->actionURL($this->ai->getQTIID()); ?>" title="<?php echo htmlspecialchars($action->description()); ?>"><?php echo htmlspecialchars(ucfirst($action->name())); ?></a></li>
				<?php }
			?>
		</ul>

		<?php
		include "htmlfooter.php";
	}
}

?>
