<?php
$CONF = array(
	'database' => array(
		'hostname' => '',
		'database' => '',
		'username' => '',
		'password' => '',
		'port' => null,
		'socket' => null
	),

	// Optional configuration to use redis. Set to enabled to "true" to use redis.
	'redis' => array(
		'enabled' => false,
		'hostname' => 'localhost',
		'port' => 6379
	),
	
	'date_default_timezone' => "America/Chicago",
);



