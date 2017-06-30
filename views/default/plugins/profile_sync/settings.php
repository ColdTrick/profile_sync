<?php

$plugin = elgg_extract("entity", $vars);

$memory_limit_options = array(
	"64M" => elgg_echo("profile_sync:settings:memory_limit:64"),
	"128M" => elgg_echo("profile_sync:settings:memory_limit:128"),
	"256M" => elgg_echo("profile_sync:settings:memory_limit:256"),
	"512M" => elgg_echo("profile_sync:settings:memory_limit:512"),
	"-1" => elgg_echo("profile_sync:settings:memory_limit:unlimited"),
);

echo "<div>";
echo elgg_echo("profile_sync:settings:memory_limit");
echo elgg_view("input/dropdown", array(
	"name" => "params[memory_limit]",
	"value" => $plugin->memory_limit,
	"options_values" => $memory_limit_options,
	"class" => "mlm"
));
echo "<div class='elgg-subtext'>" . elgg_echo("profile_sync:settings:memory_limit:description") . "</div>";
echo "</div>";

echo "<div>";
echo elgg_echo("profile_sync:settings:postfix_username");
echo elgg_view("input/text", array(
	"name" => "params[postfix_username]",
	"value" => $plugin->postfix_username,
));
echo "<div class='elgg-subtext'>" . elgg_echo("profile_sync:settings:postfix_username:description") . "</div>";
echo "</div>";

echo "<div>";
echo elgg_echo("profile_sync:settings:group_match_name");
echo elgg_view("input/text", array(
	"name" => "params[group_match_name]",
	"value" => $plugin->group_match_name,
));
echo "<div class='elgg-subtext'>" . elgg_echo("profile_sync:settings:group_match_name:description") . "</div>";
echo "</div>";