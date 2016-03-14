<?php
/**
 * Script to join API code to single file for distribution
 */

// Path to source files
$src = "src/";
$config = "src/config.php";

// Path to destination
$dist = "dist/";

// Files to be included
$files = explode("\n", file_get_contents("build.files"));

// Join the files
$contents = "<?php";

foreach($files as $file) {
	
	// Check file existence
	$path = "$src/$file";
	if (!file_exists($path)) {
		throw new Exception("Failed to load file $file ($path)");
	}
	
	// Load and split to lines
	$lines = explode("\n", str_replace("\r\n", "\n", file_get_contents($path)));
	
	// Remove PHP tag
	foreach($lines as $index => $line) {
		if (trim($line) == "<?php") {
			$lines[$index] = "";
		}
	}
	
	// Apped this file
	$contents .= implode("\n", $lines) . "\n";
	
}

// Save to result file
file_put_contents("$dist/api.php", $contents);

// Copy config template
copy($config, "$dist/config.php");