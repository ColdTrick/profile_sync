<?php

elgg_admin_gatekeeper();

$entity = elgg_extract('entity', $vars);
if (!$entity instanceof ProfileSyncConfig) {
	return;
}

$files = $entity->getOrderedLogFiles();
if (empty($files)) {
	echo elgg_echo('notfound');
	return;
}

$head = elgg_format_element('th', [], elgg_echo('profile_sync:interval:date'));
$head .= elgg_format_element('th', [], '&nbsp;');
$head = elgg_format_element('tr', [], $head);

$table = elgg_format_element('thead', [], $head);

$rows = [];
foreach ($files as $file => $datetime) {
	$row = [];
	$row[] = elgg_format_element('td', [], $datetime);
	$row[] = elgg_format_element('td', [], elgg_view('output/url', [
		'text' => elgg_echo('show'),
		'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/view_log', [
			'guid' => $entity->guid,
			'file' => $file,
		]),
		'is_trusted' => true,
		'class' => 'elgg-lightbox',
	]));
	$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $row));
}
$table .= elgg_format_element('tbody', [], implode(PHP_EOL, $rows));

$content = elgg_format_element('table', ['class' => 'elgg-table-alt'], $table);

echo elgg_view_module('info', elgg_echo('profile_sync:sync_logs:title', [$entity->getDisplayName()]), $content);
