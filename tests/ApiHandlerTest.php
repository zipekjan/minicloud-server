<?php
class ApiHandlerTest extends TestCase
{
	protected function getApi() {
		return parent::getApi(array(
			'meta' => 'TestMetaStorage',
			'storage' => 'TestContentStorage'
		));
	}
	
	public function testUpload() {
		// Prepare api
		$api = $this->getApi();
		$api->meta()->fill();
		
		// Prepare request values
		$checksum = hash("sha256", mt_rand(1,1E5));
		$encryption = md5(mt_rand(1,1E5));
		$data = md5(mt_rand(1,1E5));
		$public = mt_rand(0,1) == 0;
		$tmp = "{$this->tmp}test";
		$size = mt_rand(1,1E5);
		
		// Save request data
		file_put_contents($tmp, $data);
		
		// Create request file
		$file = new RequestFile(array(
			'error' => UPLOAD_ERR_OK,
			'name' => 'test.ext',
			'type' => 'test',
			'tmp_name' => $tmp,
			'size' => $size
		));
		
		// Create mock request
		$request = new TestRequest(array(
			'method' => 'POST',
			'action' => 'upload_file',
			'contents' => array(
				'checksum' => array('file' => $checksum),
				'encryption' => array('file' => $encryption),
				'public' => array('file' => $public)
			),
			'files' => array(
				$file
			)
		));
		
		// Create mock user
		$user = new MetaUser(array(
			'id' => 1,
			'admin' => false
		));
		
		// Execute action
		$result = $api->handler()->handle($api, $user, $request);
		$this->assertInstanceOf('ApiResponse', $result);
		
		$result_data = json_decode($result->getContents(), true);
		$this->assertNotNull($result_data);
		$this->assertEquals($result_data['type'], 'files');
		
		$returned_file = $result_data['data'][0];
		$saved_file = null;
		
		// Check for files
		$meta = $api->meta()->getPath($user);
		foreach($meta->files() as $file) {
			if ($file->id() === $returned_file['id']) {
				$saved_file = $file;
				break;
			}
		}
		
		$this->assertNotNull($saved_file, "Failed to find newly uploaded file in meta storage.");
		$this->assertEquals($saved_file->serialize(), $returned_file);
		
		$stored = $api->storage()->getFile($saved_file, "rb");
		$this->assertNotNull($stored);
		$this->assertEquals($stored->read(strlen($data)), $data);
	}
}