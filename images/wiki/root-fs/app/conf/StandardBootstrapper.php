<?php

//
// Standard Bootstrapper: Includes all settings of the codebase and loads the registered
// extensions and skins.
//
// - Standalone codebases: Used as `INTERNAL_BOOTSTRAPER` (default)
// - Farm codebases: `INTERNAL_BOOTSTRAPER` is `WikiFarm.setup.php`, which includes this file
//   through `$wgWikiFarmConfig_LocalSettingsAppendPath` (see `LocalSettings.d/Farm.Base.pre.php`)
//

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

//
// Step 1: Include all settings files of the codebase
//
// Settings files must not call `wfLoadExtension`/`wfLoadSkin` themselves, but register
// extensions and skins in `$GLOBALS['bsgEnabledExtensions']` and `$GLOBALS['bsgEnabledSkins']`:
//
//   $GLOBALS['bsgEnabledExtensions']['MyExtension'] = true;    // load from default location
//   $GLOBALS['bsgEnabledExtensions']['MyExtension'] = '/path/to/extension.json'; // load from custom location
//   $GLOBALS['bsgEnabledExtensions']['MyExtension'] = false;   // disable (e.g. in a later settings file)
//
// Runs within a closure, so the settings files must use `$GLOBALS['wg...']` instead of `$wg...`
// and `MW_INSTALL_PATH` instead of `$IP`
( static function () {
	$settingsDir = $GLOBALS['bsgSettingsDir'] ?? MW_INSTALL_PATH . '/_bluespice/settings.d';
	$GLOBALS['bsgEnabledExtensions'] = $GLOBALS['bsgEnabledExtensions'] ?? [];
	$GLOBALS['bsgEnabledSkins'] = $GLOBALS['bsgEnabledSkins'] ?? [];

	foreach ( glob( "$settingsDir/*.php" ) as $settingsFile ) {
		require_once $settingsFile;
	}
} )();

//
// Step 2: Load all registered extensions and skins
//
// When running `update.php` _all_ registered extensions and skins are loaded, even disabled
// ones, so the database schema is always complete. Otherwise enabling an extension later
// would require running `update.php` again.
//
( static function () {
	$loadAll = defined( 'MW_UPDATER' );
	$types = [
		'bsgEnabledExtensions' => [ $GLOBALS['wgExtensionDirectory'], 'extension.json', 'wfLoadExtension' ],
		'bsgEnabledSkins' => [ $GLOBALS['wgStyleDirectory'], 'skin.json', 'wfLoadSkin' ],
	];
	foreach ( $types as $registryName => [ $baseDir, $manifestName, $loadFunction ] ) {
		foreach ( $GLOBALS[$registryName] as $name => $value ) {
			$manifestPath = is_string( $value )
				? $value
				: "$baseDir/$name/$manifestName";
			if ( $value === false || $value === null ) {
				// Disabled ones are only loaded by the updater, and only if they are available
				// in this codebase at all (e.g. removed by a custom build)
				if ( !$loadAll || !file_exists( $manifestPath ) ) {
					continue;
				}
			}
			$loadFunction( $name, $manifestPath );
		}
	}
} )();
