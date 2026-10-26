<?php
// Connections to the services of the stack

if ( getenv( 'AV_HOST' ) !== '-' ) {
	$GLOBALS['wgAntivirusSetup'] = [
		'clamav' => [
			'command' => 'clamdscan --no-summary',
			'codemap' => [
				"0" => AV_NO_VIRUS,
				"1" => AV_VIRUS_FOUND,
				"52" => AV_SCAN_ABORTED,
				"*" => AV_SCAN_FAILED,
			],
			'messagepattern' => '/.*?:(.*)/sim',
		],
	];
	$GLOBALS['wgAntivirus'] = 'clamav';
	$GLOBALS['wgAntivirusRequired'] = true;
}

$GLOBALS['wgOAuth2PrivateKey'] = trim( getenv( 'WIKI_OAUTH2_PRIVATE_KEY_FILE' ) );
$GLOBALS['wgOAuth2PublicKey'] = trim( getenv( 'WIKI_OAUTH2_PUBLIC_KEY_FILE' ) );

$GLOBALS['bsgESBackendHost'] = trim( getenv( 'SEARCH_HOST' ) );
$GLOBALS['bsgESBackendPort'] = trim( getenv( 'SEARCH_PORT' ) );
$GLOBALS['bsgESBackendTransport'] = trim( getenv( 'SEARCH_PROTOCOL' ) );
$GLOBALS['bsgESBackendUsername'] = trim( getenv( 'SEARCH_USER' ) );
$GLOBALS['bsgESBackendPassword'] = trim( getenv( 'SEARCH_PASS' ) );

$GLOBALS['wgPDFCreatorOpenHtml2PdfServiceUrl'] = bsAssembleURL( 'PDF_PROTOCOL', 'PDF_HOST', 'PDF_PORT' );
$GLOBALS['wgPDFCreatorOpenHtml2PdfServiceUrl'] .= '/Html2PDF/v1';

if ( bsInfrastructureConfig( 'INTERNAL_EDITION' ) !== 'free' ) {
	// FREE edition uses public diagrams.net service
	$GLOBALS['wgDrawioEditorBackendUrl'] = bsAssembleURL( 'DIAGRAM_PROTOCOL', 'DIAGRAM_HOST', 'DIAGRAM_PORT', 'DIAGRAM_PATH' );
}

$GLOBALS['wgMathValidModes'] = [ 'mathml' ];
$GLOBALS['wgDefaultUserOptions']['math'] = 'mathml';
$GLOBALS['wgMaxShellMemory'] = 1228800;
$GLOBALS['wgHiddenPrefs'][] = 'math';
// We don't use the `MathMathML` renderer, but `MathMathMLCli`,
// but `Extension:BlueSpiceInstanceStatus` needs this variable
$GLOBALS['wgMathMathMLUrl'] = bsAssembleURL( 'FORMULA_PROTOCOL', 'FORMULA_HOST', 'FORMULA_PORT' );
// By setting `$wgMathoidCli`, `MathMathMLCli` renderer is used
// instead of `MathMathML`.
$GLOBALS['wgMathoidCli'] = [
	'/app/bin/mathoid-remote',
	$GLOBALS['wgMathMathMLUrl']
];

$GLOBALS['wgNeoWikiNeo4jInternalWriteUrl'] = $GLOBALS['wgNeoWikiNeo4jInternalReadUrl']
	= bsAssembleURL(
		'METADATASTORE_PROTOCOL', 'METADATASTORE_HOST', 'METADATASTORE_PORT', '',
		[ 'METADATASTORE_USER', 'neo4j' ], [ 'METADATASTORE_PASS', '' ]
	);

$GLOBALS['bsgInstanceStatusCheckAllowedIP'] = trim( getenv( 'WIKI_STATUSCHECK_ALLOWED' ) );

$GLOBALS['wgSimpleSAMLphp_SAMLClient'] = 'http-client';
$GLOBALS['wgSimpleSAMLphp_HTTPClientConfig'] = [
	'baseUrl' => 'http://localhost:9090/_sp/',
	// Keep aligned with `root-fs/app/simplesamlphp/config/config.php`
	'sessionIdCookieName' => getenv('DB_NAME') . ( getenv('DB_PREFIX') ) . 'SAMLSessionID',
	'authTokenCookieName' => getenv('DB_NAME') . ( getenv('DB_PREFIX') ) . 'SAMLAuthToken',
	'sessionAPItoken' => getenv( 'INTERNAL_SIMPLESAMLPHP_SESSION_API_TOKEN' ) ?: ''
];

$GLOBALS['mwsgTokenAuthenticatorSalt'] = getenv( 'INTERNAL_WIKI_TOKEN_AUTH_SALT' );

// While this is not necessarily `bluespice/chat` service specific configuration, we currently assume it is.
// This may change in future versions. See https://github.com/hallowelt/mwstake-mediawiki-component-token-authenticator/issues/2
$GLOBALS['mwsgTokenAuthenticatorServiceUser'] = 'ChatBot service user';
$GLOBALS['mwsgTokenAuthenticatorServiceToken'] = getenv( 'INTERNAL_CHAT_WIKI_ACCESS_TOKEN' );
$GLOBALS['mwsgTokenAuthenticatorServiceAllowedAPIModules'] = [
	ApiOpenSearch::class
];
$GLOBALS['mwsgTokenAuthenticatorServiceAllowedRestPaths'] = [
	'/chatintegration',
	'/mws/v1/user-token/verify',
	'/mws/v1/app-token/verify',
	'/mws/v1/app-token/generate',
	'/mws/v1/mcp/list_tools',
	'/mws/v1/mcp/get_wiki_map',
];
# By default limit to same subnet as the host (container)
$GLOBALS['mwsgTokenAuthenticatorServiceCIDR'] =
	trim( getenv( 'WIKI_SERVICE_TOKEN_AUTH_ALLOWED' ) )
	?: Wikimedia\IPUtils::sanitizeRange( gethostbyname( gethostname() ?? '' ) . '/24' );

// `bluespice/wire` service configuration
$GLOBALS['mwsgWireServiceApiKey'] = getenv( 'INTERNAL_WIRE_API_KEY' );
$GLOBALS['mwsgWireServiceUrl'] = bsAssembleURL( 'WIRE_PROTOCOL', 'WIRE_HOST', 'WIRE_PORT' );
$GLOBALS['mwsgWireServiceWebsocketUrl'] = $GLOBALS[ 'wgServer' ] . ( trim( getenv( 'WIKI_BASE_PATH' ) ?: '/' ) ) . '_wire';

// Extension:WikiRAG configuration
$GLOBALS['wgWikiRAGTarget'] = [
	'type' => 'local-directory',
	'configuration' => [
		'path' => '/data/bluespice/rag'
	]
];
$GLOBALS['wgWikiRAGPipeline'] = [ 'content.wikitext', 'repofile', 'meta.json', 'acl.json' ];

// We allow explictly disabling Chat extensions
if ( getenv( 'CHAT_HOST' ) !== '-' ) {
	// Extension:ChatIntegration configuration
	$GLOBALS['wgChatIntegrationBridge'] = [
		'url' => bsAssembleURL( 'CHAT_PROTOCOL', 'CHAT_HOST', 'CHAT_PORT' ),
		'token' => getenv( 'INTERNAL_CHAT_TOKEN' )
	];

	// Extension:ChatBot configuration
	$GLOBALS['wgChatBotService'] = [
		'url' => $GLOBALS[ 'wgServer' ] . '/_chat'
	];
}
