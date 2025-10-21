<?php

use MongoDB\Client;

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$authString = '';
$port = $config['db-port'] ?? 27017;
if ( $config['db-user'] !== '' ) {
	$authString =
		rawurlencode( $config['db-user'] ) .
		':' .
		rawurlencode( $config['db-password'] ) .
		'@';
}

$connectionString =
	'mongodb://'
	. $authString
	. $config['db-host']
	. ':'
	. $port;

echo "Connecting to $connectionString\n";

try {
	$client = new Client( $connectionString );
	echo "Connected successfully\n";
} catch ( Exception $e ) {
	echo "Failed to connect: " . $e->getMessage() . "\n";
}
