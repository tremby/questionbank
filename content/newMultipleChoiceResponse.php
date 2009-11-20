<?php

if (isset($_POST["submit"])) {
	// TODO: error checking

	header("Content-Type: text/plain");

	$ai = new SimpleXMLElement('
		<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
		adaptive="false" timeDependent="false"/>
	');
	$ai->addAttribute("identifier", "mcr_" . md5(uniqid()));
	$ai->addAttribute("title", $_POST["title"]);

	$rd = $ai->addChild("responseDeclaration");
	$rd->addAttribute("identifier", "RESPONSE");
	$rd->addAttribute("cardinality", $_POST["questiontype"] == "multiplechoice" ? "single" : "multiple");
	$rd->addAttribute("baseType", "identifier");

	$rd->addChild("correctResponse");
	if ($_POST["questiontype"] == "multiplechoice")
		$rd->correctResponse->addChild("value", $_POST["correct"]);
	else
		for ($i = 0; array_key_exists("option_{$i}_optiontext", $_POST); $i++)
			if (isset($_POST["option_{$i}_correct"]))
				$rd->correctResponse->addChild("value", "option_$i");

	$od = $ai->addChild("outcomeDeclaration");
	$od->addAttribute("identifier", "SCORE");
	$od->addAttribute("cardinality", "single");
	$od->addAttribute("baseType", "integer");
	$od->addChild("defaultValue");
	$od->defaultValue->addChild("value", "0");

	$ib = $ai->addChild("itemBody");
	if (isset($_POST["stimulus"]) && !empty($_POST["stimulus"])) {
		libxml_use_internal_errors(true);
		$stimulus = simplexml_load_string("<div>" . $_POST["stimulus"] . "</div>");
		if (!$stimulus) {
			echo "Stimulus is not valid XML\n";
			foreach (libxml_get_errors() as $error)
				echo "line " . $error->line . ", column " . $error->column . ": " . $error->message;
			libxml_clear_errors();
			exit;
		}
		libxml_use_internal_errors(false);
		simplexml_append($ib, $stimulus);
	}

	$ci = $ib->addChild("choiceInteraction");
	$ci->addAttribute("responseIdentifier", "RESPONSE");
	$ci->addAttribute("shuffle", isset($_POST["shuffle"]) ? "true" : "false");
	if ($_POST["questiontype"] == "multiplechoice")
		$ci->addAttribute("maxChoices", "1");
	else {
		if (isset($_POST["maxchoices"]))
			$ci->addAttribute("maxChoices", $_POST["maxchoices"]);
		if (isset($_POST["minchoices"]))
			$ci->addAttribute("minChoices", $_POST["minchoices"]);
	}
	$ci->addChild("prompt", $_POST["prompt"]);
	for ($i = 0; array_key_exists("option_{$i}_optiontext", $_POST); $i++) {
		$sc = $ci->addChild("simpleChoice", $_POST["option_{$i}_optiontext"]);
		$sc->addAttribute("identifier", "option_$i");
		if ($_POST["shuffle"])
			$sc->addAttribute("fixed", isset($_POST["option_{$i}_fixed"]) ? "true" : "false");
	}

	$rc = $ai->addChild("responseProcessing")->addChild("responseCondition");

	// if correct
	$ri = $rc->addChild("responseIf");
	$m = $ri->addChild("match");
	$m->addChild("variable")->addAttribute("identifier", "RESPONSE");
	$m->addChild("correct")->addAttribute("identifier", "RESPONSE");
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

	header("Content-Type: text/xml");
	echo $ai->asXML();
	exit;
}

$multipleresponse = isset($_REQUEST["questiontype"]) && $_REQUEST["questiontype"] == "multipleresponse";

?>
<?php include "htmlheader.php"; ?>

<script type="text/javascript">
	addoption = function() {
		// clone the last option on the list and increment its id
		var newoption = $("#options tr.option:last").clone();
		var oldid = parseInt($("input.optiontext", newoption).attr("id").split("_")[1]);
		var newid = oldid + 1;

		// give it the new id number and wipe its text
		newoption.attr("id", "option_" + newid);
		$("input.optiontext", newoption).attr("id", "option_" + newid + "_optiontext").attr("name", "option_" + newid + "_optiontext").val("");
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

		// ensure at least one is marked as correct
		if ($("#options input.correct:checked").size() < 1) {
			$("#option_0 input.correct").attr("checked", "checked");
		}
	};

	toggleshuffle = function() {
		if ($("#shuffle").is(":checked")) {
			$("#options th.fixed, #options td.fixed").show();
		} else {
			$("#options th.fixed, #options td.fixed").hide();
		}
	};

	switchquestiontype = function() {
		if ($("input.questiontype:checked").attr("id") == "questiontype_mc") {
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
		// TODO: more thorough checks -- empty values etc

		// at least one response must be marked as correct
		if ($("#options input.correct:checked").size() < 1) {
			alert("You need to mark at least one response as correct");
			return false;
		}

		// choice restriction options must make sense
		if ($("input.questiontype:checked").attr("id") == "questiontype_mr") {
			// maximum choices
			if ($("#maxchoices").val().length == 0 || isNaN($("#maxchoices").val())) {
				alert("Value for maximum choices is not a number");
				return false;
			}
			if ($("#maxchoices").val() < 0 || $("#maxchoices").val().indexOf(".") != -1) {
				alert("Value for maximum choices must be zero (no restriction) or a positive integer");
				return false;
			}
			if ($("#maxchoices").val() > $("#options tr.option").size()) {
				alert("Value for maximum choices cannot be greater than the number of possible choices");
				return false;
			}
			// minimum choices
			if ($("#minchoices").val().length == 0 || isNaN($("#minchoices").val())) {
				alert("Value for minimum choices is not a number");
				return false;
			}
			if ($("#minchoices").val() < 0 || $("#minchoices").val().indexOf(".") != -1) {
				alert("Value for minimum choices must be zero (not require to select any choices) or a positive integer");
				return false;
			}
			if ($("#minchoices").val() > $("#options tr.option").size()) {
				alert("Value for minimum choices cannot be greater than the number of possible choices");
				return false;
			}
			// maximum choices >= minimum choices
			if ($("#minchoices").val() > $("#maxchoices").val()) {
				alert("Value for minimum choices cannot be greater than the value for maximum choices");
				return false;
			}
		}

		return true;
	}

	$(document).ready(function() {
		$("#addoption").click(addoption);
		$(".removeoption").click(removeoption);
		$("#shuffle").click(toggleshuffle);
		$("input.questiontype").click(switchquestiontype);
		$("#submit").click(submitcheck);
	});
</script>

<form id="newquestion" action="?page=newMultipleChoiceResponse" method="post">
	<dl>
		<dt>Question type</dt>
		<dd>
			<ul><label>
				<input type="radio" name="questiontype" class="questiontype" id="questiontype_mc" value="multiplechoice"<?php if (!$multipleresponse) { ?> checked="checked"<?php } ?>>
				Multiple choice (choose one answer)
			</label></ul>
			<ul><label>
				<input type="radio" name="questiontype" class="questiontype" id="questiontype_mr" value="multipleresponse"<?php if ($multipleresponse) { ?> checked="checked"<?php } ?>>
				Multiple response (choose all appropriate answers)
			</label></ul>
		</dd>

		<dt><label for="title">Title</label></dt>
		<dd><input type="text" name="title" id="title"></dd>

		<dt><label for="stimulus">Stimulus</label></dt>
		<dd><textarea name="stimulus" id="stimulus"></textarea></dd>

		<dt><label for="prompt">Question prompt</label></dt>
		<dd><textarea name="prompt" id="prompt"></textarea></dd>

		<dt>Options</dt>
		<dd>
			<div>
				<input type="checkbox" id="shuffle" name="shuffle">
				<label for="shuffle">Shuffle the options</label>
			</div>
			<table id="options">
				<tr>
					<th>Option text</th>
					<th>Correct</th>
					<th class="fixed" style="display: none;">Fixed</th>
					<th>Actions</th>
				</tr>
				<tr class="option" id="option_0">
					<td><input type="text" id="option_0_optiontext" name="option_0_optiontext" class="optiontext" value=""></td>
					<td>
						<?php if ($multipleresponse) { ?>
							<input type="checkbox" id="option_0_correct" name="option_0_correct" class="correct" checked="checked">
						<?php } else { ?>
							<input type="radio" id="option_0_correct" name="correct" value="option_0" class="correct" checked="checked">
						<?php } ?>
					</td>
					<td class="fixed" style="display: none;">
						<input type="checkbox" id="option_0_fixed" name="option_0_fixed" class="fixed">
					</td>
					<td><input type="button" class="removeoption" value="Remove"></td>
				</tr>
			</table>
			<input type="button" id="addoption" value="Add option">
		</dd>

		<dt class="choicerestrictions"<?php if (!$multipleresponse) { ?> style="display: none;"<?php } ?>>Choice restrictions</dt>
		<dd class="choicerestrictions"<?php if (!$multipleresponse) { ?> style="display: none;"<?php } ?>>
			<dl>
				<dt>Maximum choices</dt>
				<dd>
					<input type="text" name="maxchoices" id="maxchoices" value="0" size="4">
					The maximum number of choices the candidate is allowed to select. 0 means no restriction.
				</dd>
				<dt>Minimum choices</dt>
				<dd>
					<input type="text" name="minchoices" id="minchoices" value="0" size="4">
					The minimum number of choices the candidate is required to select to form a valid response. 0 means the candidate is not required to select any choices.
				</dd>
			</dl>
		</dd>
	</dl>
	<div>
		<input id="submit" type="submit" name="submit" value="Submit">
	</div>
</form>

<?php include "htmlfooter.php"; ?>
