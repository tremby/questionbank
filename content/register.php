<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

$errors = array();

if (isset($_POST["register"])) {

	if (!isset($_POST["username"]) || empty($_POST["username"]))
		$errors[] = "You need to specify a username";
	else if (preg_match('%^[^_0-9]%', $_POST["username"]) && !preg_match('%^\w%', $_POST["username"]))
		$errors[] = "Your username must start with an alphanumeric character";
	else if (!preg_match('%^[\w.-]+$%', $_POST["username"]))
		$errors[] = "Your username can only contain alphanumeric characters, dots, underscores and hyphens";

	if ($_POST["password"] != $_POST["password2"])
		$errors[] = "The passwords you entered didn't match";
	else if (!isset($_POST["password"]) || empty($_POST["password"]))
		$errors[] = "You need to specify a password";

	$db->exec("BEGIN TRANSACTION;");

	if (userexists($_POST["username"])) {
		$errors[] = "This username has already been taken";
		$db->exec("COMMIT;");
	}

	if (empty($errors)) {
		$db->exec("INSERT INTO users VALUES (
			'" . $db->escapeString($_POST["username"]) . "',
			'" . $db->escapeString(md5($_POST["password"])) . "',
			" . time() . "
		);");
		$db->exec("COMMIT;");

		//TODO: log them in

		$title = "Registration successful";
		include "htmlheader.php";
		?>
		<h2><?php echo htmlspecialchars($title); ?></h2>
		<p>You have successfully registered as <strong><?php echo htmlspecialchars($_POST["username"]); ?></strong> and can now go ahead and deposit, rate and comment on items</p>
		<?php
		include "htmlfooter.php";
		exit;
	}
}

//TODO: check if already logged in

$title = "Register";
include "htmlheader.php";
?>
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
