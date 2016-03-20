<?php
/**
 * Script to join API code to single file for distribution
 */

// Path to source files
$src = "src/api";

// Path to destination
$dist = "dist/";

// Path to installation sources
$install = "src/install";

// Path to htaccess (nice urls)
$htaccess = "src/.htaccess";
 
/**
 * Copy a file, or recursively copy a folder and its contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       string   $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy($source, $dest, $permissions = 0755) {
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        xcopy("$source/$entry", "$dest/$entry", $permissions);
    }

    // Clean up
    $dir->close();
    return true;
}

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
	
	// Append this file
	$contents .= implode("\n", $lines) . "\n";
	
}

// Save to result file
file_put_contents("$dist/api.php", $contents);

// Copy installation procedure
xcopy($install, $dist);

// Copy htaccess
copy($htaccess, "$dist/.htaccess");