<?php
class Api
{
	protected $configFile = null;
	protected $config = array();
	protected $handler = null;
	
	protected $meta;
	protected $storage;
	
	public function __construct($config_file = './config.php') {
		$this->loadConfig($config_file);
		$this->handler = new ApiHandler();
	}
	
	protected function loadConfig($config_file) {
		$this->configFile = $config_file;
		$this->config = require_once($config_file);
		
		try {
			$meta = new ReflectionClass($this->config('meta'));
		} catch(Exception $e) {
			throw new Exception("Failed to load meta storage {$this->config('meta')}: $e");
		}
		
		try {
			$storage = new ReflectionClass($this->config('storage'));
		} catch(Exception $e) {
			throw new Exception("Failed to load data storage {$this->config('storage')}: $e");
		}
		
		$this->meta = $meta->newInstance($this);
		$this->storage = $storage->newInstance($this);
	}
	
	public function config($key) {
		if ($key === null)
			return $this->config;
		return isset($this->config[$key]) ? $this->config[$key] : null;
	}
	
	public function handle($request) {
		$this->request = $request;
		
		$user = $this->meta()->getUser($this->request->auth());
		
		return $this->handler->handle($this, $user, $this->request);
	}
	
	public function meta() {
		return $this->meta;
	}
	
	public function storage() {
		return $this->storage;
	}
	
	public function getInfo() {
		return array(
			'name' => $this->config('name'),
			'description' => $this->config('description'),
			'logo' => $this->config('logo'),
			'nice_url' => $this->config('nice_url')
		);
	}
}