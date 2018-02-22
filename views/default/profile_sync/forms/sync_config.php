<?php

$sync_config_guid = (int) get_input('guid');
$sync_config = get_entity($sync_config_guid);
if (!$sync_config instanceof ProfileSyncConfig) {
	$sync_config = null;
	$datasource_guid = (int) get_input('datasource_guid');
	$title = elgg_echo('profile_sync:admin:sync_configs:add');
} else {
	$datasource_guid = $sync_config->container_guid;
	$title = elgg_echo('profile_sync:admin:sync_configs:edit', [$sync_config->getDisplayName()]);
}

$datasource = get_entity($datasource_guid);
if (!$datasource instanceof ProfileSyncDatasource) {
	return;
}

$body_vars = profile_sync_prepare_sync_config_form_vars($datasource, $sync_config);

$body = elgg_view_form('profile_sync/sync_config/edit', [], $body_vars);

echo elgg_view_module('info', $title, $body);
