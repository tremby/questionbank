<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

// use old search unless filters or clear action are set in request vars
$newsearch = isset($_REQUEST["clear"]) || isset($_REQUEST["direction"]) || isset($_REQUEST["orderby"]) || isset($_REQUEST["user"]) || isset($_REQUEST["keyword"]);

// defaults
$direction = "ASC";

// default sort is by modified or uploaded date
$orderby = "modified";
$orderbysql = "COALESCE(modified, uploaded)";

// empty where clause
$keyword = null;
$user = null;
$where = array();

if ($newsearch) {
	if (isset($_REQUEST["clear"]))
		unset($_SESSION["search"]);
	else {
		// get ordering settings from request vars

		// override sort direction
		if (isset($_REQUEST["direction"]) && $_REQUEST["direction"] == "DESC")
			$direction = "DESC";

		// override sort
		if (isset($_REQUEST["orderby"]) && !empty($_REQUEST["orderby"])) switch ($_REQUEST["orderby"]) {
			case "uploaded":
			case "user":
			case "title":
			case "description":
				$orderbysql = $_REQUEST["orderby"];
				$orderby = $_REQUEST["orderby"];
				break;
			case "random":
				$orderbysql = "RANDOM()";
				$orderby = $_REQUEST["orderby"];
				break;
		}

		// if order not set in request vars and we're ordering by date, show 
		// newest first
		if (!isset($_REQUEST["direction"]) || empty($_REQUEST["direction"]) && (!isset($_REQUEST["orderby"]) || empty($_REQUEST["orderby"]) || $_REQUEST["orderby"] == "modified" || $_REQUEST["orderby"] == "uploaded"))
			$direction = "DESC";

		// filter by user and keyword
		if (isset($_REQUEST["user"]) && !empty($_REQUEST["user"])) {
			$user = $_REQUEST["user"];
			$where[] = "user='" . db()->escapeString($user) . "'";
		}
		if (isset($_REQUEST["keyword"]) && !empty($_REQUEST["keyword"])) {
			$keyword = $_REQUEST["keyword"];
			$where[] = "keywords.keyword='" . db()->escapeString($keyword) . "'";
		}

		// save that stuff in the session
		$_SESSION["search"] = array(
			"direction"	=>	$direction,
			"orderby"	=>	$orderby,
			"orderbysql"	=>	$orderbysql,
			"where"	=>	$where,
			"user"	=>	$user,
			"keyword"	=>	$keyword,
		);
	}

	// get items IDs from database and store in the session
	$sql = "
		SELECT items.identifier
		FROM items
		LEFT JOIN keywords ON items.identifier=keywords.item
		" . (empty($where) ? "" : "WHERE " . implode(" AND ", $where)) . "
		GROUP BY keywords.item
		ORDER BY $orderbysql $direction
	;";
	$result = db()->query($sql);
	$_SESSION["items"] = array();
	while ($row = $result->fetchArray(SQLITE3_NUM))
		$_SESSION["items"][] = $row[0];
} else if (isset($_SESSION["search"])) {
	// recover those vars set above
	if (isset($_SESSION["search"]["direction"]))
		$direction = $_SESSION["search"]["direction"];
	if (isset($_SESSION["search"]["orderby"]))
		$orderby = $_SESSION["search"]["orderby"];
	if (isset($_SESSION["search"]["orderbysql"]))
		$orderbysql = $_SESSION["search"]["direction"];
	if (isset($_SESSION["search"]["where"]))
		$where = $_SESSION["search"]["where"];
	if (isset($_SESSION["search"]["user"]))
		$user = $_SESSION["search"]["user"];
	if (isset($_SESSION["search"]["keyword"]))
		$keyword = $_SESSION["search"]["keyword"];
}

// get items
$items = array();
foreach ($_SESSION["items"] as $itemid)
	$items[$itemid] = getitem($itemid);

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
					<a href="<?php echo Uri::construct(true)->removevars("clear")->addvars(array(
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
						<?php foreach ($item["keywords"] as $itemkeyword) { ?>
							<li>
								<a href="<?php echo Uri::construct(true)->removevars("clear")->addvars("keyword", $itemkeyword)->geturi(true); ?>">
									<?php echo htmlspecialchars($itemkeyword); ?>
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
		<dd><input type="text" name="user" id="user" size="64"<?php if (isset($user)) { ?> value="<?php echo htmlspecialchars($user); ?>"<?php } ?>></dd>

		<dt><label for="keyword">Keyword</label></dt>
		<dd><input type="text" name="keyword" id="keyword" size="64"<?php if (isset($keyword)) { ?> value="<?php echo htmlspecialchars($keyword); ?>"<?php } ?>></dd>

		<dt><label for="orderby">Order by</label></dt>
		<dd>
			<select name="orderby" id="orderby">
				<?php foreach (array(
					"uploaded" => "Date uploaded",
					"modified" => "Date modified",
					"user" => "User",
					"title" => "Title",
					"description" => "Description",
					"random" => "Random",
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
			<?php if (!empty($where)) { ?>
				<input type="submit" name="clear" value="Clear filters">
			<?php } ?>
		</dd>
	</dl>
</form>

<?php include "htmlfooter.php"; ?>
