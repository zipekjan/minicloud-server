<?php
class TestMetaStorage implements MetaStorage {
	public function __construct($api) {
		$this->api = $api;
	}
	
	public function getUser($hash) {}

	public function setUser($user) {}
	
	public function getPath($user, $path = null, $recursive = false) {}
	
	public function getPathById($user, $id, $recursive = false) {}

	public function setPath($user, $folder) {}
	
	public function deletePath($user, $folder) {}

	public function getFileById($user, $id) {}

	public function setFile($user, $file) {}
	
	public function deleteFile($user, $file) {}

	public function addFileVersion($user, $file) {}
}