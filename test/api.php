<?php
$files = explode("\n", file_get_contents("../build.files"));

foreach($files as $file) {
	$path = "../src/$file";
	
	if (!file_exists($path))
		throw new Exception("Failed to load $file ($path).");
	
	require_once($path);
}