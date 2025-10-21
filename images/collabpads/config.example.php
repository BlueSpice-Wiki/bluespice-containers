<?php

return [
	'server-id' => 'mediawiki-collabpads-backend',
	'ping-interval' => 25000,
	'ping-timeout' => 5000,
	'port' => 8081,
	'request-ip' => '0.0.0.0',
	'baseurl' => 'https://host.docker.internal',
	'db-type' => 'mongo',
	'db-host' => 'collabpads-database',
	'db-port' => 27017,
	'db-name' => 'collabpads',
	'db-user' => '',
	'db-password' => '',
	'log-level' => 'INFO',
	'http-client-options' => []
];
