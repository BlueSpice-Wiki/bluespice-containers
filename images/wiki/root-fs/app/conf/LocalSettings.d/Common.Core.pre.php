<?php
// Basic settings, required by every codebase

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

$GLOBALS['wgMessageCacheType'] = CACHE_ACCEL;
$GLOBALS['wgLocalisationCacheConf']['store'] = 'files';
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

$GLOBALS['wgPdfProcessor'] = '/usr/bin/gs';
$GLOBALS['wgPdfPostProcessor'] = $GLOBALS['wgImageMagickConvertCommand'];
$GLOBALS['wgPdfInfo'] = '/usr/bin/pdfinfo';
$GLOBALS['wgPdftoText'] = '/usr/bin/pdftotext';

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
}

// Taken from `extensions/BlueSpiceWikiFarm/src/Dispatcher.php`
// Not all of this may be required
$GLOBALS['wgUploadDirectory'] = "/data/bluespice/images";
$GLOBALS['wgReadOnlyFile'] = "{$GLOBALS['wgUploadDirectory']}/lock_yBgMBwiR";
$GLOBALS['wgFileCacheDirectory'] = "{$GLOBALS['wgUploadDirectory']}/cache";
$GLOBALS['wgDeletedDirectory'] = "{$GLOBALS['wgUploadDirectory']}/deleted";
$GLOBALS['wgCacheDirectory'] = "/data/bluespice/cache";
define( 'BSROOTDIR', '/data/bluespice/extensions/BlueSpiceFoundation' );
