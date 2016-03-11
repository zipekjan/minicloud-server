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

class Api
{
	protected $configFile = null;
	protected $config = array();
	protected $handler = null;
	
	public function __construct($config_file = './config.php') {
		$this->loadConfig($config_file);
		$this->handler = new ApiHandler();
	}
	
	protected function loadConfig($config_file) {
		$this->configFile = $config_file;
		$this->config = require_once($config_file);
		
		$this->meta = new {$this->config('meta')}($this);
		$this->storage = new {$this->config('storage')}($this);
	}
	
	public function config($key) {
		if ($key === null)
			return $this->config;
		return isset($this->config[$key]) ? $this->config[$key] : null;
	}
	
	public function handle($request) {
		$this->request = $request;
		
		$user = $this->meta->getUser($this->request->auth());
		if ($user === null)
			return new Response(null, 401);
		
		return $this->handler->handle($this, $user, $this->request);
	}
	
	public function getInfo() {
		return array(
			'name' => $this->config('name'),
			'description' => $this->config('description'),
			'logo' => $this->config('logo'),
			'nice_url' => $this->config('nice_url')
		);
	}
}

class ApiExcetion extends Exception
{
	protected $type;
	
	public function __construct($message, $code=500, $type='error') {
		$this->type = $type;
		
		parent::__construct($message, $code);
	}
	
	public function getType() {
		return $this->type;
	}
}


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
			'get_server_info' => new ApiHandlerAction('getServerInfo', 'server_info'),
			
			'get_user_info' => new ApiHandlerAction('getUserInfo', 'user'),
			'set_user_info' => new ApiHandlerAction('setUserInfo', 'user'),
			
			'get_path' => new ApiHandlerAction('getPath', 'path'),
			'set_path' => new ApiHandlerAction('setPath', 'path'),
			
			'get_file' => new ApiHandlerAction('getFile', 'file'),
			'set_file' => new ApiHandlerAction('setFile', 'file'),
			'download_file' => new ApiHandlerAction('downloadFile'),
			'upload_file' => new ApiHandlerAction('uploadFile', 'files')
		);
	}
	
	public function handle($api, $user, $request) {
		// First check if we support such action
		if (!isset($this->actions[$request->action])) {
			return new Response(null, 400);
		}
		
		// Load arguments
		$this->api = $api;
		$this->user = $user;
		$this->request = $request;
		$this->actionId = $request->contents('action_id');
		
		// Load action info
		$type = $this->actions[$request->action];
		$method = $type->method();
		$response_type = $type->respose();
		
		// Try execution action, return error on exception
		try {
			$data = $this->$method($request);
		} catch(Excetion $e) {
			// Standard, expected excetion
			if ($e instanceof ApiExcetion) {
				return ApiResponse($e->getType(), $this->actionId, $e->getMessage(), $e->getCode());
			}
			
			// Basic PHP exception, handle as error
			return ApiResponse('error', $this->actionId, $e->getMessage());
		}
		
		// Send result if it's already response
		if ($data instanceof Response) {
			return $data;
		}
		
		// Wrap result in apiobject
		return ApiResponse($response_type, $this->actionId, $data);
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
		// Load requested path string (null is root)
		$path = $request->contents('path');
		
		// Load metapath object
		$path = $this->api->meta()->getPath($this->user, $path);
		
		// Failed to find path in meta
		if ($path === null)
			throw new ApiExcetion("Failed to find path", 404);
			//return new ApiResponse("Unknown path", 404);
		
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
		return new FileResponse($this->api->storage->getFile($file, 'rb'));
	}
	
	public function uploadFile($user, $request) {
		// Path to upload files to
		$path = $request->contents('path');
		
		// Load metapath object
		$path = $this->api->meta()->getPath($this->user, $path);
		
		// Failed to find path in meta
		if ($path === null) {
			throw new ApiExcetion("Unknown path", 404);
		}
		
		// Contains info about each file received
		$result = array();
		
		// Save each file in request
		foreach($request->files as $ident => $file) {
			// Skip broken files
			if ($file->error !== null) {
				$result[$ident] = array('error' => $file->error);
				continue;
			}
			
			// Create meta info
			$meta = new MetaFile(array(
				'filename' => $file->name,
				'size' => $file->size,
				'mktime' => time(),
				'mdtime' => time(),
				'path' => $path,
				'user' => $user
			));
			
			// Copy file to storage
			$storage = $this->api->storage()->getFile($meta, 'wb');
			$local = fopen($meta->tmp, 'rb');
			
			// Check for very rare errors
			if (!$local) {
				$result[$ident] = array('error' => 'Failed to receive file.');
				continue;
			}
			
			// Write to storage
			while(!feof($local)) {
				$storage->write(fread($local, $this->bufferSize));
			}
			
			// Close local file
			$storage->close();
			
			// Save meta info to meta storage
			$meta = $this->api->meta()->setFile($meta);
			
			// Add meta info to result
			$result[$ident] = $meta->serialize();
		}
		
		// Respond with file list
		return $result;
	}
	
}

class ApiHandlerAction
{
	protected $method;
	protected $response;
	
	public function __construct($method, $respose = null) {
		$this->method = $method;
		$this->respose = $response;
	}
	
	public function method() {
		return $method;
	}
	
	public function respose() {
		return $respose;
	}
}


class ApiResponse extends Response
{
	public function __construct($type, $action_id, $response, $code = 200) {
		$contents = json_encode(
			'type' => $type,
			'action_id' => $action_id,
			'data' => $respose
		);
		
		parent::__construct($contents, $code);
	}
}

/**
 * Class representing file fetched from meta storage
 */
class MetaFile
{
	///@var mixed $id file identifier
	protected $id;
	
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
	
	///@var array $meta meta data specific for meta storage
	protected $meta = array();
		
	public function __construct($data) {
		$this->set($data);
	}
	
	public function set($meta, $restricted = false) {
		$data = new ArrayWrapper($meta);
		
		// These can't be updated by user
		if (!$restricted) {
			$this->id = $data->get('id', $this->id);
			$this->user = $data->get('user', $this->user);
			$this->size = $data->get('size', $this->size);
			$this->checksum = $data->get('checksum', $this->checksum);
			$this->meta = $data;
		}
		
		// These can be updated by user
		$this->path = $data->get('path', $this->path);
		$this->filename = $data->get('filename', $this->filename);
		$this->encryption = $data->get('encryption', $this->encryption);
		$this->mktime = $data->get('mktime', $this->mktime);
		$this->mdtime = $data->get('mdtime', $this->mdtime);
		
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
			'path' => $this->path->path(),
			'path_id' => $this->path->id()
		);
	}

	public function id() {
		return $this->id;
	}
	
	public function path() {
		return $this->path;
	}
	
	public function user() {
		return $this->user;
	}
}

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

/**
 * This interface represents storage of metadata of users and files
 */
interface MetaStorage
{
	/**
	 * Fetches user from metadata storage
	 *
	 * @param string $hash sha256 made of user credentials
	 * @return MetaUser user data
	 */
	public function getUser($hash);
	
	/**
	 * Fetches data about specified path
	 *
	 * @param MetaUser $user path owner
	 * @param string $path path to fetch
	 * @returm MetaPath
	 */
	public function getPath($user, $path = null);
	
	/**
	 * Saves user data
	 *
	 * @param MetaUser $user
	 */
	public function setUser($user);
	
	/**
	 * Saves file data
	 *
	 * @param MetaFile $file
	 */
	public function setFile($file);
	
	/**
	 * Saves folder data
	 *
	 * @param MetaFolder $folder
	 */
	public function setFolder($folder);
}


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
			'password' => $this->password,
			'key' => $this->key,
			'admin' => $this->admin
		);
	}
}


/**
 * This interface represents storage of raw file contents
 */
interface ContentStorage
{
	/**
	 * Loads file contents
	 *
	 * @param MetaFile $file
	 * @param string $mode file mode, rb or wb
	 * @return ContentStorageFile file
	 */
	public function getFile($file, $mode);
}

/**
 * Class used to parse HTTP request and extract values used by API
 */
class Request
{
	protected $method;
	protected $action;
	protected $user;
	protected $uri;
	protected $type;
	protected $contents;
	protected $auth;
	protected $headers;
	protected $files;
	
	/**
	 * @param bool $real OPTIONAL
	 */
	public function __construct($real = true) {
		if ($real) {
			// HTTP Method
			$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
			
			// Request url
			$this->uri = $_SERVER['REQUEST_URI'];
			
			// Type of provided content
			$this->type = @$_SERVER['CONTENT_TYPE'];
			
			// Load all request types
			$this->contents = array_merge($_GET, $_POST);
			
			// Action can be passed by any means
			$this->action = $this->contents('action');
			
			// Load all headers passed
			$this->headers = $this->parseHeaders();
			
			// Load auth, passed by custom header value
			$this->auth = $this->headers('X-AUTH');
			
			// Load POST files
			$this->files = @$_FILES;
			
			// Convert POST files to our container
			if (is_array($this->files)) {
				foreach($this->files as $key => $value) {
					$this->files[$key] = new RequestFile($value);
				}
			}
		}
	}
	
	/**
	 * Returns HTTP header(s) value
	 *
	 * @param string $key OPTIONAL header key
	 * @return string|array if no key was provided, returns all header values
	 */
	public function headers($key = null) {
		if ($key == null)
			return $this->headers;
		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}
	
	/**
	 * Loads request headers
	 */
	protected function parseHeaders() {
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) == 'HTTP_') {
				$header = strtoupper(str_replace('_', '-', strtolower(substr($key, 5))));
				$headers[$header] = $value;
			}
		}
		return $headers;
	}
	
	/**
	 * Returns authorization string
	 */
	public function auth() {
		return $this->auth;
	}
	
	/**
	 * Get/sets user in this request
	 *
	 * @param string $value
	 * @return mixed
	 */
	public function user($value = null) {
		if ($value)
			$this->user = $value;
		return $this->user;
	}
	
	/**
	 * Returns arguments in this request
	 *
	 * @param string $key param name
	 * @param mixed $default OPTIONAL value returnd when argument is not provided
	 * @return mixed argument value
	 */
	public function contents($key = null, $default = null) {
		if ($key == null)
			return $this->contents;
		
		return isset($this->contents[$key]) ? $this->contents[$key] : $default;
	}
}

// Backwards compatiblity
// PHP 5.0.3
if (!defined('UPLOAD_ERR_NO_TMP_DIR'))
	define('UPLOAD_ERR_NO_TMP_DIR', 6);
// PHP 5.1.0
if (!defined('UPLOAD_ERR_CANT_WRITE'))
	define('UPLOAD_ERR_CANT_WRITE', 7);
// PHP 5.2.0
if (!defined('UPLOAD_ERR_EXTENSION'))
	define('UPLOAD_ERR_EXTENSION', 8);

/**
 * Class representing POST file provided in request
 */
class RequestFile
{
	public $name;
	public $type;
	public $tmp;
	public $error;
	public $size;
	
	public $errors = array(
		UPLOAD_ERR_OK => null,
		UPLOAD_ERR_INI_SIZE => 'File is too big.',
		UPLOAD_ERR_FORM_SIZE => 'File is too big.',
		UPLOAD_ERR_PARTIAL => 'Uncomplete upload.',
		UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
		UPLOAD_ERR_NO_TMP_DIR => 'Error when saving uploaded file.',
		UPLOAD_ERR_CANT_WRITE => 'Error when saving uploaded file.',
		UPLOAD_ERR_EXTENSION => 'Upload was stopped by server.'
	);
	
	public function __construct($data) {
		$this->name = $data['name'];
		$this->type = $data['type'];
		$this->tmp = $data['tmp_name'];
		$this->error = $this->errors[$data['error']];
		$this->size = $data['size'];
	}
}

/**
 * Class used to compose responses
 */
class Response
{
	///@var string $contents raw response contents
	protected $contents;
	///@var string $type type of response content
	protected $type = 'application/json';
	///@var int $code http code of response
	protected $code = 200;
	///@var string $http http version
	protected $http = 'HTTP/1.1';
	
	///@var array $codeValues string values associated with http codes
	protected $codeValues = array(
		200 => 'OK',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		404 => 'Not Found'
	);
	
	/**
	 * @param string $contents raw response contents
	 * @param int    $code     http response code
	 */
	public function __construct($contents, $code = 200) {
		$this->contents = $contents;
		$this->code = $code;
		
		if ($this->contents instanceof ResponseObject)
			$this->contents = json_encode($this->contents->serialize());
		
		if (!is_string($this->contents))
			$this->contents = json_encode($this->contents);
	}
	
	/**
	 * Assigns header values and prints the response contents
	 */
	public function execute() {
		header("$this->http $this->code {$this->codeValues[$this->code]}");
		header("Content-type: $this->type");
		header("Content-length: " . strlen($this->contents));
		echo $contents;
	}
}

/**
 * Class used to represent response containing file contents
 */
class FileResponse extends Response
{
	protected $file;
	protected $bufferSize = 1024;
	
	public function __construct($file, $code = 200) {
		$this->file = $file;
		$this->code = $code;
	}
	
	public function execute() {
		header("$this->http $this->code {$this->codeValues[$this->code]}");
		header("Content-type: $this->type");
		header("Content-length: " . strlen($this->contents));
		
		while(!$file->eof()) {
			echo $file->read($this->bufferSize);
		}
		
		$file->close();
	}
}

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

class FolderStorage implements ContentStorage
{
	protected $api;
	
	protected $folder;
	
	public function __construct($api) {
		$this->api = $api;
		$this->folder = $this->api->config('storage_folder');
	}
	
	protected function getPath($file) {
		return $this->folder . '/' . $file->id();
	}
	
	public function getFile($file, $mode) {
		return new FolderStorageFile($this->getPath($file), $mode);
	}
}


(new Api())->handle(new Request())->execute();
