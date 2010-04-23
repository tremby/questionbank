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
$newsearch = !isset($_SESSION["items"]) || isset($_REQUEST["clear"]) || isset($_REQUEST["direction"]) || isset($_REQUEST["orderby"]) || isset($_REQUEST["user"]) || isset($_REQUEST["keyword"]);

// defaults
$direction = "DESC";

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
		$direction = "ASC";

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
			case "rating":
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
		if ((!isset($_REQUEST["direction"]) || empty($_REQUEST["direction"])) && (!isset($_REQUEST["orderby"]) || empty($_REQUEST["orderby"]) || $_REQUEST["orderby"] == "modified" || $_REQUEST["orderby"] == "uploaded"))
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

	// get item IDs from database and store in the session
	$sql = "
		SELECT
			items.identifier,
			COALESCE(
				(
					SELECT AVG(rating)
					FROM ratings
					WHERE item=items.identifier
					AND posted > COALESCE(items.modified, items.uploaded)
				),
				0
			) AS rating
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
	$items[] = getitem($itemid);

// get page number from request
$page = 1;
if (isset($_REQUEST["p"]))
	$page = intval($_REQUEST["p"]);
if ($page < 1)
	$page = 1;
$perpage = 20;
if (isset($_REQUEST["perpage"]))
	$perpage = intval($_REQUEST["perpage"]);
if ($perpage < 1)
	$perpage = 1;
$numpages = ceil(count($items) / $perpage);

if (!empty($items) && count($items) <= ($page - 1) * $perpage)
	badrequest("Not enough search results for this page to exist");

ob_start();
?>
<script type="text/javascript">
	$j(document).ready(function() {
		$j(".confirmdeleteitem").click(function() {
			if (!confirm("Are you sure you want to delete this item?"))
				return false;
		});
	});
</script>
<?php
$headerextra = ob_get_clean();
$title = "Item list";
include "htmlheader.php";
?>

<h2><?php echo empty($where) ? "I" : "Filtered i"; ?>tem list</h2>
<?php if (empty($items)) { ?>
	<p>No items were found</p>
<?php } else { ?>
	<ul>
		<li><a href="<?php echo SITEROOT_WEB; ?>?page=playItem&amp;action=results">Play these results</a></li>
		<?php if (!empty($where)) { ?>
			<li><a href="<?php echo SITEROOT_WEB; ?>?page=itemList&amp;clear=true">Clear filters</a></li>
		<?php } ?>
	</ul>
	<?php if (count($items) <= $perpage) { ?>
		<p>Showing all results</p>
	<?php } else { ?>
		<p>
			Showing results
			<?php echo $perpage * ($page - 1) + 1; ?>
			to <?php echo min($perpage * $page, count($items)); ?>
			of <?php echo count($items); ?>
		</p>
	<?php } ?>
	<?php ob_start(); ?>
	<ul class="pagination right">
		<?php if ($page > 2) { ?><li><a href="<?php echo Uri::construct(true)->addvars("p", 1)->geturi(true); ?>">First</a></li><?php } ?>
		<?php if ($page > 1) { ?><li><a href="<?php echo Uri::construct(true)->addvars("p", $page - 1)->geturi(true); ?>">Previous</a></li><?php } ?>
		<li>Page <?php echo $page; ?> of <?php echo $numpages; ?></li>
		<?php if ($page < $numpages) { ?><li><a href="<?php echo Uri::construct(true)->addvars("p", $page + 1)->geturi(true); ?>">Next</a></li><?php } ?>
		<?php if ($page < $numpages - 1) { ?><li><a href="<?php echo Uri::construct(true)->addvars("p", $numpages)->geturi(true); ?>">Last</a></li><?php } ?>
	</ul>
	<?php $pagination = ob_get_flush(); ?>
	<table class="full smalltext">
		<tr>
			<?php foreach (array("uploaded", "modified", "user", "title", "description", "rating") as $type) { ?>
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
		<?php for ($i = $perpage * ($page - 1); $i < min($perpage * $page, count($items)); $i++) { $item = $items[$i]; ?>
			<tr class="row<?php echo $i % 2; ?>" id="item_<?php echo htmlspecialchars($item["identifier"]); ?>">
				<td><?php echo friendlydate($item["uploaded"]); ?></td>
				<td><?php if (!is_null($item["modified"])) echo friendlydate($item["modified"]); ?></td>
				<td><a href="<?php echo SITEROOT_WEB; ?>?page=itemList&amp;user=<?php echo urlencode($item["user"]); ?>"><?php echo htmlspecialchars($item["user"]); ?></a></td>
				<td><?php echo htmlspecialchars($item["title"]); ?></td>
				<td><?php echo htmlspecialchars($item["description"]); ?></td>
				<td>
					<?php if ($item["ratingcount"] > 0) { ?>
						<div class="stars">
							<div class="on" style="width: <?php echo ($on = 100 * $item["rating"] / 5); ?>%;"></div>
							<div class="off" style="width: <?php echo 100 - $on; ?>%;"></div>
						</div>
						<div class="smallfaded">
							From <?php echo count($item["ratingcount"]); ?> rating<?php echo plural($item["ratingcount"]); ?>
						</div>
					<?php } else { ?>
						Not yet rated
					<?php } ?>
				</td>
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
						<a href="<?php echo SITEROOT_WEB; ?>?page=playItem&amp;action=single&amp;qtiid=<?php echo htmlspecialchars($item["identifier"]); ?>">
							Play
						</a>
					</li>
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
					<?php if ($item["user"] == username() || userhasprivileges()) { ?>
						<li>
							<a class="confirmdeleteitem" href="<?php echo SITEROOT_WEB; ?>?page=deleteItem&amp;qtiid=<?php echo htmlspecialchars($item["identifier"]); ?>">
								Delete
							</a>
						</li>
					<?php } ?>
				</ul></td>
			</tr>
		<?php } ?>
	</table>
	<?php echo $pagination; ?>
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
					"rating" => "Rating",
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

		<dt><label for="perpage">Results per page</label></dt>
		<dd><input type="text" class="small" name="perpage" id="perpage" size="4" value="<?php echo $perpage; ?>"></dd>

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
