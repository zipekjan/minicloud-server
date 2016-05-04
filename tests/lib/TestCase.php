<?php
class TestCase extends PHPUnit_Framework_TestCase {
	
	protected $tmp = 'tests/tmp/';
	
	/**
	 * Creates testing database and returns PDO access.
	 */
	protected function getDB() {
		// Use memory
		$pdo = new PDO("sqlite::memory:");
		
		// Install structure
		$install = str_replace(array("\n","\r","\t"), "", file_get_contents("tests/data/struct.sql"));
		
		foreach(explode(";", $install) as $line) {
			if (!trim($line))
				continue;
			
			if (!$pdo->query($line) && $pdo->errorCode() != 0)
				throw new Exception("Failed to initialize test database. Structure sql failed. Error: " . print_r($pdo->errorInfo(), true));
		}
		
		// Install test data
		$data = str_replace(array("\n","\r","\t"), "", file_get_contents("tests/data/data.sql"));
		
		foreach(explode(";", $data) as $line) {
			if (!trim($line))
				continue;
			
			if (!$pdo->query($line) && $pdo->errorCode() != 0)
				throw new Exception("Failed to initialize test database. Test data sql failed. Error: " . print_r($pdo->errorInfo(), true));
		}
		
		return $pdo;
	}
	
	protected function getApi($config = array()) {
		return new TestApi($config);
	}
		
	protected function tearDown() {
		
		// Clean tmp foler
		$dir = opendir($this->tmp);
		while($file = readdir($dir)) {
			if (!is_dir($file) && $file != 'empty') {
				unlink($this->tmp . '/' . $file);
			}
		}
	}
}