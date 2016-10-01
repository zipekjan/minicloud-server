<?php
return array(
	'meta' => 'DBOMetaStorage',
	'storage' => 'FolderStorage',
	
	'name' => 'Test Server',
	'description' => 'Test server for testing this API',
	'logo' => null,
	
	'nice_url' => true,
	
	'storage_folder' => 'files/',
	'salt' => 'asdjfhkfasf3a54dfasdf',
	
	'database' => array(
		'driver' => 'mysql',
		'host' => '127.0.0.1',
		'schema' => 'minicloud',
		'username' => 'root',
		'password' => ''
	)
);