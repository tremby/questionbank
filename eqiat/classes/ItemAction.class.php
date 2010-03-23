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

abstract class ItemAction {
	/** title
	 * This must be implemented to return the action name as a string starting 
	 * with lowercase
	 */
	abstract public function name();

	/** description
	 * This must be implemented to return a description of the action as a 
	 * string starting with uppercase
	 */
	abstract public function description();

	/** beforeLogic
	 * This can be overridden to perform any actions to be performed before 
	 * getLogic or postLogic
	 */
	public function beforeLogic() {
	}

	/** afterLogic
	 * This can be overridden to perform any actions to be performed after 
	 * getLogic or postLogic
	 */
	public function afterLogic() {
	}

	/** getLogic
	 * This can be overridden to perform any action to take when a get request 
	 * is received -- that is, $_POST is empty
	 */
	public function getLogic() {
		badrequest("no get logic implemented for action " . $this->actionString());
	}

	/** postLogic
	 * This can be overridden to perform any action to take when a post request 
	 * is receieved -- that is, $_POST is not empty
	 */
	public function postLogic() {
		badrequest("no post logic implemented for action " . $this->actionString());
	}

	/** clickJS
	 * This can be overridden to return some javascript to be run when a link to 
	 * the action is clicked
	 * The returned code will make up the body of a function, to which the click 
	 * event is passed as an argument e
	 */
	public function clickJS() {
		return null;
	}

	/** available
	 * This can be overridden to return a boolean showing whether or not the 
	 * action is available for a given assessment item
	 * By default the action is always available
	 */
	public function available(QTIAssessmentItem $ai) {
		return true;
	}

	// return the action string -- that is, the class name with Action knocked 
	// off the end and starting with lowercase
	public function actionString() {
		return lcfirst(substr(get_class($this), 0, -6));
	}

	// return a URL to run this action
	public function actionURL($ai = null, $escape = true) {
		if ($ai instanceof QTIAssessmentItem)
			$qtiid = $ai->getQTIID();
		else
			$qtiid = $ai;

		$sep = $escape ? "&amp;" : "&";
		return SITEROOT_WEB . "?page=itemAction{$sep}action=" . $this->actionString() . (is_null($qtiid) ? "" : $sep . "qtiid=$qtiid");
	}
}

?>
