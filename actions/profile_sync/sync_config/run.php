<?php
/**
 * Start a sync config now
 */

$guid = (int) get_input('guid');
if (empty($guid)) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

$entity = get_entity($guid);
if (!$entity instanceof ProfileSyncConfig) {
	return elgg_error_response(elgg_echo('actionunauthorized'));
}

// get current memory limit
$old_memory_limit = ini_get('memory_limit');

// set new memory limit
$setting = elgg_get_plugin_setting('memory_limit', 'profile_sync');
if (!empty($setting)) {
	ini_set('memory_limit', $setting);
}

profile_sync_proccess_configuration($entity);

// log cleanup
$entity->cleanupLogFiles();

// reset memory limit
ini_set('memory_limit', $old_memory_limit);

return elgg_ok_response('', elgg_echo('profile_sync:action:sync_config:run'));
