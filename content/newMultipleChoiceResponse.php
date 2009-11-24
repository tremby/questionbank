<?php

if (isset($_POST["submit"])) {
	// form data submitted -- check it

	$errors = array();
	$warnings = array();
	$messages = array();

	// Very little server side validation is necessary here since the Java 
	// validate application does everything important. Client side checking for 
	// likely mistakes (empty boxes etc) is sufficient.

	// build XML

	// container element and other metadata
	$ai = new SimpleXMLElement('
		<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"/>
	');
	$ai->addAttribute("adaptive", "false");
	$ai->addAttribute("timeDependent", "false");
	$ai->addAttribute("identifier", "mcr_" . md5(uniqid()));
	$ai->addAttribute("title", $_POST["title"]);

	// response declaration
	$rd = $ai->addChild("responseDeclaration");
	$rd->addAttribute("identifier", "RESPONSE");
	$rd->addAttribute("cardinality", $_POST["questiontype"] == "multiplechoice" ? "single" : "multiple");
	$rd->addAttribute("baseType", "identifier");

	// correct response
	$rd->addChild("correctResponse");
	if ($_POST["questiontype"] == "multiplechoice")
		$rd->correctResponse->addChild("value", $_POST["correct"]);
	else
		for ($i = 0; array_key_exists("option_{$i}_optiontext", $_POST); $i++)
			if (isset($_POST["option_{$i}_correct"]))
				$rd->correctResponse->addChild("value", "option_$i");

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
	if (isset($_POST["stimulus"]) && !empty($_POST["stimulus"])) {
		// if stimulus doesn't start with a tag, wrap it in a div
		$_POST["stimulus"] = trim($_POST["stimulus"]);
		if ($_POST["stimulus"][0] != "<")
			$_POST["stimulus"] = "<div>" . $_POST["stimulus"] . "</div>";

		// parse it as XML
		// The stimulus must be valid XML at this point. Even if it is, and even 
		// if it's also valid XHTML, it may still not be valid QTI since QTI 
		// only allows a subset of XHTML. So we collect errors here.
		libxml_use_internal_errors(true);
		$stimulus = simplexml_load_string($_POST["stimulus"]);
		if ($stimulus === false) {
			$errors[] = "Stimulus is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
			foreach (libxml_get_errors() as $error)
				$errors[] = "Stimulus line " . $error->line . ", column " . $error->column . ": " . $error->message;
			libxml_clear_errors();
		} else {
			simplexml_append($ib, $stimulus);
		}
		libxml_use_internal_errors(false);
	}

	// choices
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

	// response processing
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

	if (empty($errors)) {
		// validate the QTI
		$pipes = null;
		$validate = proc_open("./run.sh", array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes, SITEROOT_LOCAL . "validate");
		if (!is_resource($validate))
			$errors[] = "Failed to start validator";
		else {
			// give QTI on stdin and close the pipe
			fwrite($pipes[0], $ai->asXML());
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
		}
	}

	if (empty($errors)) {
		// new QTI is fine -- display it and any warnings and messages

		$thingstosay = array();
		if (!empty($warnings)) $thingstosay[] = "warnings";
		if (!empty($messages)) $thingstosay[] = "messages";

		include "htmlheader.php";
		?>

		<h2>New QTI item complete</h2>
		<p>The new item has been successfully validated<?php if (!empty($thingstosay)) { ?> with the following <?php echo implode(" and ", $thingstosay); ?>:<?php } ?></p>

		<?php if (!empty($warnings)) { ?>
			<div class="warning">
				<h3>Warning</h3>
				<ul>
					<?php foreach ($warnings as $warning) { ?>
						<li><?php echo htmlspecialchars($warning); ?></li>
					<?php } ?>
				</ul>
			</div>
		<?php }
		if (!empty($messages)) { ?>
			<div class="message">
				<h3>Message</h3>
				<ul>
					<?php foreach ($messages as $message) { ?>
						<li><?php echo htmlspecialchars($message); ?></li>
					<?php } ?>
				</ul>
			</div>
		<?php } ?>

		<h3>XML</h3>
		<iframe width="80%" height="400" src="data:text/xml;base64,<?php echo base64_encode($ai->asXML()); ?>"></iframe>

		<h3>As plain text</h3>
		<div style="width: 80%; height: 400px; overflow: auto;">
			<pre><?php
				$dom = dom_import_simplexml($ai)->ownerDocument;
				$dom->formatOutput = true;
				echo htmlspecialchars($dom->saveXML());
			?></pre>
		</div>

		<?php
		exit;
	}
}

// show authoring form

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
			if ($("#maxchoices").val() != 0 && $("#minchoices").val() > $("#maxchoices").val()) {
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

<h2>Make a new multiple choice or multiple response item</h2>

<?php
if (isset($errors) && !empty($errors)) { ?>
	<div class="error">
		<h3>Error</h3>
		<ul>
			<?php foreach ($errors as $error) { ?>
				<li><?php echo htmlspecialchars($error); ?></li>
			<?php } ?>
		</ul>
	</div>
<?php }
if (isset($warnings) && !empty($warnings)) { ?>
	<div class="warning">
		<h3>Warning</h3>
		<ul>
			<?php foreach ($warnings as $warning) { ?>
				<li><?php echo htmlspecialchars($warning); ?></li>
			<?php } ?>
		</ul>
	</div>
<?php }
if (isset($messages) && !empty($messages)) { ?>
	<div class="message">
		<h3>Message</h3>
		<ul>
			<?php foreach ($messages as $message) { ?>
				<li><?php echo htmlspecialchars($message); ?></li>
			<?php } ?>
		</ul>
	</div>
<?php }
?>

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
		<dd><input size="64" type="text" name="title" id="title"<?php if (isset($_POST["title"])) { ?> value="<?php echo htmlspecialchars($_POST["title"]); ?>"<?php } ?>></dd>

		<dt><label for="stimulus">Stimulus</label></dt>
		<dd><textarea rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($_POST["stimulus"])) echo htmlspecialchars($_POST["stimulus"]); ?></textarea></dd>

		<dt><label for="prompt">Question prompt</label></dt>
		<dd><textarea rows="2" cols="64" name="prompt" id="prompt"><?php if (isset($_POST["prompt"])) echo htmlspecialchars($_POST["prompt"]); ?></textarea></dd>

		<dt>Options</dt>
		<dd>
			<div>
				<input type="checkbox" id="shuffle" name="shuffle"<?php if (isset($_POST["shuffle"])) { ?> checked="checked"<?php } ?>>
				<label for="shuffle">Shuffle the options</label>
			</div>
			<table id="options">
				<tr>
					<th>Option text</th>
					<th>Correct</th>
					<th class="fixed"<?php if (!isset($_POST["shuffle"])) { ?> style="display: none;"<?php } ?>>Fixed</th>
					<th>Actions</th>
				</tr>
				<?php if (!isset($_POST["option_0_optiontext"])) {
					// starting from scratch -- initialize first option
					$_POST["option_0_optiontext"] = "";
					if ($multipleresponse)
						$_POST["option_0_correct"] = true;
					else
						$_POST["correct"] = "option_0";
				} ?>
				<?php for ($i = 0; array_key_exists("option_{$i}_optiontext", $_POST); $i++) { ?>
					<tr class="option" id="option_<?php echo $i; ?>">
						<td><input size="48" type="text" id="option_<?php echo $i; ?>_optiontext" name="option_<?php echo $i; ?>_optiontext" class="optiontext" value="<?php echo htmlspecialchars($_POST["option_{$i}_optiontext"]); ?>"></td>
						<td>
							<?php if ($multipleresponse) { ?>
								<input type="checkbox" id="option_<?php echo $i; ?>_correct" name="option_<?php echo $i; ?>_correct" class="correct"<?php if (isset($_POST["option_{$i}_correct"])) { ?> checked="checked"<?php } ?>>
							<?php } else { ?>
								<input type="radio" id="option_<?php echo $i; ?>_correct" name="correct" value="option_<?php echo $i; ?>" class="correct"<?php if ($_POST["correct"] == "option_$i") { ?> checked="checked"<?php } ?>>
							<?php } ?>
						</td>
						<td class="fixed"<?php if (!isset($_POST["shuffle"])) { ?> style="display: none;"<?php } ?>>
							<input type="checkbox" id="option_<?php echo $i; ?>_fixed" name="option_<?php echo $i; ?>_fixed" class="fixed"<?php if (isset($_POST["option_{$i}_fixed"])) { ?> checked="checked"<?php } ?>>
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
					<input type="text" name="maxchoices" id="maxchoices" value="<?php echo isset($_POST["maxchoices"]) ? htmlspecialchars($_POST["maxchoices"]) : "0"; ?>" size="4">
					The maximum number of choices the candidate is allowed to select. 0 means no restriction.
				</dd>
				<dt>Minimum choices</dt>
				<dd>
					<input type="text" name="minchoices" id="minchoices" value="<?php echo isset($_POST["minchoices"]) ? htmlspecialchars($_POST["minchoices"]) : "0"; ?>" size="4">
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
