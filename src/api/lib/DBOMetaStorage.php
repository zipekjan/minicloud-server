<?php
class DBOMetaStorage implements MetaStorage
{
	protected $api;
	
	protected $usersTable = 'users';
	protected $filesTable = 'files';
	protected $pathsTable = 'paths';
	protected $versionsTable = 'versions';
	
	public function __construct($api) {
		$this->api = $api;
		$this->pdo = new PDO(
			$this->getDNS($this->api->config('database')),
			$this->api->config('database')['username'],
			$this->api->config('database')['password']
		);
	}
	
	protected function getDNS($config) {
		return "{$config['driver']}:host={$config['host']}" .
			((!empty($config['port'])) ? (';port=' . $config['port']) : '') .
			";dbname={$config['schema']}";
	}
	
	/**
	 * Creates MetaFile from DB row
	 *
	 * @param MetaUser $user owner
	 * @param array $row data
	 * @param MetaPath $parent OPTIONAL parent object, false to stop autoload
	 * @return MetaFile
	 */
	protected function fileFromRow($user, $row, $parent = null) {
		// Autoload parent metapath
		if ($parent !== false) {
			if ($parent != null) {
				$row['path_id'] = $parent;
			} elseif ($row['path_id'] && is_numeric($row['path_id'])) {
				$row['path_id'] = $this->getPathById($user, $row['path_id']);
			}
		}
		
		// Remap meta array
		$data = $row;
		$data['user'] = $user;
		$data['path'] = $row['path_id'];
		$data['versions'] = array();
	
		// Load versions
		$prep = $this->pdo->prepare("SELECT * FROM {$this->versionsTable} WHERE file_id = ? ORDER BY id ASC");
		$prep->execute(array($row['id']));
		
		while($ver = $prep->fetch()) {
			$data['versions'][] = array(
				'version' => $ver['id'],
				'created' => $ver['created']
			);
			$data['version'] = $ver['id'];
		}
		
		return new MetaFile($data);
	}
	
	/**
	 * Creates MetaPath from DB row
	 *
	 * @param MetaUser $user
	 * @param array $row
	 * @return MetaPath
	 */
	protected function pathFromRow($user, $row) {
		// Remap meta array
		$data = $row;
		$data['parent'] = $row['parent_id'];
		$data['user'] = $user;
		
		return new MetaPath($data);
	}
	
	/**
	 * Loads all children for specified path
	 *
	 * @param MetaPath $parent
	 * @param bool $recursive OPTIONAl (default false)
	 */
	protected function loadChildren($parent, $recursive = false) {
		// Get parent user
		$user = $parent->user();
		$id = $user->id();
		$parent_id = $parent->meta('id');
		
		// Load folders
		
		// Prepare query
		if ($parent_id === null) {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->pathsTable} WHERE user_id = ? AND parent_id is NULL");
		} else {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->pathsTable} WHERE user_id = ? AND parent_id = ?");
		}
		
		// Bind values
		$prep->bindValue(1, $id);
		if ($parent_id !== null) {
			$prep->bindValue(2, $parent->meta('id'), PDO::PARAM_INT);
		}
		
		// Run the query
		if (!$prep->execute()) {
			throw new Exception("Failed to execute paths query.");
		}
		
		while($row = $prep->fetch()) {
			$parent->addPath($this->pathFromRow($user, $row));
		}
		
		// Load files
		
		// Prepare query
		if ($parent_id === null) {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ? AND path_id is NULL");
		} else {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ? AND path_id = ?");
		}

		// Bind values
		$prep->bindValue(1, $id);
		if ($parent_id !== null) {
			$prep->bindValue(2, $parent->meta('id'), PDO::PARAM_INT);
		}
		
		// Run the query
		if (!$prep->execute()) {
			throw new Exception("Failed to execute files query.");
		}
		
		while($row = $prep->fetch()) {
			$parent->addFile($this->fileFromRow($user, $row, $parent));
		}
		
		// Load subdirectories if required
		if ($recursive) {
			foreach($parent->paths() as $path) {
				$this->loadChildren($path, $recursive);
			}
		}
		
		// Return isn't required, but looks nice
		return $parent;
	}
	
	public function getUser($hash) {

		$prep = $this->pdo->prepare("SELECT * FROM {$this->usersTable} WHERE SHA2(CONCAT(name, password), 256) = ?");
		$prep->execute(array($hash));
		
		$data = $prep->fetch();
		if ($data) {
			return new MetaUser($data);
		}
		
		return null;
	}
	
	public function getUserById($id) {
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->usersTable} WHERE id = ?");
		$prep->execute(array($id));
		
		$data = $prep->fetch();
		if ($data) {
			return new MetaUser($data);
		}
		
		return null;
		
	}
	
	public function getUsers() {
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->usersTable}");
		$prep->execute();
		
		$users = array();
		
		while($row = $prep->fetch()) {
			$users[] = new MetaUser($row);
		}
		
		return $users;
		
	}
	
	public function deleteUser($user) {
		
		$prep = $this->pdo->prepare("DELETE FROM {$this->usersTable} WHERE id = ? LIMIT 1");
		return $prep->execute(array($user->id()));
		
	}
	
	public function setUser($user) {
		
		$this->metaSet($user, $user);
		return $user;
		
	}
	
	private function sanitizePath($path) {
		$path = preg_replace('/\/{2,}/', "/", $path);
		
		if (substr($path, 0, 1) == "/") {
			$path = substr($path, 1);
		}
		
		if (substr($path, strlen($path) - 1, 1) == "/") {
			$path = substr($path, 0, strlen($path) - 1);
		}
		
		return $path;
	}
	
	public function getPath($user, $path = null, $recursive = false) {
		$id = $user->id();
		
		if ($path) {
			$path = $this->sanitizePath($path);
		}

		$prep = $this->pdo->prepare("SELECT * FROM {$this->pathsTable} WHERE user_id = ? AND path = ?");
		$prep->execute(array($id, (string)$path));
		$data = $prep->fetch();

		if (!$data)
			return null;

		return $this->loadChildren($this->pathFromRow($user, $data), $recursive);
	}
	
	public function getPathById($user, $path_id, $recursive = false) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->pathsTable} WHERE user_id = ? AND id = ?");
		$prep->execute(array($id, $path_id));
		$row = $prep->fetch();
		
		if (!$row)
			return null;
		
		return $this->loadChildren($this->pathFromRow($user, $row), $recursive);
	}
	
	public function deletePath($user, $path) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("DELETE FROM {$this->pathsTable} WHERE user_id = ? AND id = ? LIMIT 1");
		return $prep->execute(array($id, $path->id()));
	}
	
	public function getFileById($user, $file_id, $parent = true) {
		$id = null;
		if ($user != null) {
			$id = $user->id();
		}
		
		$prep = null;
		
		if ($id !== null) {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ? AND id = ?");
			$prep->execute(array($id, $file_id));
		} else {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE id = ?");
			$prep->execute(array($file_id));
		}
		
		$row = $prep->fetch();
		
		if (!$row) {
			return null;
		}
		
		return $this->fileFromRow($user, $row, $user !== null && $parent ? null : false);
	}
	
	public function deleteFile($user, $file) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("DELETE FROM {$this->filesTable} WHERE user_id = ? AND id = ? LIMIT 1");
		return $prep->execute(array($id, $file->id()));
	}
	
	private function metaUpdate($user, $meta) {
		
		// Table used
		$table = null;
		
		// Skiped serialized data
		$skipped = array();
		
		// Used to identify file in DB
		$id = $meta->meta('id');
		
		// Get basic file values
		$update = $meta->serialize();
		
		// Updated behaviour based on meta object type
		if ($meta instanceof MetaFile) {
			
			$skipped = array(
				'id' => true,
				'path' => true,
				'versions' => true,
				'version' => true,
				'user' => true
			);
			
			$table = $this->filesTable;
			
			// Add parent
			if ($meta->path()) {
				$update['path_id'] = $meta->path()->meta('id');
			}
			
		} else if ($meta instanceof MetaPath) {
			
			$skipped = array(
				'id' => true,
				'files' => true,
				'paths' => true,
				'user' => true,
				'parent' => true
			);
			
			$table = $this->pathsTable;
			
		} else if ($meta instanceof MetaUser) {
			
			$skipped = array(
				'id' => true
			);
			
			$table = $this->usersTable;
			
		}
		
		// Useless
		unset($update['id']);
		
		// Prepare query values
		$update_keys = array();
		$update_values = array();
		foreach($update as $key => $value) {
			if (isset($skipped[$key]))
				continue;

			if (isset($keymap[$key]))
				$key = $keymap[$key];
			
			$update_keys[] = "`$key` = ?";
			$update_values[] = $value;
		}
		
		// Add file ID for final condition
		$update_values[] = $id;
		
		// Run query
		if ($user instanceof MetaUser) {
			$prep = $this->pdo->prepare("UPDATE $table SET " . implode(", ", $update_keys) . " WHERE id = ?");
		} else {
			$update_values[] = $user->id();
			$prep = $this->pdo->prepare("UPDATE $table SET " . implode(", ", $update_keys) . " WHERE id = ? AND user_id = ?");
		}

		$prep->execute($update_values);
	}
	
	public function metaInsert($user, $meta) {
		
		// Used table
		$table = null;
		
		// Skipped serialized values
		$skipped = array();
		
		// Path has different values
		if ($meta instanceof MetaPath) {
			
			$skipped = array(
				'id' => true,
				'files' => true,
				'paths' => true
			);
			
			$table = $this->pathsTable;
			
		} else if ($meta instanceof MetaFile) {
			
			$skipped = array(
				'id' => true,
				'path' => true,
				'versions' => true,
				'version' => true
			);
			
			$table = $this->filesTable;
			
		} else if ($meta instanceof MetaUser) {
			
			$skipped = array(
				'id' => true
			);
			
			$table = $this->usersTable;
			
		}
		
		// Get serialized values
		$insert = $meta->serialize();
		
		// Add user id (only required for insert)
		if (!($meta instanceof MetaUser))
			$insert['user_id'] = $meta->user()->id();
		
		// Prepare query data
		$insert_keys = array();
		$insert_thumbs = array();
		$insert_values = array();
		
		foreach($insert as $key => $value) {
			if (isset($skipped[$key]))
				continue;
			
			if (isset($keymap[$key]))
				$key = $keymap[$key];
			
			$insert_keys[] = "`$key`";
			$insert_thumbs[] = "?";
			$insert_values[] = $value;
		}
		
		// Run the damned query
		$prep = $this->pdo->prepare("INSERT INTO $table (" . implode(", ", $insert_keys) . ") VALUES (" . implode(", ", $insert_thumbs) . ")");
		if (!$prep->execute($insert_values)) {
			throw new Exception("Failed to save meta data. " . print_r($prep->errorInfo(), true));
		}
		
		// Save DB id, for later use
		$meta->set(array('id' => $this->pdo->lastInsertId()));
		
	}
	
	private function metaSet($user, $meta) {

		// Check if meta object is already saved
		if ($meta->id()) {

			// Only update values
			$this->metaUpdate($user, $meta);
			
		} else {
			
			// Create new meta item
			$this->metaInsert($user, $meta);
			
		}
		
		return $meta;
		
	}
	
	public function setFile($user, $file) {		
	
		return $this->metaSet($user, $file);
		
	}
	
	public function setPath($user, $path) {
		
		return $this->metaSet($user, $path);
		
	}
	
	public function getFiles($user) {
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ?");
		$prep->execute(array($user->id()));
		
		$files = array();
		
		while($row = $prep->fetch()) {
			$files[] = $this->fileFromRow($user, $row, false);
		}
		
		return $files;
		
	}
	
	public function getPaths($user) {
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->pathsTable} WHERE user_id = ?");
		$prep->execute(array($user->id()));
		
		$paths = array();
		
		while($row = $prep->fetch()) {
			$paths[] = $this->pathFromRow($user, $row);
		}
		
		return $paths;
		
	}
	
	public function addFileVersion($user, $file) {
		
		$prep = $this->pdo->prepare("INSERT INTO {$this->versionsTable} (file_id, created) VALUES (?, ?)");
		if (!$prep->execute(array($file->id(), time()))) {
			throw new Exception("Failed to save meta data. " . print_r($prep->errorInfo(), true));
		}
		
		$version = $this->pdo->lastInsertId();
		
		$versions = $file->versions();
		$versions[$version] = array(
			'version' => $version,
			'created' => time()
		);
		
		$file->versions($versions);
		$file->version($version);
		
	}
	
}