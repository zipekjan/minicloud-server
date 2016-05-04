<?php
class TestApi extends Api {
	public function __construct($config = array()) {
		// Apply config
		$this->config = $config;
		
		// Load default API behaviour
		parent::__construct(null);
	}
}