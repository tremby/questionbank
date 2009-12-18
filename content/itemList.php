<?php

// get sorted array of items
// new one at the top
// then those which have titles in alphabetical order
// then others in identifier order
$items = array();
if (isset($_SESSION["items"])) foreach ($_SESSION["items"] as $id => $item) {
	// skip new item for now -- we want that at the top of the list
	if ($id == "new")
		continue;
	$items[] = $item;
}
usort($items, array("QTIAssessmentItem", "compare_by_title"));
if (isset($_SESSION["items"]["new"]))
	$items[] = $_SESSION["items"]["new"];
$items = array_reverse($items);

?>
<?php include "htmlheader.php"; ?>

<h2>Item list</h2>
<p>This page shows a list of items you are currently editing. They are not permanent 
so ensure you save them manually before logging out.</p>

<?php if (empty($items)) { ?>
	<p><strong>No items are in memory for this session</strong></p>
<?php } else { ?>
	<table>
		<tr>
			<th>Title</th>
			<th>Item type</th>
			<th>Actions</th>
		</tr>
		<?php foreach ($items as $item) { ?>
			<tr>
				<td><?php echo htmlspecialchars($item->getTitle()); ?></td>
				<td><?php echo htmlspecialchars($item->itemTypePrint()); ?></td>
				<td><ul>
					<li><a href="?page=editAssessmentItem&amp;qtiid=<?php echo $item->getQTIID(); ?>">Edit</a></li>
					<li><a href="?page=previewAssessmentItem&amp;qtiid=<?php echo $item->getQTIID(); ?>">Preview</a></li>
					<li><a href="?page=downloadAssessmentItem&amp;qtiid=<?php echo $item->getQTIID(); ?>">Download</a></li>
					<li><a href="?page=makeContentPackage&amp;qtiid=<?php echo $item->getQTIID(); ?>">Package</a></li>
					<li><a href="?page=deleteAssessmentItem&amp;qtiid=<?php echo $item->getQTIID(); ?>">Delete</a></li>
				</ul></td>
			</tr>
		<?php } ?>
	</table>
<?php } ?>

<?php include "htmlfooter.php"; ?>
