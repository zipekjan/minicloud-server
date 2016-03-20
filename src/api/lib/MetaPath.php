<?php
/**
 * Class representing path from storage
 */
class MetaPath
{
	protected $id;
	
	protected $parent;
	
	protected $path;
	protected $checksum;
	protected $mktime;
	protected $mdtime;
	
	protected $user;
	protected $meta = array();
	
	protected $files = array();
	protected $paths = array();
	
	public function __construct($data) {
		$this->set($data);
	}
	
	public function set($meta, $restricted = false) {
		$data = new ArrayWrapper($meta);
		
		if (!$restricted) {
			$this->id = $data->get('id', $this->id);
			$this->user = $data->get('user', $this->user);
			$this->path = $data->get('path', $this->path);
			$this->checksum = $data->get('checksum', $this->checksum);
			$this->meta = $meta;
		}
		
		$this->parent = $data->get('parent', $this->parent);
		$this->mktime = $data->get('mktime', $this->mktime);
		$this->mdtime = $data->get('mdtime', $this->mdtime);
		
		return $this;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function path($value = null) {
		if ($value !== null) {
			$this->path = $value;
			return $this;
		}
		return $this->path;
	}
	
	public function user() {
		return $this->user;
	}
	
	public function parent() {
		return $this->parent;
	}
	
	public function addFile($file) {
		$this->files[] = $file;
	}
	
	public function addPath($file) {
		$this->paths[] = $file;
	}
	
	public function files() {
		return $this->files;
	}
	
	public function paths() {
		return $this->paths;
	}
	
	public function meta($key = null) {
		if ($key === null)
			return $this->meta;
		return isset($this->meta[$key]) ? $this->meta[$key] : null;
	}
	
	public function json() {
		return json_encode($this->serialize());
	}
	
	public function serialize() {
		$paths = array();
		$files = array();
		
		foreach($this->paths as $path)
			$paths[] = $path->serialize();
		foreach($this->files as $file)
			$files[] = $file->serialize();
		
		return array(
			'id' => $this->id,
			'parent_id' => $this->parent,
			'path' => $this->path,
			'mktime' => $this->mktime,
			'mdtime' => $this->mdtime,
			'checksum' => $this->checksum,
			'files' => $files,
			'paths' => $paths
		);
	}
}