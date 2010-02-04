<?php

class QTIQuestionMatrix extends QTIAssessmentItem {
	public function itemTypePrint() {
		return "question matrix";
	}
	public function itemTypeDescription() {
		return "A stimulus followed by a number of question prompts. The candidate selects true or false for each.";
	}

	protected function headerJS() {
		ob_start();
		?>
		//<script type="text/javascript"> (make vim colour the syntax properly)
		addquestion = function() {
			// clone the last question on the list and increment its id
			var newquestion = $("#questions tr.question:last").clone();
			var oldid = parseInt($("textarea", newquestion).attr("id").split("_")[1]);
			var newid = oldid + 1;

			// give it the new id number and wipe its text
			newquestion.attr("id", "question_" + newid);
			$("textarea", newquestion).attr("id", "question_" + newid + "_prompt").attr("name", "question_" + newid + "_prompt").val("").removeClass("error warning");

			// clear all its checkboxes and update their question numbers
			$("td.answer input", newquestion).removeAttr("checked");
			$("td.answer label.true", newquestion).attr("id", "question_" + newid + "_true");
			$("td.answer label.false", newquestion).attr("id", "question_" + newid + "_false");
			$("td.answer label input", newquestion).attr("name", "question_" + newid + "_answer");

			// stripe it
			newquestion.removeClass("row" + (oldid % 2)).addClass("row" + (newid % 2));

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
				$(this).removeClass("row" + ((i + 1) % 2)).addClass("row" + (i % 2));
				i++;
			});
		};

		edititemsubmitcheck_itemspecificerrors = function() {
			// true or false must be chosen for each question
			var ok = true;
			$("#questions tr.question td.answer").each(function(n) {
				if ($("input:checked", this).size() == 0) {
					$(this).addClass("error");
					alert("No correct response has been chosen for question " + (n + 1));
					ok = false;
					return false;
				}
			});
			if (!ok) return false;

			return true;
		};

		edititemsubmitcheck_itemspecificwarnings = function() {
			// confirm the user wanted any empty boxes
			var ok = true;
			$("textarea.prompt").each(function(n) {
				if ($(this).val().length == 0) {
					$(this).addClass("warning");
					ok = confirm("The prompt for question " + (n + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
					if (ok)
						$(this).removeClass("error warning");
					else
						return false; //this is "break" in the Jquery each() pseudoloop
				}
			});
			if (!ok) return false;

			// warn about any identical questions
			for (var i = 0; i < $("textarea.prompt").size(); i++) {
				for (var j = i + 1; j < $("textarea.prompt").size(); j++) {
					if ($("#question_" + i + "_prompt").val() == $("#question_" + j + "_prompt").val()) {
						$("#question_" + i + "_prompt, #question_" + j + "_prompt").addClass("warning");
						ok = confirm("The prompts for questions " + (i + 1) + " and " + (j + 1) + " are the same -- click OK to continue regardless or cancel to edit them");
						if (ok)
							$("#question_" + i + "_prompt, #question_" + j + "_prompt").removeClass("error warning");
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
		});
		<?php
		return ob_get_clean();
	}

	protected function formHTML() {
		ob_start();
		?>
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
				for ($i = 0; array_key_exists("question_{$i}_prompt", $this->data); $i++) { $odd = $i % 2; ?>
					<tr class="question row<?php echo $odd; ?>" id="question_<?php echo $i; ?>">
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
		<?php
		return ob_get_clean();
	}

	public function buildQTI($data = null) {
		if (!is_null($data))
			$this->data = $data;

		if (empty($this->data))
			return false;

		// container element and other metadata
		$ai = new SimpleXMLElement('
			<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"/>
		');
		$ai->addAttribute("adaptive", "false");
		$ai->addAttribute("timeDependent", "false");
		$ai->addAttribute("identifier", $this->getQTIID());
		if (isset($this->data["title"]))
			$ai->addAttribute("title", $this->data["title"]);

		// response declarations
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$rd = $ai->addChild("responseDeclaration");
			$rd->addAttribute("identifier", "RESPONSE_question_$q");
			$rd->addAttribute("cardinality", "single");
			$rd->addAttribute("baseType", "identifier");
			if (isset($this->data["question_{$q}_answer"]))
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
			$this->data["stimulus"] = wrapindiv($this->data["stimulus"]);

			// parse it as XML
			$stimulus = stringtoxml($this->data["stimulus"], "stimulus");
			if (is_array($stimulus)) {
				// errors
				$this->errors[] = "Stimulus is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
				$this->errors = array_merge($this->errors, $stimulus);
			} else
				simplexml_append($ib, $stimulus);
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
		$data = array(
			"itemtype"	=>	$this->itemType(),
			"title"		=>	(string) $xml["title"],
			"stimulus"	=>	qti_get_stimulus($xml->itemBody),
		);

		// count the choiceInteractions
		$questioncount = count($xml->itemBody->choiceInteraction);

		// no good if there are no questions
		if ($questioncount == 0)
			return 0;

		// ensure there are the same number of responseDeclarations
		if (count($xml->responseDeclaration) != $questioncount)
			return 0;

		// ensure there are the same number of responseConditions
		if (count($xml->responseProcessing->responseCondition) != $questioncount)
			return 0;

		// ensure some stuff for each question
		$q = 0;
		foreach ($xml->itemBody->choiceInteraction as $ci) {
			// candidate can only choose one answer
			if ((string) $ci["maxChoices"] != "1")
				return 0;

			// there are two possible answers
			if (count($ci->simpleChoice) != 2)
				return 0;

			// answers are true and false
			$answers = array(null, null);
			foreach ($ci->simpleChoice as $sc) {
				if (strtolower((string) $sc) == "false")
					$answers[0] = (string) $sc["identifier"];
				else if (strtolower((string) $sc) == "true")
					$answers[1] = (string) $sc["identifier"];
				else
					return 0;
			}

			// check some responseDeclaration things
			$declarationsfound = 0;
			foreach ($xml->responseDeclaration as $rd) {
				if ((string) $rd["identifier"] != (string) $ci["responseIdentifier"])
					continue;

				$declarationsfound++;

				// has a correct response
				if (!isset($rd->correctResponse))
					return 0;

				// has one correct response
				if (count($rd->correctResponse->value) != 1)
					return 0;

				// the correct response is one of the options
				$answer = array_search((string) $rd->correctResponse->value, $answers);
				if ($answer === false)
					return 0;

				// add answer to data
				$data["question_{$q}_answer"] = $answer ? true : false;
			}

			// there was a good responseDeclaration for this question
			if ($declarationsfound != 1)
				return 0;

			// add prompt to data
			$data["question_{$q}_prompt"] = (string) $ci->prompt;

			$q++;
		}

		// happy with that -- set data property and identifier
		$this->data = $data;
		$this->setQTIID((string) $xml["identifier"]);

		// rather strange question matrix if it's only one question
		if ($questioncount == 1)
			return 127;

		return 255;
	}
}

?>
