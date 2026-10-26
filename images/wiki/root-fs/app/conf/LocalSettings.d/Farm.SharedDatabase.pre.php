<?php
// INTERNAL_ENABLE_FARM=true, INTERNAL_ENABLE_FARM_SHARED_DATABASE=true

// Share notifications
$GLOBALS['wgSharedTables'][] = 'notifications_event';
$GLOBALS['wgSharedTables'][] = 'notifications_instance';
$GLOBALS['wgSharedTables'][] = 'notifications_web_query_store';
// Share PageReadConfirmations
$GLOBALS['wgSharedTables'][] = 'page_read_confirmations';
$GLOBALS['wgSharedTables'][] = 'page_read_confirmations_assignments';
$GLOBALS['wgSharedTables'][] = 'page_read_confirmations_requests';
// Share PageVersions
$GLOBALS['wgSharedTables'][] = 'page_version';
// Share Appointments
$GLOBALS['wgSharedTables'][] = 'calendars';
$GLOBALS['wgSharedTables'][] = 'appointments';
$GLOBALS['wgSharedTables'][] = 'appointment_participant_assignments';
$GLOBALS['wgSharedTables'][] = 'appointment_participants';
$GLOBALS['wgSharedTables'][] = 'appointment_event_types';
$GLOBALS['wgSharedTables'][] = 'appointment_event_type_assignments';
// Share user index, to enable global user store (restricted further by access control)
$GLOBALS['wgSharedTables'][] = 'mws_user_index';
$GLOBALS['wgSharedTables'][] = 'wikifarm_groups';
$GLOBALS['wgSharedTables'][] = 'mws_data_stash';
$GLOBALS['wgSharedTables'][] = 'mws_title_index';
$GLOBALS['wgSharedTables'][] = 'page_excerpts';

// Share OAuth. Either all or none of these tables must be shared, as Extension:OAuth creates them
// with a single schema file, based on the existence of `oauth_registered_consumer`
$GLOBALS['wgSharedTables'][] = 'oauth2_access_tokens';
$GLOBALS['wgSharedTables'][] = 'oauth_accepted_consumer';
$GLOBALS['wgSharedTables'][] = 'oauth_registered_consumer';

$GLOBALS['wgSharedTables'][] = 'uto_tasks';
// Galaxy has uses only groups from this table, which are shared
$GLOBALS['wgSharedTables'][] = 'mwstake_dynamic_config';

$GLOBALS['wgSharedTables'][] = 'issuetrackerlinks_auth_tokens';
$GLOBALS['wgSharedTables'][] = 'issuetrackerlinks_issues';

// Share FlexiSkin
$GLOBALS['wgWikiFarmConfig_globalUploadPath'] = 'w/img_auth.php';
$GLOBALS['wgSharedTables'][] = 'flexiskin';
// Make Privacy requests global - as user management is global
$GLOBALS['wgSharedTables'][] = 'bs_privacy_request';
