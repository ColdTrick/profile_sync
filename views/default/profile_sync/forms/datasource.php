<?php

$title_text = elgg_echo('profile_sync:admin:datasources:add');

$entity = elgg_extract('entity', $vars);
if ($entity instanceof ProfileSyncDatasource) {
	$title_text = elgg_echo('profile_sync:admin:datasources:edit', [$entity->getDisplayName()]);
} else {
	$entity = null;
}

$body_vars = profile_sync_prepare_datasource_form_vars($entity);
$body = elgg_view_form('profile_sync/datasource/edit', [], $body_vars);

echo elgg_view_module('info', $title_text, $body);
