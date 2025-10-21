<?php

$idpRemoteMetaData = require( __DIR__ . '/_bluespice-saml20-idp-remote-meta.php' );

$baseUrl = $GLOBALS['wgServer'] = bsAssembleURL(
	[ 'WIKI_PROTOCOL', 'https' ],
	[ 'WIKI_HOST', 'localhost' ],
	[ 'WIKI_PORT', '443' ]
);

$config = [
	'admin' => [
		'core:AdminPassword',
	],
	'default-sp' => [
		'saml:SP',
		'entityID' => $baseUrl,
		'idp' => $idpRemoteMetaData['entityid'],
		'discoURL' => null,
		'privatekey' => '/data/simplesamlphp/certs/saml.pem',
		'certificate' => '/data/simplesamlphp/certs/saml.crt',
		'NameIDPolicy' => []
	]
];

unset( $idpRemoteMetaData );
unset( $baseUrl );
