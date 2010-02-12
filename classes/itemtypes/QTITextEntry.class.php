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

class QTITextEntry extends QTIAssessmentItem {
	public function itemTypePrint() {
		return "text entry";
	}
	public function itemTypeDescription() {
		return "A stimulus or question prompt followed by a body of text with gaps. The candidate enters the appropriate words to complete the text.";
	}

	protected function headerJS() {
		ob_start();
		?>
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

				pos = endpos;
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
					// update the first response in the table to the contents of 
					// the text gap
					$("#gap_" + gap + "_response_0").val(gapstrings[gap]);
				}

				if (match[0] == match[1]) {
					// nothing needs to be added or deleted
					currentgap = match[0] + 1;
					continue;
				}

				if (gap == match[1]) {
					// there are extra gaps in the text -- add tables in reverse 
					// order at this position
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
			// correct/add/delete tables for any gaps after the last match to 
			// simplify logic just treat all of them as after the last match

			var gapstrings = getgapstrings();
			var firstresponsestrings = getfirstresponsestrings();

			// correct number of tables
			for (var i = firstresponsestrings.length; i < gapstrings.length; i++)
				addgap(i, gapstrings[i]);
			for (var i = gapstrings.length - 1; i < firstresponsestrings.length - 1; i++)
				$("#gap_" + (i + 1)).remove();

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
			$("div.gap").each(function() {
				$(this).attr("id", "gap_" + gapid);
				$("span.gapnumber", this).html(gapid + 1);
				var responseid = 0;
				$("table.responses tr.response", this).each(function() {
					$("input.responsetext", this).attr("id", "gap_" + gapid + "_response_" + responseid).attr("name", "gap_" + gapid + "_response_" + responseid);
					$("input.responsescore", this).attr("id", "gap_" + gapid + "_response_" + responseid + "_score").attr("name", "gap_" + gapid + "_response_" + responseid + "_score");
					$(this).removeClass("row" + ((responseid + 1) % 2)).addClass("row" + (responseid % 2));
					responseid++;
				});
				gapid++;
			});
		};

		edititemsubmitcheck_pre = function() {
			// ensure the gaps table is up to date
			updategapstable();
		};

		edititemsubmitcheck_itemspecificerrors = function() {
			// must have at least one gap
			if ($("div.gap:visible").size() == 0) {
				$.scrollTo($("#textbody").addClass("error"), scrollduration, scrolloptions);
				alert("You must have at least one gap for the candidate to fill in");
				return false;
			}

			// scores must make sense
			var ok = true;
			$("input.responsescore:visible").each(function() {
				if ($(this).val().length == 0 || isNaN($(this).val()) || parseFloat($(this).val()) < 0) {
					var gapid = parseInt($(this).attr("id").split("_")[1]);
					var responseid = parseInt($(this).attr("id").split("_")[3]);
					$.scrollTo($(this).addClass("error"), scrollduration, scrolloptions);
					alert("Score for gap " + (gapid + 1) + " response " + (responseid + 1) + " must be a positive number");
					ok = false;
					return false;
				}
			});
			if (!ok) return false;

			// can't have empty responses
			$("input.responsetext:visible").each(function(n) {
				if ($(this).val().length == 0) {
					var gapid = parseInt($(this).attr("id").split("_")[1]);
					var responseid = parseInt($(this).attr("id").split("_")[3]);
					$.scrollTo($(this).addClass("error"), scrollduration, scrolloptions);
					alert("Gap " + (gapid + 1) + " response " + (responseid + 1) + " is empty -- this is not allowed");
					ok = false;
					return false; //this is "break" in the Jquery each() pseudoloop
				}
			});
			if (!ok) return false;

			// can't have identical responses for a single gap
			for (var gap = 0; gap < $("div.gap:visible").size(); gap++) {
				for (var i = 0; i < $("#gap_" + gap + " input.responsetext").size(); i++) {
					for (var j = i + 1; j < $("#gap_" + gap + " input.responsetext").size(); j++) {
						if ($("#gap_" + gap + "_response_" + i).val() == $("#gap_" + gap + "_response_" + j).val()) {
							$.scrollTo($("#gap_" + gap + "_response_" + i + ", #gap_" + gap + "_response_" + j).addClass("error"), scrollduration, scrolloptions);
							alert("No two responses can be the same but gap " + (gap + 1) + " responses " + (i + 1) + " and " + (j + 1) + " are equal");
							return false;
						}
					}
				}
			};

			return true;
		};

		edititemsubmitcheck_itemspecificwarnings = function() {
			// confirm the user wanted zero scores
			var ok = true;
			$("input.responsescore:visible").each(function(n) {
				if (parseFloat($(this).val()) == 0.0) {
					var gapid = parseInt($(this).attr("id").split("_")[1]);
					var responseid = parseInt($(this).attr("id").split("_")[3]);
					$.scrollTo($(this).addClass("warning"), scrollduration, scrolloptions);
					ok = confirm("Score for gap " + (gapid + 1) + " response " + (responseid + 1) + " is zero but this is the default score for any response not listed -- click OK to continue regardless or cancel to edit it");
					if (ok)
						$(this).removeClass("error warning");
					else
						return false; //this is "break" in the Jquery each() pseudoloop
				}
			});
			if (!ok) return false;

			return true;
		};

		$(document).ready(function() {
			$("#textbody").change(updategapstable);
			$("input.addresponse:visible").click(addresponse);
			$("input.removeresponse:visible").click(removeresponse);
			$("input.responsetext:visible").change(updatetextgap);
		});
		<?php
		return ob_get_clean();
	}

	protected function formHTML() {
		ob_start();
		?>
		<dt>Text body</dt>
		<dd>
			<p class="hint">Mark positions of gaps with [] &ndash; you can put a possible response in the brackets if you like</p>
			<textarea rows="8" cols="64" name="textbody" id="textbody"><?php if (isset($this->data["textbody"])) echo htmlspecialchars($this->data["textbody"]); ?></textarea>
		</dd>

		<dt>Responses</dt>
		<dd>
			<p class="hint">Responses are always case-sensitive</p>
			<div class="gap" id="gap_-1" style="display: none;">
				<h4>Gap <span class="gapnumber">0</span></h4>
				<table class="responses">
					<tr>
						<th>Response</th>
						<th>Score</th>
						<th>Actions</th>
					</tr>
					<tr class="response row0">
						<td><input class="responsetext" type="text" name="gap_-1_response_0" id="gap_-1_response_0" size="32"></td>
						<td><input class="responsescore small" type="text" name="gap_-1_response_0_score" id="gap_-1_response_0_score" size="3" value="1"></td>
						<td><input style="display: none;" type="button" class="removeresponse" value="Remove"></td>
					</tr>
				</table>
				<input type="button" class="addresponse" value="Add response">
			</div>
			<?php for ($i = 0; array_key_exists("gap_{$i}_response_0", $this->data); $i++) { ?>
				<div class="gap" id="gap_<?php echo $i; ?>">
					<h4>Gap <span class="gapnumber"><?php echo $i + 1; ?></span></h4>
					<div>
						<table class="responses">
							<tr>
								<th>Response</th>
								<th>Score</th>
								<th>Actions</th>
							</tr>
							<?php for ($j = 0; array_key_exists("gap_{$i}_response_{$j}", $this->data); $j++) { $odd = $j % 2; ?>
								<tr class="response row<?php echo $odd; ?>">
									<td><input class="responsetext" type="text" name="gap_<?php echo $i; ?>_response_<?php echo $j; ?>" id="gap_<?php echo $i; ?>_response_<?php echo $j; ?>" size="32" value="<?php echo htmlspecialchars($this->data["gap_{$i}_response_{$j}"]); ?>"></td>
									<td><input class="responsescore" type="text" name="gap_<?php echo $i; ?>_response_<?php echo $j; ?>_score" id="gap_<?php echo $i; ?>_response_<?php echo $j; ?>_score" size="3" value="<?php echo htmlspecialchars($this->data["gap_{$i}_response_{$j}_score"]); ?>"></td>
									<td><input <?php if ($j == 0) { ?>style="display: none;" <?php } ?>type="button" class="removeresponse" value="Remove"></td>
								</tr>
							<?php } ?>
						</table>
						<input type="button" class="addresponse" value="Add response">
					</div>
				</div>
			<?php } ?>
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
		$ai = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
			<assessmentItem
				xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
			/>
		');
		$ai->addAttribute("adaptive", "false");
		$ai->addAttribute("timeDependent", "false");
		$ai->addAttribute("identifier", $this->getQTIID());
		if (isset($this->data["title"]))
			$ai->addAttribute("title", $this->data["title"]);

		// response declarations
		for ($g = 0; array_key_exists("gap_{$g}_response_0", $this->data); $g++) {
			$rd = $ai->addChild("responseDeclaration");
			$rd->addAttribute("identifier", "RESPONSE_gap_$g");
			$rd->addAttribute("cardinality", "single");
			$rd->addAttribute("baseType", "string");

			$m = $rd->addChild("mapping");
			$m->addAttribute("defaultValue", "0");
			for ($r = 0; array_key_exists("gap_{$g}_response_{$r}", $this->data); $r++) {
				$me = $m->addChild("mapEntry");
				$me->addAttribute("mapKey", $this->data["gap_{$g}_response_$r"]);
				$me->addAttribute("mappedValue", $this->data["gap_{$g}_response_${r}_score"]);
			}
		}

		// outcome declaration
		$od = $ai->addChild("outcomeDeclaration");
		$od->addAttribute("identifier", "SCORE");
		$od->addAttribute("cardinality", "single");
		$od->addAttribute("baseType", "float");
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

		// body text
		$bt = $ib->addChild("div");
		$bt->addAttribute("class", "textentrytextbody");
		$text = xmlspecialchars($this->data["textbody"]);
		$text = preg_replace('%\n\n+%', "</p><p>", $text);
		$text = preg_replace('%\n%', "<br/>", $text);
		$text = "<p>" . $text . "</p>";
		$g = 0;
		$start = 0;
		while (($start = strpos($text, "[", $start)) !== false) {
			$start = strpos($text, "[");
			$end = strpos($text, "]", $start);

			// base expected length on the longest answer plus 10%
			$el = 0;
			for ($r = 0; array_key_exists("gap_{$g}_response_{$r}", $this->data); $r++)
				$el = max($el, strlen($this->data["gap_{$g}_response_{$r}"]));
			$el = ceil($el * 1.1);

			$text = substr($text, 0, $start)
				. '<textEntryInteraction responseIdentifier="RESPONSE_gap_' . ($g++) . '" expectedLength="' . $el . '"/>'
				. substr($text, $end + 1);
		}
		// parse it as XML
		libxml_use_internal_errors(true);
		$textxml = simplexml_load_string($text);
		if ($textxml === false) {
			$this->errors[] = "Text body did not convert to valid XML";
			foreach (libxml_get_errors() as $error)
				$this->errors[] = "Text body line " . $error->line . ", column " . $error->column . ": " . $error->message;
			libxml_clear_errors();
		} else {
			simplexml_append($bt, $textxml);
		}
		libxml_use_internal_errors(false);

		// response processing
		$rp = $ai->addChild("responseProcessing");

		// set score = 0
		$sov = $rp->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "0.0")->addAttribute("baseType", "float");

		for ($g = 0; array_key_exists("gap_{$g}_response_0", $this->data); $g++) {
			$rc = $rp->addChild("responseCondition");

			// if
			$ri = $rc->addChild("responseIf");

			// not null
			$ri->addChild("not")->addChild("isNull")->addChild("variable")->addAttribute("identifier", "RESPONSE_gap_{$g}");

			// increment score
			$sov = $ri->addChild("setOutcomeValue");
			$sov->addAttribute("identifier", "SCORE");
			$s = $sov->addChild("sum");
			$s->addChild("variable")->addAttribute("identifier", "SCORE");
			$s->addChild("mapResponse")->addAttribute("identifier", "RESPONSE_gap_{$g}");
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
		);

		// get the text body and remove it from the tree
		$tb = null;
		foreach ($xml->itemBody->children() as $child) {
			if ($child->getName() == "div" && isset($child["class"]) && (string) $child["class"] == "textentrytextbody") {
				$tb = dom_import_simplexml($child);
				$tb->parentNode->removeChild($tb);
				break;
			}
		}
		if (is_null($tb))
			return 0;

		// get text body fragments
		$fragments = self::getTextEntryInteractionsAndText($tb);
		$data["textbody"] = "";
		$gaps = array();

		// fail if there was a problem with a textEntryInteraction (for example 
		// it doesn't have a responseDeclaration id associated)
		if ($fragments === false)
			return 0;

		// go through the textbody fragments, match them to responseDeclarations 
		// and collect responses
		foreach ($fragments as $fid => $fragment) {
			if (!preg_match('%^\[.*\]$%', $fragment)) {
				$data["textbody"] .= $fragment;
				continue;
			}

			// we have a textEntryInteraction id -- look for a matching 
			// responseDeclaration
			$rdi = substr($fragment, 1, -1);
			$rd = null;
			foreach ($xml->responseDeclaration as $d) {
				if ((string) $d["identifier"] == $rdi) {
					$rd = $d;
					break;
				}
			}
			if (is_null($rd))
				return 0;

			// get all the responses for this gap and their scores
			if (!isset($rd->mapping))
				return 0;

			// fail if the default score isn't 0
			if ((string) $rd->mapping["defaultValue"] !== "0")
				return 0;

			// fail if there are no responses
			if (!isset($rd->mapping->mapEntry))
				return 0;

			// collect responses and their scores and find the best response
			$gaps[$fid] = array();
			foreach ($rd->mapping->mapEntry as $me)
				$gaps[$fid][(string) $me["mapKey"]] = (string) $me["mappedValue"];

			// sort responses by descending score
			arsort($gaps[$fid]);

			// add the plaintext gap to the textbody string
			$data["textbody"] .= "[" . key($gaps[$fid]) . "]";
		}

		// turn HTML paragraphs and line breaks into plaintext newlines
		$data["textbody"] = preg_replace(array('%\s*</p>\s*<p>\s*%', '%\s*</?p>\s*%', '%\s*<br\s*/?>\s*%'), array("\n\n", "", "\n"), trim($data["textbody"]));

		// fail if there are no gaps
		if (count($gaps) == 0)
			return 0;

		// get stimulus
		$data["stimulus"] = qti_get_stimulus($xml->itemBody);

		// add responses and their scores to data
		$g = 0;
		foreach ($gaps as $responses) {
			$r = 0;
			foreach ($responses as $response => $score) {
				$data["gap_{$g}_response_{$r}"] = $response;
				$data["gap_{$g}_response_{$r}_score"] = $score;
				$r++;
			}
			$g++;
		}

		// happy with that -- set data property and identifier
		$this->data = $data;
		$this->setQTIID((string) $xml["identifier"]);

		return 255;
	}

	// return an array of text fragments to be concatenated. a fragment starting 
	// with [ and ending with ] holds the identifier of a textEntryInteraction.
	private static function getTextEntryInteractionsAndText(DOMNode $node) {
		$fragments = array();
		foreach ($node->childNodes as $child) {
			switch ($child->nodeType) {
				case XML_TEXT_NODE:
					$fragments[] = $child->wholeText;
					break;
				case XML_ELEMENT_NODE:
					if ($child->tagName == "textEntryInteraction") {
						$ri = null;
						foreach ($child->attributes as $a) {
							if ($a->name == "responseIdentifier") {
								$ri = $a->value;
								break;
							}
						}
						if (is_null($ri))
							return false;
						$fragments[] = "[" . $a->value . "]";
					} else {
						$fragments[] = "<" . $child->tagName . ">";
						$childfragments = self::getTextEntryInteractionsAndText($child);
						if ($childfragments === false)
							return false;
						$fragments = array_merge($fragments, $childfragments);
						$fragments[] = "</" . $child->tagName . ">";
					}
					break;
			}
		}
		return $fragments;
	}
}

?>
