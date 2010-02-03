<?php

abstract class QTIMultipleChoiceResponse extends QTIAssessmentItem {
	public function __construct() {
		parent::__construct();

		$this->interactionType = "choiceInteraction";
	}

	protected function headerJS() {
		ob_start();
		?>
		//<script type="javascript"> (make vim colour the syntax properly)
		addoption = function() {
			// clone the last option on the list and increment its id
			var newoption = $("#options tr.option:last").clone();
			var oldid = parseInt($("input.optiontext", newoption).attr("id").split("_")[1]);
			var newid = oldid + 1;

			// give it the new id number and wipe its text
			newoption.attr("id", "option_" + newid);
			$("input.optiontext", newoption).attr("id", "option_" + newid + "_optiontext").attr("name", "option_" + newid + "_optiontext").val("").removeClass("error warning");
			$("input.correct", newoption).attr("id", "option_" + newid + "_correct").removeAttr("checked");
			if ($("input.correct", newoption).attr("type") == "checkbox")
				$("input.correct", newoption).attr("name", "option_" + newid + "_correct");
			$("input.fixed", newoption).attr("id", "option_" + newid + "_fixed").attr("name", "option_" + newid + "_fixed").removeAttr("checked");

			// add the remove and update feedback actions
			$("input.removeoption", newoption).click(removeoption);
			$("input.optiontext", newoption).change(updatefeedback);

			// add it to the list
			$("#options").append(newoption);

			// switch off all tinyMCEs
			removetinymces();

			// clone the last feedback row
			var newfeedback = $("#option_" + oldid + "_feedback").clone();

			// give it and its bits the new id and wipe the text
			newfeedback.attr("id", "option_" + newid + "_feedback");
			$(".feedbackoptiontext", newfeedback).text("");
			$("textarea.feedbackchosen", newfeedback).attr("name", "option_" + newid + "_feedback_chosen").attr("id", "option_" + newid + "_feedback_chosen").val("").removeClass("error warning");
			$("textarea.feedbackunchosen", newfeedback).attr("name", "option_" + newid + "_feedback_unchosen").attr("id", "option_" + newid + "_feedback_unchosen").val("").removeClass("error warning");

			// add the focus actions
			$("textarea.qtitinymce", newfeedback).focus(focustinymce);

			// add it to the list
			$("#feedbackdiv table").append(newfeedback);

			// stripe them correctly
			newoption.add(newfeedback).removeClass("row" + (oldid % 2)).addClass("row" + (newid % 2));
		};

		removeoption = function() {
			if ($("#options tr.option").size() < 2) {
				alert("Can't remove the last option");
				return;
			}

			var optionid = $(this).parents("tr:first").attr("id").split("_")[1];

			// switch off all tinyMCEs
			removetinymces();

			$("#option_" + optionid + ", #option_" + optionid + "_feedback").remove();

			// renumber and stripe the remaining options
			var i = 0;
			$("#options tr.option").each(function() {
				$(this).attr("id", "option_" + i);
				$("input.optiontext", this).attr("id", "option_" + i + "_optiontext").attr("name", "option_" + i + "_optiontext");
				$("input.correct", this).attr("id", "option_" + i + "_correct");
				if ($("input.correct", this).attr("type") == "checkbox") {
					$("input.correct", this).attr("name", "option_" + i + "_correct");
				} else {
					$("input.correct", this).attr("name", "correct").attr("value", "option_" + i);
				}
				$("input.fixed", this).attr("id", "option_" + i + "_fixed").attr("name", "option_" + i + "_fixed");
				$(this).removeClass("row" + ((i + 1) % 2)).addClass("row" + (i % 2));
				i++;
			});

			// renumber and stripe the remaining feedback
			i = 0;
			$("#feedbackdiv tr.feedback").each(function() {
				$(this).attr("id", "option_" + i + "_feedback");
				$("textarea.feedbackchosen", this).attr("name", "option_" + i + "_feedback_chosen").attr("id", "option_" + i + "_feedback_chosen");
				$("textarea.feedbackunchosen", this).attr("name", "option_" + i + "_feedback_unchosen").attr("id", "option_" + i + "_feedback_unchosen");
				$(this).removeClass("row" + ((i + 1) % 2)).addClass("row" + (i % 2));
				i++;
			});
		};

		toggleshuffle = function() {
			if ($("#shuffle").is(":checked"))
				$("#options th.fixed, #options td.fixed").show();
			else
				$("#options th.fixed, #options td.fixed").hide();
		};

		togglefeedback = function() {
			if ($("#feedback").is(":checked"))
				$("#feedbackdiv").show();
			else
				$("#feedbackdiv").hide();
		};

		updatefeedback = function() {
			var optionid = $(this).attr("id").split("_")[1];
			$("#option_" + optionid + "_feedback .feedbackoptiontext").text($(this).val());
		};

		switchitemtype = function() {
			if ($("input.itemtype:checked").attr("id") == "itemtype_mc") {
				// change to radio buttons
				if ($("#option_0_correct").attr("type") == "radio") return;
				var hadchecked = false;
				$("input.correct").each(function() {
					// remove checked attribute from all but first checked box
					if ($(this).is(":checked")) {
						if (hadchecked)
							$(this).removeAttr("checked");
						else
							hadchecked = true;
					}
					var id = $(this).attr("id");
					var index = id.split("_")[1];
					$(this).removeAttr("id");
					$(this).after('<input type="radio" id="' + id + '" name="correct" value="option_' + index + '" class="correct"' + ($(this).is(":checked") ? ' checked="checked"' : '') + '>');
					$(this).remove();
				});

				// hide choice restriction options
				$(".choicerestrictions").hide();
			} else {
				// change to checkboxes
				if ($("#option_0_correct").attr("type") == "checkbox") return;
				$("input.correct").each(function() {
					var id = $(this).attr("id");
					var index = id.split("_")[1];
					$(this).removeAttr("id");
					$(this).after('<input type="checkbox" id="' + id + '" name="' + id + '" class="correct"' + ($(this).is(":checked") ? ' checked="checked"' : '') + '>');
					$(this).remove();
				});

				// show choice restriction options
				$(".choicerestrictions").show();
			}
		};

		submitcheck = function() {
			// clear any previously set background colours
			$("input, textarea").removeClass("error warning");

			// title must be set
			if ($("#title").val().length == 0) {
				$("#title").addClass("error");
				alert("A title must be set for this item");
				return false;
			}

			// if multiple choice, one option must be correct
			if ($("input.itemtype:checked").attr("id") == "itemtype_mc") {
				if ($("input.correct:checked").size() == 0) {
					alert("One response must be marked as correct");
					return false;
				}
			}

			// choice restriction options must make sense
			if ($("input.itemtype:checked").attr("id") == "itemtype_mr") {
				// maximum choices
				if ($("#maxchoices").val().length == 0 || isNaN($("#maxchoices").val())) {
					$("#maxchoices").addClass("error");
					alert("Value for maximum choices is not a number");
					return false;
				}
				if ($("#maxchoices").val() < 0 || $("#maxchoices").val().indexOf(".") != -1) {
					$("#maxchoices").addClass("error");
					alert("Value for maximum choices must be zero (no restriction) or a positive integer");
					return false;
				}
				if ($("#maxchoices").val() > $("#options tr.option").size()) {
					$("#maxchoices").addClass("error");
					alert("Value for maximum choices cannot be greater than the number of possible choices");
					return false;
				}

				// minimum choices
				if ($("#minchoices").val().length == 0 || isNaN($("#minchoices").val())) {
					$("#minchoices").addClass("error");
					alert("Value for minimum choices is not a number");
					return false;
				}
				if ($("#minchoices").val() < 0 || $("#minchoices").val().indexOf(".") != -1) {
					$("#minchoices").addClass("error");
					alert("Value for minimum choices must be zero (not require to select any choices) or a positive integer");
					return false;
				}
				if ($("#minchoices").val() > $("#options tr.option").size()) {
					$("#minchoices").addClass("error");
					alert("Value for minimum choices cannot be greater than the number of possible choices");
					return false;
				}

				// maximum choices >= minimum choices
				if ($("#maxchoices").val() != 0 && $("#minchoices").val() > $("#maxchoices").val()) {
					$("#maxchoices, #minchoices").addClass("error");
					alert("Value for minimum choices cannot be greater than the value for maximum choices");
					return false;
				}
			}

			// issue warnings if applicable

			// maximum choices 1 for a multiple response is strange
			if ($("input.itemtype:checked").attr("id") == "itemtype_mr") {
				if ($("#maxchoices").val() == 1) {
					$("#maxchoices").addClass("warning");
					if (!confirm("Value for maximum choices is set as 1 which will lead to radio buttons rather than checkboxes -- click OK to continue regardless or cancel to edit it"))
						return false;
					else
						$("#maxchoices").removeClass("error warning");
				}
			}

			// confirm the user wanted the candidate not to be able to check all 
			// correct responses or to have to check incorrect ones
			if ($("input.itemtype:checked").attr("id") == "itemtype_mr") {
				if ($("#maxchoices").val() != 0 && $("#maxchoices").val() < $("input.correct:checked").size()) {
					$("#maxchoices").addClass("warning");
					if (!confirm("Value for maximum choices is less than the number of correct choices -- click OK to continue regardless or cancel to edit it"))
						return false;
					else
						$("#maxchoices").removeClass("error warning");
				}
				if ($("#minchoices").val() != 0 && $("#minchoices").val() > $("input.correct:checked").size()) {
					$("#minchoices").addClass("warning");
					if (!confirm("Value for minimum choices is greater than the number of correct choices -- click OK to continue regardless or cancel to edit it"))
						return false;
					else
						$("#minchoices").removeClass("error warning");
				}
			}

			// confirm the user wanted an empty stimulus
			if ($("#stimulus").val().length == 0) {
				$("#stimulus").addClass("warning");
				if (!confirm("Stimulus is empty -- click OK to continue regardless or cancel to edit it"))
					return false;
				else
					$("#stimulus").removeClass("error warning");
			}

			// confirm the user wanted an empty question prompt
			if ($("#prompt").val().length == 0) {
				$("#prompt").addClass("warning");
				if (!confirm("Question prompt is empty -- click OK to continue regardless or cancel to edit it"))
					return false;
				else
					$("#prompt").removeClass("error warning");
			}

			// confirm the user wanted any empty boxes
			var ok = true;
			$("input.optiontext").each(function(n) {
				if ($(this).val().length == 0) {
					$(this).addClass("warning");
					ok = confirm("Option " + (n + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
					if (ok)
						$(this).removeClass("error warning");
					else
						return false; //this is "break" in the Jquery each() pseudoloop
				}
			});
			if (!ok) return false;

			// warn about any identical options
			for (var i = 0; i < $("input.optiontext").size(); i++) {
				for (var j = i + 1; j < $("input.optiontext").size(); j++) {
					if ($("#option_" + i + "_optiontext").val() == $("#option_" + j + "_optiontext").val()) {
						$("#option_" + i + "_optiontext, #option_" + j + "_optiontext").addClass("warning");
						ok = confirm("Options " + (i + 1) + " and " + (j + 1) + " are the same -- click OK to continue regardless or cancel to edit them");
						if (ok)
							$("#option_" + i + "_optiontext, #option_" + j + "_optiontext").removeClass("error warning");
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

			// confirm it's what the user wanted if shuffle is on but all 
			// options are marked as fixed
			if ($("#shuffle").is(":checked") && $("input.fixed").size() == $("input.fixed:checked").size() && !confirm("Shuffle is selected but all options are marked as fixed -- click OK to continue regardless or cancel to change this"))
				return false;

			return true;
		}

		$(document).ready(function() {
			$("#addoption").click(addoption);
			$(".removeoption").click(removeoption);
			$("#shuffle").click(toggleshuffle);
			$("#feedback").click(togglefeedback);
			$("input.itemtype").click(switchitemtype);
			$("#submit").click(submitcheck);
			$("input.optiontext").change(updatefeedback);
		});
		<?php
		return ob_get_clean();
	}

	public function formHTML() {
		$multipleresponse = $this->itemType() == "multipleResponse";
		ob_start();
		?>
		<dt>Item type</dt>
		<dd>
			<ul><label>
				<input type="radio" name="itemtype" class="itemtype" id="itemtype_mc" value="multipleChoice"<?php if (!$multipleresponse) { ?> checked="checked"<?php } ?>>
				Multiple choice (choose one answer)
			</label></ul>
			<ul><label>
				<input type="radio" name="itemtype" class="itemtype" id="itemtype_mr" value="multipleResponse"<?php if ($multipleresponse) { ?> checked="checked"<?php } ?>>
				Multiple response (choose all appropriate answers)
			</label></ul>
		</dd>

		<dt><label for="prompt">Question prompt</label></dt>
		<dd><textarea rows="2" cols="64" name="prompt" id="prompt"><?php if (isset($this->data["prompt"])) echo htmlspecialchars($this->data["prompt"]); ?></textarea></dd>

		<dt>Options</dt>
		<dd>
			<div>
				<input type="checkbox" id="shuffle" name="shuffle"<?php if (isset($this->data["shuffle"])) { ?> checked="checked"<?php } ?>>
				<label for="shuffle">Shuffle the options</label>
			</div>
			<table id="options">
				<tr>
					<th>Option text</th>
					<th>Correct</th>
					<th class="fixed"<?php if (!isset($this->data["shuffle"])) { ?> style="display: none;"<?php } ?>>Fixed</th>
					<th>Actions</th>
				</tr>
				<?php if (!isset($this->data["option_0_optiontext"])) {
					// starting from scratch -- initialize first options
					$this->data["option_0_optiontext"] = "";
					$this->data["option_1_optiontext"] = "";
					if (!$multipleresponse)
						$this->data["correct"] = "option_0";
				}
				for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) { $odd = $i % 2; ?>
					<tr class="option row<?php echo $odd; ?>" id="option_<?php echo $i; ?>">
						<td><input size="48" type="text" id="option_<?php echo $i; ?>_optiontext" name="option_<?php echo $i; ?>_optiontext" class="optiontext" value="<?php echo htmlspecialchars($this->data["option_{$i}_optiontext"]); ?>"></td>
						<td>
							<?php if ($multipleresponse) { ?>
								<input type="checkbox" id="option_<?php echo $i; ?>_correct" name="option_<?php echo $i; ?>_correct" class="correct"<?php if (isset($this->data["option_{$i}_correct"])) { ?> checked="checked"<?php } ?>>
							<?php } else { ?>
								<input type="radio" id="option_<?php echo $i; ?>_correct" name="correct" value="option_<?php echo $i; ?>" class="correct"<?php if ($this->data["correct"] == "option_$i") { ?> checked="checked"<?php } ?>>
							<?php } ?>
						</td>
						<td class="fixed"<?php if (!isset($this->data["shuffle"])) { ?> style="display: none;"<?php } ?>>
							<input type="checkbox" id="option_<?php echo $i; ?>_fixed" name="option_<?php echo $i; ?>_fixed" class="fixed"<?php if (isset($this->data["option_{$i}_fixed"])) { ?> checked="checked"<?php } ?>>
						</td>
						<td><input type="button" class="removeoption" value="Remove"></td>
					</tr>
				<?php } ?>
			</table>
			<input type="button" id="addoption" value="Add option">
		</dd>

		<dt>Feedback</dt>
		<dd>
			<div>
				<input type="checkbox" id="feedback" name="feedback"<?php if (isset($this->data["feedback"])) { ?> checked="checked"<?php } ?>>
				<label for="feedback">Provide feedback based on the answer given</label>
			</div>
			<div id="feedbackdiv"<?php if (!isset($this->data["feedback"])) { ?> style="display: none;"<?php } ?>>
				<p class="hint">Each matching piece of feedback is given, in this order – leave boxes for unneccessary cases empty</p>
				<table style="width: 100%;">
					<tr>
						<th>Option</th>
						<th>Feedback if chosen</th>
						<th>Feedback if not chosen</th>
					</tr>
					<?php for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) { $odd = $i % 2; ?>
						<tr class="feedback row<?php echo $odd; ?>" id="option_<?php echo $i; ?>_feedback">
							<td><span class="feedbackoptiontext"><?php echo htmlspecialchars($this->data["option_{$i}_optiontext"]); ?></span></td>
							<td><textarea style="width: 100%;" class="qtitinymce feedbackchosen" rows="2" cols="48" name="option_<?php echo $i; ?>_feedback_chosen" id="option_<?php echo $i; ?>_feedback_chosen"><?php if (isset($this->data["option_{$i}_feedback_chosen"])) echo htmlspecialchars($this->data["option_{$i}_feedback_chosen"]); ?></textarea></td>
							<td><textarea style="width: 100%;" class="qtitinymce feedbackunchosen" rows="2" cols="48" name="option_<?php echo $i; ?>_feedback_unchosen" id="option_<?php echo $i; ?>_feedback_unchosen"><?php if (isset($this->data["option_{$i}_feedback_unchosen"])) echo htmlspecialchars($this->data["option_{$i}_feedback_unchosen"]); ?></textarea></td>
						</tr>
					<?php } ?>
				</table>
			</div>
		</dd>

		<dt class="choicerestrictions"<?php if (!$multipleresponse) { ?> style="display: none;"<?php } ?>>Choice restrictions</dt>
		<dd class="choicerestrictions"<?php if (!$multipleresponse) { ?> style="display: none;"<?php } ?>>
			<dl>
				<dt>Maximum choices</dt>
				<dd>
					<input type="text" name="maxchoices" id="maxchoices" value="<?php echo isset($this->data["maxchoices"]) ? htmlspecialchars($this->data["maxchoices"]) : "0"; ?>" size="4">
					<span class="hint">The maximum number of choices the candidate is allowed to select. 0 means no restriction.</span>
				</dd>
				<dt>Minimum choices</dt>
				<dd>
					<input type="text" name="minchoices" id="minchoices" value="<?php echo isset($this->data["minchoices"]) ? htmlspecialchars($this->data["minchoices"]) : "0"; ?>" size="4">
					<span class="hint">The minimum number of choices the candidate is required to select to form a valid response. 0 means the candidate is not required to select any choices.</span>
				</dd>
			</dl>
		</dd>
		<?php
		return ob_get_clean();
	}

	protected function buildQTI($data = null) {
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
		$ai->addAttribute("identifier", "mcr_" . md5(uniqid()));
		if (isset($this->data["title"]))
			$ai->addAttribute("title", $this->data["title"]);

		// response declaration
		$rd = $ai->addChild("responseDeclaration");
		$rd->addAttribute("identifier", "RESPONSE");
		$rd->addAttribute("cardinality", $this->itemType() == "multipleChoice" ? "single" : "multiple");
		$rd->addAttribute("baseType", "identifier");

		// correct response
		if ($this->itemType() == "multipleResponse") {
			// build array of correct responses
			$correct = array();
			for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++)
				if (isset($this->data["option_{$i}_correct"]))
					$correct[] = $i;

			// add correctResponse node only if any options are correct
			if (!empty($correct)) {
				$rd->addChild("correctResponse");
				foreach ($correct as $o)
					$rd->correctResponse->addChild("value", "option_$o");
			}
		} else {
			$rd->addChild("correctResponse");
			$rd->correctResponse->addChild("value", $this->data["correct"]);
		}

		// feedback outcome declarations
		if (isset($this->data["feedback"])) for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) {
			$od = $ai->addChild("outcomeDeclaration");
			$od->addAttribute("identifier", "feedback_option_" . $i);
			$od->addAttribute("cardinality", "single");
			$od->addAttribute("baseType", "identifier");
		}

		// score outcome declaration
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

		// choices
		$ci = $ib->addChild("choiceInteraction");
		$ci->addAttribute("responseIdentifier", "RESPONSE");
		$ci->addAttribute("shuffle", isset($this->data["shuffle"]) ? "true" : "false");
		if ($this->itemType() == "multipleChoice")
			$ci->addAttribute("maxChoices", "1");
		else {
			if (isset($this->data["maxchoices"]))
				$ci->addAttribute("maxChoices", $this->data["maxchoices"]);
			if (isset($this->data["minchoices"]))
				$ci->addAttribute("minChoices", $this->data["minchoices"]);
		}

		if (isset($this->data["prompt"]))
			$ci->addChild("prompt", $this->data["prompt"]);
		for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) {
			$sc = $ci->addChild("simpleChoice", $this->data["option_{$i}_optiontext"]);
			$sc->addAttribute("identifier", "option_$i");
			if (isset($this->data["shuffle"]))
				$sc->addAttribute("fixed", isset($this->data["option_{$i}_fixed"]) ? "true" : "false");
		}

		// response processing
		$rp = $ai->addChild("responseProcessing");

		// scoring logic
		$rc = $rp->addChild("responseCondition");
		$ri = $rc->addChild("responseIf");
		if ($this->itemType() == "multipleResponse" && empty($correct)) {
			// multiple response in which the correct response is to tick no 
			// boxes -- check number of responses is equal to zero
			$e = $ri->addChild("equal");
			$e->addAttribute("toleranceMode", "exact");
			$e->addChild("containerSize")->addChild("variable")->addAttribute("identifier", "RESPONSE");
			$e->addChild("baseValue", "0")->addAttribute("baseType", "integer");
		} else {
			// otherwise, we match responses to the correctResponse above
			$m = $ri->addChild("match");
			$m->addChild("variable")->addAttribute("identifier", "RESPONSE");
			$m->addChild("correct")->addAttribute("identifier", "RESPONSE");
		}
		$sov = $ri->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "1")->addAttribute("baseType", "integer");
		$re = $rc->addChild("responseElse");
		$sov = $re->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "0")->addAttribute("baseType", "integer");

		if (isset($this->data["feedback"])) {
			// feedback logic
			for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) {
				$rc = $rp->addChild("responseCondition");
				$ri = $rc->addChild("responseIf");
				if ($this->itemType() == "multipleResponse") {
					$c = $ri->addChild("member");
					$c->addChild("baseValue", "option_$i")->addAttribute("baseType", "identifier");	//if this
					$c->addChild("variable")->addAttribute("identifier", "RESPONSE");				//is a member of this
				} else {
					$m = $ri->addChild("match");
					$m->addChild("baseValue", "option_$i")->addAttribute("baseType", "identifier");	//if this
					$m->addChild("variable")->addAttribute("identifier", "RESPONSE");				//is equal to this
				}
				$sov = $ri->addChild("setOutcomeValue");											//then do this
				$sov->addAttribute("identifier", "feedback_option_$i");
				$sov->addChild("baseValue", "true")->addAttribute("baseType", "identifier");
				$re = $rc->addChild("responseElse");												//else do this
				$sov = $re->addChild("setOutcomeValue");
				$sov->addAttribute("identifier", "feedback_option_$i");
				$sov->addChild("null");
			}

			// the feedback itself
			for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) {
				if (isset($this->data["option_{$i}_feedback_chosen"]) && !empty($this->data["option_{$i}_feedback_chosen"])) {
					$this->data["option_{$i}_feedback_chosen"] = wrapindiv($this->data["option_{$i}_feedback_chosen"]);

					$mf = $ai->addChild("modalFeedback");
					$mf->addAttribute("outcomeIdentifier", "feedback_option_{$i}");
					$mf->addAttribute("identifier", "true");
					$mf->addAttribute("showHide", "show");

					// parse it as XML
					$feedback = stringtoxml($this->data["option_{$i}_feedback_chosen"], "feedback");
					if (is_array($feedback)) {
						// errors
						$this->errors[] = "Feedback is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
						$this->errors = array_merge($this->errors, $feedback);
					} else
						simplexml_append($mf, $feedback);
				}
				if (isset($this->data["option_{$i}_feedback_unchosen"]) && !empty($this->data["option_{$i}_feedback_unchosen"])) {
					$this->data["option_{$i}_feedback_unchosen"] = wrapindiv($this->data["option_{$i}_feedback_unchosen"]);

					$mf = $ai->addChild("modalFeedback");
					$mf->addAttribute("outcomeIdentifier", "feedback_option_{$i}");
					$mf->addAttribute("identifier", "true");
					$mf->addAttribute("showHide", "hide");

					// parse it as XML
					$feedback = stringtoxml($this->data["option_{$i}_feedback_unchosen"], "feedback");
					if (is_array($feedback)) {
						// errors
						$this->errors[] = "Feedback is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
						$this->errors = array_merge($this->errors, $feedback);
					} else
						simplexml_append($mf, $feedback);
				}
			}
		}

		if (!empty($this->errors))
			return false;

		// validate the QTI
		validateQTI($ai, $this->errors, $this->warnings, $this->messages);

		if (!empty($this->errors))
			return false;

		return $ai;
	}

	public function fromXML(SimpleXMLElement $xml) {
		$data = array(
			"itemtype"	=>	$this->itemType(),
			"title"		=>	(string) $xml["title"],
			"stimulus"	=>	qti_get_stimulus($xml->itemBody),
		);

		// there is one choiceInteraction
		if (count($xml->itemBody->choiceInteraction) != 1)
			return 0;

		// there is one responseDeclaration
		if (count($xml->responseDeclaration) != 1)
			return 0;

		// check cardinality is as expected
		if ($this->itemType() == "multipleResponse" && (string) $xml->responseDeclaration["cardinality"] != "multiple")
			return 0;
		if ($this->itemType() == "multipleChoice" && (string) $xml->responseDeclaration["cardinality"] != "single")
			return 0;

		// multiple choice must have maxchoices 1 and one correct response value
		if ($this->itemType() == "multipleChoice") {
			if (!isset($xml->itemBody->choiceInteraction["maxChoices"]) || (string) $xml->itemBody->choiceInteraction["maxChoices"] != "1")
				return 0;
			if (!isset($xml->responseDeclaration->correctResponse))
				return 0;
			if (count($xml->responseDeclaration->correctResponse->value) != 1)
				return 0;
		}

		// get shuffle value
		$shuffle = isset($xml->itemBody->choiceInteraction["shuffle"]) && (string) $xml->itemBody->choiceInteraction["shuffle"] == "true";
		if ($shuffle)
			$data["shuffle"] = "on";

		// there is at least one option
		if (count($xml->itemBody->choiceInteraction->simpleChoice) == 0)
			return 0;

		// collect options and their identifiers
		$o = 0;
		$options = array();
		foreach ($xml->itemBody->choiceInteraction->simpleChoice as $sc) {
			$options[] = (string) $sc["identifier"];
			$data["option_{$o}_optiontext"] = (string) $sc;

			if ($shuffle && isset($sc["fixed"]) && (string) $sc["fixed"] == "true")
				$data["option_{$o}_fixed"] = "on";

			$o++;
		}

		// check correct response makes sense; collect correct responses
		$correct = array();
		if (count($xml->responseDeclaration->correctResponse) > 0) {
			foreach ($xml->responseDeclaration->correctResponse->value as $value) {
				$pos = array_search((string) $value, $options);
				if ($pos === false)
					return 0;
				$correct[] = $pos;
			}
		}
		if ($this->itemType() == "multipleChoice")
			$data["correct"] = "option_" . $correct[0];
		else foreach ($correct as $o)
			$data["option_{$o}_correct"] = "on";

		// get max and min choices
		if ($this->itemType() == "multipleResponse") {
			$data["maxchoices"] = isset($xml->itemBody->choiceInteraction["maxChoices"]) ? (string) $xml->itemBody->choiceInteraction["maxChoices"] : "0";
			$data["minchoices"] = isset($xml->itemBody->choiceInteraction["minChoices"]) ? (string) $xml->itemBody->choiceInteraction["minChoices"] : "0";
		}

		// get prompt
		$data["prompt"] = (string) $xml->itemBody->choiceInteraction->prompt;

		// not checking this properly at all but if modalFeedback elements exist 
		// which look about right, use them for feedback
		foreach ($xml->modalFeedback as $mf) {
			$oi = explode("_", (string) $mf["outcomeIdentifier"]);
			if (count($oi) == 3 && $oi[2] < count($options) && (string) $mf["identifier"] == "true") {
				$data["feedback"] = true;
				if ((string) $mf["showHide"] == "show")
					$data["option_{$oi[2]}_feedback_chosen"] = xml_remove_wrapper_element($mf->asXML());
				else
					$data["option_{$oi[2]}_feedback_unchosen"] = xml_remove_wrapper_element($mf->asXML());
			}
		}

		// happy with that -- set data property
		$this->data = $data;

		// multiple response with one correct answer is less than ideal
		if ($this->itemType() == "multipleresponse" && count($options) == 1)
			return 192;

		return 255;
	}
}

?>
