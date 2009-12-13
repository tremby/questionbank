<?php

class QTIExtendedMatchingItem extends QTIAssessmentItem {
	public function __construct() {
		parent::__construct();

		$this->itemtype = "extendedMatchingItem";
		$this->itemtypeprint = "extended matching item";
		$this->itemtypedescription = "A stimulus followed by a number of possible responses and then a number of question prompts. The candidate checks each response which is correct for each question prompt.";
	}

	public function showForm($data = null) {
		include "htmlheader.php";
		?>

		<script type="text/javascript">
			alphaChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

			addoption = function() {
				// clone the last option on the list and increment its id
				var newoption = $("#options tr.option:last").clone();
				var oldid = parseInt($("input.optiontext", newoption).attr("id").split("_")[1]);
				var newid = oldid + 1;

				// give it the new id number and wipe its text
				newoption.attr("id", "option_" + newid);
				$(".optionid", newoption).text(alphaChars.charAt(newid));
				$("input.optiontext", newoption).attr("id", "option_" + newid + "_optiontext").attr("name", "option_" + newid + "_optiontext").val("").css("background-color", "");

				// reinstate the remove action
				$("input.removeoption", newoption).click(removeoption);

				// add it to the list
				$("#options").append(newoption);

				// add checkboxes for this new option to each question
				$("#questions tr.question td.correctresponses").each(function() {
					var newcorrect = $("label.correct:last", this).clone();
					var questionid = newcorrect.attr("id").split("_")[1];
					newcorrect.attr("id", "question_" + questionid + "_option_" + newid);
					$("input", newcorrect).removeAttr("checked").attr("id", "question_" + questionid + "_option_" + newid + "_correct").attr("name", "question_" + questionid + "_option_" + newid + "_correct");
					$(".optionid", newcorrect).text(alphaChars.charAt(newid));
					$(this).append(newcorrect);
				});
			};

			removeoption = function() {
				if ($("#options tr.option").size() < 2) {
					alert("Can't remove the last option");
					return;
				}

				var row = $(this).parents("tr:first");

				// get its id
				var optionid = row.attr("id").split("_")[1];

				// remove it
				row.remove();

				// renumber the remaining options
				var i = 0;
				$("#options tr.option").each(function() {
					$(this).attr("id", "option_" + i);
					$(".optionid", this).text(alphaChars.charAt(i));
					$("input.optiontext", this).attr("id", "option_" + i + "_optiontext").attr("name", "option_" + i + "_optiontext");
					i++;
				});

				// remove this option's checkboxes from each question
				for (var i = 0; i < $("#questions tr.question").size(); i++) {
					$("#question_" + i + "_option_" + optionid).remove();
				}

				// renumber the remaining checkboxes
				$("#questions tr.question td.correctresponses").each(function() {
					var questionid = $(this).parents("tr.question:first").attr("id").split("_")[1];
					i = 0;
					$("label.correct", this).each(function() {
						$(this).attr("id", "question_" + questionid + "_option_" + i);
						$(".optionid", this).text(alphaChars.charAt(i));
						$("input.correct", this).attr("id", "question_" + questionid + "_option_" + i + "_correct").attr("name", "question_" + questionid + "_option_" + i + "_correct");
						i++;
					});
				});
			};

			addquestion = function() {
				// clone the last question on the list and increment its id
				var newquestion = $("#questions tr.question:last").clone();
				var oldid = parseInt($("textarea", newquestion).attr("id").split("_")[1]);
				var newid = oldid + 1;

				// give it the new id number and wipe its text
				newquestion.attr("id", "question_" + newid);
				$("textarea", newquestion).attr("id", "question_" + newid + "_prompt").attr("name", "question_" + newid + "_prompt").val("").css("background-color", "");

				// clear all its checkboxes and update their question numbers
				$("input.correct", newquestion).removeAttr("checked");
				var i = 0;
				$("td.correctresponses label.correct", newquestion).each(function() {
					$(this).attr("id", "question_" + newid + "_option_" + i);
					$("input.correct", this).attr("id", "question_" + newid + "_option_" + i + "_correct").attr("name", "question_" + newid + "_option_" + i + "_correct");
					i++;
				});

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
					var j = 0;
					$("td.correctresponses label.correct", this).each(function() {
						$(this).attr("id", "question_" + i + "_option_" + j);
						$("input.correct", this).attr("id", "question_" + i + "_option_" + j + "_correct").attr("name", "question_" + i + "_option_" + j + "_correct");
						j++;
					});
					i++;
				});
			};

			submitcheck = function() {
				// clear any previously set background colours
				$("input, textarea").css("background-color", "");

				// title must be set
				if ($("#title").val().length == 0) {
					$("#title").css("background-color", errorcolour);
					alert("A title must be set for this item");
					return false;
				}

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
				var ok = true;
				$("input.optiontext").each(function(n) {
					if ($(this).val().length == 0) {
						$(this).css("background-color", warningcolour);
						ok = confirm("Option " + (n + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
						if (ok)
							$(this).css("background-color", "");
						else
							return false; //this is "break" in the Jquery each() pseudoloop
					}
				});
				if (!ok) return false;
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

				// warn about any identical options
				for (var i = 0; i < $("input.optiontext").size(); i++) {
					for (var j = i + 1; j < $("input.optiontext").size(); j++) {
						if ($("#option_" + i + "_optiontext").val() == $("#option_" + j + "_optiontext").val()) {
							$("#option_" + i + "_optiontext, #option_" + j + "_optiontext").css("background-color", warningcolour);
							ok = confirm("Options " + (i + 1) + " and " + (j + 1) + " are the same -- click OK to continue regardless or cancel to edit them");
							if (ok)
								$("#option_" + i + "_optiontext, #option_" + j + "_optiontext").css("background-color", "");
							else
								break;
						}
					}
					if (!ok) break;
				}
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

				// confirm the user wanted only one option
				if ($("input.optiontext").size() == 1 && !confirm("There is only one option -- click OK to continue regardless or cancel to add more"))
					return false;

				// confirm the user wanted only one question
				if ($("textarea.prompt").size() == 1 && !confirm("There is only one question -- click OK to continue regardless or cancel to add more"))
					return false;

				return true;
			};

			$(document).ready(function() {
				$("#addoption").click(addoption);
				$(".removeoption").click(removeoption);
				$("#addquestion").click(addquestion);
				$(".removequestion").click(removequestion);
				$("#submit").click(submitcheck);
			});
		</script>

		<h2>Make a new extended matching item</h2>

		<?php $this->showmessages(); ?>

		<form id="newitem" action="?page=newAssessmentItem" method="post">
			<input type="hidden" name="itemtype" value="extendedMatchingItem">
			<dl>
				<dt><label for="title">Title</label></dt>
				<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

				<dt><label for="stimulus">Stimulus</label></dt>
				<dd><textarea rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

				<dt>Options</dt>
				<dd>
					<table id="options">
						<tr>
							<th>ID</th>
							<th>Option text</th>
							<th>Actions</th>
						</tr>
						<?php if (!isset($this->data["option_0_optiontext"])) {
							// starting from scratch -- initialize first options
							$this->data["option_0_optiontext"] = "";
							$this->data["option_1_optiontext"] = "";
						}
						for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) { ?>
							<tr class="option" id="option_<?php echo $i; ?>">
								<td class="optionid"><?php echo chr(ord("A") + $i); ?></td>
								<td><input size="48" type="text" id="option_<?php echo $i; ?>_optiontext" name="option_<?php echo $i; ?>_optiontext" class="optiontext" value="<?php echo htmlspecialchars($this->data["option_{$i}_optiontext"]); ?>"></td>
								<td><input type="button" class="removeoption" value="Remove"></td>
							</tr>
						<?php } ?>
					</table>
					<input type="button" id="addoption" value="Add option">
				</dd>

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
								<td class="correctresponses">
									<?php for ($j = 0; array_key_exists("option_{$j}_optiontext", $this->data); $j++) { ?>
										<label class="correct" id="question_<?php echo $i; ?>_option_<?php echo $j; ?>">
											<span class="optionid"><?php echo chr(ord("A") + $j); ?></span>
											<input type="checkbox" id="question_<?php echo $i; ?>_option_<?php echo $j; ?>_correct" name="question_<?php echo $i; ?>_option_<?php echo $j; ?>_correct" class="correct"<?php if (isset($this->data["question_{$i}_option_{$i}_correct"])) { ?> checked="checked"<?php } ?>>
										</label>
									<?php } ?>
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
		$ai->addAttribute("identifier", "emi_" . md5(uniqid()));
		$ai->addAttribute("title", $this->data["title"]);

		// response declarations
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$rd = $ai->addChild("responseDeclaration");
			$rd->addAttribute("identifier", "RESPONSE_question_$q");
			$rd->addAttribute("cardinality", "multiple");
			$rd->addAttribute("baseType", "identifier");

			// build array of correct responses
			$correct = array();
			for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++)
				if (isset($this->data["question_{$q}_option_{$o}_correct"]))
					$correct[] = $o;

			// add correctResponse node only if any options are correct
			if (!empty($correct)) {
				$rd->addChild("correctResponse");
				foreach ($correct as $o)
					$rd->correctResponse->addChild("value", "question_{$q}_option_$o");
			}
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
			// The stimulus must be valid XML at this point. Even if it is, and even 
			// if it's also valid XHTML, it may still not be valid QTI since QTI 
			// only allows a subset of XHTML. So we collect errors here.
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

		// list the options
		$options = "";
		for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++)
			$options .= "<tr><th>" . chr(ord("A") + $o) . "</th><td>" . xmlspecialchars($this->data["option_{$o}_optiontext"]) . "</td></tr>";
		simplexml_append($ib, simplexml_load_string('<table class="emioptions"><tbody>' . $options . '</tbody></table>'));

		// questions
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$ci = $ib->addChild("choiceInteraction");
			$ci->addAttribute("maxChoices", "0");
			$ci->addAttribute("minChoices", "0");
			$ci->addAttribute("shuffle", "false");
			$ci->addAttribute("responseIdentifier", "RESPONSE_question_$q");
			$ci->addChild("prompt", $this->data["question_{$q}_prompt"]);
			for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++) {
				$sc = $ci->addChild("simpleChoice", chr(ord("A") + $o));
				$sc->addAttribute("identifier", "question_{$q}_option_$o");
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

			// build array of correct responses
			$correct = array();
			for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++)
				if (isset($this->data["question_{$q}_option_{$o}_correct"]))
					$correct[] = $o;

			// criteria for a correct answer
			if (empty($correct)) {
				// multiple response in which the correct response is to tick no 
				// boxes -- check number of responses is equal to zero
				$e = $ri->addChild("equal");
				$e->addAttribute("toleranceMode", "exact");
				$e->addChild("containerSize")->addChild("variable")->addAttribute("identifier", "RESPONSE_question_$q");
				$e->addChild("baseValue", "0")->addAttribute("baseType", "integer");
			} else {
				// otherwise, we match responses to the correctResponse above
				$m = $ri->addChild("match");
				$m->addChild("variable")->addAttribute("identifier", "RESPONSE_question_$q");
				$m->addChild("correct")->addAttribute("identifier", "RESPONSE_question_$q");
			}

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
