<?php
/**
 * ProfileSyncCSV connect to a CSV datasrouce
 *
 * @package ProfileSync
 *
 */
class ProfileSyncCSV extends ProfileSync {
	
	protected $fh;
	protected $offset;
	
	protected $delimiter;
	protected $enclosure;
	protected $first_row;
	
	protected $named_columns;
	
	/**
	 * Connect to the datasource
	 *
	 * @return bool
	 */
	public function connect() {
		
		if ($this->fh) {
			return true;
		}
		
		$datasource = $this->getDatasource();
		if (empty($datasource)) {
			return false;
		}
		
		if ($datasource->datasource_type !== 'csv') {
			return false;
		}
		
		$new_fh = fopen($datasource->csv_location, 'r');
		if ($new_fh === false) {
			return false;
		}
		
		// store file handler
		$this->fh = $new_fh;
		
		// cache some settings
		$this->prepareSettings();
		
		return true;
	}
	
	/**
	 * Get the available columns in the datasource
	 *
	 * @return false|array
	 */
	public function getColumns() {
		
		if (!$this->connect()) {
			return false;
		}
		
		// store file offset
		$this->offset = ftell($this->fh);
		// set file pointer to the beginning
		fseek($this->fh, 0);
		
		$fields = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure);
		
		// restore file pointer location
		fseek($this->fh, $this->offset);
		
		if (empty($fields)) {
			return false;
		}
		
		if ($this->first_row) {
			// first row contain header fields
			$this->named_columns = $fields;
			
			return array_combine($fields, $fields);
		}
		
		foreach ($fields as $index => $data) {
			$fields[$index] = elgg_echo('profile_sync:csv:column', [$index + 1, $data]);
		}
		
		return $fields;
	}
	
	/**
	 * Get a row from the datasource
	 *
	 * @return false|array
	 */
	public function fetchRow() {
		
		if (!$this->connect()) {
			return false;
		}
		
		if (!isset($this->offset)) {
			$this->offset = ftell($this->fh);
		}
		
		// set the file pointer to the last know location
		fseek($this->fh, $this->offset);
		
		// check if we need to skip the first row
		if ($this->offset === 0 && $this->first_row) {
			// skip the first row, as it contains headers
			fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure);
			
			$this->offset = ftell($this->fh);
		}
		
		// get a row
		$fields = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure);
		
		// set offset for next run
		$this->offset = ftell($this->fh);
		
		if ($fields === false) {
			// some error occured
			return false;
		}
		
		// return named columns?
		if ($this->first_row) {
			if (!isset($this->named_columns)) {
				$this->named_columns = false;
				
				$this->getColumns();
			}
			
			if (!empty($this->named_columns)) {
				return array_combine($this->named_columns, $fields);
			}
		}
		
		// return row
		return $fields;
	}
	
	/**
	 * Invalidate all cached data, run this after the sync is done
	 *
	 * @return void
	 */
	public function cleanup() {
		
		// close file connection
		if ($this->fh) {
			fclose($this->fh);
			unset($this->fh);
		}
		
		unset($this->offset);
		
		unset($this->delimiter);
		unset($this->enclosure);
		unset($this->first_row);
		
		unset($this->named_columns);
	}
	
	/**
	 * Cache some of the datasource settings
	 *
	 * @return false|void
	 */
	protected function prepareSettings() {
		
		if (!$this->connect()) {
			return false;
		}
		
		$datasource = $this->getDatasource();
		if (empty($datasource)) {
			return false;
		}
		
		$this->delimiter = $datasource->csv_delimiter;
		$this->enclosure = $datasource->csv_enclosure;
		$this->first_row = (bool) $datasource->csv_first_row;
	}
}
