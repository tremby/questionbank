<?php

$xml = file_get_contents(SITEROOT_LOCAL . "/test2.xml");

$errors = array();
$warnings = array();
$messages = array();

if (!validateQTI($xml, $errors, $warnings, $messages))
	die("not valid QTI");

$xml = simplexml_load_string($xml);

$items = item_types();

$scores = array();
$ai = null;
foreach ($items as $item) {
	$score = $item->fromXML($xml);
	$scores[] = $score;

	if ($score == 255) {
		$ai = $item;
		break;
	}
}
if (is_null($ai)) {
	arsort($scores);
	$keys = array_keys($scores);

	if ($scores[$keys[0]] == 0)
		die("Item type not recognized");

	$ai = $items[$keys[0]];
	$ai->addWarning("Item did not exactly match any of the implemented types. The closest match ({$ai->itemTypePrint()}, " . round($scores[$keys[0]] / 2.55) . "% match) was chosen.");
}

$ai->showForm();

?>
