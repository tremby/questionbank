<?php

class QTIMultipleChoice extends QTIMultipleChoiceResponse {
	public function itemTypePrint() {
		return "multiple choice";
	}
	public function itemTypeDescription() {
		return "A stimulus followed by a question prompt and a number of possible responses. The candidate chooses the correct response.";
	}
}

?>
