<?php

abstract class QTIAssessmentItem {
	protected $errors = array();
	protected $warnings = array();
	protected $messages = array();

	protected $data = array();
	protected $qti = null; //SimpleXML element

	protected $itemtype = null; //set by child classes' constructors
	protected $itemtypeprint = null; //set by child classes' constructors
	protected $itemtypedescription = null; //set by child classes' constructors

	// constructor
	public function __construct() {}

	// build QTI
	public function getQTI($data = null) {
		if (is_null($data)) {
			if (is_null($this->qti)) {
				$this->errors[] = "No data yet given and so QTI cannot be built";
				return false;
			}
			return $this->qti;
		}

		$this->data = $data;

		// This must be overridden to then build, store and return the QTI from 
		// the given data. It should call validateQTI() as part of it before 
		// storing and returning the SimpleXML element.
		// Return false if there were any problems.
	}

	// show authoring form
	public function showForm($data = null) {
		if (!is_null($data))
			$this->data = $data;

		// This must be overridden to then output an authoring form (which 
		// should be blank if $this->data is an empty array)
		// Very little server side validation is necessary here since the Java 
		// validate application does everything important. Client side checking 
		// for likely mistakes (empty boxes etc) is sufficient.
		// The form should submit with "newitem"
	}

	public function showMessages() {
		foreach(array("error" => $this->errors, "warning" => $this->warnings, "message" => $this->messages) as $type => $messages)
			showmessages($messages, ucfirst($type), $type);
	}

	// get item type string
	public function itemType() {
		//echo "hi there";
		return $this->itemtype;
	}
	// get printable item type string
	public function itemTypePrint() {
		return $this->itemtypeprint;
	}
	// get item type description string
	public function itemTypeDescription() {
		return $this->itemtypedescription;
	}

	// get errors
	public function getErrors() {
		return $this->errors;
	}
	// get warnings
	public function getWarnings() {
		return $this->warnings;
	}
	// get warnings
	public function getMessages() {
		return $this->messages;
	}
}

?>
