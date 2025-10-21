<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$GLOBALS['wgServer'] = bsAssembleURL(
	[ 'WIKI_PROTOCOL', 'https' ],
	[ 'WIKI_HOST', 'localhost' ],
	[ 'WIKI_PORT', '443' ]
);

$GLOBALS['wgSitename'] = trim(  getenv( 'WIKI_NAME' ) ?: 'BlueSpice' );
$GLOBALS['wgScriptPath'] = ( trim(  getenv( 'WIKI_BASE_PATH' ) ?: '/' ) ) .'w';

$GLOBALS['wgResourceBasePath'] = $GLOBALS['wgScriptPath'];
$GLOBALS['wgLogos'] = [
	'1x' => $GLOBALS['wgResourceBasePath'] . '/resources/assets/change-your-logo.svg',
	'icon' => $GLOBALS['wgResourceBasePath']. '/resources/assets/change-your-logo-icon.svg',
];
$GLOBALS['wgEmergencyContact'] = trim( getenv( 'WIKI_EMERGENCYCONTACT' ) ?: '' );
$GLOBALS['wgPasswordSender'] = trim( getenv( 'WIKI_PASSWORDSENDER' )
	?: 'no-reply@' . trim ( getenv( 'WIKI_HOST' ) ?: 'localhost' ) );
$GLOBALS['wgDBtype'] = trim( getenv( 'DB_TYPE' ) ?: 'mysql' );
$GLOBALS['wgDBserver'] = trim( getenv( 'DB_HOST' ) ?: 'database' );
$GLOBALS['wgDBname'] = trim( getenv( 'DB_NAME' ) ?: 'bluespice' );
$GLOBALS['wgDBuser'] = trim( getenv( 'DB_USER' ) ?: 'bluespice' );
$GLOBALS['wgDBpassword'] = trim(  getenv( 'DB_PASS' ) );
$GLOBALS['wgDBprefix'] = trim(  getenv( 'DB_PREFIX' ) ?: '' );
$GLOBALS['wgDBTableOptions'] = "ENGINE=InnoDB, DEFAULT CHARSET=binary";
$GLOBALS['wgMainCacheType'] = CACHE_ACCEL;
$GLOBALS['wgSessionCacheType'] = CACHE_DB;
if ( getenv( 'CACHE_HOST' ) !== '-' ) {
	$cacheHost = trim( getenv( 'CACHE_HOST' ) ?: 'cache' );
	$cachePort = trim( getenv( 'CACHE_PORT' ) ?: '11211' );
	$GLOBALS['wgMemCachedServers'] = [ "$cacheHost:$cachePort" ];
	unset( $cacheHost );
	unset( $cachePort );
	$GLOBALS['wgMainCacheType'] = CACHE_MEMCACHED;
	$GLOBALS['wgSessionCacheType'] = CACHE_MEMCACHED;
}
$GLOBALS['wgMessageCacheType'] = CACHE_ACCEL;
$GLOBALS['wgLocalisationCacheConf']['store'] = 'array';
$GLOBALS['wgLocalisationCacheConf']['storeDirectory'] = "/tmp/cache/l10n";
$GLOBALS['wgEnableUploads'] = true;
$GLOBALS['wgUploadPath'] = $GLOBALS['wgScriptPath'] . '/img_auth.php';
$GLOBALS['wgUseImageMagick'] = true;
$GLOBALS['wgImageMagickConvertCommand'] = "/usr/bin/magick";
$GLOBALS['wgLanguageCode'] = trim( getenv( 'WIKI_LANG' ) ?: 'en' );
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
	'port' => trim( getenv( 'SMTP_PORT' ) ?: 25 ),
	'auth' => getenv( 'SMTP_USER' ) ? true : false,
	'username' => trim( getenv( 'SMTP_USER' ) ),
	'password' => trim( getenv( 'SMTP_PASS' ) ),
];
if ( getenv( 'AV_HOST' ) ) {
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
if ( getenv('WIKI_PROXY') ) {
	$GLOBALS['wgCdnServersNoPurge'] = explode( ',', trim( getenv( 'WIKI_PROXY' ) ) );
	array_walk( $GLOBALS['wgCdnServersNoPurge'], function ( &$value ) {
		$value = trim( $value );
	} );
}
if ( getenv( 'WIKI_SUBSCRIPTION_KEY' ) ) {
	$GLOBALS['bsgOverrideLicenseKey'] = trim( getenv( 'WIKI_SUBSCRIPTION_KEY' ) ) ;
}

$GLOBALS['wgOAuth2PrivateKey'] = '/data/bluespice/oauth_private.key';
$GLOBALS['wgOAuth2PublicKey'] = '/data/bluespice/oauth_public.key';

$GLOBALS['bsgESBackendHost'] = trim( getenv( 'SEARCH_HOST' ) ?: 'search' );
$GLOBALS['bsgESBackendPort'] = trim( getenv( 'SEARCH_PORT' ) ?: '9200' );
$GLOBALS['bsgESBackendTransport'] = trim( getenv( 'SEARCH_PROTOCOL' ) ?: 'http' );
$GLOBALS['bsgESBackendUsername'] = trim( getenv( 'SEARCH_USER' ) ?: '' );
$GLOBALS['bsgESBackendPassword'] = trim( getenv( 'SEARCH_PASS' ) ?: '' );

$GLOBALS['wgPDFCreatorOpenHtml2PdfServiceUrl'] = bsAssembleURL(
	[ 'PDF_PROTOCOL', 'http' ],
	[ 'PDF_HOST', 'pdf' ],
	[ 'PDF_PORT', '8080' ]
);
$GLOBALS['wgPDFCreatorOpenHtml2PdfServiceUrl'] .= '/Html2PDF/v1';

$GLOBALS['wgPdfProcessor'] = '/usr/bin/gs';
$GLOBALS['wgPdfPostProcessor'] = $GLOBALS['wgImageMagickConvertCommand'];
$GLOBALS['wgPdfInfo'] = '/usr/bin/pdfinfo';
$GLOBALS['wgPdftoText'] = '/usr/bin/pdftotext';

if ( getenv( 'EDITION' ) !== 'free' ) {
	// FREE edition uses public diagrams.net service
	// HINT: Keep in sync with assembly of $GLOBALS['wgServer']
	$GLOBALS['wgDrawioEditorBackendUrl'] = bsAssembleURL(
		[ 'DIAGRAM_PROTOCOL', trim( getenv( 'WIKI_PROTOCOL' ) ?: 'https' ) ],
		[ 'DIAGRAM_HOST', trim( getenv( 'WIKI_HOST' ) ?: 'localhost' ) ],
		[ 'DIAGRAM_PORT', trim( getenv( 'WIKI_PORT' ) ?: '443' ) ],
		[ 'DIAGRAM_PATH', '/_diagram/' ]
	);
}

$GLOBALS['wgMathValidModes'] = [ 'mathml' ];
$GLOBALS['wgDefaultUserOptions']['math'] = 'mathml';
$GLOBALS['wgMaxShellMemory'] = 1228800;
$GLOBALS['wgHiddenPrefs'][] = 'math';
// We don't use the `MathMathML` renderer, but `MathMathMLCli`,
// but `Extension:BlueSpiceInstanceStatus` needs this variable
$GLOBALS['wgMathMathMLUrl'] = bsAssembleURL(
	[ 'FORMULA_PROTOCOL', 'http' ],
	[ 'FORMULA_HOST', 'formula' ],
	[ 'FORMULA_PORT', '10044' ]
);
// By setting `$wgMathoidCli`, `MathMathMLCli` renderer is used
// instead of `MathMathML`.
$GLOBALS['wgMathoidCli'] = [
	'/app/bin/mathoid-remote',
	$GLOBALS['wgMathMathMLUrl']
];

$GLOBALS['bsgInstanceStatusCheckAllowedIP'] = trim( getenv( 'WIKI_STATUSCHECK_ALLOWED' ) ?? '' );

$GLOBALS['wgSimpleSAMLphp_InstallDir'] = '/app/simplesamlphp';

if ( getenv( 'DEV_WIKI_DEBUG' ) ) {
	$GLOBALS['wgShowExceptionDetails'] = true;
	$GLOBALS['wgDevelopmentWarnings'] = true;
	$GLOBALS['wgDebugDumpSql'] = true;
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
	$GLOBALS['wgWikiFarmConfig_dbPrefix'] = trim( getenv( 'WIKI_FARM_DB_PREFIX' ) ?: 'sfr_' );
	$GLOBALS['wgWikiFarmConfig_LocalSettingsAppendPath'] = "$IP/LocalSettings.BlueSpice.php";
	$GLOBALS['wgWikiFarmConfig_useSharedDB'] = getenv( 'WIKI_FARM_USE_SHARED_DB' ) ? true : false;
	$GLOBALS['wgWikiFarmConfig_basePath'] = trim( getenv( 'WIKI_BASE_PATH' ) ?: '' );
	$GLOBALS['wgSharedDB'] = $GLOBALS['wgDBname'];
	$GLOBALS['wgSharedPrefix'] = $GLOBALS['wgDBprefix'];
	$GLOBALS['wgSharedTables'] = [ 'bs_translationtransfer_translations' ];
}

require_once '/data/bluespice/pre-init-settings.php';
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

$GLOBALS['wgArticlePath'] = ( trim(  getenv( 'WIKI_BASE_PATH' ) ?: '/' ) ) . 'wiki/$1';
if ( getenv( 'EDITION' ) === 'farm' ) {
	if( FARMER_IS_ROOT_WIKI_CALL === false ) {
		$GLOBALS['wgScriptPath'] =  trim( getenv( 'WIKI_BASE_PATH' ) ?: '/' ) . FARMER_CALLED_INSTANCE;
		$GLOBALS['wgArticlePath'] = trim( getenv( 'WIKI_BASE_PATH' ) ?: '/' ) . FARMER_CALLED_INSTANCE . '/wiki/$1';
		$GLOBALS['wgWebDAVBaseUri'] = trim( getenv( 'WIKI_BASE_PATH' ) ?: '/' ) . FARMER_CALLED_INSTANCE . '/webdav/';
		// We must store L10N cache file of ROOT_WIKI and INSTANCEs independently, as they have different extensions enabled,
		// which otherwise causes the cache to be invalidated all the time.
		$GLOBALS['wgLocalisationCacheConf']['storeDirectory'] = '/tmp/cache/l10n-instances';
	}
}

require_once '/data/bluespice/post-init-settings.php';
