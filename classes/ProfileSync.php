<?php

/**
 * ProfileSync defines the functions available for synchronization
 */
abstract class ProfileSync {
	
	/**
	 * @var ProfileSyncDatasource
	 */
	protected $datasource;
	
	/**
	 * @var int
	 */
	protected $lastrun;
	
	/**
	 * Create a connection to a datasource
	 *
	 * @param ProfileSyncDatasource $datasource the datasource configuration
	 * @param int                   $lastrun    the timestamp of the sync config last run
	 *
	 * @return void
	 */
	public function __construct(ProfileSyncDatasource $datasource, $lastrun = 0) {
		$this->datasource = $datasource;
		$this->lastrun = (int) $lastrun;
	}
	
	/**
	 * Fetch the datasource object
	 *
	 * @return ProfileSyncDatasource
	 */
	protected function getDatasource() {
		return $this->datasource;
	}
	
	/**
	 * Connect to the datasource
	 *
	 * @return bool
	 */
	abstract public function connect();
	
	/**
	 * Get the available columns in the datasource
	 *
	 * @return false|array
	 */
	abstract public function getColumns();
	
	/**
	 * Get a row from the datasource
	 *
	 * @return false|array
	 */
	abstract public function fetchRow();
	
	/**
	 * Invalidate all cached data, run this after the sync is done
	 *
	 * @return void
	 */
	abstract public function cleanup();
}
