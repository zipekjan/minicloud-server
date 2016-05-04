<?php
class TestContentStorageFile implements ContentStorageFile
{
	protected $contents = null;
	protected $position = 0;
	
	public function setMode($mode) {
		if (strpos($mode, 'w'))
			$this->contents = "";
	}
	
	public function write($data) {
		$this->contents .= $data;
	}
	
	public function read($length) {
		$value = substr($this->contents, $this->position, $length);
		$this->position += $length;
		return $value;
	}
	
	public function eof() {
		return $this->position >= strlen($this->contents);
	}
	
	public function close() {
	}
}