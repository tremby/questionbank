<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

// get ordering settings from request vars
$direction = "ASC";
if (isset($_REQUEST["direction"]) && $_REQUEST["direction"] == "DESC")
	$direction = "DESC";

// default sort is by modified or uploaded date
$orderby = "modified";
$orderbysql = "COALESCE(modified, uploaded)";

// override if set in request vars
if (isset($_REQUEST["orderby"]) && !empty($_REQUEST["orderby"])) switch ($_REQUEST["orderby"]) {
	case "uploaded":
	case "user":
	case "title":
	case "description":
		$orderbysql = $_REQUEST["orderby"];
		$orderby = $_REQUEST["orderby"];
		break;
}

// if order not set in request vars and we're ordering by date, show newest 
// first
if (!isset($_REQUEST["direction"]) || empty($_REQUEST["direction"]) && (!isset($_REQUEST["orderby"]) || empty($_REQUEST["orderby"]) || $_REQUEST["orderby"] == "modified" || $_REQUEST["orderby"] == "uploaded"))
	$direction = "DESC";

// filter by user and keyword
$where = array();
if (isset($_REQUEST["user"]) && !empty($_REQUEST["user"]))
	$where[] = "user='" . db()->escapeString($_REQUEST["user"]) . "'";
if (isset($_REQUEST["keyword"]) && !empty($_REQUEST["keyword"]))
	$where[] = "keywords.keyword='" . db()->escapeString($_REQUEST["keyword"]) . "'";

// TODO: limits (pagination)

// get items from database
$sql = "
	SELECT items.*
	FROM items
	LEFT JOIN keywords ON items.identifier=keywords.item
	" . (empty($where) ? "" : "WHERE " . implode(" AND ", $where)) . "
	GROUP BY keywords.item
	ORDER BY $orderbysql $direction
;";
$result = db()->query($sql);
$items = array();
while ($row = $result->fetchArray(SQLITE3_ASSOC))
	$items[$row["identifier"]] = $row;

// get keywords for the items we've collected
if (!empty($items)) {
	$sql = "
		SELECT *
		FROM keywords
		WHERE item IN ('" . implode("', '", array_keys($items)) . "')
		ORDER BY keyword ASC
	;";
	$result = db()->query($sql);
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		if (!isset($items[$row["item"]]["keywords"]))
			$items[$row["item"]]["keywords"] = array();
		$items[$row["item"]]["keywords"][] = $row["keyword"];
	}
}

$title = "Item list";
include "htmlheader.php";
?>

<h2><?php echo empty($where) ? "I" : "Filtered i"; ?>tem list</h2>
<?php if (empty($items)) { ?>
	<p>No items were found</p>
<?php } else { ?>
	<table>
		<tr>
			<?php foreach (array("uploaded", "modified", "user", "title", "description") as $type) { ?>
				<th<?php if ($orderby == $type) { ?> class="ordered"<?php } ?>>
					<a href="<?php echo Uri::construct(true)->addvars(array(
						"orderby" => $type,
						"direction" => $orderby == $type && $direction == "ASC" ? "DESC" : "ASC",
					))->geturi(true); ?>">
						<?php echo ucfirst($type); ?>
					</a>
				</th>
			<?php } ?>
			<th>Keywords</th>
			<th>Actions</th>
		</tr>
		<?php $odd = 0; foreach ($items as $item) { $odd = ++$odd % 2; ?>
			<tr class="row<?php echo $odd; ?>" id="item_<?php echo htmlspecialchars($item["identifier"]); ?>">
				<td><?php echo friendlydate($item["uploaded"]); ?></td>
				<td><?php if (!is_null($item["modified"])) echo friendlydate($item["modified"]); ?></td>
				<td><a href="<?php echo SITEROOT_WEB; ?>?page=itemList&amp;user=<?php echo urlencode($item["user"]); ?>"><?php echo htmlspecialchars($item["user"]); ?></a></td>
				<td><?php echo htmlspecialchars($item["title"]); ?></td>
				<td><?php echo htmlspecialchars($item["description"]); ?></td>
				<td><?php if (!empty($item["keywords"])) { ?>
					<ul class="keywords">
						<?php foreach ($item["keywords"] as $keyword) { ?>
							<li>
								<a href="<?php echo Uri::construct(true)->addvars("keyword", $keyword)->geturi(true); ?>">
									<?php echo htmlspecialchars($keyword); ?>
								</a>
							</li>
						<?php } ?>
					</ul>
				<?php } ?></td>
				<td><ul>
					<li>
						<a href="<?php echo SITEROOT_WEB; ?>?page=toEqiat&amp;qtiid=<?php echo htmlspecialchars($item["identifier"]); ?>&amp;clone=true">
							Clone and edit
						</a>
					</li>
					<?php if ($item["user"] == username()) { ?>
						<li>
							<a href="<?php echo SITEROOT_WEB; ?>?page=toEqiat&amp;qtiid=<?php echo htmlspecialchars($item["identifier"]); ?>">
								Edit
							</a>
						</li>
					<?php } ?>
				</ul></td>
			</tr>
		<?php } ?>
	</table>
<?php } ?>

<h2>Filter items</h2>
<form action="<?php echo SITEROOT_WEB; ?>" method="get">
	<dl>
		<dt><label for="user">User</label></dt>
		<dd><input type="text" name="user" id="user" size="64"<?php if (isset($_REQUEST["user"])) { ?> value="<?php echo htmlspecialchars($_REQUEST["user"]); ?>"<?php } ?>></dd>

		<dt><label for="keyword">Keyword</label></dt>
		<dd><input type="text" name="keyword" id="keyword" size="64"<?php if (isset($_REQUEST["keyword"])) { ?> value="<?php echo htmlspecialchars($_REQUEST["keyword"]); ?>"<?php } ?>></dd>

		<dt><label for="orderby">Order by</label></dt>
		<dd>
			<select name="orderby" id="orderby">
				<?php foreach (array(
					"uploaded" => "Date uploaded",
					"modified" => "Date modified",
					"user" => "User",
					"title" => "Title",
					"description" => "Description",
				) as $k => $v) { ?>
					<option value="<?php echo htmlspecialchars($k); ?>"<?php if ($orderby == $k) { ?> selected="selected"<?php } ?>><?php echo htmlspecialchars($v); ?></option>
				<?php } ?>
			</select>
			<select name="direction" id="direction">
				<option value="ASC"<?php if ($direction == "ASC") { ?> selected="selected"<?php } ?>>Ascending</option>
				<option value="DESC"<?php if ($direction == "DESC") { ?> selected="selected"<?php } ?>>Descending</option>
			</select>
		</dd>

		<dt></dt>
		<dd>
			<input type="hidden" name="page" value="itemList">
			<input type="submit" name="filter" value="Filter">
		</dd>
	</dl>
</form>

<?php include "htmlfooter.php"; ?>
