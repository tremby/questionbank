<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

class DepositInQuestionBankAction extends ItemAction {
	public function name() {
		return "save to Question Bank";
	}

	public function description() {
		return "Deposit the item in Question Bank";
	}

	public function getLogic() {
		redirect(dirname(SITEROOT_WEB) . "/?page=depositFromEqiat&qtiid=" . $_REQUEST["qtiid"]);
	}

	public function available(QTIAssessmentItem $ai) {
		return $ai->getQTI() && !count($ai->getErrors());
	}
}

?>
