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
				'onClick' => '$(document).click();',
			]);
		}
		
		if ($entity->canWriteToContainer(0, 'object', \ProfileSyncConfig::SUBYPE)) {
			$return_value[] = \ElggMenuItem::factory([
				'name' => 'add_sync_config',
				'text' => elgg_echo('profile_sync:admin:sync_configs:add'),
				'href' => false,
				'icon' => 'plus',
				'link_class' => 'elgg-lightbox',
				'section' => 'config',
				'data-colorbox-opts' => json_encode([
					'innerWidth' => '900px',
					'href' => elgg_http_add_url_query_elements('ajax/view/profile_sync/forms/sync_config', [
						'datasource_guid' => $entity->guid,
					]),
				]),
				'onClick' => '$(document).click();',
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
		foreach ($return_value as $key => $menu_item) {
			$name = $menu_item->getName();
			switch ($name) {
				case 'edit':
					// edit in lightbox
					$menu_item->setHref("ajax/view/profile_sync/forms/sync_config?guid={$entity->getGUID()}");
					$menu_item->setLinkClass('elgg-lightbox');
					$menu_item->setTooltip('');
					break;
				case 'delete':
					break;
				default:
					unset($return_value[$key]);
					break;
			}
		}
		
		$schedule_text = elgg_echo("profile_sync:interval:{$entity->schedule}");
		if ($entity->schedule === 'manual') {
			$schedule_text = elgg_echo('profile_sync:sync_configs:schedule:manual');
		}
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'sync_config_interval',
			'text' => $schedule_text,
			'href' => false,
			'priority' => 10,
		]);
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'run',
			'text' => elgg_echo('profile_sync:sync_config:run'),
			'href' => "ajax/view/profile_sync/sync_config/run?guid={$entity->getGUID()}",
			'priority' => 50,
			'is_action' => true,
			'link_class' => 'elgg-lightbox',
		]);
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'logs',
			'text' => elgg_echo('profile_sync:sync_config:logs'),
			'href' => "ajax/view/profile_sync/sync_logs/?guid={$entity->getGUID()}",
			'priority' => 100,
			'link_class' => 'elgg-lightbox',
		]);
		
		return $return_value;
	}
}
