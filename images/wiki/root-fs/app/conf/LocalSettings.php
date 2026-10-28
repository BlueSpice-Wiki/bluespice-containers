<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

// The files in `LocalSettings.d/` are included based on the infrastructure configuration
// of the image (`/app/.env.infrastructure`, see `/app/bin/init-infrastructure-envs`).
// File names: `<Scope>.<Purpose>.<pre|post>.php`
// - Scope: `Common` (always), `Farm` (INTERNAL_ENABLE_FARM=true) or `Standalone` (INTERNAL_ENABLE_FARM=false)
// - pre/post: Included before/after the bootstrapper (`INTERNAL_BOOTSTRAPER`)
// The order of inclusion is defined here, not by the file names.
//
// Everything is included within the scope of this closure, so no local variable leaks into the
// global scope. Therefore all included files (including `WIKI_PRE_INIT_SETTINGS_FILE`,
// `WIKI_POST_INIT_SETTINGS_FILE` and the settings of the codebase) must use `$GLOBALS['wg...']`
// instead of `$wg...`, and `MW_INSTALL_PATH` instead of `$IP`.
// Local variables of included files are shared within this scope, e.g. `$cacheUsed` and `$s3Used`
// are set by `*.pre.php` files and used by `*.post.php` files.
( static function () {
	$settingsDir = __DIR__ . '/LocalSettings.d';
	$isFarm = bsInfrastructureFlag( 'INTERNAL_ENABLE_FARM' );

	require_once "$settingsDir/Common.Core.pre.php";
	require_once "$settingsDir/Common.Cache.pre.php";
	require_once "$settingsDir/Common.Services.pre.php";
	require_once "$settingsDir/Common.FileStorage.pre.php";

	if ( $isFarm ) {
		require_once "$settingsDir/Farm.Base.pre.php";
		if ( bsInfrastructureFlag( 'INTERNAL_ENABLE_FARM_SHARED_USER_SESSION' ) ) {
			require_once "$settingsDir/Farm.SharedUserSession.pre.php";
		}
		if ( bsInfrastructureFlag( 'INTERNAL_ENABLE_FARM_SHARED_SEARCH' ) ) {
			require_once "$settingsDir/Farm.SharedSearch.pre.php";
		}
		if ( bsInfrastructureFlag( 'INTERNAL_ENABLE_FARM_SHARED_DATABASE' ) ) {
			require_once "$settingsDir/Farm.SharedDatabase.pre.php";
		}
		if ( bsInfrastructureFlag( 'INTERNAL_ENABLE_FARM_SHARED_JOBQUEUE' ) ) {
			require_once "$settingsDir/Farm.SharedJobQueue.pre.php";
		}
	}

	require_once trim( getenv( 'WIKI_PRE_INIT_SETTINGS_FILE' ) );

	if ( !$isFarm ) {
		// Must be included after `WIKI_PRE_INIT_SETTINGS_FILE`
		require_once "$settingsDir/Standalone.Base.pre.php";
	}

	// Standalone: `StandardBootstrapper.php`
	// Farm: `WikiFarm.setup.php`, which includes `StandardBootstrapper.php` on its own
	require_once bsInfrastructureConfig( 'INTERNAL_BOOTSTRAPER' ) ?: __DIR__ . '/StandardBootstrapper.php';

	require_once "$settingsDir/Common.Core.post.php";
	if ( $s3Used ) {
		require_once "$settingsDir/Common.FileStorage.post.php";
	}
	if ( $isFarm ) {
		require_once "$settingsDir/Farm.Base.post.php";
		if ( bsInfrastructureFlag( 'INTERNAL_ENABLE_FARM_SHARED_DATABASE' ) ) {
			require_once "$settingsDir/Farm.SharedDatabase.post.php";
		}
	}

	require_once trim( getenv( 'WIKI_POST_INIT_SETTINGS_FILE' ) );
} )();
