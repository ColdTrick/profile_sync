<?php

namespace ColdTrick\ProfileSync;

class PageMenu {
	
	
	public static function registerAdmin(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in() || !elgg_in_context('admin')) {
			return;
		}
		
		$return_value = $hook->getValue();
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'profile_sync',
			'text' => elgg_echo('admin:configure_utilities:profile_sync'),
			'href' => 'admin/configure_utilities/profile_sync',
			'section' => 'configure',
			'parent_name' => 'configure_utilities',
		]);
		
		return $return_value;
	}
}
