<?php
class ContentStorageTest extends TestCase
{
	protected function getApi() {
		return parent::getApi(array(
			'meta' => 'TestMetaStorage',
			'storage' => 'FolderStorage',
			'storage_folder' => $this->tmp
		));
	}
	
	/**
	 * @covers FolderStorage::getFile
	 * @covers FolderStorageFile
	 */
	public function testCreation() {
		$api = $this->getApi();
		$file = new MetaFile(array(
			'id' => 1,
			'version' => 1
		));
		
		$data = md5(mt_rand(1,1E5));
		
		$handle = $api->storage()->getFile($file, 'wb');
		$handle->write($data);
		$handle->close();
		
		$handle = $api->storage()->getFile($file, 'rb');
		$this->assertEquals($data, $handle->read(strlen($data)));
	}
	
	/**
	 * @covers FolderStorage::getFile
	 * @covers FolderStorageFile
	 */
	public function testUpdate() {
		$api = $this->getApi();
		$file = new MetaFile(array(
			'id' => 1,
			'version' => 1
		));
		
		$data = md5(mt_rand(1,1E5));
		
		$handle = $api->storage()->getFile($file, 'wb');
		$handle->write($data);
		$handle->close();
		
		$handle = $api->storage()->getFile($file, 'rb');
		$this->assertEquals($data, $handle->read(strlen($data)));
		
		$data = md5(mt_rand(1,1E5));
		
		$handle = $api->storage()->getFile($file, 'wb');
		$handle->write($data);
		$handle->close();
		
		$handle = $api->storage()->getFile($file, 'rb');
		$this->assertEquals($data, $handle->read(strlen($data)));
	}
	
	/**
	 * @covers FolderStorage::getFile
	 * @covers FolderStorageFile
	 */
	public function testDelete() {
		$api = $this->getApi();
		$file = new MetaFile(array(
			'id' => 1,
			'version' => 1,
			'versions' => array('version' => 1)
		));
		
		$data = md5(mt_rand(1,1E5));
		
		$handle = $api->storage()->getFile($file, 'wb');
		$handle->write($data);
		$handle->close();
		
		$api->storage()->deleteFile($file);
		
		$this->setExpectedException('Exception');
		$api->storage()->getFile($file, 'rb');
	}
}