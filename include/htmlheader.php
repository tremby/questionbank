<?php
header("Content-Language: en");
header("Content-Style-Type: text/css");
header("Content-Script-Type: text/javascript");
?>
<!DOCTYPE HTML PUBLIC "-//W3C/DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="<?php echo SITEROOT_WEB; ?>include/jquery.js"></script>
	<script type="text/javascript" src="<?php echo SITEROOT_WEB; ?>include/tiny_mce/jquery.tinymce.js"></script>
	<script type="text/javascript" src="<?php echo SITEROOT_WEB; ?>include/authoringtool.js.php"></script>
	<?php if (isset($GLOBALS["headerjs"])) { ?>
		<script type="text/javascript">
			<?php echo $GLOBALS["headerjs"]; ?>
		</script>
	<?php } ?>
	<link rel="stylesheet" href="<?php echo SITEROOT_WEB; ?>include/styles.css">
	<?php if (isset($GLOBALS["headercss"])) { ?>
		<style type="text/css">
			<?php echo $GLOBALS["headercss"]; ?>
		</style>
	<?php } ?>
</head>
<body>
<h1>QTI authoring tool</h1>
<ul>
	<li><a href="?">Back to main menu</a></li>
</ul>
