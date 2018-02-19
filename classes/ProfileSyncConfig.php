<?php

class ProfileSyncConfig extends ElggObject {
	
	const SUBYPE = 'profile_sync_config';
	
	/**
	 * {@inheritDoc}
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();
		
		$site = elgg_get_site_entity();
		
		$this->attributes['subtype'] = self::SUBYPE;
		$this->attributes['owner_guid'] = $site->guid;
		$this->attributes['access_id'] = ACCESS_PUBLIC;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function canComment($user_guid = 0, $default = null) {
		
		if (!is_bool($default)) {
			$default = false;
		}
		
		return parent::canComment($user_guid, $default);
	}
}
