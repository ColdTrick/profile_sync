<?php

elgg_admin_gatekeeper();

$filename = get_input('file');
$entity = elgg_extract('entity', $vars);
if (empty($filename) || !$entity instanceof ProfileSyncConfig) {
	return;
}

$fh = new ElggFile();
$fh->owner_guid = $entity->guid;
$fh->setFilename($filename);

if (!$fh->exists()) {
	echo elgg_echo('notfound');
	return;
}

list($time) = explode('.', $filename);
$datetime = date(elgg_echo('friendlytime:date_format'), $time);

$content = elgg_view('output/longtext', [
	'value' => $fh->grabFile(),
]);

$back = elgg_view('output/url', [
	'text' => elgg_echo('back'),
	'icon' => 'arrow-left',
	'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/sync_logs', [
		'guid' => $entity->guid,
	]),
	'is_trusted' => true,
	'class' => 'elgg-lightbox',
]);

$content .= elgg_format_element('div', [], $back);

echo elgg_view_module('info', elgg_echo('profile_sync:view_log:title', [$entity->getDisplayName(), $datetime]), $content, [
	'menu' => $back,
]);
