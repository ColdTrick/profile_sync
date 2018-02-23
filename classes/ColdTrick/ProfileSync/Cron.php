<?php

namespace ColdTrick\ProfileSync;

class Cron {
	
	/**
	 * Listen to the cron to perform sync tasks
	 *
	 * @param \Elgg\Hook $hook 'cron', 'all'
	 *
	 * @return void
	 */
	public static function runSyncs(\Elgg\Hook $hook) {
		
		$allowed_intervals = [
			'hourly',
			'daily',
			'weekly',
			'monthly',
			'yearly',
		];
		
		$interval = $hook->getType();
		if (!in_array($interval, $allowed_intervals)) {
			return;
		}
		
		echo "Stating ProfileSync: {$interval}" . PHP_EOL;
		elgg_log("Stating ProfileSync: {$interval}", 'NOTICE');
		
		// get current memory limit
		$old_memory_limit = ini_get('memory_limit');
		
		// set new memory limit
		$setting = elgg_get_plugin_setting('memory_limit', 'profile_sync');
		if (!empty($setting)) {
			ini_set('memory_limit', $setting);
		}
		
		// get sync configs
		$options = [
			'type' => 'object',
			'subtype' => \ProfileSyncConfig::SUBYPE,
			'limit' => false,
			'metadata_name_value_pairs' => [
				'name' => 'schedule',
				'value' => $interval,
			],
		];
		$batch = new \ElggBatch('elgg_get_entities_from_metadata', $options);
		/* @var $sync_config \ProfileSyncConfig */
		foreach ($batch as $sync_config) {
			// start the sync
			profile_sync_proccess_configuration($sync_config);
			
			// log cleanup
			$sync_config->cleanupLogFiles();
		}
		
		// reset memory limit
		ini_set('memory_limit', $old_memory_limit);
		
		echo "Done with ProfileSync: {$interval}" . PHP_EOL;
		elgg_log("Done with ProfileSync: {$interval}", 'NOTICE');
	}
}
