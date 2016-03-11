<?php
class Api
{
	protected $configFile = null;
	protected $config = array();
	protected $handler = null;
	
	public function __construct($config_file = './config.php') {
		$this->loadConfig($config_file);
		$this->handler = new ApiHandler();
	}
	
	protected function loadConfig($config_file) {
		$this->configFile = $config_file;
		$this->config = require_once($config_file);
		
		$this->meta = new {$this->config('meta')}($this);
		$this->storage = new {$this->config('storage')}($this);
	}
	
	public function config($key) {
		if ($key === null)
			return $this->config;
		return isset($this->config[$key]) ? $this->config[$key] : null;
	}
	
	public function handle($request) {
		$this->request = $request;
		
		$user = $this->meta->getUser($this->request->auth());
		if ($user === null)
			return new Response(null, 401);
		
		return $this->handler->handle($this, $user, $this->request);
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