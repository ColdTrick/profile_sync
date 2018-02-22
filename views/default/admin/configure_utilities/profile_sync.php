<?php

// elgg_require_js('profile_sync/admin');

$datasource_title = elgg_echo('profile_sync:admin:datasources');

echo elgg_view_module('info', $datasource_title, elgg_view('profile_sync/datasources'), [
	'menu' => elgg_view('output/url', [
		'class' => 'elgg-lightbox',
		'href' => 'ajax/view/profile_sync/forms/datasource',
		'text' => elgg_echo('add'),
		'icon' => 'plus',
		'data-colorbox-opts' => json_encode([
			'innerWidth' => '700px',
		]),
	]),
]);

$configs_title = elgg_echo('profile_sync:admin:sync_configs');

echo elgg_view_module('info', $configs_title, elgg_view('profile_sync/sync_configs'));
