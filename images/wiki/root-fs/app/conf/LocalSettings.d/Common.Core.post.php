<?php
// Basic settings that must be applied after the codebase settings

$GLOBALS['wgArticlePath'] = ( trim(  getenv( 'WIKI_BASE_PATH' ) ) ) . trim( getenv( 'WIKI_ARTICLE_PATH' ) ?: 'wiki' ) . '/$1';

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
}
