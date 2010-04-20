<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (loggedin()) {
	$title = "Already logged in";
	include "htmlheader.php";
	?>
	<h2><?php echo htmlspecialchars($title); ?></h2>
	<p>
		You can't register since you're already logged in as
		<strong><?php echo htmlspecialchars(username()); ?></strong>
		– if this isn't you you can
		<a href="<?php echo SITEROOT_WEB; ?>?page=logout">log out</a>.
	</p>
	<?php
	include "htmlfooter.php";
	exit;
}

$errors = array();

if (isset($_POST["register"])) {
	// keep it atomic
	db()->exec("BEGIN TRANSACTION;");

	// check input
	if (!isset($_POST["username"]) || empty($_POST["username"]))
		$errors[] = "You need to specify a username";
	else if (preg_match('%^[^_0-9]%', $_POST["username"]) && !preg_match('%^\w%', $_POST["username"]))
		$errors[] = "Your username must start with an alphanumeric character";
	else if (!preg_match('%^[\w.-]+$%', $_POST["username"]))
		$errors[] = "Your username can only contain alphanumeric characters, dots, underscores and hyphens";
	else if (userexists($_POST["username"])) {
		$errors[] = "This username has already been taken";
	}

	if ($_POST["password"] != $_POST["password2"])
		$errors[] = "The passwords you entered didn't match";
	else if (!isset($_POST["password"]) || empty($_POST["password"]))
		$errors[] = "You need to specify a password";

	// grant privileges if this is the first user
	$privileges = db()->querySingle("SELECT COUNT(*) FROM users;") == 0 ? 1 : 0;

	if (empty($errors)) {
		db()->exec("INSERT INTO users VALUES (
			'" . db()->escapeString($_POST["username"]) . "',
			'" . db()->escapeString(md5($_POST["password"])) . "',
			" . time() . ",
			$privileges
		);");
		db()->exec("COMMIT;");

		login($_POST["username"], $_POST["password"]);

		$title = "Registration successful";
		include "htmlheader.php";
		?>
		<h2><?php echo htmlspecialchars($title); ?></h2>
		<p>You have successfully registered as <strong><?php echo htmlspecialchars($_POST["username"]); ?></strong>. 
		You've been logged in and can now go ahead and deposit, rate and comment 
		on items.</p>
		<?php if (userhasprivileges()) { ?>
			<p><strong>You have raised privileges.</strong></p>
		<?php } ?>
		<?php
		include "htmlfooter.php";
		exit;
	}

	// finish transaction (which didn't happen anyway)
	db()->exec("COMMIT;");
}

$title = "Register";
include "htmlheader.php";
?>

<?php if (db()->querySingle("SELECT COUNT(*) FROM users;") == 0) { ?>
	<div class="messagebox">
		<h2>No users exist</h2>
		<p>The first user to register will have raised privileges. If that's you 
		there will be a message to say so. If something goes wrong delete the 
		database file and try again.</p>
	</div>
<?php } ?>

<h2><?php echo htmlspecialchars($title); ?></h2>
<p>Use the form below to register so you can deposit, rate and comment on questions</p>

<?php if (!empty($errors)) showmessages($errors, "Error", "error"); ?>

<form action="<?php echo SITEROOT_WEB; ?>?page=register" method="post">
	<dl>
		<dt><label for="username">Username</label></dt>
		<dd><input type="text" width="32" name="username" id="username"<?php if (isset($_POST["username"])) { ?> value="<?php echo htmlspecialchars($_POST["username"]); ?>"<?php } ?>></dd>

		<dt><label for="password">Password</label></dt>
		<dd><input type="password" width="32" name="password" id="password"></dd>

		<dt><label for="password2">Password again</label></dt>
		<dd><input type="password" width="32" name="password2" id="password2"></dd>

		<dt></dt>
		<dd><input type="submit" name="register" value="Register"></dd>
	</dl>
</form>

<?php include "htmlfooter.php"; ?>
