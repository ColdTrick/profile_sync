<?php

$entity = elgg_extract('entity', $vars);
if (!$entity instanceof ProfileSyncConfig) {
	return;
}

$datasource = $entity->getContainerEntity();

$title = $entity->getDisplayName();
$title .= elgg_format_element('span', [
	'class' => ['mls', 'elgg-quiet'],
	'title' => elgg_echo('profile_sync:admin:sync_configs:edit:datasource'),
], '(' . $datasource->getDisplayName() . ')');

$subtitle = [];

// type of job
if ($entity->create_user) {
	$subtitle[] = elgg_format_element('span', ['class' => 'mrs'], elgg_echo('profile_sync:sync_config:sync_status:create'));
} elseif ($entity->ban_user) {
	$subtitle[] = elgg_format_element('span', ['class' => 'mrs'], elgg_echo('profile_sync:sync_config:sync_status:ban'));
} elseif ($entity->unban_user) {
	$subtitle[] = elgg_format_element('span', ['class' => 'mrs'], elgg_echo('profile_sync:sync_config:sync_status:unban'));
} else {
	$subtitle[] = elgg_format_element('span', ['class' => 'mrs'], elgg_echo('profile_sync:sync_config:sync_status:default'));
}

// schedule
if ($entity->schedule === 'manual') {
	$schedule_text = elgg_echo('profile_sync:sync_configs:schedule:manual');
} else {
	$schedule_text = elgg_echo("profile_sync:interval:{$entity->schedule}");
}
$subtitle[] = elgg_format_element('span', ['class' => 'mrs'], elgg_echo('profile_sync:admin:sync_configs:edit:schedule') . ': ' . $schedule_text);

// last run
if ($entity->lastrun) {
	$subtitle[] = elgg_format_element('span', ['class' => 'mrs'], elgg_echo('profile_sync:interval:friendly') . ': ' . elgg_view_friendly_time($entity->lastrun));
}

// output
$params = [
	'entity' => $entity,
	'title' => $title,
	'subtitle' => implode('', $subtitle),
];
$params = $params + $vars;
$list_body = elgg_view('object/elements/summary', $params);

echo elgg_view_image_block('', $list_body);
