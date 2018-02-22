<?php

/* @var $plugin \ElggPlugin */
$plugin = elgg_extract('entity', $vars);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('profile_sync:settings:memory_limit'),
	'#help' => elgg_echo('profile_sync:settings:memory_limit:description'),
	'name' => 'params[memory_limit]',
	'value' => $plugin->memory_limit,
	'options_values' => [
		'64M' => elgg_echo('profile_sync:settings:memory_limit:64'),
		'128M' => elgg_echo('profile_sync:settings:memory_limit:128'),
		'256M' => elgg_echo('profile_sync:settings:memory_limit:256'),
		'512M' => elgg_echo('profile_sync:settings:memory_limit:512'),
		'-1' => elgg_echo('profile_sync:settings:memory_limit:unlimited'),
	],
]);
