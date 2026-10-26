<?php
// S3 compatible file storage; Only included if `$s3Used`

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
