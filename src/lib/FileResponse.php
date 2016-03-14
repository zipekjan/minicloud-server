<?php
/**
 * Class used to represent response containing file contents
 */
class FileResponse extends Response
{
	protected $file;
	protected $bufferSize = 1024;
	
	public function __construct($file, $code = 200) {
		$this->file = $file;
		$this->code = $code;
	}
	
	public function execute() {
		header("$this->http $this->code {$this->codeValues[$this->code]}");
		header("Content-type: $this->type");
		header("Content-length: " . strlen($this->contents));
		
		while(!$this->file->eof()) {
			echo $this->file->read($this->bufferSize);
		}
		
		$this->file->close();
	}
}