<?php
if (!isset($_GET['action'])) {
	header("Location: install.php");
	exit;
}

$action = $_GET['action'];

switch($action) {
	case 'validate':
		
		$result = array(
			'database' => 'OK',
			'storage' => 'OK',
			'php' => 'OK'
		);
		
		$db_host = $_POST['db_host'];
		$db_user = $_POST['db_user'];
		$db_pass = $_POST['db_pass'];
		$db_name = $_POST['db_name'];
		
		$storage_folder = $_POST['storage_folder'];
		
		try {
			$pdo = new PDO(
				"mysql:host=$db_host;dbname=$db_name",
				$db_user, $db_pass
			);
		} catch(PDOException $e) {
			$result['database'] = "Failed to connect.";
		}
		
		if (!file_exists($storage_folder)) {
			$result['storage'] = "Path doesn't exists.";
		} else if (!is_writable($storage_folder)) {
			$result['storage'] = "Path isn't writable.";
		}
		
		die(json_encode($result));

		break;
		
	case 'install':
	
		$db_host = $_POST['db_host'];
		$db_user = $_POST['db_user'];
		$db_pass = $_POST['db_pass'];
		$db_name = $_POST['db_name'];
		
		$storage_folder = $_POST['storage_folder'];
		
		$admin_user = $_POST['admin_user'];
		$admin_pass = $_POST['admin_pass'];
		
		$server_name = $_POST['server_name'];
		$server_desc = $_POST['server_desc'];
		
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
		
		$prep = $pdo->prepare("INSERT INTO users (name, password) VALUES (?,SHA2(?, 256))");
		if (!$prep->execute(array($admin_user, $admin_pass))) {
			die(json_encode(array(
				'install' => 'SQL Failed: ' . print_r($prep->errorInfo(), true)
			)));
		}
		
		$config = file_get_contents('config.php.template');
		
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
		
		foreach($values as $key => $value) {
			$config = str_replace("$$key$", $value, $config);
		}
		
		file_put_contents('config.php', $config);
		
		die(json_encode(array('install' => 'OK')));
		
		break;
	
}