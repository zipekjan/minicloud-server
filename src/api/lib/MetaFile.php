<?php
/**
 * Class representing file fetched from meta storage
 */
class MetaFile
{
	///@var mixed $id file identifier
	protected $id;
	
	///@var MetaUser $user file owner
	protected $user;
	
	///@var string $filename name of this file
	protected $filename;
	
	///@var int $mktime unix timestamp of file creation
	protected $mktime;
	///@var int $mdtime unix timestamp of last file modification
	protected $mdtime;
	
	///@var int $size size of file in bytes
	protected $size;
	
	///@var string $encryption data about file encryption
	protected $encryption;
	
	///@var MetaPath $path parent path of this file
	protected $path;
	
	///@var string $checksum file md5 checksum
	protected $checksum;
	
	///@var bool $isPublic is file shareable
	protected $isPublic;
	
	///@var array $meta meta data specific for meta storage
	protected $meta = array();
		
	public function __construct($data) {
		$this->set($data);
	}
	
	public function set($meta, $restricted = false) {
		$data = new ArrayWrapper($meta);

		// These can't be updated by user
		if (!$restricted) {
			$this->id = (int)$data->get('id', $this->id);
			$this->user = $data->get('user', $this->user);
			$this->size = $data->get('size', $this->size);
			$this->checksum = $data->get('checksum', $this->checksum);
			$this->meta = $meta;
		}
		
		// These can be updated by user
		$this->path = $data->get('path', $this->path);
		$this->filename = $data->get('filename', $this->filename);
		$this->encryption = $data->get('encryption', $this->encryption);
		$this->mktime = (int)$data->get('mktime', $this->mktime);
		$this->mdtime = (int)$data->get('mdtime', $this->mdtime);
		$this->isPublic = (int)$data->get('public', $this->isPublic);
		
		return $this;
	}
	
	/**
	 * Returns meta data
	 *
	 * @param string $key OPTIONAL
	 * @return mixed if key is specified, returns meta value specified by key, else, returns all metadata
	 */
	public function meta($key = null) {
		if ($key === null)
			return $this->meta;
		return isset($this->meta[$key]) ? $this->meta[$key] : null;
	}
	
	/**
	 * Serializes this object to associative array
	 *
	 * @return array serialized data about this file
	 */
	public function serialize() {
		return array(
			'id' => $this->id,
			'filename' => $this->filename,
			'size' => $this->size,
			'encryption' => $this->encryption,
			'mktime' => $this->mktime,
			'mdtime' => $this->mdtime,
			'public' => $this->isPublic,
			'path' => $this->path->path(),
			'path_id' => $this->path->id()
		);
	}

	public function id() {
		return $this->id;
	}
	
	public function filename() {
		return $this->filename;
	}
	
	public function path() {
		return $this->path;
	}
	
	public function user() {
		return $this->user;
	}
	
	public function size() {
		return $this->size;
	}
	
	public function isPublic() {
		return $this->isPublic;
	}
	
	public function checksum() {
		return $this->checksum;
	}
	
}