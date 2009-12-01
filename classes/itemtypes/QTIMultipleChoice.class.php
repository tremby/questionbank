<?php

class QTIMultipleChoice extends QTIMultipleChoiceResponse {
	public function __construct() {
		parent::__construct();

		$this->itemtype = "multipleChoice";
		$this->itemtypeprint = "multiple choice";
		$this->itemtypedescription = "A stimulus followed by a question prompt and a number of possible responses. The candidate chooses the correct response.";
	}
}

?>
