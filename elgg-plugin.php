<?php

return [
	'actions' => [
		'profile_sync/datasource/edit' => [
			'access' => 'admin',
		],
		'profile_sync/sync_config/edit' => [
			'access' => 'admin',
		],
		'profile_sync/sync_config/run' => [
			'access' => 'admin',
		],
	],
	'entities' => [
		[
			'type' => 'object',
			'subtype' => 'profile_sync_datasource',
			'class' => 'ProfileSyncDatasource',
		],
		[
			'type' => 'object',
			'subtype' => 'profile_sync_config',
			'class' => 'ProfileSyncConfig',
		],
	],
];
