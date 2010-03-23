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

class QTIMultipleResponse extends QTIMultipleChoiceResponse {
	public function itemTypePrint() {
		return "multiple response";
	}
	public function itemTypeDescription() {
		return "A stimulus followed by a question prompt and a number of possible responses. The candidate checks each response which is correct.";
	}
}

?>
