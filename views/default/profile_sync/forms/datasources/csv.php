<?php

$class = [
	'profile-sync-datasource-type',
	'profile-sync-datasource-type-csv',
];

$disabled = false;
if (elgg_extract('datasource_type', $vars) !== 'csv') {
	$disabled = true;
	$class[] = 'hidden';
}

$fields = [];

$fields[] = [
	'#type' => 'text',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:csv:location'),
	'name' => 'params[csv_location]',
	'value' => elgg_extract('csv_location', $vars),
	'required' => true,
	'disabled' => $disabled,
];

$fields[] = [
	'#type' => 'text',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:csv:delimiter'),
	'name' => 'params[csv_delimiter]',
	'value' => elgg_extract('csv_delimiter', $vars),
	'max_length' => 1,
	'required' => true,
	'disabled' => $disabled,
];

$fields[] = [
	'#type' => 'text',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:csv:enclosure'),
	'name' => 'params[csv_enclosure]',
	'value' => elgg_extract('csv_enclosure', $vars),
	'max_length' => 1,
	'required' => true,
	'disabled' => $disabled,
];

$fields[] = [
	'#type' => 'checkbox',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:csv:first_row'),
	'name' => 'params[csv_first_row]',
	'default' => 0,
	'value' => 1,
	'checked' => (bool) elgg_extract('csv_first_row', $vars),
	'switch' => true,
];

echo elgg_view_field([
	'#type' => 'fieldset',
	'#class' => $class,
	'legend' => elgg_echo('profile_sync:admin:datasources:edit:csv'),
	'fields' => $fields,
]);
