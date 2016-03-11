<?php
/**
 * Class used to compose responses
 */
class Response
{
	///@var string $contents raw response contents
	protected $contents;
	///@var string $type type of response content
	protected $type = 'application/json';
	///@var int $code http code of response
	protected $code = 200;
	///@var string $http http version
	protected $http = 'HTTP/1.1';
	
	///@var array $codeValues string values associated with http codes
	protected $codeValues = array(
		200 => 'OK',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		404 => 'Not Found'
	);
	
	/**
	 * @param string $contents raw response contents
	 * @param int    $code     http response code
	 */
	public function __construct($contents, $code = 200) {
		$this->contents = $contents;
		$this->code = $code;
		
		if ($this->contents instanceof ResponseObject)
			$this->contents = json_encode($this->contents->serialize());
		
		if (!is_string($this->contents))
			$this->contents = json_encode($this->contents);
	}
	
	/**
	 * Assigns header values and prints the response contents
	 */
	public function execute() {
		header("$this->http $this->code {$this->codeValues[$this->code]}");
		header("Content-type: $this->type");
		header("Content-length: " . strlen($this->contents));
		echo $contents;
	}
}