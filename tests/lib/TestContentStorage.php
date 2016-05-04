<?php
class TestContentStorage implements ContentStorage
{
	public function __construct($api) {
		$this->api = $api;
		$this->contents = array();
	}
	
	public function getFile($file, $mode) {
		$contents = @$this->contents[$file->version()];
		if (!$contents)
			$this->contents[$file->version()] = $contents = new TestContentStorageFile();
		
		$contents->setMode($mode);
		
		return $contents;
	}

	public function deleteFile($file) {
		unset($this->contents[$file->version()]);
	}
}