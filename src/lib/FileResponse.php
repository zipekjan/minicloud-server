<?php
/**
 * Class used to represent response containing file contents
 */
class FileResponse extends Response
{
	protected $file;
	protected $filename;
	protected $size;
	protected $bufferSize = 1024;
	
	public function __construct($file, $filename, $size, $code = 200) {
		$this->file = $file;
		$this->filename = $filename;
		$this->size = $size;
		$this->code = $code;
	}
	
	public function execute() {
		header("$this->http $this->code {$this->codeValues[$this->code]}");
		header("Content-Length: " . $this->size);
		header('Content-Disposition: attachment; filename="' . $this->filename . '"');
		
		while(!$this->file->eof()) {
			echo $this->file->read($this->bufferSize);
		}
		
		$this->file->close();
	}
}