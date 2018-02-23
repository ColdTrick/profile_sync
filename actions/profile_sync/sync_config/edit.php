<?php
/**
 * create/edit a sync config
 */

elgg_make_sticky_form('sync_config/edit');

$guid = (int) get_input('guid');
$container_guid = (int) get_input('container_guid');

$title = elgg_get_title_input();

$datasource_id = get_input('datasource_id');
$datasource_id_fallback = get_input('datasource_id_fallback');
$profile_id = get_input('profile_id');
$profile_id_fallback = get_input('profile_id_fallback');

$datasource_cols = get_input('datasource_cols');
$profile_cols = get_input('profile_cols');
$access = get_input('access');
$always_override = get_input('always_override');

$schedule = get_input('schedule');
$create_user = (int) get_input('create_user');
$ban_user = (int) get_input('ban_user');
$unban_user = (int) get_input('unban_user');
$notify_user = (int) get_input('notify_user');
$log_cleanup_count = (int) get_input('log_cleanup_count');

if (empty($guid) && empty($container_guid)) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:guid'));
}

if (empty($title)) {
	return elgg_error_response(elgg_echo('profile_sync:action:error:title'));
}

if (($datasource_id === '') || empty($profile_id)) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:unique_id'));
}

if ((!$ban_user && !$unban_user) && (empty($datasource_cols) || empty($profile_cols))) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:fields'));
}

if ($create_user && $ban_user) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:create_ban'));
}
if ($create_user && $unban_user) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:create_unban'));
}
if ($ban_user && $unban_user) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:ban_unban'));
}

// translate datasource_cols and profile_cols
$default_access = get_default_access();
$sync_match = [];
foreach ($datasource_cols as $index => $datasource_col_name) {
	if (($datasource_col_name === '') || ($profile_cols[$index] === '')) {
		continue;
	}
	
	$sync_match[$datasource_col_name . PROFILE_SYNC_DATASOURCE_COL_SEPERATOR . $index] = [
		'profile_field' => $profile_cols[$index],
		'access' => (int) elgg_extract($index, $access, $default_access),
		'always_override' => (int) elgg_extract($index, $always_override, true),
	];
}

if ((!$ban_user && !$unban_user) && empty($sync_match)) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:fields'));
}

if (!empty($guid)) {
	$entity = get_entity($guid);
	if (!$entity instanceof ProfileSyncConfig || !$entity->canEdit()) {
		return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:entity'));
	}
} else {
	$entity = new ProfileSyncConfig();
	$entity->container_guid = $container_guid;
	
	if (!$entity->save()) {
		return elgg_error_response(elgg_echo('save:fail'));
	}
}

if (!$entity instanceof ProfileSyncConfig) {
	return elgg_error_response(elgg_echo('profile_sync:action:sync_config:edit:error:entity'));
}

// save all the data
$entity->title = $title;
$entity->datasource_id = $datasource_id;
$entity->datasource_id_fallback = $datasource_id_fallback;
$entity->profile_id = $profile_id;
$entity->profile_id_fallback = $profile_id_fallback;

$entity->sync_match = json_encode($sync_match);
$entity->schedule = $schedule;
$entity->create_user = $create_user;
$entity->ban_user = $ban_user;
$entity->unban_user = $unban_user;
$entity->notify_user = $notify_user;

$entity->log_cleanup_count = $log_cleanup_count;

if (!$entity->save()) {
	return elgg_error_response(elgg_echo('save:fail'));
}

elgg_clear_sticky_form('sync_config/edit');

return elgg_ok_response('', elgg_echo('admin:configuration:success'));
