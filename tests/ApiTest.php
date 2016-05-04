<?php
class ApiTest extends TestCase
{
	protected function getApi() {
		return parent::getApi(array(
			'meta' => 'TestMetaStorage',
			'storage' => 'TestContentStorage'
		));
	}
	
	public function testAuth() {
		// Prepare api
		$api = $this->getApi();
		$api->meta()->fill();
		
		$user = $api->meta()->users();
		$user = reset($user);
		
		// Create mock request
		$request = new TestRequest(array(
			'method' => 'GET',
			'action' => 'get_user',
			'auth' => hash('sha256', $user->name() . $user->password())
		));
		
		// Request response
		$result = $api->handle($request);
		$this->assertInstanceOf('ApiResponse', $result);
		
		// Check response data
		$result_data = json_decode($result->getContents(), true);
		$this->assertNotNull($result_data);
		$this->assertEquals($result_data['type'], 'user');
		$this->assertEquals($result_data['data'], $user->serialize());
		
		// Create mock request, with wrong auth
		$request = new TestRequest(array(
			'method' => 'GET',
			'action' => 'get_user',
			'auth' => 'negative'
		));
		
		// Request response
		$result = $api->handle($request);
		$this->assertInstanceOf('ApiResponse', $result);
		
		// Check response data, should be error
		$result_data = json_decode($result->getContents(), true);
		$this->assertNotNull($result_data);
		$this->assertEquals($result_data['type'], 'error');
	}
}