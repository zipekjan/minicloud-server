<?php
class ArrayWrapper
{
	public function __construct($array) {
		if (!is_array($array)) {
			$this->container = array();
			foreach(get_object_vars($array) as $key => $value) {
				$this->container[$key] = $value;
			}	
		} else {
			$this->container = $array;
		}
	}
	
	public function get($key, $default=null) {
		if (isset($this->container[$key]) || array_key_exists($key, $this->container))
			return $this->container[$key];
		return $default;
	}
}