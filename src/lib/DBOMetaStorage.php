<?php
class DBOMetaStorage implements MetaStorage
{
	protected $api;
	
	protected $usersTable = 'users';
	protected $filesTable = 'files';
	protected $foldersTable = 'folders';
	
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
			((!empty($options['port'])) ? (';port=' . $options['port']) : '') .
			";dbname={$options['schema']}";
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
			$row['folder_id'] = $parent;
		} elseif ($row['folder_id'] && is_numeric($row['folder_id'])) {
			$row['folder_id'] = $this->getPathById($user, $row['folder_id']);
		}
		
		// Remap meta array
		$data = $row;
		$data['user'] = $user;
		$data['path'] = $row['folder_id'];
		$data['mktime'] = $row['created'];
		$data['mdtime'] = $row['updated'];
		
		return new MetaFile($row);
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
		$data['parent'] = $row['folder_id'];
		$data['user'] = $user;
		$data['mktime'] = $row['created'];
		$data['mdtime'] = $row['updated'];
		
		return new MetaPath($data);
	}
	
	/**
	 * Loads all children for specified path
	 *
	 * @param MetaPath $parent
	 */
	protected function loadChildren($parent) {
		// Get parent user
		$user = $parent->user();
		
		// Load folders
		$prep = $this->pdo->prepare("SELECT * FROM {$this->foldersTable} WHERE user_id = ? AND folder_id = ?");
		$prep->execute(array($id, $parent->meta('id')));
		
		while($row = $prep->fetchArray()) {
			$parent->addPath($this->pathFromRow($user, $row));
		}
		
		// Load files
		$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ? AND folder_id = ?");
		$prep->execute(array($id, $parent->meta('id')));
		
		while($row = $prep->fetchArray()) {
			$parent->addFile($this->fileFromRow($user, $row, $parent));
		}
		
		// Return isn't required, but looks nice
		return $parent;
	}
	
	public function getUser($hash) {
		//@TODO: Maybe use something random to make hash different everytime
		$prep = $this->pdo->prepare("SELECT * FROM {$this->usersTable} WHERE SHA2(CONCAT(login, password), 256) = ?");
		$prep->execute(array($hash));
		
		return $prep->fetchObject();
	}
	
	public function setUser($user) {
		// Get user ID
		if (!$user->id())
			return null;
		
		$id = $user->id();
		
		// Get basic values
		$update = $user->serialize();
		
		// Add meta values
		foreach($user->meta() as $key => $value) {
			$update[$key] = $value;
		}
		
		// Useless
		unset($update['id']);
		
		// Prepare query values
		$update_keys = array();
		$update_values = array();
		foreach($update as $key => $value) {
			$update_keys[] = "$key = ?";
			$update_values[] = $value;
		}
		
		// Add file ID for final condition
		$update_values[] = $id;
		
		// Run query
		$prep = $this->pdo->prepare("UPDATE {$this->usersTable} SET " . implode(", ", $update_keys) . " WHERE id = ?");
		$prep->execute($update_values);
		
		return $user->serialize();
	}
	
	public function getPath($user, $path = null) {
		$id = $user->id();
		
		$parent = new MetaPath(null, $user, null, array('id' => null));
		
		if ($path != null) {
			$prep = $this->pdo->prepare("SELECT * FROM {$this->foldersTable} WHERE user_id = ? AND path = ?");
			$prep->execute(array($id, $path));
			$data = $prep->fetchObject();
			$parent = new MetaPath($data['id'], $user, $path, $data);
			
			if (!$data)
				return null;
		}
		
		return $this->loadChildren($parent);
	}
	
	public function getPathById($user, $path_id) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->foldersTable} WHERE user_id = ? AND id = ?");
		$prep->execute(array($id, $path_id));
		$row = $prep->fetchObject();
		
		if (!$row)
			return null;
		
		$parent = $this->pathFromRow($user, $row);
		
		return $this->loadChildren($parent);
	}
	
	public function getFile($user, $path) {
		throw new Exception("Not yet implemented.");
	}
	
	public function getFileById($user, $file_id) {
		$id = $user->id();
		
		$prep = $this->pdo->prepare("SELECT * FROM {$this->filesTable} WHERE user_id = ? AND id = ?");
		$prep->execute(array($id, $file_id));
		$row = $prep->fetchObject();
		
		if (!$row)
			return null;
		
		return $this->fileFromRow($user, $row);
	}
	
	public function setFile($file) {
		// Values which have different column name
		$keymap = array(
			'path_id' => 'folder_id',
			'mktime' => 'created',
			'mdtime' => 'updated'
		);
		
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
				$update['folder_id'] = $file->path->meta('id');
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
				
				$update_keys[] = "$key = ?";
				$update_values[] = $value;
			}
			
			// Add file ID for final condition
			$update_values[] = $id;
			
			// Run query
			$prep = $this->pdo->prepare("UPDATE {$this->filesTable} SET " . implode(", ", $update_keys) . " WHERE id = ?");
			$prep->execute($update_values);
			
			return $file;
		} else {
			
			// Get file values
			$insert = $file->serialize();
			
			// Add user id (only required for insert)
			$insert['user_id'] = $insert->user()->id();
			
			// Prepare query data
			$insert_keys = array();
			$insert_thumbs = array();
			$insert_values = array();
			
			foreach($insert as $key => $value) {
				if (isset($skipped[$key]))
					continue;
				
				if (isset($keymap[$key]))
					$key = $keymap[$key];
				
				$insert_keys[] = "$key";
				$insert_thumbs[] = "?";
				$insert_values[] = $value;
			}
			
			// Run the damned query
			$prep = $this->pdo->prepare("INSERT INTO {$this->filesTable} (" . implode(", ", $insert_keys) . ") VALUES (" . implode(", ", $insert_thumbs) . ")");
			$prep->execute($insert_values);
			
			// Save DB id, for later use
			$file->set(array('id' => $this->pdo->lastInsertId));
			
			// Return saved file
			return $file;
		}
		
	}
	
	public function setFolder($folder) {
		
	}
}