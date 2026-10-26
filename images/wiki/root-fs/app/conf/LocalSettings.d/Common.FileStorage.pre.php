<?php
// S3 compatible file storage; Sets `$s3Used`

# See https://github.com/edwardspec/mediawiki-aws-s3/tree/master?tab=readme-ov-file#using-another-s3-compatible-service-not-amazon-s3-itself
$s3Used = false;
if ( getenv( 'FILESTORE_HOST' ) && getenv( 'FILESTORE_HOST' ) !== '-' ) {
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
