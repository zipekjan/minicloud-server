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
			
			'get_path' => new ApiHandlerAction('getPath', 'path'),
			'set_path' => new ApiHandlerAction('setPath', 'path'),
			
			'get_file' => new ApiHandlerAction('getFile', 'file'),
			'set_file' => new ApiHandlerAction('setFile', 'file'),
			
			'download_file' => new ApiHandlerAction('downloadFile'),
			
			'upload_file' => new ApiHandlerAction('uploadFile', 'files')
		);
	}
	
	public function handle($api, $user, $request) {		
		// Load arguments
		$this->api = $api;
		$this->user = $user;
		$this->request = $request;
		$this->actionId = $request->contents('action_id');
		
		// Require user login
		if (!$user) {
			return new ApiResponse("error", $this->actionId, "Unauthorized access.", 401);
		}
		
		// First check if we support such action
		if (!isset($this->actions[$request->action()])) {
			return new ApiResponse("error", $this->actionId, "Uknown method.", 400);
		}
		
		// Load action info
		$type = $this->actions[$request->action()];
		$method = $type->method();
		$response_type = $type->response();
		
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
	
	public function getServerInfo($request) {
		return $this->api->getInfo();
	}
	
	public function setUserInfo($request) {
		$this->user->set($request->contents(), true);
		
		return $this->api->meta()->setUser($this->user);
	}
	
	public function getUserInfo($request) {
		return $this->user->serialize();
	}
	
	public function getPath($request) {
		// Load requested path string
		$path = $request->contents('path');
		$id = $request->contents('id');
		
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
		if ($file_id === null) {
			throw new ApiExcetion("Id is required", 400);
		}
		
		// Load file from meta
		$file = $this->api->meta()->getFileById($this->user, $file_id);
		
		// Uknown file
		if ($file === null) {
			throw new ApiExcetion("Uknown file", 404);
		}
		
		// Return file contents
		return new FileResponse($this->api->storage()->getFile($file, 'rb'));
	}
	
	public function uploadFile($request) {
		// Path to upload files to
		$path = $request->contents('path');

		// Used to override existing files
		$replace = $request->contents('replace');
		
		// Load metapath object
		$path = $this->api->meta()->getPath($this->user, $path);
		
		// Failed to find path in meta
		if ($path === null) {
			throw new ApiExcetion("Unknown path", 404);
		}
		
		// Contains info about each file received
		$result = array();
		
		// Save each file in request
		foreach($request->files() as $ident => $file) {
			$replacing = false;
			
			// Skip broken files
			if ($file->error !== null) {
				$result[$ident] = array('error' => $file->error);
				continue;
			}
					
			// Load meta info
			if ($replace && isset($replace[$ident])) {
				
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
					'mdtime' => time()
				), true);
				
			} else {
				
				// Create new file
				$meta = new MetaFile(array(
					'filename' => $file->name,
					'size' => $file->size,
					'mktime' => time(),
					'mdtime' => time(),
					'path' => $path,
					'user' => $this->user
				));
				
			}
			
			// Save meta info to meta storage
			$meta = $this->api->meta()->setFile($this->user, $meta);
			
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
	
}