<?php

class QTIMultipleResponse extends QTIMultipleChoiceResponse {
	public function __construct() {
		parent::__construct();

		$this->itemtype = "multipleResponse";
		$this->itemtypeprint = "multiple response";
		$this->itemtypedescription = "A stimulus followed by a question prompt and a number of possible responses. The candidate checks each response which is correct.";
	}
}

?>
