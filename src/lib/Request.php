<?php
/**
 * Class used to parse HTTP request and extract values used by API
 */
class Request
{
	protected $method;
	protected $action;
	protected $user;
	protected $uri;
	protected $type;
	protected $contents;
	protected $auth;
	protected $headers;
	protected $files;
	
	/**
	 * @param bool $real OPTIONAL
	 */
	public function __construct($real = true) {
		if ($real) {
			// HTTP Method
			$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
			
			// Request url
			$this->uri = $_SERVER['REQUEST_URI'];
			
			// Type of provided content
			$this->type = @$_SERVER['CONTENT_TYPE'];
			
			// Load all request types
			$this->contents = array_merge($_GET, $_POST);
			
			// Action can be passed by any means
			$this->action = $this->contents('action');
			
			// Load all headers passed
			$this->headers = $this->parseHeaders();
			
			// Load auth, passed by custom header value
			$this->auth = $this->headers('X-AUTH');
			
			// Load POST files
			$this->files = @$_FILES;
			
			// Convert POST files to our container
			if (is_array($this->files)) {
				foreach($this->files as $key => $value) {
					$this->files[$key] = new RequestFile($value);
				}
			}
		}
	}
	
	/**
	 * Returns HTTP header(s) value
	 *
	 * @param string $key OPTIONAL header key
	 * @return string|array if no key was provided, returns all header values
	 */
	public function headers($key = null) {
		if ($key == null)
			return $this->headers;
		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}
	
	/**
	 * Loads request headers
	 */
	protected function parseHeaders() {
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) == 'HTTP_') {
				$header = strtoupper(str_replace('_', '-', strtolower(substr($key, 5))));
				$headers[$header] = $value;
			}
		}
		return $headers;
	}
	
	/**
	 * Returns authorization string
	 */
	public function auth() {
		return $this->auth;
	}
	
	/**
	 * Get/sets user in this request
	 *
	 * @param string $value
	 * @return mixed
	 */
	public function user($value = null) {
		if ($value)
			$this->user = $value;
		return $this->user;
	}
	
	/**
	 * Returns arguments in this request
	 *
	 * @param string $key param name
	 * @param mixed $default OPTIONAL value returnd when argument is not provided
	 * @return mixed argument value
	 */
	public function contents($key = null, $default = null) {
		if ($key == null)
			return $this->contents;
		
		return isset($this->contents[$key]) ? $this->contents[$key] : $default;
	}
}