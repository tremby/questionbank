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

abstract class QTIAssessmentItem {
	protected $errors = array();
	protected $warnings = array();
	protected $messages = array();

	protected $data = array();
	protected $qti = null; //XML string (can't store SimpleXML element in session data)

	private $modified;

	protected $identifier = null;
	protected $midentifier = null;

	/** constructor
	 * Child classes' constructors, if implemented, must call this
	 * A QTI identifier can be specified or pass null (default) to generate a 
	 * random one or false to not generate one (and so not store the item in 
	 * session memory)
	 */
	public function __construct($id = null) {
		if ($id === false)
			return;

		$this->setQTIID($id);
		$this->setMID();
	}

	/* --------------------------------------------------------------------- */
	/* abstract methods which must be implemented for each item type         */
	/* --------------------------------------------------------------------- */

	/** itemTypePrint
	 * This must return the item type as a string starting with lowercase. The 
	 * string can contain spaces.
	 */
	abstract public function itemTypePrint();

	/** itemTypeDescription
	 * This must return a description of the item type as a string starting with 
	 * uppercase. The string can contain multiple sentences if necessary.
	 */
	abstract public function itemTypeDescription();

	/** buildQTI
	 * This must build and return the QTI from the $data property.
	 * It should use the initialXML method to get the root assessmentItem with 
	 * attributes (including title and identifier) already set. It should then 
	 * add all necessary child nodes including the stimulus.
	 * It should call validateQTI() before returning the SimpleXML element.
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

	/** fromXML
	 * This must attempt to parse the given SimpleXML element to whatever kind 
	 * of item type is being implemented. Return a score where 0 is "given XML 
	 * is definitely not this question type" and 255 is "given XML is definitely 
	 * this question type". If the parsing was successful (even if not ideal) 
	 * the $data property should be populated.
	 * It can be assumed that $xml is valid QTI.
	 */
	abstract public function fromXML(SimpleXMLElement $xml);

	/* --------------------------------------------------------------------- */
	/* methods specifically for optional overriding                          */
	/* --------------------------------------------------------------------- */

	/** headerJS
	 * This can optionally be overridden to return a string of Javascript which 
	 * should be added to the page header
	 * The normal client-side warning and error checking can be extended by 
	 * implementing the functions
	 * 	edititemsubmitcheck_itemspecificwarnings
	 * 	edititemsubmitcheck_itemspecificerrors
	 * which should show any warning or error messages and then return true if 
	 * submission should continue or false if it should be aborted.
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

	/* --------------------------------------------------------------------- */
	/* public utility methods                                                */
	/* --------------------------------------------------------------------- */

	// touch the item -- update its modification date to now
	public function touch() {
		$this->modified = time();
	}

	// render the authoring form
	public function showForm($data = null) {
		if (!is_null($data))
			$this->data = $data;

		$GLOBALS["headerjs"] = $this->headerJS();
		$GLOBALS["headercss"] = $this->headerCSS();
		$GLOBALS["title"] = is_null($this->data("title")) ? "Edit untitled assessment item" : ("Edit assessment item \"" . htmlspecialchars($this->data("title")) . "\"");
		include "htmlheader.php";
		?>
			<h2><?php echo $GLOBALS["title"]; ?></h2>
			<?php $this->showmessages(); ?>
			<form id="edititem" action="<?php $action = new EditAssessmentItemAction(); echo $action->actionURL(); ?>" method="post">
				<dl>
					<dt><label for="title">Title</label></dt>
					<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

					<dt><label for="description">Description</label></dt>
					<dd><textarea rows="8" cols="64" name="description" id="description"><?php if (isset($this->data["description"])) echo htmlspecialchars($this->data["description"]); ?></textarea></dd>

					<dt><label for="keywords">Keywords (comma-separated)</label></dt>
					<dd><textarea id="keywords" name="keywords" rows="4" cols="64"><?php echo htmlspecialchars(implode(", ", $this->getKeywords())); ?></textarea></dd>

					<dt><label for="stimulus">Stimulus</label></dt>
					<dd><textarea class="qtitinymce resizable" rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

					<?php echo $this->formHTML(); ?>

					<dt>Actions</dt>
					<dd>
						<input type="hidden" name="qtiid" value="<?php echo $this->getQTIID(); ?>">
						<input id="edititemsubmit" type="submit" name="edititem" value="Submit">
					</dd>
				</dl>
			</form>

		<?php
		include "htmlfooter.php";
	}

	// get QTI as SimpleXML object
	public function getQTI($data = null) {
		if (!is_null($data))
			$this->data = $data;
		else if (!is_null($this->qti))
			return simplexml_load_string($this->qti);

		// don't even bother if title isn't set -- probably an abandoned item
		if (is_null($this->data("title")))
			return false;

		// clear errors, warnings and messages
		$this->errors = array();
		$this->warnings = array();
		$this->messages = array();

		$this->touch();
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

		return $this->qti;
	}

	// get QTI identifier
	public function getQTIID() {
		return $this->identifier;
	}

	// get manifest identifier
	public function getMID() {
		return $this->midentifier;
	}

	// set QTI identifier or generate a new one if none given
	public function setQTIID($identifier = null) {
		$this->touch();
		$this->clearQTI();
		if (is_null($identifier))
			$this->identifier = "ITEM_" . md5(uniqid());
		else
			$this->identifier = $identifier;
	}

	// get the item as a content package as a binary zip string
	public function getContentPackage() {
		// build the manifest

		$manifest = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
			<manifest
				xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
				xmlns:imsmd="' . NS_IMSMD . '"
				xmlns:imsqti="' . NS_IMSQTI . '"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://www.imsglobal.org/xsd/imsmd_v1p2 imsmd_v1p2p4.xsd http://www.imsglobal.org/xsd/imsqti_v2p1  http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
			/>
		');
		$manifest->addAttribute("identifier", $this->getMID());

		// organizations element
		$manifest->addChild("organizations");

		// resources element
		$rs = $manifest->addChild("resources");
		$r = $rs->addChild("resource");
		$r->addAttribute("identifier", $this->getQTIID());
		$r->addAttribute("type", "imsqti_item_xmlv2p1");
		$r->addAttribute("href", "{$this->getTitleFS()}.xml");
		$md = $r->addChild("metadata");

		// resource qti metadata
		$qmd = $md->addChild("qtiMetadata", null, NS_IMSQTI);
		$qmd->addChild("timeDependent", "false", NS_IMSQTI);
		foreach ($this->interactionTypes() as $it)
			$qmd->addChild("interactionType", $it, NS_IMSQTI);
		$qmd->addChild("feedbackType", is_null($this->data("feedback")) ? "none" : "nonadaptive", NS_IMSQTI);
		$qmd->addChild("solutionAvailable", "true", NS_IMSQTI);

		// resource LOM metadata
		$lom = $md->addChild("lom", null, NS_IMSMD);
		$g = $lom->addChild("general", null, NS_IMSMD);
		$g->addChild("title", null, NS_IMSMD)->addChild("langstring", $this->data("data"), NS_IMSMD);
		if (!is_null($this->data("description")))
			$g->addChild("description", null, NS_IMSMD)->addChild("langstring", $this->data("description"), NS_IMSMD);
		foreach ($this->getKeywords() as $keyword)
			$g->addChild("keyword", null, NS_IMSMD)->addChild("langstring", $keyword, NS_IMSMD);

		// file element
		$r->addChild("file")->addAttribute("href", "{$this->getTitleFS()}.xml");

		// make temporary zip archive
		$zip = new ZipArchive();
		$filename = "/tmp/" . uniqid("zip");
		if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true)
			servererror("couldn't make zip file");
		$zip->addFromString("imsmanifest.xml", simplexml_indented_string($manifest));
		$zip->addFromString("{$this->getTitleFS()}.xml", $this->getQTIIndentedString());
		$zip->close();

		// slurp contents
		$zipcontents = file_get_contents($filename);

		// delete the temporary zip archive
		unlink($filename);

		// return the zip contents as a binary string
		return $zipcontents;
	}

	// set manifest identifier or generate a new one if none given
	public function setMID($midentifier = null) {
		if (is_null($midentifier))
			$this->midentifier = "MANIFEST_" . md5(uniqid());
		else
			$this->midentifier = $midentifier;
	}

	// get item title with non filesystem-friendly characters replaced with 
	// underscores
	public function getTitleFS() {
		if (!$this->data("title"))
			return false;

		return preg_replace('%[^A-Za-z0-9._-]%', "_", $this->data("title"));
	}

	// output nice HTML for any errors, warnings and messages
	public function showMessages() {
		foreach(array("error" => $this->errors, "warning" => $this->warnings, "message" => $this->messages) as $type => $messages)
			showmessages($messages, ucfirst($type) . plural($messages), $type);
	}

	// get item type string (same as the class name but without QTI prefix and 
	// starting with a small letter)
	public function itemType() {
		return lcfirst(substr(get_class($this), 3));
	}

	// return an array of interaction types present in the QTI
	// see http://imsglobal.org/question/qtiv2p1pd2/imsqti_bindv2p1pd2.html#binding_interactionType
	public function interactionTypes() {
		$qti = $this->getQTI();

		// php5's support for default namespace is useless so we have to define 
		// it manually
		$namespaces = $qti->getDocNamespaces();
		$defaultnamespace = $namespaces[""];
		$qti->registerXPathNamespace("n", $defaultnamespace);

		$types = array();
		foreach (array(
			"associateInteraction",
			"choiceInteraction",
			"customInteraction",
			"drawingInteraction",
			"endAttemptInteraction",
			"extendedTextInteraction",
			"gapMatchInteraction",
			"graphicAssociateInteraction",
			"graphicGapMatchInteraction",
			"graphicOrderInteraction",
			"hotspotInteraction",
			"hottextInteraction",
			"inlineChoiceInteraction",
			"matchInteraction",
			"orderInteraction",
			"positionObjectInteraction",
			"selectPointInteraction",
			"sliderInteraction",
			"textEntryInteraction",
			"uploadInteraction",
		) as $it) {
			$nodes = $qti->xpath("//n:$it");
			if ($nodes !== false && count($nodes))
				$types[] = $it;
		}
		return $types;
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

	// get the data, get one element from the data or set on element in the data
	public function data() {
		$args = func_get_args();

		// no arguments -- return the data array
		if (count($args) == 0)
			return $this->data();

		// one argument -- return the corresponding item in the data array
		if (count($args) == 1) {
			if (array_key_exists($args[0], $this->data))
				return $this->data[$args[0]];
			return null;
		}

		// two arguments -- set the data item indicated by the first argument to 
		// the value in the second argument
		if (count($args) == 2) {
			$this->clearQTI();
			return $this->data[$args[0]] = $args[1];
		}

		// more than two arguments -- error
		trigger_error("Too many arguments to QTIAssessmentItem::data -- expected 0, 1 or 2", E_USER_ERROR);
		return false;
	}

	// get an array of unique keywords based on the comma-separated string in 
	// data
	public function getKeywords() {
		$keywords = array();

		$str = $this->data("keywords");

		if (is_null($str))
			return $keywords;

		$bits = array_map("trim", explode(",", $str));
		foreach ($bits as $keyword) {
			if (empty($keyword))
				continue;
			$keywords[] = $keyword;
		}

		sort($keywords);

		return array_unique($keywords);
	}

	// store the item in session memory
	public function sessionStore() {
		if (!isset($_SESSION["items"]) || !is_array($_SESSION["items"]))
			$_SESSION["items"] = array();
		$_SESSION["items"][$this->getQTIID()] = $this;
	}

	// remove the item from session memory
	public function sessionRemove() {
		if (!isset($_SESSION["items"]) || !isset($_SESSION["items"][$this->getQTIID()]))
			return;
		unset($_SESSION["items"][$this->getQTIID()]);
	}

	/* --------------------------------------------------------------------- */
	/* protected utility methods                                             */
	/* --------------------------------------------------------------------- */

	// return an initial SimpleXML assessmentItem element
	protected function initialXML() {
		$ai = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
			<assessmentItem
				xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
			/>
		');
		$ai->addAttribute("toolName", PROGRAMNAME);
		$ai->addAttribute("toolVersion", VERSION);

		$ai->addAttribute("adaptive", "false");
		$ai->addAttribute("timeDependent", "false");

		$ai->addAttribute("identifier", $this->getQTIID());
		if (isset($this->data["title"]))
			$ai->addAttribute("title", $this->data["title"]);

		return $ai;
	}

	// clear the qti property so that the QTI will be rebuilt next time it's 
	// asked for
	protected function clearQTI() {
		$this->qti = null;
	}

	/* --------------------------------------------------------------------- */
	/* static utility methods                                                */
	/* --------------------------------------------------------------------- */

	// compare items by title or ID
	public static function compare_by_title(QTIAssessmentItem $a, QTIAssessmentItem $b) {
		// get titles
		$ta = $a->data("title");
		$tb = $b->data("title");

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

	// compare items by modification date
	public static function compare_by_modification_date(QTIAssessmentItem $a, QTIAssessmentItem $b) {
		return $a->getModified() - $b->getModified();
	}

	// get a QTIAssessmentItem object from session memory given a QTIID string
	// return false if it is not found
	public static function fromQTIID($qtiid) {
		if (!isset($_SESSION["items"]))
			return false;
		if (!isset($_SESSION["items"][$qtiid]))
			return false;
		return $_SESSION["items"][$qtiid];
	}

	// return an array of all items in session memory
	public static function allItems() {
		if (!isset($_SESSION["items"]))
			return array();
		return $_SESSION["items"];
	}
}

?>
