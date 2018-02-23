<?php

class ProfileSyncConfig extends ElggObject {
	
	/**
	 * @var string
	 */
	const SUBYPE = 'profile_sync_config';
	
	/**
	 * @var resource A reference to the logfile
	 */
	protected $fh;
	
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
	
	/**
	 * Add text the the logfile
	 *
	 * @param string $text  the text to log
	 * @param bool   $close close the log file after writing text
	 *
	 * @return void
	 */
	public function log($text, $close = false) {
		
		$close = (bool) $close;
		
		if (empty($text) || !is_string($text) || empty($this->guid)) {
			return;
		}
		
		if (!isset($this->fh)) {
			$file = new ElggFile();
			$file->owner_guid = $this->guid;
			$file->setFilename(time() . '.log');
			
			// create the log file
			$file->open('write');
			$file->write('Start processing: ' . date(elgg_echo('friendlytime:date_format')) . PHP_EOL);
			
			// now keep open for appending
			$this->fh = $file->open('append');
		}
		
		fwrite($this->fh, $text . PHP_EOL);
		elgg_log("Profile sync log({$this->guid}): {$text}", 'NOTICE');
		
		if ($close) {
			$this->closeLog();
		}
	}
	
	/**
	 * Close the log file handler
	 *
	 * @return void
	 */
	public function closeLog() {
		
		if (!isset($this->fh)) {
			return;
		}
		
		if (fclose($this->fh)) {
			unset($this->fh);
		}
	}
	
	/**
	 * Get the logfiles in an ordered array
	 *
	 * @param bool $with_label transform filename to friendly time (default: true)
	 *
	 * @return array
	 */
	public function getOrderedLogFiles($with_label = true) {
		
		if (empty($this->guid)) {
			return false;
		}
		
		$with_label = (bool) $with_label;
		
		$fh = new ElggFile();
		$fh->owner_guid = $this->guid;
		$fh->setFilename('temp');
		
		$dir = $fh->getFilenameOnFilestore();
		$dir = substr($dir, 0, -4);
		
		$files = [];
		
		$dh = new DirectoryIterator($dir);
		foreach ($dh as $file_info) {
			if ($file_info->isDot() || $file_info->isDir()) {
				continue;
			}
			
			if ($with_label) {
				$files[$file_info->getFilename()] = date(elgg_echo('friendlytime:date_format'), $file_info->getBasename('.log'));
			} else {
				$files[] = $file_info->getFilename();
			}
		}
		unset($dh);
		
		if ($with_label) {
			krsort($files);
		} else {
			natcasesort($files);
			$files = array_reverse($files);
		}
		
		return $files;
	}
	
	/**
	 * Cleanup older logfiles
	 *
	 * @return bool
	 */
	public function cleanupLogFiles() {
		
		if (empty($this->guid)) {
			return false;
		}
		
		$keep_count = (int) $this->log_cleanup_count;
		if ($keep_count < 1) {
			// nothing to cleanup
			return true;
		}
		
		$files = $this->getOrderedLogFiles(false);
		if (empty($files) || count($files) <= $keep_count) {
			// not enough logfiles
			return true;
		}
		
		$remove = array_slice($files, $keep_count);
		if (empty($remove)) {
			// shouldn't happen
			return true;
		}
		
		$fh = new ElggFile();
		$fh->owner_guid = $this->guid;
		
		$result = true;
		foreach ($remove as $log_file) {
			
			$fh->setFilename($log_file);
			if (!$fh->exists()) {
				continue;
			}
			
			$result = $result & $fh->delete();
		}
		
		return $result;
	}
}
