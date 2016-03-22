<?php
if (!file_exists('config.php'))
	die('No config.');

(new Api())->handle(new Request())->execute();