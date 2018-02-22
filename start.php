<?php
/**
 * The main plugin file for Profile Sync
 */

define('PROFILE_SYNC_DATASOURCE_COL_SEPERATOR', '||$||');

// load libs
require_once(dirname(__FILE__) . '/lib/functions.php');

// register default Elgg events
elgg_register_event_handler('init', 'system', 'profile_sync_init');

/**
 * Init function for Profile Sync
 *
 * @return void
 */
function profile_sync_init() {
		
	elgg_extend_view('css/admin', 'css/profile_sync/admin');
	
	// register ajax views
	elgg_register_ajax_view('profile_sync/forms/datasource');
	elgg_register_ajax_view('profile_sync/forms/sync_config');
	elgg_register_ajax_view('profile_sync/sync_logs');
	elgg_register_ajax_view('profile_sync/view_log');
	elgg_register_ajax_view('profile_sync/sync_config/run');
	
	// register hooks
	elgg_register_plugin_hook_handler('register', 'menu:entity', '\ColdTrick\ProfileSync\EntityMenu::addDataSourceMenus');
	elgg_register_plugin_hook_handler('register', 'menu:entity', '\ColdTrick\ProfileSync\EntityMenu::addSyncConfigMenus');
	elgg_register_plugin_hook_handler('register', 'menu:page', '\ColdTrick\ProfileSync\PageMenu::registerAdmin');
	elgg_register_plugin_hook_handler('cron', 'all', '\ColdTrick\ProfileSync\Cron::runSyncs');
}
