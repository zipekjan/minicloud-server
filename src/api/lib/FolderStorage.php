<?php
class FolderStorage implements ContentStorage
{
	protected $api;
	
	protected $folder;
	
	public function __construct($api) {
		$this->api = $api;
		$this->folder = $this->api->config('storage_folder');
	}
	
	protected function getPath($file) {
		return $this->folder . '/' . $file->id() . '.' . $file->version();
	}
	
	public function getFile($file, $mode) {
		return new FolderStorageFile($this->getPath($file), $mode);
	}
	
	public function deleteFile($file) {
		foreach($file->versions() as $version) {
			$file->version($version['version']);
			@unlink($this->getPath($file));
		}
	}
}
