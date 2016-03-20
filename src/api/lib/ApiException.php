<?php
class ApiExcetion extends Exception
{
	protected $type;
	
	public function __construct($message, $code=500, $type='error') {
		parent::__construct($message, $code);
		
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
}
