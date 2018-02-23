<?php
/**
 * All helper functions are bundled here
 */

use Elgg\Project\Paths;

/**
 * Run the profile synchronization based on the provided configuration
 *
 * @param ProfileSyncConfig $sync_config The sync configuration
 *
 * @return void
 */
function profile_sync_proccess_configuration(ProfileSyncConfig $sync_config) {
	
	$datasource = $sync_config->getContainerEntity();
	if (!$datasource instanceof ProfileSyncDatasource) {
		return;
	}
	
	$sync_match = json_decode($sync_config->sync_match, true);
	$datasource_id = $sync_config->datasource_id;
	$profile_id = $sync_config->profile_id;
	$lastrun = (int) $sync_config->lastrun;
	
	$ban_user = (bool) $sync_config->ban_user;
	$unban_user = (bool) $sync_config->unban_user;
	
	$sync_config->log("Last run timestamp: {$lastrun} (" . date(elgg_echo('friendlytime:date_format'), $lastrun) . ")" . PHP_EOL);
	
	$profile_fields = elgg_get_config('profile_fields');
	
	if ((!$ban_user && !$unban_user && empty($sync_match)) || ($datasource_id === '') || empty($profile_id)) {
		$sync_config->log('Configuration error', true);
		return;
	}
	
	if (!in_array($profile_id, ['name', 'username', 'email']) && !array_key_exists($profile_id, $profile_fields)) {
		$sync_config->log("Invalid profile identifier: {$profile_id}", true);
		return;
	}
	
	$sync_source = $datasource->getProfileSync();
	if (!$sync_source instanceof ProfileSync) {
		$sync_config->log("Invalid datasource type: {$datasource->datasource_type}", true);
		return;
	}
	
	if (!$sync_source->connect()) {
		$sync_config->log('Unable to connect to the datasource', true);
		return;
	}
	
	$datasource_id_fallback = $sync_config->datasource_id_fallback;
	$profile_id_fallback = $sync_config->profile_id_fallback;
	
	$create_user = (bool) $sync_config->create_user;
	$notify_user = (bool) $sync_config->notify_user;
	
	$create_user_name = false;
	$create_user_email = false;
	$create_user_username = false;
	
	if ($create_user) {
		$sync_config->log('User creation is allowed');
		
		foreach ($sync_match as $datasource_col => $datasource_config) {
			list($datasource_col) = explode(PROFILE_SYNC_DATASOURCE_COL_SEPERATOR, $datasource_col);
			
			switch ($datasource_config['profile_field']) {
				case 'name':
					$create_user_name = $datasource_col;
					break;
				case 'email':
					$create_user_email = $datasource_col;
					break;
				case 'username':
					$create_user_username = $datasource_col;
					break;
			}
		}
		
		if (($create_user_name === false) || ($create_user_username === false) || ($create_user_email === false)) {
			$sync_config->log('Missing information to create users');
			$sync_config->log("- name: {$create_user_name}");
			$sync_config->log("- email: {$create_user_email}");
			$sync_config->log("- username: {$create_user_username}");
			$create_user = false;
		}
	}
	
	if ($ban_user) {
		$sync_config->log('Matching users will be banned');
	}
	
	if ($unban_user) {
		$sync_config->log('Matching users will be unbanned');
	}
	
	if ($ban_user && $create_user) {
		$sync_config->log('Both create and ban users is allowed, don\'t know what to do', true);
		return;
	}
	
	if ($unban_user && $create_user) {
		$sync_config->log('Both create and unban users is allowed, don\'t know what to do', true);
		return;
	}
	
	if ($ban_user && $unban_user) {
		$sync_config->log('Both ban and unban users is allowed, don\'t know what to do', true);
		return;
	}
	
	// start the sync process
	set_time_limit(0);
	_elgg_services()->db->disableQueryCache();
	
	$default_access = get_default_access();
	$ia = elgg_set_ignore_access(true);
	$site = elgg_get_site_entity();
	
	// we want to cache entity metadata on first __get()
	$metadata_cache = _elgg_services()->metadataCache;
	
	$counters = [
		'source rows' => 0,
		'empty source id' => 0,
		'duplicate email' => 0,
		'duplicate name' => 0,
		'duplicate profile field' => 0,
		'user not found' => 0,
		'user created' => 0,
		'user banned' => 0,
		'user unbanned' => 0,
		'empty attributes' => 0,
		'invalid profile field' => 0,
		'invalid source field' => 0,
		'processed users' => 0,
	];
	
	$base_location = '';
	if ($sync_source instanceof ProfileSyncCSV) {
		// get base path
		$csv_location = $datasource->csv_location;
		$csv_filename = basename($csv_location);
		
		$base_location = rtrim(str_ireplace($csv_filename, '', $csv_location), DIRECTORY_SEPARATOR);
	}
	
	while (($source_row = $sync_source->fetchRow()) !== false) {
		$counters['source rows']++;
		
		// let other plugins change the row data
		$params = [
			'datasource' => $datasource,
			'sync_config' => $sync_config,
			'source_row' => $source_row,
		];
		$source_row = elgg_trigger_plugin_hook('source_row', 'profile_sync', $params, $source_row);
		
		if (!is_array($source_row) || empty($source_row[$datasource_id])) {
			$counters["empty source id"]++;
			continue;
		}
		
		// find user
		$profile_used_id = $profile_id;
		$datasource_unique_id = elgg_extract($datasource_id, $source_row);
		
		$user = profile_sync_find_user($profile_id, $datasource_unique_id, $sync_config, $counters);
		
		// fallback user
		if (empty($user) && ($datasource_id_fallback !== '') && !empty($source_row[$datasource_id_fallback]) && !empty($profile_id_fallback)) {
// 			$sync_config->log("User not found: {$profile_id} => {$datasource_unique_id} trying fallback");
			
			$profile_used_id = $profile_id_fallback;
			$datasource_unique_id = elgg_extract($datasource_id_fallback, $source_row);
			
			$user = profile_sync_find_user($profile_id_fallback, $datasource_unique_id, $sync_config, $counters);
		}
		
		// check if we need to create a user
		if (empty($user) && $create_user) {
			
			$pwd = generate_random_cleartext_password();
			
			try {
				// convert to utf-8
				$username = profile_sync_filter_var($source_row[$create_user_username]);
				$name = profile_sync_filter_var($source_row[$create_user_name]);
				$email = profile_sync_filter_var($source_row[$create_user_email]);
				
				$user_guid = register_user($username, $pwd, $name, $email);
				if (!empty($user_guid)) {
					$counters['user created']++;
					$sync_config->log("Created user: {$name}");
					
					$user = get_user($user_guid);
					$user->language = elgg_get_config('language');
					
					if ($notify_user) {
						$subject = elgg_echo('useradd:subject', [], $user->language);
						$body = elgg_echo('useradd:body', [
							$user->getDisplayName(),
							$site->getDisplayName(),
							$site->getURL(),
							$user->username,
							$pwd,
						], $user->language);
						
						$notify_params = [
							'action' => 'useradd',
							'object' => $user,
							'password' => $pwd,
						];
						
						notify_user($user->guid, $site->guid, $subject, $body, $notify_params);
					}
				}
			} catch (RegistrationException $r) {
				$name = profile_sync_filter_var($source_row[$create_user_name]);
				$sync_config->log("Failure creating user: {$name} - {$r->getMessage()}");
			}
		}
		
		// did we get a user
		if (empty($user)) {
			$counters['user not found']++;
			$sync_config->log("User not found: {$profile_used_id} => {$datasource_unique_id}");
			continue;
		} else {
			$counters['processed users']++;
		}
		
		// ban the user
		if ($ban_user) {
			// already banned?
			if (!$user->isBanned()) {
				$counters['user banned']++;
				$user->ban("Profile Sync: {$sync_config->getDisplayName()}");
				$sync_config->log("User banned: {$user->getDisplayName()} ({$user->username})");
			}
			
			continue;
		}
		
		// unban the user
		if ($unban_user) {
			// already banned?
			if ($user->isBanned()) {
				$counters['user unbanned']++;
				$user->unban();
				$sync_config->log("User unbanned: {$user->getDisplayName()} ({$user->username})");
			}
			
			continue;
		}
		
		// start of profile sync
		$special_sync_fields = [
			'name',
			'username',
			'email',
			'user_icon_relative_path',
			'user_icon_full_path',
		];
		
		// keep track if userdata is changed
		$user_touched = false;
		
		foreach ($sync_match as $datasource_col => $profile_config) {
			list($datasource_col) = explode(PROFILE_SYNC_DATASOURCE_COL_SEPERATOR, $datasource_col);
			
			$profile_field = elgg_extract('profile_field', $profile_config);
			$access = (int) elgg_extract('access', $profile_config, $default_access);
			$override = (bool) elgg_extract('always_override', $profile_config, true);
			
			if (!in_array($profile_field, $special_sync_fields) && !array_key_exists($profile_field, $profile_fields)) {
				$counters['invalid profile field']++;
				continue;
			}
			if (!isset($source_row[$datasource_col])) {
				$counters['invalid source field']++;
				continue;
			}
			
			$value = elgg_extract($datasource_col, $source_row);
			$value = profile_sync_filter_var($value);
			
			switch ($profile_field) {
				case 'email':
					if (!is_email_address($value)) {
						continue(2);
					}
				case 'username':
					if ($override && ($user->username !== $value)) {
						// new username, check for availability
						if (get_user_by_username($value)) {
							// already taken
							$sync_config->log("New username: {$value} for {$user->getDisplayName()} is already taken");
							continue(2);
						}
					}
				case 'name':
					if (empty($value)) {
						$counters['empty attributes']++;
						$sync_config->log("Empty user attribute: {$datasource_col} for user {$user->getDisplayName()}");
						continue(2);
					}
					
					if (isset($user->$profile_field) && !$override) {
						// don't override profile field
// 						$sync_config->log("Profile field already set: {$profile_field} for user {$user->getDisplayName()}");
						continue(2);
					}
					
					// check for the same value
					if ($user->$profile_field === $value) {
						// same value, no need to update
						continue(2);
					}
					
					// save user attribute
					$user->$profile_field = $value;
					$user_touched = true;
					break;
				case 'user_icon_relative_path':
					// get a user icon based on a relative file path/url
					// only works with file based datasources (eg. csv)
					if (!($sync_source instanceof ProfileSyncCSV)) {
						$sync_config->log("Can't fetch relative user icon path in non CSV datasouces: trying user {$user->getDisplayName()}");
						continue(2);
					}
					
					// make new icon path
					if (!empty($value)) {
						$value = Paths::sanitize($value, false); // prevent abuse (like ../../......)
						$value = ltrim($value, DIRECTORY_SEPARATOR); // remove beginning /
						$value = $base_location . DIRECTORY_SEPARATOR . $value; // concat base location and rel path
					}
					
				case 'user_icon_full_path':
					// get a user icon based on a full file path/url
					
					if (!empty($user->icontime) && !$override) {
						// don't override icon
// 						$sync_config->log("User already has an icon: {$user->getDisplayName()}");
						continue(2);
					}
					
						
					if (empty($value) && $user->icontime) {
						// no icon, so unset current icon
						$sync_config->log("Removing icon for user: {$user->getDisplayName()}");
						
						$user->deleteIcon();
						
						// on to the next field
						continue(2);
					}
					
					// try to get the user icon
					$icon_contents = file_get_contents($value);
					if (empty($icon_contents)) {
						$sync_config->log("Unable to fetch user icon: {$value} for user {$user->getDisplayName()}");
						continue(2);
					}
					
					// was csv image updated
					$csv_icontime = @filemtime($value);
					if (($csv_icontime !== false) && isset($user->icontime)) {
						$csv_icontime = (int) $csv_icontime;
						$icontime = (int) $user->icontime;
						
						if ($csv_icontime === $icontime) {
							// base image has same modified time as user icontime, so skipp
// 							$sync_config->log("No need to update user icon for user: {$user->getDisplayName()}");
							continue(2);
						}
					}
					
					try {
						$user->saveIconFromLocalFile($value);
					
						$user_touched = true;
					} catch (Exception $e) {
// 						$sync_config->log("Error during profile icon update for user: {$user->getDisplayName()}");
					}
					
					break;
				default:
					// check overrides
					$annotations = $user->getAnnotations([
						'annotation_name' => "profile:{$profile_field}",
						'limit' => false,
					]);
					if (!empty($annotations) && !$override) {
						// don't override profile field
// 						$sync_config->log("Profile field already set: {$profile_field} for user {$user->getDisplayName()}");
						continue(2);
					}
					
					// convert tags
					if ($profile_fields[$profile_field] === 'tags') {
						$value = string_to_tag_array($value);
					}
					
					// remove existing value
					if (empty($value)) {
						if (!empty($annotations)) {
							$user->deleteAnnotations("profile:{$profile_field}");
							$user->deleteMetadata($profile_field);
						}
						continue(2);
					}
					
					// check for the same value
					$profile_values = [];
					if (!empty($annotations)) {
						foreach ($annotations as $a) {
							$profile_values[] = $a->value;
						}
					}
					$new_values = (array) $value;
					if (array_diff($profile_values, $new_values) === array_diff($new_values, $profile_values)) {
						// same value, no need to update
						continue(2);
					}
					
// 					$sync_config->log("Updating {$profile_field} with value '" . implode(',', $new_values) . "' old value '" . implode(',', $profile_values) . "'");
					
					// get the access of existing profile data
					$access = profile_sync_get_profile_field_access($user->guid, $profile_field, $access);
					
					// save new value
					// first remove old values
					$user->deleteAnnotations("profile:{$profile_field}");
					$user->deleteMetadata($profile_field);
					
					// store profile data in annotations
					if (is_array($value)) {
						foreach ($value as $v) {
							$user->annotate("profile:{$profile_field}", $v, $access, $user->guid, 'text');
						}
					} else {
						$user->annotate("profile:{$profile_field}", $value, $access, $user->guid, 'text');
					}
					
					// and in metadata for BC
					$user->$profile_field = $value;
					
					$user_touched = true;
					break;
			}
		}
		
		if ($user_touched) {
			// if user data changed update user
			$user->save();
		}
		
		// let others know we updated the user
		$update_event_params = [
			'entity' => $user,
			'source_row' => $source_row,
			'sync_config' => $sync_config,
			'datasource' => $datasource,
		];
		elgg_trigger_event('update_user', 'profile_sync', $update_event_params);
		
		// cache cleanup
		_elgg_services()->entityCache->delete($user->guid);
		$metadata_cache->clear($user->guid);
	}
	
	$sync_config->log(PHP_EOL . 'End processing: ' . date(elgg_echo('friendlytime:date_format')) . PHP_EOL);
	foreach ($counters as $name => $count) {
		$sync_config->log("{$name}: {$count}");
	}
	
	// close logfile
	$sync_config->closeLog();
	
	// save last run
	$sync_config->lastrun = time();
	
	// cleanup datasource cache
	$sync_source->cleanup();
	// re-enable db caching
	_elgg_services()->db->enableQueryCache();
	// restore access
	elgg_set_ignore_access($ia);
	
	$metadata_cache->clearAll();
}

/**
 * Convert string to UTF-8 charset
 *
 * @param string $string the input string
 *
 * @return string
 */
function profile_sync_convert_string_encoding($string) {
	
	if (function_exists('mb_convert_encoding')) {
		$source_encoding = mb_detect_encoding($string);
		if (!empty($source_encoding)) {
			$source_aliases = mb_encoding_aliases($source_encoding);
			
			return mb_convert_encoding($string, 'UTF-8', $source_aliases);
		}
	}
	
	// if no mbstring extension, we just try to convert to UTF-8 (from ISO-8859-1)
	return utf8_encode($string);
}

/**
 * Find a user based on a profile field and it's value
 *
 * @param string            $profile_field profile field name
 * @param string            $field_value   profile field value
 * @param ProfileSyncConfig $sync_config   sync configuration (for logging)
 * @param array             $log_counters  array with logging counters
 *
 * @return false|ElggUser
 */
function profile_sync_find_user($profile_field, $field_value, ProfileSyncConfig $sync_config, &$log_counters) {
	static $profile_fields;
	static $dbprefix;
	
	if (!isset($profile_fields)) {
		$profile_fields = elgg_get_config('profile_fields');
	}
	if (!isset($dbprefix)) {
		$dbprefix = elgg_get_config('dbprefix');
	}
	
	if (empty($log_counters) || !is_array($log_counters)) {
		return false;
	}
	
	if (!in_array($profile_field, ['name', 'username', 'email']) && !array_key_exists($profile_field, $profile_fields)) {
		return false;
	}
	
	$field_value = profile_sync_filter_var($field_value);
	if (empty($field_value)) {
		return false;
	}
	
	$user = false;
	switch ($profile_field) {
		case 'username':
			$user = get_user_by_username($field_value);
			break;
		case 'email':
			$users = get_user_by_email($field_value);
			if (count($users) > 1) {
				$log_counters['duplicate email']++;
				$sync_config->log("Duplicate email address: {$field_value}");
			} elseif (count($users) === 1) {
				$user = $users[0];
			}
			break;
		case 'name':
			$options = [
				'type' => 'user',
				'limit' => false,
				'metadata_name_value_pairs' => [
					'name' => $profile_field,
					'value' => $field_value,
				],
			];
			$users = elgg_get_entities($options);
			if (count($users) > 1) {
				$log_counters['duplicate name']++;
				$sync_config->log("Duplicate name: {$field_value}");
			} elseif(count($users) == 1) {
				$user = $users[0];
			}
			break;
		default:
			$options = [
				'type' => 'user',
				'limit' => false,
				'annotation_name_value_pairs' => [
					'name' => "profile:{$profile_field}",
					'value' => $field_value,
				],
			];
			$users = elgg_get_entities($options);
			if (count($users) > 1) {
				$log_counters['duplicate profile field']++;
				$sync_config->log("Duplicate profile field: {$profile_field} => {$field_value}");
			} elseif(count($users) === 1) {
				$user = $users[0];
			}
			break;
	}
	
	return $user;
}

/**
 * Do the same as get_input() and /action/profile/edit on sync data values
 *
 * @param string $value the value to filter
 *
 * @see get_input()
 *
 * @return string
 */
function profile_sync_filter_var($value) {
	
	// convert to UTF-8
	$value = profile_sync_convert_string_encoding($value);
	
	// filter tags
	$value = filter_tags($value);
	
	// correct html encoding
	if (is_array($value)) {
		array_walk_recursive($value, 'profile_sync_array_decoder');
	} else {
		$value = trim(elgg_html_decode($value));
	}
	
	return $value;
}

/**
 * Wrapper for recursive array walk decoding
 *
 * @param string $value the value of array_walk_recursive
 *
 * @see array_walk_recursive()
 *
 * @return void
 */
function profile_sync_array_decoder(&$value) {
	$value = trim(elgg_html_decode($value));
}

/**
 * Get the access of a profile field (if exists) for the given user
 *
 * @param int    $user_guid      the user_guid to check
 * @param string $profile_field  the name of the profile field
 * @param int    $default_access the default access if profile field doesn't exist for the user
 *
 * @return int
 */
function profile_sync_get_profile_field_access($user_guid, $profile_field, $default_access) {
	static $field_access;
	static $running_user_guid;
	
	$user_guid = (int) $user_guid;
	$default_access = (int) $default_access;
	
	if ($user_guid < 1) {
		return $default_access;
	}
	
	if (empty($profile_field) || !is_string($profile_field)) {
		return $default_access;
	}
	
	$update = ($running_user_guid !== $user_guid);
	
	if ($update) {
		$field_access = [];
		$running_user_guid = $user_guid;
		
		$profile_fields = elgg_get_config('profile_fields');
		$profile_names = array_keys($profile_fields);
		array_walk($profile_names, function(&$profile_field_name) {
			$profile_field_name = "profile:{$profile_field_name}";
		});
		
		$options = [
			'guid' => $user_guid,
			'annotation_names' => $profile_names,
			'limit' => false,
		];
		$annotations = elgg_get_annotations($options);
		if (!empty($annotations)) {
			/* $annotation \ElggAnnotation */
			foreach ($annotations as $annotation) {
				$profile_field_name = substr($annotation->name, strlen('profile:'));
				$field_access[$profile_field_name] = (int) $annotation->access_id;
			}
		}
	}
	
	return elgg_extract($profile_field, $field_access, $default_access);
}

/**
 * Prepare form vars for a datasource
 *
 * @param ProfileSyncDatasource $entity the datasource to edit
 *
 * @return array
 */
function profile_sync_prepare_datasource_form_vars(ProfileSyncDatasource $entity = null) {
	
	$result = [
		'title' => '',
		'datasource_type' => '',
		
		// csv settings
		'csv_location' => '',
		'csv_delimiter' => ',',
		'csv_enclosure' => '"',
		'csv_first_row' => '',
		
		// mysql settings
		'dbhost' => '',
		'dbport' => 3306,
		'dbname' => '',
		'dbusername' => '',
		'dbpassword' => '',
		'dbquery' => '',
	];
	
	if ($entity instanceof ProfileSyncDatasource) {
		
		foreach ($result as $name => $value) {
			$result[$name] = $entity->$name;
		}
		
		$result['entity'] = $entity;
	}
	
	$sticky_values = elgg_get_sticky_values('datasource/edit');
	if (!empty($sticky_values)) {
		foreach ($sticky_values as $name => $value) {
			$result[$name] = $value;
		}
		
		elgg_clear_sticky_form('datasource/edit');
	}
	
	return $result;
}

/**
 * Prepare form vars for sync_config
 *
 * @param ProfileSyncDatasource $source the datasource for the sync config
 * @param ProfileSyncConfig     $config the sync_config to edit
 *
 * @return array
 */
function profile_sync_prepare_sync_config_form_vars(ProfileSyncDatasource $source, ProfileSyncConfig $config = null) {
	
	$result = [
		'title' => '',
		'schedule' => 'daily',
		'datasource_id' => '',
		'datasource_id_fallback' => '',
		'profile_id' => '',
		'profile_id_fallback' => '',
		'create_user' => false,
		'ban_user' => false,
		'unban_user' => false,
		'notify_user' => false,
		'log_cleanup_count' => null,
	];
	
	if ($config instanceof ProfileSyncConfig) {
		foreach ($result as $name => $default) {
			
			$value = $config->$name;
			if (is_bool($default)) {
				$value = (bool) $value;
			}
			
			$result[$name] = $value;
		}
		
		$result['entity'] = $config;
	}
	
	$sticky_values = elgg_get_sticky_values('sync_config/edit');
	if (!empty($sticky_values)) {
		foreach ($sticky_values as $name => $value) {
			$result[$name] = $value;
		}
		
		elgg_clear_sticky_form('sync_config/edit');
	}
	
	$result['profile_sync'] = $source->getProfileSync();
	$result['container_guid'] = $source->guid;
	$result['datasource'] = $source;
	
	return $result;
}
