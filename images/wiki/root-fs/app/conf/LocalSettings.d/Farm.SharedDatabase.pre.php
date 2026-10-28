<?php
// INTERNAL_ENABLE_FARM=true, INTERNAL_ENABLE_FARM_SHARED_DATABASE=true

// Share FlexiSkin: images of the shared skin are served by the root wiki
$GLOBALS['wgWikiFarmConfig_globalUploadPath'] = 'w/img_auth.php';

// Share OAuth (`oauth2_access_tokens`, `oauth_accepted_consumer`, `oauth_registered_consumer`).
// Either all or none of these tables must be shared, as Extension:OAuth creates them
// with a single schema file, based on the existence of `oauth_registered_consumer`
$GLOBALS['wgSharedTables'][] = 'appointment_event_type_assignments';
$GLOBALS['wgSharedTables'][] = 'appointment_event_types';
$GLOBALS['wgSharedTables'][] = 'appointment_participant_assignments';
$GLOBALS['wgSharedTables'][] = 'appointment_participants';
$GLOBALS['wgSharedTables'][] = 'appointments';
$GLOBALS['wgSharedTables'][] = 'bs_privacy_request';
$GLOBALS['wgSharedTables'][] = 'calendars';
$GLOBALS['wgSharedTables'][] = 'flexiskin';
$GLOBALS['wgSharedTables'][] = 'issuetrackerlinks_auth_tokens';
$GLOBALS['wgSharedTables'][] = 'issuetrackerlinks_issues';
$GLOBALS['wgSharedTables'][] = 'mws_data_stash';
$GLOBALS['wgSharedTables'][] = 'mwstake_dynamic_config';
$GLOBALS['wgSharedTables'][] = 'mws_title_index';
$GLOBALS['wgSharedTables'][] = 'mws_user_index';
$GLOBALS['wgSharedTables'][] = 'notifications_event';
$GLOBALS['wgSharedTables'][] = 'notifications_instance';
$GLOBALS['wgSharedTables'][] = 'notifications_web_query_store';
$GLOBALS['wgSharedTables'][] = 'oauth2_access_tokens';
$GLOBALS['wgSharedTables'][] = 'oauth_accepted_consumer';
$GLOBALS['wgSharedTables'][] = 'oauth_registered_consumer';
$GLOBALS['wgSharedTables'][] = 'page_excerpts';
$GLOBALS['wgSharedTables'][] = 'page_read_confirmations';
$GLOBALS['wgSharedTables'][] = 'page_read_confirmations_assignments';
$GLOBALS['wgSharedTables'][] = 'page_read_confirmations_requests';
$GLOBALS['wgSharedTables'][] = 'page_version';
$GLOBALS['wgSharedTables'][] = 'uto_tasks';
$GLOBALS['wgSharedTables'][] = 'wikifarm_groups';
