<?php
// INTERNAL_ENABLE_FARM=true, INTERNAL_ENABLE_FARM_SHARED_SEARCH=true

$GLOBALS['wgWikiFarmConfig_useUnifiedSearch'] = true;
// Do not check for permissions per-title when searching, as it cannot be done on foreign pages
// Neo ACL takes care of that on its own
$GLOBALS['bsgESSecureResults'] = false;
