<?php
class ApiHandlerAction
{
	protected $method;
	protected $response;
	protected $isPublic;
	
	public function __construct($method, $response = null, $isPublic = false) {
		$this->method = $method;
		$this->response = $response;
		$this->isPublic = $isPublic;
	}
	
	public function method() {
		return $this->method;
	}
	
	public function response() {
		return $this->response;
	}
	
	public function isPublic() {
		return $this->isPublic;
	}
}
