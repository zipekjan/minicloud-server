<?php
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