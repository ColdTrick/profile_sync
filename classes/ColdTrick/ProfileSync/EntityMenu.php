<?php

namespace ColdTrick\ProfileSync;

class EntityMenu {
	
	/**
	 * Add menu items to the datasource entity_menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:entity'
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function addDataSourceMenus(\Elgg\Hook $hook) {
		
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ProfileSyncDatasource) {
			return;
		}
		
		$return_value = $hook->getValue();
		
		if ($entity->canEdit()) {
			$return_value[] = \ElggMenuItem::factory([
				'name' => 'edit',
				'text' => elgg_echo('edit'),
				'title' => elgg_echo('edit:this'),
				'icon' => 'edit',
				'href' => false,
				'link_class' => 'elgg-lightbox',
				'data-colorbox-opts' => json_encode([
					'innerWidth' => '700px',
					'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/forms/datasource', [
						'guid' => $entity->guid,
					]),
				]),
			]);
		}
		
		if ($entity->canWriteToContainer(0, 'object', \ProfileSyncConfig::SUBYPE)) {
			$return_value[] = \ElggMenuItem::factory([
				'name' => 'add_sync_config',
				'text' => elgg_echo('profile_sync:admin:sync_configs:add'),
				'href' => false,
				'icon' => 'plus',
				'link_class' => 'elgg-lightbox',
				'section' => 'actions',
				'data-colorbox-opts' => json_encode([
					'innerWidth' => '900px',
					'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/forms/sync_config', [
						'datasource_guid' => $entity->guid,
					]),
				]),
			]);
		}
		
		return $return_value;
	}
	
	/**
	 * Add menu items to the sync_config entity_menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:entity'
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function addSyncConfigMenus(\Elgg\Hook $hook) {
		
		$entity = $hook->getEntityParam();
		if (!($entity instanceof \ProfileSyncConfig)) {
			return;
		}
		
		$return_value = $hook->getValue();
		
		if ($entity->canEdit()) {
			$return_value[] = \ElggMenuItem::factory([
				'name' => 'edit',
				'text' => elgg_echo('edit'),
				'title' => elgg_echo('edit:this'),
				'icon' => 'edit',
				'href' => false,
				'link_class' => 'elgg-lightbox',
				'data-colorbox-opts' => json_encode([
					'innerWidth' => '900px',
					'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/forms/sync_config', [
						'guid' => $entity->guid,
					]),
				]),
			]);
		}
		
		if (elgg_is_admin_logged_in()) {
			$return_value[] = \ElggMenuItem::factory([
				'name' => 'profile_sync_run',
				'text' => elgg_echo('profile_sync:sync_config:run'),
				'icon' => 'play',
				'href' => elgg_generate_action_url('profile_sync/sync_config/run', [
					'guid' => $entity->guid,
				]),
				'deps' => [
					'profile_sync/sync_config/run',
				],
				'section' => 'actions',
			]);
			
			$return_value[] = \ElggMenuItem::factory([
				'name' => 'profile_sync_logs',
				'text' => elgg_echo('profile_sync:sync_config:logs'),
				'icon' => 'files-o',
				'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/sync_logs', [
					'guid' => $entity->guid,
				]),
				'link_class' => 'elgg-lightbox',
			]);
		}
		
		return $return_value;
	}
}
