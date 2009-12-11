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

	// get QTI
	public function getQTI($data = null) {
		if (is_null($data)) {
			if (!is_null($this->qti))
				return $this->qti;
		} else
			$this->data = $data;

		$qti = $this->buildQTI();
		if (!$qti)
			return false;

		$this->qti = $qti;
		return $this->qti;
	}

	// get QTI identifier
	public function getQTIID() {
		$qti = $this->getQTI();

		if (!$qti)
			return false;

		return (string) $qti["identifier"];
	}

	/** buildQTI
	 * This must build and return the QTI from the $data property. It should 
	 * call validateQTI() before returning the SimpleXML element.
	 * Populate $this->errors, $this->warnings and $this->messages with anything 
	 * appropriate.
	 * Return false if there were any errors.
	 */
	abstract protected function buildQTI();

	/** showForm
	 * This must replace $this->data with the $data argument if given and then 
	 * output an authoring form (which should be blank if $this->data is empty).
	 * Very little server side validation is necessary here since the Java 
	 * validate application does everything important. Client side checking for 
	 * likely mistakes (empty boxes etc) is sufficient.
	 * The form should submit with "newitem".
	 */
	abstract public function showForm($data = null);

	// output nice HTML for any errors, warnings and messages
	public function showMessages() {
		foreach(array("error" => $this->errors, "warning" => $this->warnings, "message" => $this->messages) as $type => $messages)
			showmessages($messages, ucfirst($type), $type);
	}

	// get item type string
	public function itemType() {
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
