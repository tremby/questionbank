<?php

class QTITextEntry extends QTIAssessmentItem {
	public function __construct() {
		parent::__construct();

		$this->itemtype = "textEntry";
		$this->itemtypeprint = "text entry";
		$this->itemtypedescription = "A stimulus or question prompt followed by a body of text with gaps. The candidate enters the appropriate words to complete the text.";
		$this->interactionType = "textEntryInteraction";
	}

	public function showForm($data = null) {
		if (!is_null($data))
			$this->data = $data;

		include "htmlheader.php";
		?>

		<script type="text/javascript">
			getgapstrings = function() {
				var value = $("#textbody").val();
				var pos = 0;
				var endpos;
				var gaps = [];

				while (true) {
					pos = value.indexOf("[", pos);
					if (pos == -1)
						break;
					endpos = value.indexOf("]", pos);
					if (endpos == -1)
						break;

					gaps[gaps.length] = value.substring(pos + 1, endpos);

					pos++;
				}
				return gaps;
			};

			updatetextgap = function() {
				var gapid = parseInt($(this).parents("div.gap:first").attr("id").split("_")[1]);
				var gapstrings = getgapstrings();
				if (gapid > gapstrings.length - 1) {
					console.error("trying to update gapstring which doesn't exist");
					return;
				}

				var value = $("#textbody").val();
				var pos = -1;
				var endpos;
				var gap = -1;
				while (gap < gapid) {
					pos++;
					pos = value.indexOf("[", pos);
					if (pos == -1) {
						console.error("didn't find next gap");
						return;
					}
					endpos = value.indexOf("]", pos);
					if (endpos == -1) {
						console.error("didn't find end of gap");
						return;
					}
					gap++;
				}

				$("#textbody").val(value.substring(0, pos + 1) + $("#gap_" + gapid + " input.responsetext:first").val() + value.substring(endpos));
			};

			getfirstresponsestrings = function() {
				var strings = [];
				$("table.responses:visible").each(function() {
					strings[strings.length] = $("input.responsetext:first", this).val();
				});
				return strings;
			};

			updategapstable = function() {
				var currentgap = 0;

				while (true) {
					var gapstrings = getgapstrings();
					var firstresponsestrings = getfirstresponsestrings();

					// finished if current gap doesn't exist
					if (currentgap >= gapstrings.length)
						break;

					// match gaps in text to table gaps, in order
					var matches = [];
					var prev = -1;
					for (var textgap = 0; textgap < gapstrings.length; textgap++) {
						for (var gaptable = prev + 1; gaptable < firstresponsestrings.length; gaptable++) {
							if (gapstrings[textgap] == firstresponsestrings[gaptable]) {
								matches[matches.length] = [textgap, gaptable];
								prev = textgap;
								break;
							}
						}
					}

					// consider the first match from the current gap
					var match = undefined;
					for (var i = 0; i < matches.length; i++)
						if (matches[i][0] >= currentgap)
							match = matches[i];

					// if no more matches, correct remaining gaps
					if (match == undefined)
						break;

					// go through the textgap/gaptable pairs from current gap to 
					// current match
					for (var gap = currentgap; gap < match[0] && gap < match[1]; gap++) {
						// update the first response in the table to the 
						// contents of the text gap
						$("#gap_" + gap + "_response_0").val(gapstrings[gap]);
					}

					if (match[0] == match[1]) {
						// nothing needs to be added or deleted
						currentgap = match[0] + 1;
						continue;
					}

					if (gap == match[1]) {
						// there are extra gaps in the text -- add tables in 
						// reverse order at this position
						for (var i = match[0]; i > match[1]; i--)
							addgap(match[1], gapstrings[i - 1]);
						currentgap = match[0] + 1;
						continue;
					}

					if (gap == match[0]) {
						// there are extra gap tables -- delete the extras
						for (var i = match[0]; i < match[1]; i++)
							$("#gap_" + i).remove();
						renumber();
						currentgap = match[0] + 1;
						continue;
					}
				}

				// we're after the last match now -- all that's left is to 
				// correct/add/delete tables for any gaps after the last match
				// to simplify logic just treat all of them as after the last 
				// match

				var gapstrings = getgapstrings();
				var firstresponsestrings = getfirstresponsestrings();

				// correct number of tables
				for (var i = firstresponsestrings.length; i < gapstrings.length; i++)
					addgap(i, gapstrings[i]);
				for (var i = gapstrings.length - 1; i < firstresponsestrings.length - 1; i++)
					$("#gap_" + i).remove();

				// update first responses
				for (var i = 0; i < gapstrings.length; i++)
					$("#gap_" + i + "_response_0").val(gapstrings[i]);
			};

			addgap = function(newid, response) {
				// clone the template gap
				var newgap = $("#gap_-1").clone();

				// give it and its bits the new id number
				$("input.responsetext", newgap).val(response).change(updatetextgap);

				// reinstate the add action
				$("input.addresponse", newgap).click(addresponse);

				// make it visible
				newgap.show();

				// add it to the list in place
				$("#gap_" + (newid - 1)).after(newgap);

				// renumber everything
				renumber();

				return newid;
			};

			removeresponse = function() {
				// get our gap and its id
				var gap = $(this).parents("div.gap:first");
				var gapid = gap.attr("id").split("_")[1];

				// can't delete the last response
				if ($("table.responses tr.response", gap).size() < 2) {
					alert("Can't remove the only response");
					return;
				}

				$(this).parents("tr:first").remove();

				// renumber everything
				renumber();
			};

			addresponse = function() {
				// get our gap and its id
				var gap = $(this).parents("div.gap:first");
				var gapid = gap.attr("id").split("_")[1];

				// get the new response id
				var newid = parseInt($("table.responses tr.response:last input.responsetext", gap).attr("id").split("_")[3]) + 1;

				// clone the template response and update the ids
				var newresponse = $("#gap_-1 table.responses tr.response:first").clone();

				// reinstate the remove action and make it visible
				$("input.removeresponse", newresponse).click(removeresponse).show();

				// add the new row to the table
				$("table.responses", gap).append(newresponse);

				// renumber everything
				renumber();
			};

			renumber = function() {
				var gapid = -1; // include template gap
				$("#gaps div.gap").each(function() {
					$(this).attr("id", "gap_" + gapid);
					$("span.gapnumber", this).html(gapid + 1);
					var responseid = 0;
					$("table.responses tr.response", this).each(function() {
						$("input.responsetext", this).attr("id", "gap_" + gapid + "_response_" + responseid).attr("name", "gap_" + gapid + "_response_" + responseid);
						$("input.responsescore", this).attr("id", "gap_" + gapid + "_response_" + responseid + "_score").attr("name", "gap_" + gapid + "_response_" + responseid + "_score");
						responseid++;
					});
					gapid++;
				});
			};

			$(document).ready(function() {
				$("#textbody").change(updategapstable);
				// TODO: default handlers for existing gap table button/fields
			});
		</script>

		<h2>Edit text entry item</h2>

		<?php $this->showmessages(); ?>

		<form id="edititem" action="?page=editAssessmentItem" method="post">
			<input type="hidden" name="qtiid" value="<?php echo $this->getQTIID(); ?>">
			<dl>
				<dt><label for="title">Title</label></dt>
				<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

				<dt><label for="stimulus">Stimulus or question prompt</label></dt>
				<dd><textarea rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

				<dt>Text body</dt>
				<dd>
					<p class="hint">Mark positions of gaps with [] &ndash; you can put a possible response in the brackets if you like</p>
					<textarea rows="8" cols="64" name="textbody" id="textbody"><?php if (isset($this->data["textbody"])) echo htmlspecialchars($this->data["textbody"]); ?></textarea>
				</dd>

				<dt>Responses</dt>
				<dd>
					<p class="hint">Responses are always case-sensitive</p>
					<dl id="gaps">
						<div class="gap" id="gap_-1" style="display: none;">
							<dt>Gap <span class="gapnumber">0</span></dt>
							<dd>
								<table class="responses">
									<tr>
										<th>Response</th>
										<th>Score</th>
										<th>Actions</th>
									</tr>
									<tr class="response">
										<td><input class="responsetext" type="text" name="gap_-1_response_0" id="gap_-1_response_0" size="32"></td>
										<td><input class="responsescore" type="text" name="gap_-1_response_0_score" id="gap_-1_response_0_score" size="3" value="1"></td>
										<td><input style="display: none;" type="button" class="removeresponse" value="Remove"></td>
									</tr>
								</table>
								<input type="button" class="addresponse" value="Add response">
							</dd>
						</div>
						<?php if (isset($this->data["gap_0_response_0"])) { ?>
							<?php for ($i = 0; array_key_exists("gap_{$i}_prompt_0", $this->data); $i++) { ?>
								<div class="gap" id="gap_<?php echo $i; ?>">
									<dt>Gap <span class="gapnumber"><?php echo $i + 1; ?></span></dt>
									<dd>
										<table class="responses">
											<tr>
												<th>Response</th>
												<th>Score</th>
												<th>Actions</th>
											</tr>
											<?php for ($j = 0; array_key_exists("gap_{$i}_prompt_{$j}", $this->data); $j++) { ?>
												<tr class="response">
													<td><input type="text" name="gap_<?php echo $i; ?>_response_<?php echo $j; ?>" id="gap_<?php echo $i; ?>_response_<?php echo $j; ?>" size="32" value="<?php echo htmlspecialchars($this->data["gap_{$i}_response_{$j}"]); ?>"></td>
													<td><input type="text" name="gap_<?php echo $i; ?>_response_<?php echo $j; ?>_score" id="gap_<?php echo $i; ?>_response_<?php echo $j; ?>_score" size="3" value="<?php echo htmlspecialchars($this->data["gap_{$i}_response_{$j}_score"]); ?>"></td>
													<td><input type="button" class="removequestion" value="Remove"></td>
												</tr>
											<?php } ?>
										</table>
										<input type="button" class="addresponse" value="Add response">
									</dd>
								</div>
							<?php } ?>
						<?php } ?>
					</dl>
				</dd>
			</dl>
			<div><input id="submit" type="submit" name="edititem" value="Submit"></div>
		</form>

		<?php
		include "htmlfooter.php";
	}

	public function buildQTI($data = null) {
		if (!is_null($data))
			$this->data = $data;

		if (empty($this->data))
			return false;

		// TODO -- build QTI

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
		);

		// TODO: logic

		// happy with that -- set data property
		$this->data = $data;

		return 255;
	}
}

?>
