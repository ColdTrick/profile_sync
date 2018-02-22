<?php

elgg_require_js('profile_sync/datasource/edit');

$entity = elgg_extract('entity', $vars);
if ($entity instanceof ProfileSyncDatasource) {
	echo elgg_view_field([
		'#type' => 'hidden',
		'name' => 'guid',
		'value' => $entity->guid,
	]);
}

echo elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('title'),
	'name' => 'title',
	'value' => elgg_extract('title', $vars),
	'required' => true,
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('profile_sync:admin:datasources:type'),
	'id' => 'profile-sync-edit-datasource-type',
	'name' => 'params[datasource_type]',
	'options_values' => [
		'' => elgg_echo('profile_sync:admin:datasources:type:choose'),
		'mysql' => elgg_echo('profile_sync:admin:datasources:type:mysql'),
		'csv' => elgg_echo('profile_sync:admin:datasources:type:csv'),
	],
	'value' => elgg_extract('datasource_type', $vars),
	'required' => true,
]);

// datasource type extra fields
echo elgg_view('profile_sync/forms/datasources/mysql', $vars);
echo elgg_view('profile_sync/forms/datasources/csv', $vars);

// form footer
$footer = elgg_view_field([
	'#type' => 'submit',
	'value' => elgg_echo('save'),
]);

elgg_set_form_footer($footer);
