<?php
class ApiHandlerAction
{
	protected $method;
	protected $response;
	
	public function __construct($method, $respose = null) {
		$this->method = $method;
		$this->respose = $response;
	}
	
	public function method() {
		return $method;
	}
	
	public function respose() {
		return $respose;
	}
}
