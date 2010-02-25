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

class DownloadAssessmentItemAction extends ItemAction {
	public function name() {
		return "download XML";
	}

	public function description() {
		return "Download the assessment item as QTI XML";
	}

	public function getLogic() {
		$ai = QTIAssessmentItem::fromQTIID($_REQUEST["qtiid"]);

		// download the QTI
		//header("Content-Type: application/qti+xml"); //proposed Mime type -- http://www.imsglobal.org/question/ims-qti-archives/2003-January/000502.html
		header("Content-Type: application/xml");
		header("Content-Disposition: attachment; filename=\"{$ai->getTitleFS()}.xml\"");
		echo $ai->getQTIIndentedString();
	}

	public function available(QTIAssessmentItem $ai) {
		return $ai->getQTI() && !count($ai->getErrors());
	}
}

?>
