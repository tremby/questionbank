<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (!userhasprivileges())
	forbidden();

$message = null;
if (isset($_REQUEST["action"])) {
	if (!isset($_REQUEST["user"]))
		badrequest("no user specified");
	if (!userexists($_REQUEST["user"]))
		badrequest("user doesn't exist");
	switch ($_REQUEST["action"]) {
		case "delete":
			if ($_REQUEST["user"] == username())
				badrequest("you can't delete yourself");
			db()->exec("DELETE FROM users WHERE username='" . db()->escapeString($_REQUEST["user"]) . "';");
			$message = "User <strong>" . htmlspecialchars($_REQUEST["user"]) . "</strong> has been deleted";
			break;
		case "grant":
			db()->exec("UPDATE users SET privileges=1 WHERE username='" . db()->escapeString($_REQUEST["user"]) . "';");
			$message = "User <strong>" . htmlspecialchars($_REQUEST["user"]) . "</strong> is now privileged";
			break;
		case "revoke":
			if (userhasprivileges($_REQUEST["user"]) && privilegedusers() == 1)
				badrequest("can't revoke the privileges of the last remaining privileged user");
			db()->exec("UPDATE users SET privileges=0 WHERE username='" . db()->escapeString($_REQUEST["user"]) . "';");
			$message = "User <strong>" . htmlspecialchars($_REQUEST["user"]) . "</strong> is now unprivileged";
			break;
		default:
			badrequest("unrecognized action");
	}
}

// get users from database
$result = db()->query("
	SELECT
		users.username AS username,
		users.registered AS registered,
		users.privileges AS privileges,
		COALESCE(items.cnt, 0) AS itemcount,
		COALESCE(ratings.cnt, 0) AS ratingcount,
		COALESCE(comments.cnt, 0) AS commentcount
	FROM users
	LEFT JOIN (SELECT user, COUNT(*) AS cnt FROM items GROUP BY user) AS items ON users.username=items.user
	LEFT JOIN (SELECT user, COUNT(*) AS cnt FROM ratings GROUP BY user) AS ratings ON users.username=ratings.user
	LEFT JOIN (SELECT user, COUNT(*) AS cnt FROM comments GROUP BY user) AS comments ON users.username=comments.user
	ORDER BY users.username ASC
;");
$users = array();
while ($row = $result->fetchArray(SQLITE3_ASSOC))
	$users[] = $row;

ob_start();
?>
<script type="text/javascript">
	$j(document).ready(function() {
		$j(".confirmrevokeself").click(function() {
			if (!confirm("Are you sure you want to revoke your own privileges?"))
				return false;
		});
		$j(".confirmdelete").click(function() {
			if (!confirm("Are you sure you want to delete the user " + $j(this).parents("tr:first").find("td:first").text() + "?"))
				return false;
		});
	});
</script>
<?php
$headerextra = ob_get_clean();
$title = "Administer users";
include "htmlheader.php";
?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<?php if (!is_null($message)) { ?>
	<div class="messagebox">
		<h2>Message</h2>
		<?php echo $message; ?>
	</div>
<?php } ?>

<table class="full">
	<tr>
		<th>Username</th>
		<th>Registered</th>
		<th>Privileged</th>
		<th>Items</th>
		<th>Ratings</th>
		<th>Comments</th>
		<th>Actions</th>
	</tr>
	<?php foreach ($users as $k => $user) { ?>
		<tr class="row<?php echo $k % 2; ?>">
			<td><?php echo htmlspecialchars($user["username"]); ?></td>
			<td><?php echo friendlydate_html($user["registered"]); ?></td>
			<td><?php echo $user["privileges"] ? "yes" : "no"; ?></td>
			<td><?php echo $user["itemcount"]; ?></td>
			<td><?php echo $user["ratingcount"]; ?></td>
			<td><?php echo $user["commentcount"]; ?></td>
			<td>
				<ul>
					<?php if (!$user["privileges"]) { ?>
						<li><a href="<?php echo SITEROOT_WEB; ?>?page=users&amp;action=grant&amp;user=<?php echo urlencode($user["username"]); ?>">Grant privileges</a></li>
					<?php } else if ($user["username"] != username() || privilegedusers() > 1) { ?>
						<li><a <?php if ($user["username"] == username()) { ?>class="confirmrevokeself" <?php } ?>href="<?php echo SITEROOT_WEB; ?>?page=users&amp;action=revoke&amp;user=<?php echo urlencode($user["username"]); ?>">Revoke privileges</a></li>
					<?php } ?>
					<?php if ($user["username"] != username()) { ?>
						<li><a class="confirmdelete" href="<?php echo SITEROOT_WEB; ?>?page=users&amp;action=delete&amp;user=<?php echo urlencode($user["username"]); ?>">Delete</a></li>
					<?php } ?>
				</ul>
			</td>
		</tr>
	<?php } ?>
</table>

<?php
include "htmlfooter.php";
?>
