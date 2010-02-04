<?php

abstract class QTIAssessmentItem {
	protected $errors = array();
	protected $warnings = array();
	protected $messages = array();

	protected $data = array();
	protected $qti = null; //XML string (can't store SimpleXML element in session data)

	private $modified;

	// the following are set by child classes' constructors
	protected $itemtype = null;
	protected $itemtypeprint = null;
	protected $itemtypedescription = null;
	protected $interactionType = null;

	protected $identifier;

	/** constructor
	 * Child classes must call parent::__construct()
	 */
	public function __construct() {
		$this->modified = time();
		$this->setQTIID();
	}

	/** buildQTI
	 * This must build and return the QTI from the $data property. It should 
	 * call validateQTI() before returning the SimpleXML element.
	 * Populate $this->errors, $this->warnings and $this->messages with anything 
	 * appropriate.
	 * Return false if there were any errors.
	 */
	abstract protected function buildQTI();

	/** formHTML
	 * This should return elements of an authoring form (which should be blank 
	 * if $this->data is empty) specific to this item type (that is, no title, 
	 * description, keywords or stimulus).
	 * The HTML returned will be put into a definition list element and so 
	 * should consist of <dt> and <dd> elements.
	 */
	abstract protected function formHTML();

	/** headerJS
	 * This can optionally be overridden to return a string of Javascript which 
	 * should be added to the page header
	 * The normal client-side warning and error checking can be extended by 
	 * implementing the functions
	 * 	edititemsubmitcheck_itemspecificwarnings
	 * 	edititemsubmitcheck_itemspecificerrors
	 * which should return show any warning or error messages and then return 
	 * true if submission should continue or false if it should be aborted.
	 * If possible, indicate the elements on which warnings or errors occured by 
	 * adding the appropriate CSS class ("warning" or "error").
	 * Additionally, if defined the function
	 *	edititemsubmitcheck_pre
	 * is called before all the warning and error checking is done.
	 */
	protected function headerJS() {
		return null;
	}

	/** headerCSS
	 * This can optionally be overridden to return a string of CSS which should 
	 * be added to the page header
	 */
	protected function headerCSS() {
		return null;
	}

	/** fromXML
	 * This must attempt to parse the given SimpleXML element to whatever kind 
	 * of item type is being implemented. Return a score where 0 is "given XML 
	 * is definitely not this question type" and 255 is "given XML is definitely 
	 * this question type". If the parsing was successful (even if not ideal) 
	 * the $data property should be populated.
	 * It can be assumed that $xml is valid QTI.
	 */
	abstract public function fromXML(SimpleXMLElement $xml);

	// render the authoring form
	public function showForm($data = null) {
		if (!is_null($data))
			$this->data = $data;

		$GLOBALS["headerjs"] = $this->headerJS();
		$GLOBALS["headercss"] = $this->headerCSS();
		include "htmlheader.php";
		?>
			<h2>Edit an assessment item</h2>
			<?php $this->showmessages(); ?>
			<form id="edititem" action="?page=editAssessmentItem" method="post">
				<input type="hidden" name="qtiid" value="<?php echo $this->getQTIID(); ?>">
				<dl>
					<dt><label for="title">Title</label></dt>
					<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

					<dt><label for="description">Description</label></dt>
					<dd><textarea rows="8" cols="64" name="description" id="description"><?php if (isset($this->data["description"])) echo htmlspecialchars($this->data["description"]); ?></textarea></dd>

					<dt>Keywords (comma-separated)</dt>
					<dd><textarea id="keywords" name="keywords" rows="4" cols="64"><?php if (isset($this->data["keywords"])) echo htmlspecialchars($this->data["keywords"]); ?></textarea></dd>

					<dt><label for="stimulus">Stimulus</label></dt>
					<dd><textarea class="qtitinymce resizable" rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

					<?php echo $this->formHTML(); ?>

				</dl>
				<div><input id="edititemsubmit" type="submit" name="edititem" value="Submit"></div>
			</form>

		<?php
		include "htmlfooter.php";
	}

	// get QTI
	public function getQTI($data = null) {
		if (is_null($data)) {
			if (!is_null($this->qti))
				return simplexml_load_string($this->qti);
		} else
			$this->data = $data;

		// clear errors, warnings and messages
		$this->errors = array();
		$this->warnings = array();
		$this->messages = array();

		$this->modified = time();
		$qti = $this->buildQTI();
		if (!$qti)
			return false;

		// store the QTI string in a property
		$this->qti = simplexml_indented_string($qti);

		return $qti;
	}

	// get QTI as indented XML string
	public function getQTIIndentedString() {
		if (!$this->getQTI())
			return false;

		return simplexml_indented_string($this->getQTI());
	}

	// get QTI identifier
	public function getQTIID() {
		return $this->identifier;
	}

	// set QTI identifier or generate a new one if none given
	public function setQTIID($identifier = null) {
		if (is_null($identifier))
			$this->identifier = "item_" . md5(uniqid());
		else
			$this->identifier = $identifier;
	}

	// get item title
	public function getTitle() {
		$qti = $this->getQTI();
		if (!$qti)
			return false;

		return (string) $qti["title"];
	}

	// get item title with non filesystem-friendly characters replaced with 
	// underscores
	public function getTitleFS() {
		if (!$this->getTitle())
			return false;

		return preg_replace('%[^A-Za-z0-9._-]%', "_", $this->getTitle());
	}

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
	// get interaction type string
	public function interactionType() {
		return $this->interactionType;
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

	// add error
	public function addError($string) {
		$this->errors[] = $string;
	}
	// add warning
	public function addWarning($string) {
		$this->warnings[] = $string;
	}
	// add message
	public function addMessage($string) {
		$this->messages[] = $string;
	}

	// get modification time
	public function getModified() {
		return $this->modified;
	}

	// get the data or one element from the data
	public function data($key = null) {
		if (isset($key)) {
			if (array_key_exists($key, $this->data))
				return $this->data[$key];
			return null;
		}
		return $this->data;
	}

	// compare items by title or ID
	public static function compare_by_title(QTIAssessmentItem $a, QTIAssessmentItem $b) {
		// get titles
		$ta = $a->getTitle();
		$tb = $b->getTitle();

		// if both lack titles compare by ID
		if ($ta === false && $tb === false)
			return strcasecmp($a->getQTIID(), $b->getQTIID());

		// if A lacks a title B is further up the list and so A is "more"
		if ($ta === false)
			return 1;

		// if B lacks a title A is further up the list and so A is "less"
		if ($tb === false)
			return -1;

		return strcasecmp($ta, $tb);
	}
}

?>
