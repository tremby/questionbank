<?php

foreach ($_SESSION as $k => $v)
	unset($_SESSION[$k]);

echo "session data cleared";

?>
