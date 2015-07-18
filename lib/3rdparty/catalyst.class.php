<?php
/* catalyst
 *
 * mysqli wrapper with CRUD
 */
 
 class catalyst {
	public static $_LINK = 'nope';
	public $link = 'nope';
	
	public $primary_key = null;

	public $raw_fields = array(); // raw fields with unsanitized data
	public $mod_fields = array(); // modified fields with sanitized data
	public $prepared = array(); // prepared data to replace {variables} with in query statements
	public $last_query_result = null;

	public function __construct(){
		$this->link = self::$_LINK;
	}

	// static function used for setting up a link to the mysqli instance
	public function setlink($lnk){
		self::$_LINK = $lnk;
	}
	
	public function set($var, $val){
		
	}
	
	public function get($var){
		return $this->raw_fields[$var];
	}
	
	public function findby($var, $val){
		
	}
	
	public function save(){
		
	}
	
	public function delete(){
		
	}
	
	// Used to sanitize data going through query()
	public function prepare($var, $val = null) {
		if (is_array($var) && count($var) > 0) {
			foreach ($var as $key => $val) {
				$this->prepared[$key] = $val;
			}
		} elseif ($val != null) {
			$this->prepared[$var] = $val;
		}
	}
	
	// function for running MySQL queries
	public function query($query, $autonext = true) {
		// Sanitize that shizzzzz if there are things to fill in from $this->prepared
		if (count($this->prepared) > 0) {
			foreach ($this->prepared as $key => $val) {
				if (!strpos($query, '"{' . $key . '}"') && !strpos($query, "'{" . $key . "}'")) {
					// remove quoted stuff...
					$query = str_replace('"{' . $key . '}"', '{' . $key . '}', $query);
					$query = str_replace("'{" . $key . "}'", '{' . $key . '}', $query);
					// add the sanitized string
					$val_sanitized = $this->link->escape_string($val);
					$query = str_replace('{' . $key . '}', '"' . $val_sanitized . '"', $query);
				} else {
					// add the sanitized string
					$val_sanitized = $this->link->escape_string($val);
					$query = str_replace('{' . $key . '}', $val_sanitized, $query);
				}
			}
			// Empty this out now!
			$this->prepared = array();
		}
		$this->last_query = $query;
		$this->last_query_result = $this->link->query($query);
		if (!$this->last_query_result) {
			echo "MySQL Error: " . $this->link->error . "<br>\n";
		}
		if ($autonext && $this->count() > 0)
			$this->next();
	}
	
	public function next(){
		$this->raw_fields = $this->last_query_result->fetch_assoc();
		$this->mod_fields = array();
		// return false if we're done providing data
		if ($this->raw_fields == NULL) {
			// clean up variables
			$this->raw_fields = array();
			$this->mod_fields = array();
			
			// the fuck off result
			return false;
		}
		// returns true if we were successful
		return true;
		
	}
	
	// Gives us a count of the number of rows from the last query
	public function count() {
		if (!$this->last_query_result)
			return 0;
		return $this->last_query_result->num_rows;
	}
}
