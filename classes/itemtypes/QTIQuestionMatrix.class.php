<?php

class QTIQuestionMatrix extends QTIAssessmentItem {
	public function __construct() {
		parent::__construct();

		$this->itemtype = "questionMatrix";
		$this->itemtypeprint = "question matrix";
		$this->itemtypedescription = "A stimulus followed by a number of question prompts. The candidate selects true or false for each.";
	}

	public function showForm($data = null) {
		include "htmlheader.php";
		?>

		<script type="text/javascript">
			addquestion = function() {
				// clone the last question on the list and increment its id
				var newquestion = $("#questions tr.question:last").clone();
				var oldid = parseInt($("textarea", newquestion).attr("id").split("_")[1]);
				var newid = oldid + 1;

				// give it the new id number and wipe its text
				newquestion.attr("id", "question_" + newid);
				$("textarea", newquestion).attr("id", "question_" + newid + "_prompt").attr("name", "question_" + newid + "_prompt").val("").css("background-color", "");

				// clear all its checkboxes and update their question numbers
				$("td.answer input", newquestion).removeAttr("checked");
				$("td.answer label.true", newquestion).attr("id", "question_" + newid + "_true");
				$("td.answer label.false", newquestion).attr("id", "question_" + newid + "_false");
				$("td.answer label input", newquestion).attr("name", "question_" + newid + "_answer");

				// reinstate the remove action
				$("input.removequestion", newquestion).click(removequestion);

				// add it to the list
				$("#questions").append(newquestion);
			};

			removequestion = function() {
				if ($("#questions tr.question").size() < 2) {
					alert("Can't remove the last question");
					return;
				}

				$(this).parents("tr:first").remove();

				// renumber the remaining questions
				var i = 0;
				$("#questions tr.question").each(function() {
					$(this).attr("id", "question_" + i);
					$("textarea", this).attr("id", "question_" + i + "_prompt").attr("name", "question_" + i + "_prompt");
					$("td.answer label.true", this).attr("id", "question_" + i + "_true");
					$("td.answer label.false", this).attr("id", "question_" + i + "_false");
					$("td.answer label input", this).attr("name", "question_" + i + "_answer");
					i++;
				});
			};

			submitcheck = function() {
				// clear any previously set background colours
				$("input, textarea, td.answer").css("background-color", "");

				// title must be set
				if ($("#title").val().length == 0) {
					$("#title").css("background-color", errorcolour);
					alert("A title must be set for this item");
					return false;
				}

				// true or false must be chosen for each question
				var ok = true;
				$("#questions tr.question td.answer").each(function(n) {
					if ($("input:checked", this).size() == 0) {
						$(this).css("background-color", errorcolour);
						alert("No correct response has been chosen for question " + (n + 1));
						ok = false;
						return false;
					}
				});
				if (!ok) return false;

				// issue warnings if applicable

				// confirm the user wanted an empty stimulus
				if ($("#stimulus").val().length == 0) {
					$("#stimulus").css("background-color", warningcolour);
					if (!confirm("Stimulus is empty -- click OK to continue regardless or cancel to edit it"))
						return false;
					else
						$("#stimulus").css("background-color", "");
				}

				// confirm the user wanted any empty boxes
				$("textarea.prompt").each(function(n) {
					if ($(this).val().length == 0) {
						$(this).css("background-color", warningcolour);
						ok = confirm("The prompt for question " + (n + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
						if (ok)
							$(this).css("background-color", "");
						else
							return false; //this is "break" in the Jquery each() pseudoloop
					}
				});
				if (!ok) return false;

				// warn about any identical questions
				for (var i = 0; i < $("textarea.prompt").size(); i++) {
					for (var j = i + 1; j < $("textarea.prompt").size(); j++) {
						if ($("#question_" + i + "_prompt").val() == $("#question_" + j + "_prompt").val()) {
							$("#question_" + i + "_prompt, #question_" + j + "_prompt").css("background-color", warningcolour);
							ok = confirm("The prompts for questions " + (i + 1) + " and " + (j + 1) + " are the same -- click OK to continue regardless or cancel to edit them");
							if (ok)
								$("#question_" + i + "_prompt, #question_" + j + "_prompt").css("background-color", "");
							else
								break;
						}
					}
					if (!ok) break;
				}
				if (!ok) return false;

				// confirm the user wanted only one question
				if ($("textarea.prompt").size() == 1 && !confirm("There is only one question -- click OK to continue regardless or cancel to add more"))
					return false;

				return true;
			};

			$(document).ready(function() {
				$("#addquestion").click(addquestion);
				$(".removequestion").click(removequestion);
				$("#submit").click(submitcheck);
			});
		</script>

		<h2>Make a new question matrix item</h2>

		<?php $this->showmessages(); ?>

		<form id="newitem" action="?page=newAssessmentItem" method="post">
			<input type="hidden" name="itemtype" value="questionMatrix">
			<dl>
				<dt><label for="title">Title</label></dt>
				<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

				<dt><label for="stimulus">Stimulus</label></dt>
				<dd><textarea rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

				<dt>Questions</dt>
				<dd>
					<table id="questions">
						<tr>
							<th>Question prompt</th>
							<th>Correct response</th>
							<th>Actions</th>
						</tr>
						<?php if (!isset($this->data["question_0_prompt"])) {
							// starting from scratch -- initialize first questions
							$this->data["question_0_prompt"] = "";
							$this->data["question_1_prompt"] = "";
						}
						for ($i = 0; array_key_exists("question_{$i}_prompt", $this->data); $i++) { ?>
							<tr class="question" id="question_<?php echo $i; ?>">
								<td><textarea class="prompt" rows="2" cols="48" name="question_<?php echo $i; ?>_prompt" id="question_<?php echo $i; ?>_prompt"><?php if (isset($this->data["question_{$i}_prompt"])) echo htmlspecialchars($this->data["question_{$i}_prompt"]); ?></textarea></td>
								<td class="answer">
									<label id="question_<?php echo $i; ?>_true" class="true">
										<input type="radio" name="question_<?php echo $i; ?>_answer" value="true"<?php if (isset($this->data["question_{$i}_answer"]) && $this->data["question_{$i}_answer"] == "true") { ?> checked="checked"<?php } ?>>
										true
									</label>
									<label id="question_<?php echo $i; ?>_false" class="false">
										<input type="radio" name="question_<?php echo $i; ?>_answer" value="false"<?php if (isset($this->data["question_{$i}_answer"]) && $this->data["question_{$i}_answer"] != "true") { ?> checked="checked"<?php } ?>>
										false
									</label>
								</td>
								<td><input type="button" class="removequestion" value="Remove"></td>
							</tr>
						<?php } ?>
					</table>
					<input type="button" id="addquestion" value="Add question">
				</dd>
			</dl>
			<div>

				<input id="submit" type="submit" name="newitem" value="Submit">
			</div>
		</form>

		<?php
		include "htmlfooter.php";
	}

	public function buildQTI($data = null) {
		if (!is_null($data))
			$this->data = $data;

		// container element and other metadata
		$ai = new SimpleXMLElement('
			<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"/>
		');
		$ai->addAttribute("adaptive", "false");
		$ai->addAttribute("timeDependent", "false");
		$ai->addAttribute("identifier", "qm_" . md5(uniqid()));
		$ai->addAttribute("title", $this->data["title"]);

		// response declarations
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$rd = $ai->addChild("responseDeclaration");
			$rd->addAttribute("identifier", "RESPONSE_question_$q");
			$rd->addAttribute("cardinality", "single");
			$rd->addAttribute("baseType", "identifier");
			$rd->addChild("correctResponse")->addChild("value", "question_{$q}_" . $this->data["question_{$q}_answer"]);
		}

		// outcome declaration
		$od = $ai->addChild("outcomeDeclaration");
		$od->addAttribute("identifier", "SCORE");
		$od->addAttribute("cardinality", "single");
		$od->addAttribute("baseType", "integer");
		$od->addChild("defaultValue");
		$od->defaultValue->addChild("value", "0");

		// item body
		$ib = $ai->addChild("itemBody");

		// get stimulus and add to the XML tree
		if (isset($this->data["stimulus"]) && !empty($this->data["stimulus"])) {
			// if stimulus doesn't start with a tag, wrap it in a div
			$this->data["stimulus"] = trim($this->data["stimulus"]);
			if ($this->data["stimulus"][0] != "<")
				$this->data["stimulus"] = "<div>" . $this->data["stimulus"] . "</div>";

			// parse it as XML
			// The stimulus must be valid XML at this point. Even if it is, and 
			// even if it's also valid XHTML, it may still not be valid QTI 
			// since QTI only allows a subset of XHTML. So we collect errors 
			// here.
			libxml_use_internal_errors(true);
			$stimulus = simplexml_load_string($this->data["stimulus"]);
			if ($stimulus === false) {
				$this->errors[] = "Stimulus is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
				foreach (libxml_get_errors() as $error)
					$this->errors[] = "Stimulus line " . $error->line . ", column " . $error->column . ": " . $error->message;
				libxml_clear_errors();
			} else {
				simplexml_append($ib, $stimulus);
			}
			libxml_use_internal_errors(false);
		}

		// questions
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$ci = $ib->addChild("choiceInteraction");
			$ci->addAttribute("maxChoices", "1");
			$ci->addAttribute("shuffle", "false");
			$ci->addAttribute("responseIdentifier", "RESPONSE_question_$q");
			$ci->addChild("prompt", $this->data["question_{$q}_prompt"]);
			foreach (array("true", "false") as $o) {
				$sc = $ci->addChild("simpleChoice", $o);
				$sc->addAttribute("identifier", "question_{$q}_$o");
			}
		}

		// response processing
		$rp = $ai->addChild("responseProcessing");

		// set score = 0
		$sov = $rp->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "0")->addAttribute("baseType", "integer");

		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$rc = $rp->addChild("responseCondition");

			// if
			$ri = $rc->addChild("responseIf");

			// criteria for a correct answer
			$m = $ri->addChild("match");
			$m->addChild("variable")->addAttribute("identifier", "RESPONSE_question_$q");
			$m->addChild("correct")->addAttribute("identifier", "RESPONSE_question_$q");

			// increment score
			$sov = $ri->addChild("setOutcomeValue");
			$sov->addAttribute("identifier", "SCORE");
			$s = $sov->addChild("sum");
			$s->addChild("variable")->addAttribute("identifier", "SCORE");
			$s->addChild("baseValue", "1")->addAttribute("baseType", "integer");
		}

		if (!empty($this->errors))
			return false;

		// validate the QTI
		validateQTI($ai, $this->errors, $this->warnings, $this->messages);

		if (!empty($this->errors))
			return false;

		$this->qti = $ai;
		return $this->qti;
	}

	public function fromXML(SimpleXMLElement $xml) {
		return 0;
	}
}

?>
