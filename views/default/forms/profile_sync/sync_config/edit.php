<?php

elgg_require_js('profile_sync/sync_config/edit');

/* @var $datasource ProfileSyncDatasource */
$datasource = elgg_extract('datasource', $vars);
$entity = elgg_extract('entity', $vars);
$ps = elgg_extract('profile_sync', $vars);

if (!$ps instanceof ProfileSync) {
	echo elgg_view('output/longtext', [
		'value' => elgg_echo('profile_sync:admin:sync_configs:edit:no_datasource'),
	]);
	return;
}

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'container_guid',
	'value' => (int) elgg_extract('container_guid', $vars),
]);

if ($entity instanceof ProfileSyncConfig) {
	echo elgg_view_field([
		'#type' => 'hidden',
		'name' => 'guid',
		'value' => $entity->guid,
	]);
}

// get field config
$datasource_cols = $ps->getColumns();
$profile_fields = elgg_get_config('profile_fields');

// show which datasource
echo elgg_format_element('div', [],
	elgg_format_element('strong', ['class' => 'mrs'], elgg_echo('profile_sync:admin:sync_configs:edit:datasource') . ':') .
	$datasource->getDisplayName()
);

if (empty($datasource_cols) || empty($profile_fields)) {
	echo elgg_view('output/longtext', [
		'value' => elgg_echo('profile_sync:admin:sync_configs:edit:no_columns'),
	]);
	return;
}

$override_options = [
	'1' => elgg_echo('option:yes'),
	'0' => elgg_echo('option:no'),
];

$datasource_columns = [
	'' => elgg_echo('profile_sync:admin:sync_configs:edit:select_datasource_column'),
];
$datasource_columns = array_merge($datasource_columns, $datasource_cols);

$profile_columns = [
	'' => elgg_echo('profile_sync:admin:sync_configs:edit:select_profile_column'),
	'name' => elgg_echo('name'),
	'username' => elgg_echo('username'),
	'email' => elgg_echo('email'),
	'user_icon_full_path' => elgg_echo('profile_sync:admin:sync_configs:edit:profile_column:icon_full'),
	'user_icon_relative_path' => elgg_echo('profile_sync:admin:sync_configs:edit:profile_column:icon_relative'),
];
foreach ($profile_fields as $metadata_name => $type) {
	$name = $metadata_name;
	
	$lan_key = "profile:{$metadata_name}";
	if (elgg_language_key_exists($lan_key)) {
		$name = elgg_echo($lan_key);
	}
	
	$profile_columns[$metadata_name] = $name;
}

$profile_columns_id = $profile_columns;
unset($profile_columns_id['user_icon_full_path']);
unset($profile_columns_id['user_icon_relative_path']);

// unique title
echo elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('title'),
	'name' => 'title',
	'value' => elgg_extract('title', $vars),
	'required' => true,
]);

// unique fields to match
echo elgg_view_field([
	'#type' => 'fieldset',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:unique_id'),
	'fields' => [
		[
			'#type' => 'select',
			'name' => 'datasource_id',
			'options_values' => $datasource_columns,
			'value' => elgg_extract('datasource_id', $vars),
			'required' => true,
		],
		[
			'#html' => elgg_view_icon('arrow-right', ['class' => 'mrm']),
		],
		[
			'#type' => 'select',
			'name' => 'profile_id',
			'options_values' => $profile_columns_id,
			'value' => elgg_extract('profile_id', $vars),
			'required' => true,
		],
	],
	'align' => 'horizontal',
	'required' => true,
]);

// fallback fields to match
echo elgg_view_field([
	'#type' => 'fieldset',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:unique_id_fallback'),
	'#help' => elgg_echo('profile_sync:admin:sync_configs:edit:unique_id_fallback:description'),
	'fields' => [
		[
			'#type' => 'select',
			'name' => 'datasource_id_fallback',
			'options_values' => $datasource_columns,
			'value' => elgg_extract('datasource_id_fallback', $vars),
		],
		[
			'#html' => elgg_view_icon('arrow-right', ['class' => 'mrm']),
		],
		[
			'#type' => 'select',
			'name' => 'profile_id_fallback',
			'options_values' => $profile_columns_id,
			'value' => elgg_extract('profile_id_fallback', $vars),
		],
	],
	'align' => 'horizontal',
]);

// fields
$table_head = elgg_format_element('th', [], elgg_echo('profile_sync:admin:sync_configs:edit:datasource_column'));
$table_head .= elgg_format_element('th', ['class' => 'profile-sync-arrow'], '&nbsp;');
$table_head .= elgg_format_element('th', [], elgg_echo('profile_sync:admin:sync_configs:edit:profile_column'));
$table_head .= elgg_format_element('th', [], elgg_echo('default_access:label'));
$table_head .= elgg_format_element('th', [], elgg_echo('profile_sync:admin:sync_configs:edit:always_override'));

$table = elgg_format_element('thead', [], elgg_format_element('tr', [], $table_head));

$table_body = '';
if ($entity instanceof ProfileSyncConfig) {
	$sync_match = json_decode($entity->sync_match, true);
	
	foreach ($sync_match as $datasource_name => $profile_config) {
		list($datasource_name) = explode(PROFILE_SYNC_DATASOURCE_COL_SEPERATOR, $datasource_name);
		
		$profile_name = elgg_extract('profile_field', $profile_config);
		$access = (int) elgg_extract('access', $profile_config);
		$always_override = (int) elgg_extract('always_override', $profile_config, true);
		
		$row = elgg_format_element('td', [], elgg_view('input/select', [
			'name' => 'datasource_cols[]',
			'options_values' => $datasource_columns,
			'value' => $datasource_name,
		]));
		$row .= elgg_format_element('td', ['class' => 'profile-sync-arrow'], elgg_view_icon('arrow-right'));
		$row .= elgg_format_element('td', [], elgg_view('input/select', [
			'name' => 'profile_cols[]',
			'options_values' => $profile_columns,
			'value' => $profile_name,
		]));
		$row .= elgg_format_element('td', [], elgg_view('input/access', [
			'name' => 'access[]',
			'value' => $access,
		]));
		$row .= elgg_format_element('td', ['class' => 'center'], elgg_view('input/select', [
			'name' => 'always_override[]',
			'value' => $always_override,
			'options_values' => $override_options,
		]));
		
		$table_body .= elgg_format_element('tr', [], $row);
	}
} else {
	$row = elgg_format_element('td', [], elgg_view_field([
		'#type' => 'select',
		'name' => 'datasource_cols[]',
		'options_values' => $datasource_columns,
	]));
	$row .= elgg_format_element('td', ['class' => 'profile-sync-arrow'], elgg_view_icon('arrow-right'));
	$row .= elgg_format_element('td', [], elgg_view_field([
		'#type' => 'select',
		'name' => 'profile_cols[]',
		'options_values' => $profile_columns,
	]));
	$row .= elgg_format_element('td', [], elgg_view_field([
		'#type' => 'access',
		'name' => 'access[]',
	]));
	$row .= elgg_format_element('td', ['class' => 'center'], elgg_view_field([
		'#type' => 'select',
		'name' => 'always_override[]',
		'options_values' => $override_options,
	]));
	
	$table_body .= elgg_format_element('tr', [], $row);
}

$template_row_data = elgg_format_element('td', [], elgg_view_field([
	'#type' => 'select',
	'name' => 'datasource_cols[]',
	'options_values' => $datasource_columns,
]));
$template_row_data .= elgg_format_element('td', [], elgg_view_icon('arrow-right'));
$template_row_data .= elgg_format_element('td', [], elgg_view_field([
	'#type' => 'select',
	'name' => 'profile_cols[]',
	'options_values' => $profile_columns,
]));
$template_row_data .= elgg_format_element('td', [], elgg_view_field([
	'#type' => 'access',
	'name' => 'access[]'
]));
$template_row_data .= elgg_format_element('td', ['class' => 'center'], elgg_view_field([
	'#type' => 'select',
	'name' => 'always_override[]',
	'options_values' => $override_options,
]));
$table_body .= elgg_format_element('tr', ['id' => 'profile-sync-field-config-template', 'class' => 'hidden'], $template_row_data);

$table .= elgg_format_element('tbody', [], $table_body);
$table = elgg_format_element('table', ['class' => 'elgg-table-alt'], $table);

$link = elgg_view('output/url', [
	'id' => 'profile-sync-edit-sync-add-field',
	'text' => elgg_echo('add'),
	'icon' => 'plus',
	'href' => false,
]);

// fields to sync
$field_class = ['profile-sync-edit-sync-fields'];
if ($ban_user || $unban_user) {
	$field_class[] = 'hidden';
}
// echo elgg_format_element('div', ['class' => $field_class], $fields);
echo elgg_view_module('aside', elgg_echo("profile_sync:admin:sync_configs:edit:fields"), $table, [
	'menu' => $link,
	'class' => $field_class,
]);

// schedule
echo elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:schedule'),
	'name' => 'schedule',
	'value' => elgg_extract('schedule', $vars),
	'options_values' => [
		'hourly' => elgg_echo('profile_sync:interval:hourly'),
		'daily' => elgg_echo('profile_sync:interval:daily'),
		'weekly' => elgg_echo('profile_sync:interval:weekly'),
		'monthly' => elgg_echo('profile_sync:interval:monthly'),
		'yearly' => elgg_echo('profile_sync:interval:yearly'),
		'manual' => elgg_echo('profile_sync:sync_configs:schedule:manual'),
	],
]);

// special actions
echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:create_user'),
	'#help' => elgg_echo('profile_sync:admin:sync_configs:edit:create_user:description'),
	'name' => 'create_user',
	'value' => 1,
	'checked' => (bool) elgg_extract('create_user', $vars),
	'switch' => true,
	'class' => ['profile-sync-edit-sync-unique-checkbox'],
]);
echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:notify_user'),
	'name' => 'notify_user',
	'value' => 1,
	'checked' => (bool) elgg_extract('notify_user', $vars),
	'switch' => true,
]);
echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:ban_user'),
	'#help' => elgg_echo('profile_sync:admin:sync_configs:edit:ban_user:description'),
	'id' => 'profile-sync-edit-sync-ban-user',
	'name' => 'ban_user',
	'value' => 1,
	'checked' => (bool) elgg_extract('ban_user', $vars),
	'switch' => true,
	'class' => ['profile-sync-edit-sync-unique-checkbox'],
]);
echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:unban_user'),
	'#help' => elgg_echo('profile_sync:admin:sync_configs:edit:unban_user:description'),
	'id' => 'profile-sync-edit-sync-unban-user',
	'name' => 'unban_user',
	'value' => 1,
	'checked' => (bool) elgg_extract('unban_user', $vars),
	'switch' => true,
	'class' => ['profile-sync-edit-sync-unique-checkbox'],
]);

// log cleanup
echo elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('profile_sync:admin:sync_configs:edit:log_cleanup_count'),
	'#help' => elgg_echo('profile_sync:admin:sync_configs:edit:log_cleanup_count:description'),
	'name' => 'log_cleanup_count',
	'value' => elgg_extract('log_cleanup_count', $vars),
	'min' => 0,
]);

// form footer
$footer = elgg_view_field([
	'#type' => 'submit',
	'value' => elgg_echo('save'),
]);

elgg_set_form_footer($footer);
