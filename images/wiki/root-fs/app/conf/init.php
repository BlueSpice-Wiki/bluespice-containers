<?php

if ( isset( $_REQUEST['_profiler'] ) ) {
	$excimer = new ExcimerProfiler();
	$excimer->setPeriod( 0.001 ); // 1ms
	$excimer->setEventType( EXCIMER_REAL );
	$excimer->start();
	register_shutdown_function( function () use ( $excimer ) {
		$excimer->stop();
		$profilerType = $_REQUEST['_profiler'] === 'speedscope' ? 'speedscope' : 'trace';
		$fileExt = $_REQUEST['_profiler'] === 'speedscope' ? 'json' : 'log';
		unset( $_REQUEST['_profiler'] ); // Prevent `Unrecognized parameter: _profiler.` on API modules
		$timestamp = ( new DateTime )->format( 'Y-m-d_His_v' );
		$filename = "/data/bluespice/logs/{$profilerType}-{$timestamp}-" . MW_ENTRY_POINT . ".{$fileExt}";
		$fileContent = '';
		if ( $profilerType === 'speedscope' ) {
			$data = $excimer->getLog()->getSpeedscopeData();
			$data['profiles'][0]['name'] = $_SERVER['REQUEST_URI'];
			$fileContent = json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		} else if ( $profilerType === 'trace' ) {
			$fileContent = $excimer->getLog()->formatCollapsed();
		}
		file_put_contents( $filename, $fileContent );
	} );
}

function bsAssembleURL( $proto, $hostname, $port, $path = [] ) {
	$protocol = trim( getenv( $proto[0] ) ?: $proto[1] );
	$host = trim( getenv( $hostname[0] ) ?: $hostname[1] );
	if ( !empty( $path ) ) {
		$path = trim( getenv( $path[0] ) ?: $path[1] );
	} else {
		$path = '';
	}
	$portSuffix = getenv( $port[0] )
					? ':' . trim( getenv( $port[0] ) )
					: ':' . $port[1];

	if ( $protocol === 'http' && $portSuffix === ':80' ) {
		$portSuffix = '';
	} elseif ( $protocol === 'https' && $portSuffix === ':443' ) {
		$portSuffix = '';
	}

	return "$protocol://$host{$portSuffix}$path";
}

// We must not set MW_CONFIG_FILE if we are running the CLI installer
// in `run-installation`, because otherwise it will not allow us to install
if (
	PHP_SAPI === 'cli'
	&& isset( $_SERVER['SCRIPT_NAME'] )
	&& basename( $_SERVER['SCRIPT_NAME'] ) === 'install.php'
) {
return;
}

define( 'MW_CONFIG_FILE', '/app/conf/LocalSettings.php' );
