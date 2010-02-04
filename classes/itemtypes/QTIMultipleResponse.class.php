<?php

class QTIMultipleResponse extends QTIMultipleChoiceResponse {
	public function itemTypePrint() {
		return "multiple response";
	}
	public function itemTypeDescription() {
		return "A stimulus followed by a question prompt and a number of possible responses. The candidate checks each response which is correct.";
	}
}

?>
