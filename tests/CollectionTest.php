<?php
class CollectionTest extends TestCase
{
	protected function getApi() {
		return parent::getApi(array(
			'meta' => 'DBOMetaStorage',
			'storage' => 'FolderStorage',
			'database' => array(
				'driver' => 'direct',
				'source' => $this->getDB()
			),
			'storage_folder' => $this->tmp
		));
	}
	
	public function testBasic() {
		$api = $this->getApi();

		// Load test user
		$user = $api->meta()->getUserById(1);
		$auth = $user->name() . ':' . $user->password();
		
		// Create mock request
		$request = new TestRequest(array(
			'method' => 'GET',
			'action' => 'get_user',
			'auth' => $auth
		));
		
		// Request response
		$result = $api->handle($request);
		$this->assertInstanceOf('ApiResponse', $result);
		
		// Check response data
		$result_data = json_decode($result->getContents(), true);
		$this->assertNotNull($result_data);
		
		$this->assertEquals($result_data['type'], 'user');
		$this->assertEquals($result_data['data'], $user->serialize());
		
		// Create mock request
		$request = new TestRequest(array(
			'method' => 'GET',
			'action' => 'get_path',
			'auth' => $auth
		));
		
		// Request response
		$result = $api->handle($request);
		$this->assertInstanceOf('ApiResponse', $result);
		
		// Check response data
		$result_data = json_decode($result->getContents(), true);
		$this->assertNotNull($result_data);
		$this->assertEquals($result_data['type'], 'path');
	}
}