<?php
// INTERNAL_ENABLE_FARM=true, INTERNAL_ENABLE_FARM_SHARED_JOBQUEUE=true
// Requires a `bluespice/jobqueue` compatible (Redis/Valkey) service; Can be disabled with `JOBQUEUE_HOST=-`

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
}
