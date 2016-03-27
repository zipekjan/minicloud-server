<?php
class ApiHandlerAction
{
	protected $method;
	protected $response;
	protected $isPublic;
	protected $adminOnly;
	
	public function __construct($method, $response = null, $isPublic = false, $adminOnly = false) {
		$this->method = $method;
		$this->response = $response;
		$this->isPublic = $isPublic;
		$this->adminOnly = $adminOnly;
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
	
	public function isAdminOnly() {
		return $this->adminOnly;
	}
}
