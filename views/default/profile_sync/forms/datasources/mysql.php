<?php

$class = [
	'profile-sync-datasource-type',
	'profile-sync-datasource-type-mysql',
];

$disabled = false;
if (elgg_extract('datasource_type', $vars) !== 'mysql') {
	$disabled = true;
	$class[] = 'hidden';
}

$fields = [];

$fields[] = [
	'#type' => 'text',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbhost'),
	'name' => 'params[dbhost]',
	'value' => elgg_extract('dbhost', $vars),
	'required' => true,
	'disabled' => $disabled,
];

$fields[] = [
	'#type' => 'number',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbport'),
	'name' => 'params[dbport]',
	'value' => elgg_extract('dbport', $vars),
	'required' => true,
	'disabled' => $disabled,
	'placeholder' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbport:default'),
	'min' => 0,
	'max' => 65535,
];

$fields[] = [
	'#type' => 'text',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbname'),
	'name' => 'params[dbname]',
	'value' => elgg_extract('dbname', $vars),
	'required' => true,
	'disabled' => $disabled,
];

$fields[] = [
	'#type' => 'text',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbusername'),
	'name' => 'params[dbusername]',
	'value' => elgg_extract('dbusername', $vars),
	'required' => true,
	'disabled' => $disabled,
];

$fields[] = [
	'#type' => 'password',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbpassword'),
	'name' => 'params[dbpassword]',
	'value' => elgg_extract('dbpassword', $vars),
	'always_empty' => false,
	'class' => 'elgg-input-text',
];

$fields[] = [
	'#type' => 'plaintext',
	'#label' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbquery'),
	'#help' => elgg_echo('profile_sync:admin:datasources:edit:mysql:dbquery:description', ['[[lastrun]]']),
	'name' => 'params[dbquery]',
	'value' => elgg_extract('dbquery', $vars),
];

echo elgg_view_field([
	'#type' => 'fieldset',
	'#class' => $class,
	'legend' => elgg_echo('profile_sync:admin:datasources:edit:mysql'),
	'fields' => $fields,
]);
