<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

// clear the nextpage session var if we've come to the login page directly
if ($GLOBALS["page"] == "login" && isset($_SESSION["nextpage"]))
	unset($_SESSION["nextpage"]);

if (!isset($_REQUEST["async"]) && loggedin()) {
	$title = "Already logged in";
	include "htmlheader.php";
	?>
	<h2><?php echo htmlspecialchars($title); ?></h2>
	<p>
		You're already logged in as
		<strong><?php echo htmlspecialchars(username()); ?></strong>
		– if this isn't you you can
		<a href="<?php echo SITEROOT_WEB; ?>?page=logout">log out</a>
	</p>
	<?php
	include "htmlfooter.php";
	exit;
}

$errors = array();

if (isset($_POST["username"]) && isset($_POST["password"])) {
	if (login($_POST["username"], $_POST["password"])) {
		if (isset($_REQUEST["async"]))
			ok();

		// redirect if nextpage session var is set (and unset it)
		if (isset($_SESSION["nextpage"])) {
			$nextpage = $_SESSION["nextpage"];
			unset($_SESSION["nextpage"]);
			redirect($nextpage);
		}

		$title = "Successfully logged in";
		include "htmlheader.php";
		?>
		<h2><?php echo htmlspecialchars($title); ?></h2>
		<p>
			You're now logged in as 
			<strong><?php echo htmlspecialchars(username()); ?></strong>
		</p>
		<?php
		include "htmlfooter.php";
		exit;
	}

	if (isset($_REQUEST["async"]))
		badrequest("That username and password combination did not match any user in the database");

	$errors[] = "That username and password combination did not match any user in the database";
} else if (isset($_REQUEST["async"]))
	badrequest("username and password not given");

$title = "Log in";
include "htmlheader.php";
?>
<h2><?php echo htmlspecialchars($title); ?></h2>
<?php if (isset($_SESSION["nextpage"])) { ?>
	<p><strong>You need to be logged in to view the page you requested</strong></p>
<?php } ?>
<p>Use the form below to log in. If you don't yet have an account you can <a href="<?php echo SITEROOT_WEB; ?>?page=register">register</a>.</p>

<?php if (!empty($errors)) showmessages($errors, "Error", "error"); ?>

<form action="<?php echo isset($_SESSION["nextpage"]) ? $_SESSION["nextpage"] : SITEROOT_WEB . "?page=login"; ?>" method="post">
	<dl>
		<dt><label for="username">Username</label></dt>
		<dd><input type="text" width="32" name="username" id="username"<?php if (isset($_POST["username"])) { ?> value="<?php echo htmlspecialchars($_POST["username"]); ?>"<?php } ?>></dd>

		<dt><label for="password">Password</label></dt>
		<dd><input type="password" width="32" name="password" id="password"></dd>

		<dt></dt>
		<dd><input type="submit" name="login" value="Log in"></dd>
	</dl>
</form>

<?php include "htmlfooter.php"; ?>
