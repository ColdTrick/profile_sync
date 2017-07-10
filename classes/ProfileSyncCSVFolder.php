<?php
/**
 * ProfileSyncCSVFolder connect to multiple CSV datasources
 *
 * @package ProfileSync
 *
 */
class ProfileSyncCSVFolder extends ProfileSyncCSV {
	
	protected $dir_iterator;
	
	protected function initialize() {
		$ia = elgg_set_ignore_access(true);
		$this->datasource->csv_location = $this->datasource->csv_folder_location;
		$this->datasource->csv_delimiter = $this->datasource->csv_folder_delimiter;
		$this->datasource->csv_enclosure = $this->datasource->csv_folder_enclosure;
		$this->datasource->csv_first_row = $this->datasource->csv_folder_first_row;
		
		elgg_set_ignore_access($ia);
	}
	
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
		
		if ($datasource->datasource_type !== "csv_folder") {
			return false;
		}
		
		$this->dir_iterator = new \DirectoryIterator($datasource->csv_location);
		
		// store file handler
		$this->fh = $this->getNextFile();
		if (!$this->fh) {
			return false;
		}
		
		// cache some settings
		$this->prepareSettings();
		
		return true;
	}
	
	/**
	 * Returns next file in the folder
	 *
	 * @return SplFileObject|false
	 */
	public function getNextFile() {
		if (!$this->dir_iterator) {
			return false;
		}
		$this->dir_iterator->next();
		$file = $this->dir_iterator->current();
		if ($file) {
			return false;
		}
		
		while($file->getExtension() !== 'csv') {
			$this->dir_iterator->next();
			$file = $this->dir_iterator->current();
		}
		if (!$file) {
			return false;
		}
		
		return fopen($file->getPathname(), "r");
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
			// no more fields, check next file
			$next_file = $this->getNextFile();
			
			if (empty($next_file)) {
				// no more files
				return false;
			}
			
			unset($this->offset);
			$this->fh = $next_file;
			
			return $this->fetchRow();
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
		
		// remove all files
		
	}
}