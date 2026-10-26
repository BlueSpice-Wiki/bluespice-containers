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

/**
 * Assembles a URL from ENV variables. Parameters can either be
 * the ENV variable names as strings, or arrays with the ENV
 * variable names as the first element and a fallback value as
 * the second element.
 *
 * Examples:
 * ```
 *	$GLOBALS['wgServer'] = bsAssembleURL( 'WIKI_PROTOCOL', 'WIKI_HOST','WIKI_PORT' );
 * ```
 *
 * ```
 *	$GLOBALS['wgServer'] = bsAssembleURL(
 *		[ 'WIKI_PROTOCOL', 'https' ],
 *		[ 'WIKI_HOST', 'localhost' ],
 *		[ 'WIKI_PORT', '443' ]
 *	);
 * ```
 *
 * @param array|string $proto
 * @param array|string $hostname
 * @param array|string $port
 * @param array|string $path
 * @param array|string $username
 * @param array|string $password
 * @return string The assembled URL
 */
function bsAssembleURL( $proto, $hostname, $port, $path = [], $username = [], $password = [] ) {

	// Allow for ENV variable names without fallback, as fallbacks are now
	// set centrally in `/app/bin/init-envs`
	$proto = is_string( $proto ) ? [ $proto, '' ] : $proto;
	$hostname = is_string( $hostname ) ? [ $hostname, '' ] : $hostname;
	$port = is_string( $port ) ? [ $port, '' ] : $port;
	$path = is_string( $path ) ? [ $path, '' ] : $path;
	$username = is_string( $username ) ? [ $username, '' ] : $username;
	$password = is_string( $password ) ? [ $password, '' ] : $password;

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

	$auth = '';
	if ( !empty( $username ) && !empty( $password ) ) {
		$username = trim( getenv( $username[0] ) ?: $username[1] );
		$password = trim( getenv( $password[0] ) ?: $password[1] );

		if ( $username && $password ) {
			$encUsername = rawurlencode( $username );
			$encPassword = rawurlencode( $password );
			$auth = "$encUsername:$encPassword@";
		}
	}

	return "$protocol://$auth$host{$portSuffix}$path";
}

/**
 * Returns a value of the infrastructure configuration of the image. It is created at build time from
 * `_bluespice/.env.infrastructure.<edition>` of the codebase and on container start completed by
 * deployment dependent values of the runtime environment (see `/app/bin/init-infrastructure-envs`).
 * Falls back to the build time values, if the container was not started through `/app/bin/entrypoint`.
 *
 * @param string $name e.g. `INTERNAL_ENABLE_FARM`
 * @return string Empty string if not set
 */
function bsInfrastructureConfig( string $name ): string {
	static $config = null;
	if ( $config === null ) {
		$config = [];
		$file = file_exists( '/app/.env.infrastructure' )
			? '/app/.env.infrastructure'
			: '/app/.env.infrastructure.build';
		$lines = @file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) ?: [];
		foreach ( $lines as $line ) {
			if ( str_starts_with( $line, '#' ) || !str_contains( $line, '=' ) ) {
				continue;
			}
			[ $key, $value ] = explode( '=', $line, 2 );
			$config[trim( $key )] = trim( $value );
		}
	}
	return $config[$name] ?? '';
}

/**
 * @param string $name e.g. `INTERNAL_ENABLE_FARM`
 * @return bool
 */
function bsInfrastructureFlag( string $name ): bool {
	return bsInfrastructureConfig( $name ) === 'true';
}

// Contract with the codebase: product and edition of the image are available as ENV variables
// `INTERNAL_PRODUCT` and `INTERNAL_EDITION` to every PHP process (web, CLI, `docker exec`).
// The codebase should prefer them over its `BLUESPICE-PRODUCT`/`BLUESPICE-EDITION` files, as
// `BLUESPICE-EDITION` is only the default edition of a codebase that may serve several editions.
// The values of the image always win, as they can not be changed at runtime.
foreach ( [ 'INTERNAL_PRODUCT', 'INTERNAL_EDITION' ] as $bsInfrastructureEnvName ) {
	if ( bsInfrastructureConfig( $bsInfrastructureEnvName ) !== '' ) {
		putenv( "$bsInfrastructureEnvName=" . bsInfrastructureConfig( $bsInfrastructureEnvName ) );
	}
}
unset( $bsInfrastructureEnvName );

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
