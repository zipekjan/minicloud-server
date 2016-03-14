<?php
class ApiHandlerAction
{
	protected $method;
	protected $response;
	
	public function __construct($method, $response = null) {
		$this->method = $method;
		$this->response = $response;
	}
	
	public function method() {
		return $this->method;
	}
	
	public function response() {
		return $this->response;
	}
}
