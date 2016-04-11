<?php

// Allow only valid request
if (!isset($_GET['action'])) {
	header("Location: install.php");
	exit;
}

$action = $_GET['action'];

switch($action) {
	
	/**
	 * Validate input fields
	 */
	 
	case 'validate':
		
		// Each key is category
		$result = array(
			'database' => 'OK',
			'storage' => 'OK',
			'php' => 'OK'
		);
		
		// Load input values
		$db_host = $_POST['db_host'];
		$db_user = $_POST['db_user'];
		$db_pass = $_POST['db_pass'];
		$db_name = $_POST['db_name'];
		
		$storage_folder = $_POST['storage_folder'];
		
		// Validate database params
		try {
			$pdo = new PDO(
				"mysql:host=$db_host;dbname=$db_name",
				$db_user, $db_pass
			);
		} catch(PDOException $e) {
			$result['database'] = "Failed to connect.";
		}
		
		// Validate storage params
		if (!file_exists($storage_folder)) {
			$result['storage'] = "Path doesn't exists.";
		} else if (!is_writable($storage_folder)) {
			// Try to set folder writable
			if (!chmod($storage_folder, 0770)) {
				$result['storage'] = "Path isn't writable.";
			}
		}
		
		// Dump result
		die(json_encode($result));

		break;
		
	/**
	 * Do installation
	 */
	
	case 'install':
	
		// Load necessary values
		$db_host = $_POST['db_host'];
		$db_user = $_POST['db_user'];
		$db_pass = $_POST['db_pass'];
		$db_name = $_POST['db_name'];
		
		$storage_folder = $_POST['storage_folder'];
		
		$admin_user = $_POST['admin_user'];
		$admin_pass = $_POST['admin_pass'];
		
		$server_name = $_POST['server_name'];
		$server_desc = $_POST['server_desc'];
		
		// Connect to server
		try {
			$pdo = new PDO(
				"mysql:host=$db_host;dbname=$db_name",
				$db_user, $db_pass
			);
		} catch(PDOException $e) {
			die(json_encode(array(
				'install' => "Failed to connect to database."
			)));
		}
		
		// Create database tables
		$commands = explode(';', file_get_contents('install.sql'));
		
		foreach($commands as $command) {
			
			if (trim($command)) {
				if ($pdo->query($command . ";") === false) {
					die(json_encode(array(
						'install' => 'SQL Failed: ' . print_r($pdo->errorInfo(), true)
					)));
				}
			}
			
		}
		
		// Clear database
		$pdo->query("DELETE FROM users;");
		
		// Create admin user
		$prep = $pdo->prepare("INSERT INTO users (name, password, admin) VALUES (?,SHA2(?, 256),1)");
		if (!$prep->execute(array($admin_user, $admin_pass))) {
			die(json_encode(array(
				'install' => 'SQL Failed: ' . print_r($prep->errorInfo(), true)
			)));
		}
		
		// Get created user ID
		$user_id = $pdo->lastInsertId();
		
		// Assign storage
		$prep = $pdo->prepare("INSERT INTO paths (user_id, path, mktime, mdtime) VALUES (?, '', ?, ?)");
		if (!$prep->execute(array($user_id, time(), time()))) {
			die(json_encode(array(
				'install' => 'SQL Failed: ' . print_r($prep->errorInfo(), true)
			)));
		}
		
		// Load config template
		$config = file_get_contents('config.php.template');
		
		// Prepare values
		$values = array(
			'SERVER_NAME' => $server_name,
			'SERVER_DESCRIPTION' => $server_desc,
			'SERVER_LOGO' => 'null',
			'NICE_URL' => 'false',
			'STORAGE_FOLDER' => $storage_folder,
			'DB_HOST' => $db_host,
			'DB_NAME' => $db_name,
			'DB_USER' => $db_user,
			'DB_PASS' => $db_pass
		);
		
		// Replace values in template
		foreach($values as $key => $value) {
			$config = str_replace("$$key$", $value, $config);
		}
		
		// Create config
		file_put_contents('config.php', $config);
		
		// Remove installation files
		unlink('install-service.php');
		unlink('config.php.template');
		unlink('install.php');
		unlink('install.css');
		unlink('install.js');
		unlink('install.sql');
		unlink('logo.png');
		
		// Output result
		die(json_encode(array('install' => 'OK')));
		
		break;
	
}