<?php
class TestMetaStorage implements MetaStorage {
	public function __construct($api) {
		$this->api = $api;
		
		$this->users = array();
		$this->files = array();
		$this->paths = array();
		$this->versions = array();
	}
	
	public function fill() {
		$this->users[1] = new MetaUser(array(
			'id' => 1,
			'name' => 'admin', 
			'email' => 'admin@admin',
			'password' => hash('sha256', 'admin'),
			'key' => hash('sha256', 'admin'),
			'keyEncryption' => null,
			'admin' => true
		));
		$this->users[2] = new MetaUser(array(
			'id' => 2,
			'name' => 'user', 
			'email' => 'user@user',
			'password' => hash('sha256', 'user'),
			'key' => hash('sha256', 'user'),
			'keyEncryption' => null,
			'admin' => false
		));
		
		$id = 1;
		$fid = 1;
		
		foreach($this->users as $user) {
			$this->paths[$id] = new MetaPath(array(
				'id' => $id,
				'user' => $user,
				'path' => '',
				'mktime' => time(),
				'mdtime' => time()
			));
						
			$this->versions[$fid] = array('version' => $fid, 'created' => time());
			
			$this->files[$fid] = new MetaFile(array(
				'id' => $fid,
				'path' => $this->paths[$id],
				'user' => $user,
				'size' => mt_rand(1,1E5),
				'filename' => 'file.ext',
				'encryption' => null,
				'checksum' => md5(mt_rand(1,1E5)),
				'mktime' => time(),
				'mdtime' => time(),
				'public' => true,
				'versions' => array( $this->versions[$fid] ),
				'version' => $fid
			));
			
			$fid++;
			$id++;
		}
		
	}
	
	public function getUser($hash) {
		foreach($this->users as $user) {
			if (hash('sha256', $user->name() . $user->password()) == $hash)
				return $user;
		}
		return null;
	}

	public function setUser($user) {
		if (isset($this->users[$user->id()])) {
			$dd = $this->users[$user->id()];
			$dd->set($user->serialize());
		} else {
			$this->users[$user->id()] = $user;
		}
		return $this->users[$user->id()];
	}
	
	public function getPath($user, $path = null, $recursive = false) {
		foreach($this->paths as $path) {
			if ($path->user()->id()== $user->id())
				return $path;
		}
		return null;
	}
	
	public function getPathById($user, $id, $recursive = false) {
		return @$this->paths[$id];
	}

	public function setPath($user, $folder) {
		if (isset($this->paths[$folder->id()])) {
			$dd = $this->paths[$folder->id()];
			$dd->set($folder->serialize());
		} else {
			$this->paths[$folder->id()] = $folder;
		}
		return $this->paths[$folder->id()];
	}
	
	public function deletePath($user, $folder) {
		unset($this->paths[$folder->id()]);
	}

	public function getFileById($user, $id) {
		return @$this->files[$id];
	}

	public function setFile($user, $file) {
		if (isset($this->files[$file->id()])) {
			$dd = $this->files[$file->id()];
			$dd->set($file->serialize());
		} else {
			$this->files[$file->id()] = $file;
			$file->path()->addFile($file);
		}
		return $this->files[$file->id()];
	}
	
	public function deleteFile($user, $file) {
		unset($this->files[$file->id()]);
	}

	public function addFileVersion($user, $file) {
		if (!$file)
			throw new Exception("Trying to add version to undefined file.");
		
		$last = end($this->versions);
		$id = $last['version'] + 1;
		$this->versions[$id] = array('version' => $id, 'created' => time());
		$versions = $file->versions();
		$versions[$id] = $this->versions[$id];
		$file->versions($versions);
		$file->version($id);
	}
	
	public function users() {
		return $this->users;
	}
	
	public function paths() {
		return $this->paths;
	}
	
	public function files() {
		return $this->files;
	}
	
	public function versions() {
		return $this->versions;
	}
}