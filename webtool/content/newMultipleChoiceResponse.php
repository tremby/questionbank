<?php

$multipleresponse = isset($_REQUEST["questiontype"]) && $_REQUEST["questiontype"] == "multipleresponse";

if (isset($_POST["submit"])) {
	exit;
}

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
		$("input.correct", newoption).attr("id", "option_" + newid + "_correct").attr("name", "option_" + newid + "_correct").removeAttr("checked");
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
		}
	};

	submitcheck = function() {
		// TODO: proper checks
		if ($("#options input.correct:checked").size() < 1) {
			alert("You need to mark at least one response as correct");
			return false;
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

<form id="newquestion" action="<?php echo $_SERVER["PHP_SELF"]; ?> method="post">
	<dl>
		<dt>Question type</dt>
		<dd>
			<ul><label>
				<input type="radio" name="questiontype" class="questiontype" id="questiontype_mc" value="multiplechoice"<?php if (!$multipleresponse) echo ' checked="checked"'; ?>>
				Multiple choice (choose one answer)
			</label></ul>
			<ul><label>
				<input type="radio" name="questiontype" class="questiontype" id="questiontype_mr" value="multipleresponse"<?php if ($multipleresponse) echo ' checked="checked"'; ?>>
				Multiple response (choose all appropriate answers)
			</label></ul>
		</dd>

		<dt><label for="stimulus">Stimulus</label></dt>
		<dd><textarea id="stimulus"></textarea></dd>

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
							<input type="checkbox" id="option_0_correct" name="option_0_correct" class="correct" checked="checked"></td>
						<?php } else { ?>
							<input type="radio" id="option_0_correct" name="correct" value="option_0" class="correct" checked="checked"></td>
						<?php } ?>
					<td class="fixed" style="display: none;"><input type="checkbox" id="option_0_fixed" name="option_0_fixed" class="fixed"></td>
					<td><input type="button" class="removeoption" value="Remove"></td>
				</tr>
			</table>
			<input type="button" id="addoption" value="Add option">
		</dd>
	</dl>
	<div>
		<input id="submit" type="submit" value="Submit">
	</div>
</form>

<?php include "htmlfooter.php"; ?>
