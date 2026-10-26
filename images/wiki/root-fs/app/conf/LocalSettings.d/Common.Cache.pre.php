<?php
// Object cache; Sets `$cacheUsed` to the cache type (`memcached`, `redis`, `valkey`) or `false`

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
}
