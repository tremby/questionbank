<?php

abstract class QTIMultipleChoiceResponse extends QTIAssessmentItem {
	public function __construct() {
		parent::__construct();
	}

	public function showForm($data = null) {
		parent::showForm($data);

		$multipleresponse = $this->itemType() == "multipleResponse";

		include "htmlheader.php";
		?>

		<script type="text/javascript">
			addoption = function() {
				// clone the last option on the list and increment its id
				var newoption = $("#options tr.option:last").clone();
				var oldid = parseInt($("input.optiontext", newoption).attr("id").split("_")[1]);
				var newid = oldid + 1;

				// give it the new id number and wipe its text
				newoption.attr("id", "option_" + newid);
				$("input.optiontext", newoption).attr("id", "option_" + newid + "_optiontext").attr("name", "option_" + newid + "_optiontext").val("").css("background-color", "");
				$("input.correct", newoption).attr("id", "option_" + newid + "_correct").removeAttr("checked");
				if ($("input.correct", newoption).attr("type") == "checkbox")
					$("input.correct", newoption).attr("name", "option_" + newid + "_correct");
				$("input.fixed", newoption).attr("id", "option_" + newid + "_fixed").attr("name", "option_" + newid + "_fixed").removeAttr("checked");

				// reinstate the remove action
				$("input.removeoption", newoption).click(removeoption);

				// add it to the list
				$("#options").append(newoption);
			};

			removeoption = function() {
				if ($("#options tr.option").size() < 2) {
					alert("Can't remove the last option");
					return;
				}

				$(this).parents("tr:first").remove();

				// renumber the remaining options
				var options = $("#options tr.option");
				var i = 0;
				options.each(function() {
					$(this).attr("id", "option_" + i);
					$("input.optiontext", this).attr("id", "option_" + i + "_optiontext").attr("name", "option_" + i + "_optiontext");
					$("input.correct", this).attr("id", "option_" + i + "_correct");
					if ($("input.correct", this).attr("type") == "checkbox") {
						$("input.correct", this).attr("name", "option_" + i + "_correct");
					} else {
						$("input.correct", this).attr("name", "correct").attr("value", "option_" + i);
					}
					$("input.fixed", this).attr("id", "option_" + i + "_fixed").attr("name", "option_" + i + "_fixed");
					i++;
				});
			};

			toggleshuffle = function() {
				if ($("#shuffle").is(":checked"))
					$("#options th.fixed, #options td.fixed").show();
				else
					$("#options th.fixed, #options td.fixed").hide();
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
				// background colours
				var errorcolour = "#ffbaba";
				var warningcolour = "#ffdca4";

				// clear any previously set background colours
				$("input, textarea").css("background-color", "");

				// title must be set
				if ($("#title").val().length == 0) {
					$("#title").css("background-color", errorcolour);
					alert("A title must be set for this item");
					return false;
				}

				// choice restriction options must make sense
				if ($("input.itemtype:checked").attr("id") == "itemtype_mr") {
					// maximum choices
					if ($("#maxchoices").val().length == 0 || isNaN($("#maxchoices").val())) {
						$("#maxchoices").css("background-color", errorcolour);
						alert("Value for maximum choices is not a number");
						return false;
					}
					if ($("#maxchoices").val() < 0 || $("#maxchoices").val().indexOf(".") != -1) {
						$("#maxchoices").css("background-color", errorcolour);
						alert("Value for maximum choices must be zero (no restriction) or a positive integer");
						return false;
					}
					if ($("#maxchoices").val() > $("#options tr.option").size()) {
						$("#maxchoices").css("background-color", errorcolour);
						alert("Value for maximum choices cannot be greater than the number of possible choices");
						return false;
					}

					// minimum choices
					if ($("#minchoices").val().length == 0 || isNaN($("#minchoices").val())) {
						$("#minchoices").css("background-color", errorcolour);
						alert("Value for minimum choices is not a number");
						return false;
					}
					if ($("#minchoices").val() < 0 || $("#minchoices").val().indexOf(".") != -1) {
						$("#minchoices").css("background-color", errorcolour);
						alert("Value for minimum choices must be zero (not require to select any choices) or a positive integer");
						return false;
					}
					if ($("#minchoices").val() > $("#options tr.option").size()) {
						$("#minchoices").css("background-color", errorcolour);
						alert("Value for minimum choices cannot be greater than the number of possible choices");
						return false;
					}

					// maximum choices >= minimum choices
					if ($("#maxchoices").val() != 0 && $("#minchoices").val() > $("#maxchoices").val()) {
						$("#maxchoices, #minchoices").css("background-color", errorcolour);
						alert("Value for minimum choices cannot be greater than the value for maximum choices");
						return false;
					}
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

				// confirm the user wanted an empty question prompt
				if ($("#prompt").val().length == 0) {
					$("#prompt").css("background-color", warningcolour);
					if (!confirm("Question prompt is empty -- click OK to continue regardless or cancel to edit it"))
						return false;
					else
						$("#prompt").css("background-color", "");
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

				// confirm the user wanted only one option
				if ($("input.optiontext").size() == 1 && !confirm("There is only one option -- click OK to continue regardless or cancel to add more"))
					return false;

				// confirm it's what the user wanted if shuffle is on but all options 
				// are marked as fixed
				if ($("#shuffle").is(":checked") && $("input.fixed").size() == $("input.fixed:checked").size() && !confirm("Shuffle is selected but all options are marked as fixed -- click OK to continue regardless or cancel to change this"))
					return false;

				return true;
			}

			$(document).ready(function() {
				$("#addoption").click(addoption);
				$(".removeoption").click(removeoption);
				$("#shuffle").click(toggleshuffle);
				$("input.itemtype").click(switchitemtype);
				$("#submit").click(submitcheck);
			});
		</script>

		<h2>Make a new multiple choice or multiple response item</h2>

		<?php $this->showmessages(); ?>

		<form id="newitem" action="?page=newAssessmentItem" method="post">
			<dl>
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

				<dt><label for="title">Title</label></dt>
				<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

				<dt><label for="stimulus">Stimulus</label></dt>
				<dd><textarea rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

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
							// starting from scratch -- initialize first option
							$this->data["option_0_optiontext"] = "";
							if ($multipleresponse)
								$this->data["option_0_correct"] = true;
							else
								$this->data["correct"] = "option_0";
						} ?>
						<?php for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) { ?>
							<tr class="option" id="option_<?php echo $i; ?>">
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

				<dt class="choicerestrictions"<?php if (!$multipleresponse) { ?> style="display: none;"<?php } ?>>Choice restrictions</dt>
				<dd class="choicerestrictions"<?php if (!$multipleresponse) { ?> style="display: none;"<?php } ?>>
					<dl>
						<dt>Maximum choices</dt>
						<dd>
							<input type="text" name="maxchoices" id="maxchoices" value="<?php echo isset($this->data["maxchoices"]) ? htmlspecialchars($this->data["maxchoices"]) : "0"; ?>" size="4">
							The maximum number of choices the candidate is allowed to select. 0 means no restriction.
						</dd>
						<dt>Minimum choices</dt>
						<dd>
							<input type="text" name="minchoices" id="minchoices" value="<?php echo isset($this->data["minchoices"]) ? htmlspecialchars($this->data["minchoices"]) : "0"; ?>" size="4">
							The minimum number of choices the candidate is required to select to form a valid response. 0 means the candidate is not required to select any choices.
						</dd>
					</dl>
				</dd>
			</dl>
			<div>
				<input id="submit" type="submit" name="newitem" value="Submit">
			</div>
		</form>

		<?php
		include "htmlfooter.php";
	}

	public function getQTI($data = null) {
		parent::getQTI($data);

		// container element and other metadata
		$ai = new SimpleXMLElement('
			<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"/>
		');
		$ai->addAttribute("adaptive", "false");
		$ai->addAttribute("timeDependent", "false");
		$ai->addAttribute("identifier", "mcr_" . md5(uniqid()));
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
				$rd->correctResponse->addChild("value", "option_$i");
			}
		} else {
			$rd->addChild("correctResponse");
			$rd->correctResponse->addChild("value", $this->data["correct"]);
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
		$ci->addChild("prompt", $this->data["prompt"]);
		for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) {
			$sc = $ci->addChild("simpleChoice", $this->data["option_{$i}_optiontext"]);
			$sc->addAttribute("identifier", "option_$i");
			if (isset($this->data["shuffle"]))
				$sc->addAttribute("fixed", isset($this->data["option_{$i}_fixed"]) ? "true" : "false");
		}

		// response processing
		$rc = $ai->addChild("responseProcessing")->addChild("responseCondition");

		// if
		$ri = $rc->addChild("responseIf");

		// criteria for a correct answer
		if ($this->itemType() == "multipleResponse" && empty($correct)) {
			// multiple response in which the correct response is to tick no boxes 
			// -- check number of responses is equal to zero
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

		// set score = 1
		$sov = $ri->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "1")->addAttribute("baseType", "integer");

		// else
		$re = $rc->addChild("responseElse");

		// set score = 0
		$sov = $re->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "0")->addAttribute("baseType", "integer");

		if (!empty($this->errors))
			return false;

		// validate the QTI
		self::validate($ai, $this->errors, $this->warnings, $this->messages);

		if (!empty($this->errors))
			return false;

		$this->qti = $ai;
		return $this->qti;
	}
}

?>
