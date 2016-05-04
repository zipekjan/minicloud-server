<?php
class MetaStorageTest extends TestCase
{
	protected function getApi($override = array()) {
		$config = array(
			'meta' => 'DBOMetaStorage',
			'storage' => 'TestContentStorage',
			'database' => array(
				'driver' => 'direct',
				'source' => $this->getDB()
			)
		);
		$config = array_merge($config, $override);
		
		return parent::getApi($config);
	}
	
	private function getTestFileValues() {
		return array(
			'size' => mt_rand(5,1E5),
			'checksum' => md5(mt_rand(1,1E5)),
			'filename' => md5(mt_rand(1,1E5)),
			'encryption' => md5(mt_rand(1,1E5)),
			'mktime' => mt_rand(5,1E5),
			'mdtime' => mt_rand(5,1E5),
			'public' => mt_rand(0,1) == 0,
		);
	}
	
	private function checkFile($file, $options) {
		foreach($file->serialize() as $key => $value) {
			if (isset($options[$key]))
				$this->assertEquals($options[$key], $value, "$key has different value");
		}
	}
	
	/**
	 * @covers DBOMetaStorage::setFile
	 * @covers DBOMetaStorage::getFileById
	 */
	public function testCreation() {
		// Prepare api
		$api = $this->getApi();
		
		// Prepare tested options
		$options = $this->getTestFileValues();
		
		// Add untested options
		$extended = $options;
		$extended['user'] = $api->meta()->getUserById(1);
		$extended['path'] = $api->meta()->getPathById($extended['user'], 1);
		
		// Save file to DB
		$file = $api->meta()->setFile($extended['user'], new MetaFile($extended));
		$this->assertTrue($file !== null);
		
		// Test saved file
		$this->checkFile($file, $options);
		
		// Load file from DB
		$file = $api->meta()->getFileById($file->user(), $file->id());
		$this->assertTrue($file !== null);
		
		// Test loaded file
		$this->checkFile($file, $options);
	}
	
	/**
	 * @covers DBOMetaStorage::setFile
	 * @covers DBOMetaStorage::getFileById
	 */
	public function testSave() {
		// Prepare api
		$api = $this->getApi();
		
		// Prepare tested options
		$options = $this->getTestFileValues();
		
		// Add untested options
		$extended = $options;
		$extended['user'] = $api->meta()->getUserById(1);
		$extended['path'] = $api->meta()->getPathById($extended['user'], 1);
		
		// Save file
		$file = $api->meta()->setFile($extended['user'], new MetaFile($extended));
		$this->assertTrue($file !== null);
		
		// Update file
		$updated = $this->getTestFileValues();
		$file->set($updated);
		
		// Save updated file
		$file = $api->meta()->setFile($file->user(), $file);
		$this->assertTrue($file !== null);
		
		// Load updated file
		$file = $api->meta()->getFileById($file->user(), $file->id());
		$this->assertTrue($file !== null);
		
		// Test updated file
		$this->checkFile($file, $updated);
	}
	
	/**
	 * @covers DBOMetaStorage::setFile
	 * @covers DBOMetaStorage::deleteFile
	 */
	public function testDelete() {
		// Prepare api
		$api = $this->getApi();
		
		// Prepare tested options
		$options = $this->getTestFileValues();
		
		// Add untested options
		$extended = $options;
		$extended['user'] = $api->meta()->getUserById(1);
		$extended['path'] = $api->meta()->getPathById($extended['user'], 1);
		
		// Save file
		$file = $api->meta()->setFile($extended['user'], new MetaFile($extended));
		$this->assertTrue($file !== null);
		
		// Delete file
		$api->meta()->deleteFile($file->user(), $file);
		
		// Test existence
		$this->assertNull($api->meta()->getFileById($file->user(), $file->id()));
	}
}