<?php

$csv_location = "";
$csv_delimiter = ",";
$csv_enclosure = "\"";
$csv_first_row = false;

$class = "hidden";

$entity = elgg_extract("entity", $vars);
if (!empty($entity) && ($entity->datasource_type === "csv_folder")) {
	$csv_location = $entity->csv_folder_location;
	$csv_delimiter = $entity->csv_folder_delimiter;
	$csv_enclosure = $entity->csv_folder_enclosure;
	$csv_first_row = (bool) $entity->csv_folder_first_row;
	
	$class = "";
}

echo "<div class='profile-sync-datasource-type profile-sync-datasource-type-csv_folder $class'>";

echo elgg_view('output/longtext', [
	'value' => elgg_echo('profile_sync:admin:datasources:type:csv_folder:description'),
]);

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:csv:location") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[csv_folder_location]",
	"value" => $csv_location
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:csv:delimiter") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[csv_folder_delimiter]",
	"value" => $csv_delimiter,
	"maxlength" => 1
));
echo "</div>";

echo "<div>";
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:csv:enclosure") . "</label>";
echo elgg_view("input/text", array(
	"name" => "params[csv_folder_enclosure]",
	"value" => $csv_enclosure,
	"maxlength" => 1
));
echo "</div>";

echo "<div>";
echo elgg_view("input/checkbox", array(
	"name" => "params[csv_folder_first_row]",
	"value" => "1",
	"checked" => $csv_first_row,
	"class" => "mrs"
));
echo "<label>" . elgg_echo("profile_sync:admin:datasources:edit:csv:first_row") . "</label>";
echo "</div>";

echo "</div>";
