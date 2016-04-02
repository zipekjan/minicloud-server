<?php
class ApiHandler
{
	protected $api;
	protected $user;
	protected $request;
	
	protected $actions;
	protected $responses;
	
	protected $bufferSize = 1024;
	
	public function __construct() {
		// Action => method map
		$this->actions = array(
			'get_server_info' => new ApiHandlerAction('getServerInfo', 'server'),
			
			'get_user' => new ApiHandlerAction('getUserInfo', 'user'),
			'set_user' => new ApiHandlerAction('setUserInfo', 'user'),
			
			'admin_create_user' => new ApiHandlerAction('adminCreateUser', 'user', false, true),
			'admin_delete_user' => new ApiHandlerAction('adminDeleteUser', 'bool', false, true),
			'admin_set_user' => new ApiHandlerAction('adminSetUser', 'user', false, true),
			'admin_get_users' => new ApiHandlerAction('adminGetUsers', 'users', false, true),
			
			'get_path' => new ApiHandlerAction('getPath', 'path'),
			'set_path' => new ApiHandlerAction('setPath', 'path'),
			'create_path' => new ApiHandlerAction('createPath', 'path'),
			'delete_path' => new ApiHandlerAction('deletePath', 'path'),
						
			'get_paths' => new ApiHandlerAction('getPaths', 'paths'),
			'delete_paths' => new ApiHandlerAction('deletePaths', 'bool'),
			
			'get_file' => new ApiHandlerAction('getFile', 'file'),
			'set_file' => new ApiHandlerAction('setFile', 'file'),
			'delete_file' => new ApiHandlerAction('deleteFile', 'bool'),
			'delete_files' => new ApiHandlerAction('deleteFiles', 'bool'),
			'download_file' => new ApiHandlerAction('downloadFile', null, true),
			'upload_file' => new ApiHandlerAction('uploadFile', 'files'),
		);
	}
	
	public function handle($api, $user, $request) {		
		// Load arguments
		$this->api = $api;
		$this->user = $user;
		$this->request = $request;
		$this->actionId = $request->contents('action_id');
		
		// First check if we support such action
		if (!isset($this->actions[$request->action()])) {
			return new ApiResponse("error", $this->actionId, "Uknown method {$request->action()}.", 400);
		}		
		
		// Load action info
		$type = $this->actions[$request->action()];
		$method = $type->method();
		$response_type = $type->response();
		$public = $type->isPublic();
		$adminOnly = $type->isAdminOnly();
				
		// Require user login
		if ((!$public || $this->request->auth()) && !$user) {
			return new ApiResponse("error", $this->actionId, "Unauthorized access.", 401);
		}
		
		// Restrict access to admin only actions
		if ($adminOnly && (!$user || !($user->isAdmin()))) {
			return new ApiResponse("error", $this->actionId, "Unauthorized access.", 401);
		}
		
		// Try execution action, return error on exception
		try {
			
			$data = $this->$method($request);
			
		} catch(ApiExcetion $e) {
			
			// Standard, expected excetion
			return new ApiResponse($e->getType(), $this->actionId, $e->getMessage(), $e->getCode());
			
		} catch(Exception $e) {
			
			// Basic PHP exception, handle as error
			return new ApiResponse('error', $this->actionId, $e->getMessage());
			
		}
		
		// Send result if it's already response
		if ($data instanceof Response) {
			return $data;
		}
		
		// Wrap result in apiobject
		return new ApiResponse($response_type, $this->actionId, $data);
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
	
	private function mkPath($path) {
		// Sanitize path (remove /)
		$path = $this->sanitizePath($path);
		
		// Check for duplicates
		$existing = $this->api->meta()->getPath($this->user, $path);
		
		if ($existing)
			return $existing;
		
		// Load user root
		$parent = $this->api->meta()->getPath($this->user, null);
		
		// Split path
		$parts = explode('/', $path);
		
		// Current path in loop
		$current = "";
		
		// Now advance
		foreach($parts as $part) {
			
			// Don't add slash to root
			if ($current != "")
				$current .= "/";
			
			$current .= $part;
			
			// Check if subpath exists
			$found = null;
			foreach($parent->paths() as $path) {
				if ($path->path() == $current) {
					$found = $this->api->meta()->getPathById($this->user, $path->id());
					break;
				}
			}
			
			// Create new path or just continue
			if ($found) {
				$parent = $found;
			} else {
				$parent = new MetaPath(array(
					'user' => $this->user,
					'parent' => $parent->id(),
					'mktime' => time(),
					'mdtime' => time(),
					'path' => $current
				));
				
				$this->api->meta()->setPath($this->user, $parent);
			}
			
		}
		
		// Last parent is new path
		return $parent;
	}
	
	public function getServerInfo($request) {
		return $this->api->getInfo();
	}
	
	public function setUserInfo($request) {
		
		$this->user->set($request->contents(), true);
		
		return $this->api->meta()->setUser($this->user)->serialize();
	}
	
	public function getUserInfo($request) {
		return $this->user->serialize();
	}
	
	public function createPath($request) {
		
		// Load provided params
		$path = $request->contents('path');
		
		if ($path == null) {
			throw new ApiExcetion("Path is required.", 400);
		}
		
		return $this->mkPath($path)->serialize();
	}
	
	public function setPath($request) {
		// Load provided params
		$id = $request->contents('id');
		$path = $request->contents('path');
		
		if ($id === null && $path === null) {
			throw new ApiExcetion("Identification is required.", 400);
		}
		
		// Normalize
		$id = (int)$id;
		
		// Load file from meta
		if ($path !== null) {
			$path = $this->api->meta()->getPath($this->user, $path);			
		} else {
			$path = $this->api->meta()->getPathById($this->user, $id);
		}
		
		// Unknown
		if ($path === null) {
			throw new ApiExcetion("Uknown path.", 404);
		}
		
		// Load provided params
		$info = $request->contents();
		
		// Apply provided params
		$path->set($info, true);
		
		// Save to meta database
		$this->api->meta()->setPath($this->user, $path);
		
		return $path->serialize();
	}
	
	public function getPath($request) {
		// Load requested path string
		$path = $request->contents('path');
		$id = $request->contents('id');
		$recursive = $request->contents('recursive', false);
		
		// Load metapath object
		if ($id !== null) {
			$path = $this->api->meta()->getPathById($this->user, $id);
		} else {
			$path = $this->api->meta()->getPath($this->user, $path);
		}
		
		// Failed to find path in meta
		if ($path === null) {
			throw new ApiExcetion("Failed to find path", 404);
		}
		
		// Return serialized path info
		return $path->serialize();
	}
	
	public function setFile($request) {
		// Load file id, which is required
		$file_id = (int)$request->contents('id');
		if ($file_id === null) {
			throw new ApiExcetion("Id is required", 400);
		}
		
		// Load file from meta
		$file = $this->api->meta()->getFileById($this->user, $file_id);
		
		// Unknown file
		if ($file === null) {
			throw new ApiExcetion("Uknown file", 404);
		}
		
		$info = $request->contents();
		
		if ($info['public'] != 'false' && $info['public'] != '0' && ($info['public'] || $info['public'] == 'true'))
			$info['public'] = 1;
		
		$file->set($info, true);
		
		$this->api->meta()->setFile($this->user, $file);
		
		return $file->serialize();
	}
	
	public function getFile($request) {
		// Load file id, which is required
		$file_id = (int)$request->contents('id');
		if ($file_id === null) {
			throw new ApiExcetion("Id is required", 400);
		}
		
		// Load file from meta
		$file = $this->api->meta()->getFileById($this->user, $file_id);
		
		// Uknown file
		if ($file === null) {
			throw new ApiExcetion("Uknown file", 404);
		}
		
		// Serialized file info
		return $file->serialize();
	}
	
	public function downloadFile($request) {
		// Load file id, which is required
		$file_id = (int)$request->contents('id');
		$file_hash = null;
		$file_name = null;
		$file_version = (int)$request->contents('version');
		
		if ($file_id === null) {
			throw new ApiExcetion("Id is required", 400);
		}
		
		// File accessed from public, check additional params
		if (!$this->user) {
			$file_hash = $request->contents('hash');
			$file_name = $request->contents('filename');
			
			if ($file_hash === null) {
				throw new ApiExcetion("Hash is required", 400);
			}
			
			if ($file_name === null) {
				throw new ApiExcetion("Filename is required", 400);
			}
			
			$file_hash = strtolower($file_hash);
		}
		
		// Load file from meta
		$file = $this->api->meta()->getFileById($this->user, $file_id);
		
		// Set version if needed
		foreach($file->versions() as $version) {
			if ($version['version'] === $file_version) {
				$file->version($file_version);
				break;
			}
		}
		
		// Uknown file
		if ($file === null ||
			($file_hash !== null && substr(md5($file->id() . $file->checksum()), 0, 8) != $file_hash) ||
			($file_name !== null && $file->filename() != $file_name)) {
			throw new ApiExcetion("Uknown file.", 404);
		}
		
		// Check if file can be accessed
		if (!$this->user && !$file->isPublic()) {
			return new ApiResponse("error", $this->actionId, "Unauthorized access.", 401);
		}
		
		// Return file contents
		return new FileResponse($this->api->storage()->getFile($file, 'rb'), $file->filename(), $file->size());
	}
	
	public function uploadFile($request) {

		// Path to upload files to
		$path = $request->contents('path');

		// Load file specific params
		$replace = $request->contents('replace', array());
		$checksums = $request->contents('checksum', array());
		$encryptions = $request->contents('encryption', array());
		$publics = $request->contents('public', array());
		
		// Validate params
		if (!is_array($replace))
			$replace = array();
		
		if (!is_array($checksums))
			$checksums = array();
		
		if (!is_array($encryptions))
			$encryptions = array();
		
		if (!is_array($publics))
			$publics = array();
		
		// Load metapath object
		//$path = $this->api->meta()->getPath($this->user, $path);
		$path = $this->mkPath($path);
		
		// Failed to find path in meta
		if ($path === null) {
			throw new ApiExcetion("Unknown path", 404);
		}
		
		// Contains info about each file received
		$result = array();
		
		// Save each file in request
		foreach($request->files() as $ident => $file) {
			
			// If we're replacing existing file
			$replacing = false;
			
			// Load file specific params
			$encryption = isset($encryptions[$ident]) ? $encryptions[$ident] : null;
			$checksum = isset($checksums[$ident]) ? $checksums[$ident] : null;
			$public = isset($publics[$ident]) ? $publics[$ident] : false;
			
			// Skip broken files
			if ($file->error !== null) {
				$result[$ident] = array('error' => $file->error);
				continue;
			}
					
			// Load meta info
			if (isset($replace[$ident])) {
				
				// Check if overriden file exists
				$meta = $this->api->meta()->getFileById($this->user, $replace[$ident]);
				
				if (!$meta) {
					$result[$ident] = array('error' => 'Failed to override file.');
					continue;
				}
				
				$replacing = true;
				
				// Apply changes
				$meta->set(array(
					'size' => $file->size,
					'mdtime' => time(),
					'public' => $public,
					'encryption' => $encryption,
					'checksum' => $checksum
				), true);
				
			} else {

				// Create new file
				$meta = new MetaFile(array(
					'filename' => $file->name,
					'size' => $file->size,
					'mktime' => time(),
					'mdtime' => time(),
					'path' => $path,
					'user' => $this->user,
					'encryption' => $encryption,
					'checksum' => $checksum,
					'public' => $public
				));
				
			}
			
			// Save meta info to meta storage
			$meta = $this->api->meta()->setFile($this->user, $meta);
			
			// Add new file version
			$this->api->meta()->addFileVersion($this->user, $meta);
			
			// Load file handle from storage
			$storage = $this->api->storage()->getFile($meta, 'wb');
			$local = fopen($file->tmp, 'rb');
			
			// Check for very rare errors
			if (!$local) {
				
				// Only remove file reference if we weren't replacing
				if (!$replacing)
					$this->api->meta()->deleteFile($this->user, $meta);
				
				$result[$ident] = array('error' => 'Failed to receive file.');
				continue;
			}
			
			// Write to storage
			while(!feof($local)) {
				$storage->write(fread($local, $this->bufferSize));
			}
			
			// Close local file
			$storage->close();
			
			// Add meta info to result
			$result[$ident] = $meta->serialize();
		}

		// Respond with file list
		return $result;
	}
		
	public function deleteFile($request) {
		$id = (int)$request->contents('id');
		
		$file = $this->api->meta()->getFileById($this->user, $id);
		
		if ($file === null) {
			throw new ApiExcetion("Failed to find file.", 404);
		}
		
		$this->api->meta()->deleteFile($this->user, $file);
		$this->api->storage()->deleteFile($file);
		
		return true;
	}
	
	public function deleteFiles($request) {
		$files = $request->contents('files');

		if ($files === null || !is_array($files)) {
			throw new ApiExcetion("No file specified.", 400);
		}
		
		foreach($files as $id) {
			$file = $this->api->meta()->getFileById($this->user, $id);
			
			if ($file === null) {
				throw new ApiExcetion("Failed to find file.", 404);
			}
			
			$this->api->meta()->deleteFile($this->user, $file);
			$this->api->storage()->deleteFile($file);
		}
		
		return true;
	}
	
	public function deletePaths($request) {
	
		throw new Exception("Not yet implemented.");
	
	}
	
	public function getPaths($request) {
	
		$list = array();
		$paths = $this->api->meta()->getPaths($this->user);
		
		foreach($paths as $path)
			$list[] = $path->serialize();
			
		return $list;
	
	}
	
	public function adminCreateUser($request) {
		
		$user = new MetaUser($request->contents());
		
		$this->api->meta()->setUser($user);
		
		$path = new MetaPath(array(
			'user' => $user,
			'path' => '',
			'mktime' => time(),
			'mdtime' => time()
		));
		
		$this->api->meta()->setPath($user, $path);
		
		return $user->serialize();
		
	}
	
	public function adminDeleteUser($request) {
		
		$id = $this->request->contents('id', null);
				
		$user = $this->api->meta()->getUserById($id);
		
		if ($id === null || $user === null) {
			throw new ApiExcetion("Failed to find user.", 400);
		}
		
		$this->api->meta()->deleteUser($user);
		
		return true;
		
	}
	
	public function adminSetUser($request) {
		
		$id = $this->request->contents('id', null);
				
		$user = $this->api->meta()->getUserById($id);
		
		if ($id === null || $user === null) {
			throw new ApiExcetion("Failed to find user.", 400);
		}
		
		$user->set($this->request->contents(), true);
		
		$this->api->meta()->setUser($user);
		
		return $user->serialize();
		
	}
	
	public function adminGetUsers($request) {
		
		$list = array();
		$users = $this->api->meta()->getUsers();
		
		foreach($users as $user) {
			$list[] = $user->serialize();
		}
		
		return $list;
		
	}
	
}