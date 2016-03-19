<?php
class DBOMetaStorage implements MetaStorage
{
	protected $api;
	
	protected $usersTable = 'users';
	protected $filesTable = 'files';
	protected $pathsTable = 'paths';
	
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
	 * @param MetaPath $parent OPTIONAL parent object (prevents autoload)
	 * @return MetaFile
	 */
	protected function fileFromRow($user, $row, $parent = null) {
		// Autoload parent metapath
		if ($parent != null) {
			$row['path_id'] = $parent;
		} elseif ($row['path_id'] && is_numeric($row['path_id'])) {
			$row['path_id'] = $this->getPathById($user, $row['path_id']);
		}
		
		// Remap meta array
		$data = $row;
		$data['user'] = $user;
		$data['path'] = $row['path_id'];

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
			foreach($parent->files() as $file) {
				$this->loadChildren($file, $recursive);
			}
		}
		
		// Return isn't required, but looks nice
		return $parent;
	}
	
	public function getUser($hash) {
		//@TODO: Maybe use something random to make hash different everytime
		$prep = $this->pdo->prepare("SELECT * FROM {$this->usersTable} WHERE SHA2(CONCAT(name, password), 256) = ?");
		$prep->execute(array($hash));
		
		$data = $prep->fetch();
		if ($data) {
			return new MetaUser($data);
		}
		
		return null;
	}
	
	public function setUser($user) {
		// Get user ID
		if (!$user->id())
			return null;
		
		$id = $user->id();
		
		// Get basic values
		$update = $user->serialize();
		
		// Useless
		unset($update['id']);
		
		// Prepare query values
		$update_keys = array();
		$update_values = array();
		foreach($update as $key => $value) {
			$update_keys[] = "`$key` = ?";
			$update_values[] = $value;
		}
		
		// Add file ID for final condition
		$update_values[] = $id;

		// Run query
		$prep = $this->pdo->prepare("UPDATE {$this->usersTable} SET " . implode(", ", $update_keys) . " WHERE id = ?");
		$prep->execute($update_values);
		
		return $user->serialize();
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
	
	public function getFile($user, $path) {
		throw new Exception("Not yet implemented.");
	}
	
	public function getFileById($user, $file_id) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ? AND id = ?");
		$prep->execute(array($id, $file_id));
		$row = $prep->fetch();
		
		if (!$row)
			return null;
		
		return $this->fileFromRow($user, $row);
	}
	
	public function deleteFile($user, $file) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("DELETE FROM {$this->filesTable} WHERE user_id = ? AND id = ? LIMIT 1");
		return $prep->execute(array($id, $file->id()));
	}
	
	public function setFile($user, $file) {
		// Values which have different column name
		$keymap = array();
		
		// Values which shouldn't be saved to DB
		$skipped = array(
			'id' => true,
			'path' => true
		);
		
		// Either we're creating or updating
		if ($file->meta('id')) {
			// Used to identify file in DB
			$id = $file->meta('id');
			
			// Get basic file values
			$update = $file->serialize();
			
			// Add meta values
			foreach($file->meta() as $key => $value) {
				$update[$key] = $value;
			}
			
			// Add parent
			if ($file->path) {
				$update['path_id'] = $file->path()->meta('id');
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
			$update_values[] = $user->id();
			
			// Run query
			$prep = $this->pdo->prepare("UPDATE {$this->filesTable} SET " . implode(", ", $update_keys) . " WHERE id = ? AND user_id = ?");
			$prep->execute($update_values);
			
			return $file;
		} else {
			
			// Get file values
			$insert = $file->serialize();
			
			// Add user id (only required for insert)
			$insert['user_id'] = $file->user()->id();
			
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
			$prep = $this->pdo->prepare("INSERT INTO {$this->filesTable} (" . implode(", ", $insert_keys) . ") VALUES (" . implode(", ", $insert_thumbs) . ")");
			if (!$prep->execute($insert_values)) {
				print_r($prep->errorInfo());
				throw new Exception("Failed to save file.");
			}
			
			// Save DB id, for later use
			$file->set(array('id' => $this->pdo->lastInsertId()));
			
			// Return saved file
			return $file;
		}
		
	}
	
	public function setFolder($user, $folder) {
		
	}
}