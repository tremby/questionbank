<?php if ($_SERVER["REMOTE_ADDR"] == "152.78.64.88") { ?>
	<h3>Debug stuff</h3>
	<pre style="height: 300px; overflow: auto; background-color: #eeffee;">
Post:
<?php echo htmlspecialchars(print_r($_POST, true)); ?>


Session:
<?php echo htmlspecialchars(print_r($_SESSION, true)); ?>
<?php } ?>
</pre>
</body>
</html>
