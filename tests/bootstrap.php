<?php
$files = explode("\n", file_get_contents("build.files"));

foreach($files as $file) {
	// Skip initialization file
	if ($file === "main.php")
		continue;
	
	$path = "src/api/$file";
	
	if (!file_exists($path))
		throw new Exception("Failed to load $file ($path).");
	
	require_once($path);
}

require_once('lib/TestCase.php');
require_once('lib/TestApi.php');
require_once('lib/TestContentStorage.php');
require_once('lib/TestMetaStorage.php');
require_once('lib/TestRequest.php');