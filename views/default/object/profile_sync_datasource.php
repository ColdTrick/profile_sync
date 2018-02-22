<?php

$entity = elgg_extract('entity', $vars);
if (!$entity instanceof ProfileSyncDatasource) {
	return;
}

$title = $entity->getDisplayName();
$title .= ' (' . elgg_echo('profile_sync:admin:datasources:type:' . $entity->datasource_type) . ')';

$params = [
	'title' => $title,
	'subtitle' => false,
];
$params = $params + $vars;
echo elgg_view('object/elements/summary', $params);
