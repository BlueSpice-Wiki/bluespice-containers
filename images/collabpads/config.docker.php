<?php

$serializedHttpClientOptions = getenv( 'COLLABPADS_BACKEND_HTTP_CLIENT_OPTIONS' );
$httpClientOptions = [];
if ( $serializedHttpClientOptions ) {
	$httpClientOptions = json_decode( $serializedHttpClientOptions, true );
	if ( !is_array( $httpClientOptions ) ) {
		$httpClientOptions = [];
	}
}

return [
	'server-id' => 'mediawiki-collabpads-backend',
	'ping-interval' => 25000,
	'ping-timeout' => 5000,
	'port' => getenv( 'COLLABPADS_BACKEND_PORT' ) ?: 80,
	'request-ip' => '0.0.0.0',
	'baseurl' => getenv( 'COLLABPADS_BACKEND_WIKI_BASEURL' ),
	'db-type' => 'mongo',
	'db-host' => getenv( 'COLLABPADS_BACKEND_MONGO_DB_HOST' ),
	'db-port' => getenv( 'COLLABPADS_BACKEND_MONGO_DB_PORT' ) ?: 27017,
	'db-name' => getenv( 'COLLABPADS_BACKEND_MONGO_DB_NAME' ) ?: 'collabpads',
	'db-user' => getenv( 'COLLABPADS_BACKEND_MONGO_DB_USER' ) ?: '',
	'db-password' => getenv( 'COLLABPADS_BACKEND_MONGO_DB_PASSWORD' ) ?: '',
	'db-defaultauthdb' => getenv( 'COLLABPADS_BACKEND_MONGO_DB_DEFAULT_AUTH_DB' ) ?: 'admin',
	'log-level' => getenv( 'COLLABPADS_BACKEND_LOG_LEVEL' ) ?: 'warn',
	'http-client-options' => $httpClientOptions
];
