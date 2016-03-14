<?php
/**
 * Class containing user data
 */
class MetaUser
{	
	protected $meta;
	
	protected $id;
	protected $login;
	protected $name;
	protected $email;
	protected $password;
	protected $key;
	protected $admin;
	
	public function __construct($data) {
		$this->set($data);
	}
	
	public function set($meta, $restricted = false) {
		$data = new ArrayWrapper($meta);
		
		if (!$restricted) {
			$this->id = $data->get('id');
			$this->name = $data->get('name');
			$this->admin = $data->get('admin');
		}
		
		$this->email = $data->get('email');
		$this->password = $data->get('password');
		$this->key = $data->get('key');
		
		$this->meta = $meta;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function name() {
		return $this->name;
	}
	
	public function email() {
		return $this->email;
	}
	
	public function password() {
		return $this->password;
	}
	
	public function meta($key = null) {
		if ($key === null)
			return $this->meta;
		return isset($this->meta[$key]) ? $this->meta[$key] : null;
	}
	
	public function isAdmin() {
		return $this->admin;
	}
	
	public function serialize() {
		return array(
			'id' => $this->id,
			'name' => $this->name,
			'email' => $this->email,
			'key' => $this->key,
			'admin' => $this->admin
		);
	}
}
