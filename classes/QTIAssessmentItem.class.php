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
	public function __construct() {
		//echo "assessmentitem constructor";
	}

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
		// the given data. It should call the validate() method as part of it 
		// before storing and returning the SimpleXML element.
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

	// validate a string of QTI XML or SimpleXML element
	// $errors, $warnings and $messages should be arrays
	public static function validate($xml, &$errors, &$warnings, &$messages) {
		if ($xml instanceof SimpleXMLElement)
			$xml = $xml->asXML();

		$pipes = null;
		$validate = proc_open("./run.sh", array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes, SITEROOT_LOCAL . "validate");
		if (!is_resource($validate)) {
			$errors[] = "Failed to start validator";
			return false;
		}

		// give QTI on stdin and close the pipe
		fwrite($pipes[0], $xml);
		fclose($pipes[0]);

		// get contents of stdout and stderr
		$stdout = trim(stream_get_contents($pipes[1]));
		fclose($pipes[1]);
		$stderr = trim(stream_get_contents($pipes[2]));
		fclose($pipes[2]);

		$exitcode = proc_close($validate);

		if (!empty($stderr))
			$errors = array_merge($errors, explode("\n", $stderr));
		if (!empty($stdout)) {
			$stdout = explode("\n", $stdout);
			foreach ($stdout as $message) {
				$parts = explode("\t", $message);
				switch ($parts[0]) {
					case "Error":
						$errors[] = "Validator error: {$parts[1]} ({$parts[2]})";
						break;
					case "Warning":
						$warnings[] = "Validator warning: {$parts[1]} ({$parts[2]})";
						break;
					default:
						$messages[] = "Validator message: {$parts[1]} ({$parts[2]})";
				}
			}
		}

		if (empty($errors) && $exitcode != 0)
			$errors[] = "Validator exited with code $exitcode";

		return $exitcode == 0;
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
