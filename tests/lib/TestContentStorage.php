<?php
class TestContentStorage implements ContentStorage
{
	public function __construct($api) {
		$this->api = $api;
	}
	
	public function getFile($file, $mode) {
		return null;
	}

	public function deleteFile($file) {
		return null;
	}
}