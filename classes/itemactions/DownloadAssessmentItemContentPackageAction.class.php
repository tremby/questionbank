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

class DownloadAssessmentItemContentPackageAction extends ItemAction {
	public function name() {
		return "download content package";
	}

	public function description() {
		return "Download the assessment item as a content package to include metadata";
	}

	public function getLogic() {
		$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

		// download the content package
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"{$ai->getTitleFS()}.zip\"");
		echo $ai->getContentPackage();
	}

	public function available(QTIAssessmentItem $ai) {
		return $ai->getQTI() && !count($ai->getErrors());
	}
}

?>
