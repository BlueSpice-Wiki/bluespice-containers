<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$GLOBALS['wgServer'] = bsAssembleURL( 'WIKI_PROTOCOL', 'WIKI_HOST', 'WIKI_PORT' );
$GLOBALS['wgSitename'] = trim(  getenv( 'WIKI_NAME' ) );
$GLOBALS['wgScriptPath'] = ( trim(  getenv( 'WIKI_BASE_PATH' ) ) ) .'w';

$GLOBALS['wgResourceBasePath'] = $GLOBALS['wgScriptPath'];
$GLOBALS['wgLogos'] = [
	'1x' => $GLOBALS['wgResourceBasePath'] . '/resources/assets/change-your-logo.svg',
	'icon' => $GLOBALS['wgResourceBasePath']. '/resources/assets/change-your-logo-icon.svg',
];
$GLOBALS['wgEmergencyContact'] = trim( getenv( 'WIKI_EMERGENCYCONTACT' ) );
$GLOBALS['wgPasswordSender'] = trim( getenv( 'WIKI_PASSWORDSENDER' ) );
$GLOBALS['wgDBtype'] = trim( getenv( 'DB_TYPE' ) );
$GLOBALS['wgDBserver'] = trim( getenv( 'DB_HOST' ) );
$GLOBALS['wgDBname'] = trim( getenv( 'DB_NAME' ) );
$GLOBALS['wgDBuser'] = trim( getenv( 'DB_USER' ) );
$GLOBALS['wgDBpassword'] = trim(  getenv( 'DB_PASS' ) );
$GLOBALS['wgDBprefix'] = trim(  getenv( 'DB_PREFIX' ) );
$GLOBALS['wgDBTableOptions'] = "ENGINE=InnoDB, DEFAULT CHARSET=binary";
$GLOBALS['wgMainCacheType'] = CACHE_ACCEL;
$GLOBALS['wgSessionCacheType'] = CACHE_DB;

$cacheUsed = false;
if ( getenv( 'CACHE_HOST' ) !== '-' ) {
	$cacheType = trim( getenv( 'CACHE_TYPE' ) ?: 'memcached' );
	$cacheHost = trim( getenv( 'CACHE_HOST' ) ?: 'cache' );
	if ( $cacheType === 'memcached' ) {
		$cachePort = trim( getenv( 'CACHE_PORT' ) ?: '11211' );
		$GLOBALS['wgMemCachedServers'] = [ "$cacheHost:$cachePort" ];
		$GLOBALS['wgMainCacheType'] = CACHE_MEMCACHED;
		$GLOBALS['wgSessionCacheType'] = CACHE_MEMCACHED;
	}
	# See https://www.mediawiki.org/wiki/MediaWiki-Docker/Configuration_recipes/Redis
	if ( $cacheType === 'redis' || $cacheType === 'valkey' ) {
		$cachePort = trim( getenv( 'CACHE_PORT' ) ?: '6379' );
		$GLOBALS['wgObjectCaches']['redis'] = [
			'class' => 'RedisBagOStuff',
			'servers'=> [ "$cacheHost:$cachePort" ],
		];
		$GLOBALS['wgMainCacheType'] = 'redis';
		$GLOBALS['wgSessionCacheType'] = 'redis';
		$GLOBALS['wgMainStash'] = 'redis';
	}
	$cacheUsed = $cacheType;
	unset( $cacheHost );
	unset( $cachePort );
}
$GLOBALS['wgMessageCacheType'] = CACHE_ACCEL;
$GLOBALS['wgLocalisationCacheConf']['store'] = 'array';
$GLOBALS['wgLocalisationCacheConf']['storeDirectory'] = "/tmp/cache/l10n";
$GLOBALS['wgEnableUploads'] = true;
$GLOBALS['wgUploadPath'] = $GLOBALS['wgScriptPath'] . '/img_auth.php';
// Use thumb.php for on-demand thumbnail creation instead of pre-generating at parse time.
// transformVia404 is NOT used: that approach requires the web server to serve thumbnail files
// directly from the filesystem (so a missing file triggers a 404 → handler chain). Since all
// image delivery here goes through img_auth.php (a PHP entrypoint), there is no direct
// filesystem serving — a 404 would mean the file truly does not exist, not merely that the
// thumbnail hasn't been rendered yet. thumb.php is therefore the correct mechanism.
$GLOBALS['wgThumbnailScriptPath'] = $GLOBALS['wgScriptPath'] . '/thumb.php';
$GLOBALS['wgGenerateThumbnailOnParse'] = false;
$GLOBALS['wgNativeImageLazyLoading'] = true;
$GLOBALS['wgUseImageMagick'] = true;
$GLOBALS['wgImageMagickConvertCommand'] = "/usr/bin/magick";
$GLOBALS['wgLanguageCode'] = trim( getenv( 'WIKI_LANG' ) );
$GLOBALS['wgLocaltimezone'] = null;
$GLOBALS['wgSecretKey'] = trim( getenv( 'INTERNAL_WIKI_SECRETKEY' ) );
$GLOBALS['wgAuthenticationTokenVersion'] = "1";
$GLOBALS['wgUpgradeKey'] = trim( getenv( 'INTERNAL_WIKI_UPGRADEKEY' ) );
$GLOBALS['wgRightsPage'] = "";
$GLOBALS['wgRightsUrl'] = "";
$GLOBALS['wgRightsText'] = "";
$GLOBALS['wgRightsIcon'] = "";
$GLOBALS['wgMetaNamespace'] = "Site";
$GLOBALS['wgPhpCli'] = '/bin/php';
$GLOBALS['wgSMTP'] = [
	'host' => trim( getenv( 'SMTP_HOST' ) ),
	'IDHost' => trim( getenv( 'SMTP_IDHOST' ) ),
	'port' => trim( getenv( 'SMTP_PORT' ) ),
	'auth' => getenv( 'SMTP_USER' ) ? true : false,
	'username' => trim( getenv( 'SMTP_USER' ) ),
	'password' => trim( getenv( 'SMTP_PASS' ) ),
];

if ( getenv( 'JOBQUEUE_HOST' ) && getenv( 'JOBQUEUE_HOST' ) !== '-' ) {
	$jobQueueHost = trim( getenv( 'JOBQUEUE_HOST' ) ?: 'jobqueue' );
	$jobQueuePort = trim( getenv( 'JOBQUEUE_PORT' ) ?: '6379' );
	$GLOBALS['wgJobTypeConf']['default'] = [
		'class' => 'JobQueueRedis',
		'redisServer' => "$jobQueueHost:$jobQueuePort",
		'redisConfig' => [],
		'claimTTL' => 3600,
		'daemonized' => true
	];
	unset( $jobQueueHost );
	unset( $jobQueuePort );
}

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

$GLOBALS['wgCdnServersNoPurge'] = [ Wikimedia\IPUtils::sanitizeRange( gethostbyname( gethostname() ?? '' ) . '/24' ) ];
if ( getenv('WIKI_PROXY') ) {
	$GLOBALS['wgCdnServersNoPurge'] = explode( ',', trim( getenv( 'WIKI_PROXY' ) ) );
	array_walk( $GLOBALS['wgCdnServersNoPurge'], function ( &$value ) {
		$value = trim( $value );
	} );
}
if ( getenv( 'WIKI_SUBSCRIPTION_KEY' ) ) {
	$GLOBALS['bsgOverrideLicenseKey'] = trim( getenv( 'WIKI_SUBSCRIPTION_KEY' ) ) ;
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

$GLOBALS['wgPdfProcessor'] = '/usr/bin/gs';
$GLOBALS['wgPdfPostProcessor'] = $GLOBALS['wgImageMagickConvertCommand'];
$GLOBALS['wgPdfInfo'] = '/usr/bin/pdfinfo';
$GLOBALS['wgPdftoText'] = '/usr/bin/pdftotext';

if ( getenv( 'EDITION' ) !== 'free' ) {
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

$GLOBALS['bsgInstanceStatusCheckAllowedIP'] = trim( getenv( 'WIKI_STATUSCHECK_ALLOWED' ) );

$GLOBALS['wgSimpleSAMLphp_SAMLClient'] = 'http-client';
$GLOBALS['wgSimpleSAMLphp_HTTPClientConfig'] = [
	'baseUrl' => 'http://localhost:9090/_sp/',
	// Keep aligned with `root-fs/app/simplesamlphp/config/config.php`
	'sessionIdCookieName' => getenv('DB_NAME') . ( getenv('DB_PREFIX') ) . 'SAMLSessionID',
	'authTokenCookieName' => getenv('DB_NAME') . ( getenv('DB_PREFIX') ) . 'SAMLAuthToken',
	'sessionAPItoken' => getenv( 'INTERNAL_SIMPLESAMLPHP_SESSION_API_TOKEN' ) ?: ''
];

if ( getenv( 'DEV_WIKI_DEBUG' ) ) {
	$GLOBALS['wgShowExceptionDetails'] = true;
	$GLOBALS['wgDevelopmentWarnings'] = true;
	$GLOBALS['wgDebugDumpSql'] = true;
}

# See https://github.com/edwardspec/mediawiki-aws-s3/tree/master?tab=readme-ov-file#using-another-s3-compatible-service-not-amazon-s3-itself
$s3Used = false;
if ( getenv( 'FILESTORE_HOST' ) ) {
	$s3Used = true;
	$GLOBALS['mwsgFileStorageUseS3'] = true;

	$GLOBALS['wgAWSCredentials'] = [
		'key' => trim( getenv( 'FILESTORE_ACCESS_KEY' ) ?: '' ),
		'secret' => trim( getenv( 'FILESTORE_SECRET_KEY' ) ?: '' ),
		'token' => trim( getenv( 'FILESTORE_TOKEN' ) ?: false ),
	];
	$GLOBALS['wgAWSBucketName'] = trim( getenv( 'FILESTORE_BUCKET_NAME' ) ?: $GLOBALS['wgDBname'].$GLOBALS['wgDBprefix'] );
	// Use `img_auth.php` or `nsfr_img_auth.php` for frontend URLs
	$GLOBALS['wgAWSBucketDomain'] = $GLOBALS['wgServer'] . $GLOBALS['wgUploadPath'];
	// Has to be set to make AWS SDK work; Not required for non-AWS S3 services
	$GLOBALS['wgAWSRegion'] = trim( getenv( 'FILESTORE_REGION' ) ?: 'eu-north-1' );
	// By default we use the `$wgScriptPath` as top subdirectory
	// For farm instances we change the top subdirectory to the respective Sub-WikiID
	$GLOBALS['wgAWSBucketTopSubdirectory'] = $GLOBALS['wgScriptPath'];
	// For b/c to legacy FS layout
	$GLOBALS['wgAWSRepoHashLevels'] = '2';
	$GLOBALS['wgAWSRepoDeletedHashLevels'] = '3';

	$GLOBALS['wgFileBackends']['s3']['use_path_style_endpoint'] = true;
	$GLOBALS['wgFileBackends']['s3']['endpoint'] = bsAssembleURL(
		[ 'FILESTORE_PROTOCOL', 'http' ],
		[ 'FILESTORE_HOST', 'filestore' ],
		[ 'FILESTORE_PORT', '9000' ]
	);
}

if ( getenv( 'DEV_WIKI_DEBUG_LOGCHANNELS' ) ) {
	$logChannels = explode( ',', trim( getenv( 'DEV_WIKI_DEBUG_LOGCHANNELS' ) ) );
	$logChannels = array_map( 'trim', $logChannels );
	foreach ( $logChannels as $channel ) {
		$GLOBALS['bsgDebugLogGroups'][$channel] = true;
	}
	unset( $logChannels );
}

// Taken from `extensions/BlueSpiceWikiFarm/src/Dispatcher.php`
// Not all of this may be required
$GLOBALS['wgUploadDirectory'] = "/data/bluespice/images";
$GLOBALS['wgReadOnlyFile'] = "{$GLOBALS['wgUploadDirectory']}/lock_yBgMBwiR";
$GLOBALS['wgFileCacheDirectory'] = "{$GLOBALS['wgUploadDirectory']}/cache";
$GLOBALS['wgDeletedDirectory'] = "{$GLOBALS['wgUploadDirectory']}/deleted";
$GLOBALS['wgCacheDirectory'] = "/data/bluespice/cache";
define( 'BSROOTDIR', '/data/bluespice/extensions/BlueSpiceFoundation' );

if ( getenv( 'EDITION' ) === 'farm' ) {
	$GLOBALS['wgWikiFarmConfig_instanceDirectory'] = '/data/bluespice/farm-instances/';
	$GLOBALS['wgWikiFarmConfig_archiveDirectory'] = '/data/bluespice/farm-archives/';
	$GLOBALS['wgWikiFarmConfig_dbAdminUser'] = trim( getenv( 'DB_ROOT_USER' ) ?: $GLOBALS['wgDBuser'] );
	$GLOBALS['wgWikiFarmConfig_dbAdminPassword'] = trim( getenv( 'DB_ROOT_PASS' ) ?: $GLOBALS['wgDBpassword'] );
	$GLOBALS['wgWikiFarmConfig_dbPrefix'] = trim( getenv( 'WIKI_FARM_DB_PREFIX' ) );
	$GLOBALS['wgWikiFarmConfig_LocalSettingsAppendPath'] = "$IP/LocalSettings.BlueSpice.php";
	$GLOBALS['wgWikiFarmConfig_useSharedDB'] = getenv( 'WIKI_FARM_USE_SHARED_DB' ) ? true : false;
	$GLOBALS['wgWikiFarmConfig_basePath'] = trim( getenv( 'WIKI_BASE_PATH' ) );
	$GLOBALS['wgSharedDB'] = $GLOBALS['wgDBname'];
	$GLOBALS['wgSharedPrefix'] = $GLOBALS['wgDBprefix'];
	$GLOBALS['wgSharedTables'] = [ 'bs_translationtransfer_translations' ];
}


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
	?? Wikimedia\IPUtils::sanitizeRange( gethostbyname( gethostname() ?? '' ) . '/24' );

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

require_once trim( getenv( 'WIKI_PRE_INIT_SETTINGS_FILE' ) );
if ( getenv( 'EDITION' ) === 'farm' ) {
	require_once "$IP/extensions/BlueSpiceWikiFarm/WikiFarm.setup.php";
}
else {
	define( 'BSDATADIR', BSROOTDIR . "/data" ); //Present
	define( 'BS_DATA_DIR', "{$GLOBALS['wgUploadDirectory']}/bluespice" ); //Future
	define( 'BS_CACHE_DIR', "{$GLOBALS['wgFileCacheDirectory']}/bluespice" );
	define( 'BS_DATA_PATH', "{$GLOBALS['wgUploadPath']}/bluespice" );
	require_once "$IP/LocalSettings.BlueSpice.php";
}

$GLOBALS['wgArticlePath'] = ( trim(  getenv( 'WIKI_BASE_PATH' ) ) ) . trim( getenv( 'WIKI_ARTICLE_PATH' ) ?: 'wiki' ) . '/$1';

if ( $s3Used ) {
	$GLOBALS['wgAWSBucketDomain'] = $GLOBALS['wgServer'] . $GLOBALS['wgUploadPath'];
	$GLOBALS['wgHooks']['SetupAfterCache'][] = static function () {
		// Setup "global" repo for farm. Actual bucket root
		$bucketName = $GLOBALS['wgAWSBucketName'];
		$wikiId = \MediaWiki\WikiMap\WikiMap::getCurrentWikiId();
		$GLOBALS['wgFileBackends']['s3']['containerPaths']["$wikiId-instances-public"] = $bucketName;
		$GLOBALS['wgFileBackends']['s3']['containerPaths']["$wikiId-archive-public"] = "$bucketName/_archive";

		/**
		 * Extension:AWS adds the $wgAWSBucketTopSubdirectory to the Zone-URLs. While this technically works,
		 * it results in odd URLs for farm instances and is not necessary.
		 * Example::
		 *  - IS:        https://<server>/<instance>/*img_auth.php/<instance>/thumb
		 *  - SHOULD BE: https://<server>/<instance>/*img_auth.php/thumb
		 * Therefore we replace the URLs here to remove the $wgAWSBucketTopSubdirectory from them.
		 */
		$instanceName = defined( 'FARMER_CALLED_INSTANCE' ) ? FARMER_CALLED_INSTANCE : 'w';
		$search = $GLOBALS['wgUploadPath'] . '/' . $instanceName;
		$replace = $GLOBALS['wgUploadPath'];
		foreach ( $GLOBALS['wgLocalFileRepo']['zones'] as $zone => $info ) {
			if ( !isset( $info['url'] ) || !$info['url']) {
				continue;
			}
			$GLOBALS['wgLocalFileRepo']['zones'][$zone]['url'] = str_replace( $search, $replace, $info['url'] );
		}
	};
}

if ( getenv( 'EDITION' ) === 'farm' ) {
	if( FARMER_IS_ROOT_WIKI_CALL === false ) {
		$GLOBALS['wgScriptPath'] =  trim( getenv( 'WIKI_BASE_PATH' ) ) . FARMER_CALLED_INSTANCE;
		$GLOBALS['wgArticlePath'] = trim( getenv( 'WIKI_BASE_PATH' ) ) . FARMER_CALLED_INSTANCE . '/' . trim( getenv( 'WIKI_ARTICLE_PATH' ) ?: 'wiki' ) . '/$1';
		$GLOBALS['wgWebDAVBaseUri'] = trim( getenv( 'WIKI_BASE_PATH' ) ) . FARMER_CALLED_INSTANCE . '/webdav/';
		// We must store L10N cache file of ROOT_WIKI and INSTANCEs independently, as they have different extensions enabled,
		// which otherwise causes the cache to be invalidated all the time.
		$GLOBALS['wgLocalisationCacheConf']['storeDirectory'] = '/tmp/cache/l10n-instances';

		// Setup file storage of uploadfiles _and_ farm instance vaults/archives to use S3, if configured
		if ( $s3Used ) {
			// Overwrite S3 bucket top subdirectory to use Sub-WikiID (see above)
			$GLOBALS['wgAWSBucketTopSubdirectory'] = $GLOBALS['wgScriptPath'];
		}

		// Setup process runner/wikicron to use Redis, if configured
		// Has to be done in post-init due to farm consts
		if ( $cacheUsed === 'redis' || $cacheUsed === 'valkey' ) {
			$GLOBALS['mwsgProcessManagerQueueConfig']['farm-redis'] = [
				'class' => \BlueSpice\WikiFarm\ProcessQueue\FarmRedisProcessQueue::class,
				'args' => [ [
					'redisConfig' => [],
					'redisServer' => $GLOBALS['wgObjectCaches']['redis']['servers'][0],
					'isRoot' => FARMER_IS_ROOT_WIKI_CALL,
					'forInstance' => FARMER_CALLED_INSTANCE
				] ]
			];
			$GLOBALS['mwsgProcessManagerQueue'] = 'farm-redis';

			$GLOBALS['mwsgWikiCronStore'] = [
				'class' => \BlueSpice\WikiFarm\ProcessQueue\WikiCronRedisStore::class,
				'args' => [ [
					'redisConfig' => [],
					'redisServer' => $GLOBALS['wgObjectCaches']['redis']['servers'][0],
					'isRoot' => FARMER_IS_ROOT_WIKI_CALL,
					'forInstance' => FARMER_CALLED_INSTANCE
				] ]
			];
		}
	}

	// Setup file storage of uploadfiles _and_ farm instance vaults/archives to use S3, if configured
	if ( $s3Used ) {
		// Original local FS backend configured in Extension:BlueSpiceWikiFarm is not used.
		unset( $GLOBALS['wgFileBackends']['_instances'] );
		$GLOBALS['wgWikiFarmConfig_instanceStorageBackend'] = $GLOBALS['mwsgFileStorageBackend'];
	}
}

unset( $cacheUsed );
unset( $s3Used );

if ( getenv( 'MAX_UPLOAD_SIZE' ) ) {
	$uploadSize = getenv( 'MAX_UPLOAD_SIZE' );
	if ( preg_match( '/^(\d+)([a-zA-Z]+)$/', $uploadSize, $matches ) ) {
		$value = (int)$matches[1];
		$suffix = strtolower( $matches[2] );

		if ( $suffix === "m" ) {
			$GLOBALS['wgMaxUploadSize']  = 1024 * 1024 * $value;
		}
		elseif ( $suffix === "g" ) {
			$GLOBALS['wgMaxUploadSize']  = 1024 * 1024 * 1024 * $value;
		}
		//If Value is not Readable default = 1024*1024*1024
	}
	unset( $uploadSize );
	unset( $value );
	unset( $suffix );
	unset( $matches );
}

require_once trim( getenv( 'WIKI_POST_INIT_SETTINGS_FILE' ) );
