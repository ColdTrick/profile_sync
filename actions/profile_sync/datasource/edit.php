<?php

$guid = (int) get_input('guid');
$params = get_input('params', [], false);
$title = get_input('title');

if (!is_array($params)) {
	return elgg_error_response(elgg_echo('profile_sync:action:datasource:edit:error:params'));
}

if (empty($title)) {
	return elgg_error_response(elgg_echo('profile_sync:action:error:title'));
}

$datasource_type = elgg_extract('datasource_type', $params);
if (empty($datasource_type)) {
	return elgg_error_response(elgg_echo('profile_sync:action:datasource:edit:error:type'));
}

$entity = false;
if (!empty($guid)) {
	$entity = get_entity($guid);
	if (!$entity instanceof ProfileSyncDatasource || !$entity->canEdit()) {
		return elgg_error_response(elgg_echo('actionunauthorized'));
	}
} else {
	$entity = new ProfileSyncDatasource();
	if (!$entity->save()) {
		return elgg_error_response(elgg_echo('save:fail'));
	}
}

if (!$entity instanceof ProfileSyncDatasource) {
	return elgg_error_response(elgg_echo('profile_sync:action:datasource:edit:error:entity'));
}
	
$entity->title = $title;

// some inputs need to be unfiltered
$unfiltered_params = [
	'dbquery',
];

foreach ($params as $key => $param) {
	// filter input
	if (!in_array($key, $unfiltered_params)) {
		$param = filter_tags($param);
	}
	
	if (empty($param)) {
		unset($entity->{$key});
	} else {
		$entity->{$key} = $param;
	}
}

if (!$entity->save()) {
	return elgg_error_response(elgg_echo('save:fail'));
}

return elgg_ok_response('', elgg_echo('admin:configuration:success'));
