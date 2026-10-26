<?php
// INTERNAL_ENABLE_FARM=false; Must be included after `WIKI_PRE_INIT_SETTINGS_FILE`
// For farms this is done by `extensions/BlueSpiceWikiFarm/src/Dispatcher.php`

define( 'BSDATADIR', BSROOTDIR . "/data" ); //Present
define( 'BS_DATA_DIR', "{$GLOBALS['wgUploadDirectory']}/bluespice" ); //Future
define( 'BS_CACHE_DIR', "{$GLOBALS['wgFileCacheDirectory']}/bluespice" );
define( 'BS_DATA_PATH', "{$GLOBALS['wgUploadPath']}/bluespice" );
