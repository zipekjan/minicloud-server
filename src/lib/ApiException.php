<?php
class ApiExcetion extends Exception
{
	protected $type;
	
	public function __construct($message, $code=500, $type='error') {
		$this->type = $type;
		
		parent::__construct($message, $code);
	}
	
	public function getType() {
		return $this->type;
	}
}
