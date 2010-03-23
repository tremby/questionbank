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

class QTIMultipleChoice extends QTIMultipleChoiceResponse {
	public function itemTypePrint() {
		return "multiple choice";
	}
	public function itemTypeDescription() {
		return "A stimulus followed by a question prompt and a number of possible responses. The candidate chooses the correct response.";
	}
}

?>
