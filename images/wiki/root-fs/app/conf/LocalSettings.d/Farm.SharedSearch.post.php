<?php
// INTERNAL_ENABLE_FARM=true, INTERNAL_ENABLE_FARM_SHARED_SEARCH=true

// `rootInstanceWikiId` is only available after the bootstrapper has run
$GLOBALS['bsgESIndexPrefix'] = $GLOBALS['wgWikiFarmConfigInternal']->get( 'rootInstanceWikiId' ) . '_farm-shared-index';
