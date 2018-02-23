<?php

echo elgg_list_entities([
	'type' => 'object',
	'subtype' => ProfileSyncDatasource::SUBYPE,
	'limit' => false,
	'no_results' => elgg_echo('notfound'),
]);
