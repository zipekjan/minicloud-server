<?php
class TestRequest extends Request
{
	public function __construct($data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}