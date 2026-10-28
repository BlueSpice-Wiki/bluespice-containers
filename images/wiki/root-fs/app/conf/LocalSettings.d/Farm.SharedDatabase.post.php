<?php
// INTERNAL_ENABLE_FARM=true, INTERNAL_ENABLE_FARM_SHARED_DATABASE=true

$GLOBALS['wgMWOAuthCentralWiki'] = $GLOBALS['wgWikiFarmConfigInternal']->get( 'rootInstanceWikiId' );
$GLOBALS['wgMWOAuthSharedUserIDs'] = true;
// The codebase maps this domain while `wgMWOAuthCentralWiki` is not set yet, so it must be set again
$GLOBALS['wgVirtualDomainsMapping']['virtual-oauth'] = [ 'db' => $GLOBALS['wgMWOAuthCentralWiki'] ];
