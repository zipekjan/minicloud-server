<?php
class FolderStorageFile implements ContentStorageFile
{
	public function __construct($path, $mode) {
		$this->handle = fopen($path, $mode);
		
		if (!$this->handle)
			throw new Exception("Failed to open file.");	
	}
	
	public function write($data) {
		return fwrite($this->handle, $data);
	}
	
	public function read($length) {
		return fread($this->handle, $length);
	}
	
	public function eof() {
		return feof($this->handle);
	}
	
	public function close() {
		fclose($this->handle);
		$this->handle = null;
	}
	
	public function __destruct() {
		if ($this->handle)
			$this->close();
	}
}