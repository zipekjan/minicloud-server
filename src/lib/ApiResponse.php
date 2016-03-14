<?php
class ApiResponse extends Response
{
	public function __construct($type, $action_id, $response, $code = 200) {
		$contents = json_encode(array(
			'type' => $type,
			'action_id' => $action_id,
			'data' => $response
		));
		
		parent::__construct($contents, $code);
	}
}