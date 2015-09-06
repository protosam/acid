<?php
/* catalyst
 *
 * mysqli wrapper with CRUD
 */
 
 class catalyst {
	public static $_LINK = 'nope';
	public $link = 'nope';
	
	public $primary_key = null;
	public $table_name = null;

	public $raw_fields = array(); // raw fields with unsanitized data
	public $mod_fields = array(); // modified fields with unsanitized data | this is used for efficient row updates
	
	public $prepared = array(); // prepared data to replace {variables} with in query statements
	public $last_query_result = null; // used by the db $link

	public function __construct(){
		$this->link = self::$_LINK;
	}

	// static function used for setting up a link to the mysqli instance
	public static function setlink($lnk){
		self::$_LINK = $lnk;
	}
	
	public function set($var, $val){
		$this->raw_fields[$var] = $val;
		$this->mod_fields[$var] = $val;
	}
	
	public function get($var){
		return $this->raw_fields[$var];
	}
	
	
	// fuction for finding results by a single 
	public function findby($var, $val, $limit = 0, $offset = 0){
		$this->prepare('value', $val);
		$sql = "select * from `".$this->table_name."` where $var = '{value}'";
		if($limit > 0)
			$sql .= " limit $limit";
		if($offset > 0)
			$sql .= " offset $offset";
		
		$this->query($sql);
	}
	
	public function save(){
		// New data needs to be inserted
		if (!array_key_exists($this->primary_key, $this->raw_fields)) {
			// INSERT INTO $tablename (col1, col2, col3) VALUES (val1, val2, val3);
			$final_statement = "INSERT INTO `" . $this->table_name . "` ";

			$fields = '`' . $this->primary_key . '`';
			$values = 'null';
			foreach ($this->raw_fields as $field => $value) {
				$values .= ", '".$this->link->escape_string($value)."'";
				$fields .= ', `' . $field . '`';
			}
			$final_statement .= "(" . $fields . ") VALUES (" . $values . ");";

			$err_check = $this->link->query($final_statement);
			if (!$err_check) {
				echo "MySQL Error: " . $this->link->error . "<br>\n";
			}


			// now we will return only this one
			$this->findby($this->primary_key, $this->link->insert_id, 1);
		} else {
			// UPDATE $tablename SET col1 = val, col2 = val WHERE $primary_key = $this->get($primary_key)
			$final_statement = "UPDATE `" . $this->table_name . "` SET";
			$changed_fields = count($this->mod_fields);
			$builds = 1;

			foreach ($this->mod_fields as $field => $value) {
				$value = $this->link->escape_string($value);
				$final_statement .= ' `' . $this->table_name . '`.`' . $field . '` = "' . $value . '"';
				if ($builds != $changed_fields)
					$final_statement .= ',';
				$builds++;
			}

			$final_statement .= ' WHERE `' . $this->primary_key . '` = "' . $this->raw_fields[$this->primary_key] . '";';
			$this->link->query($final_statement);
		}
	}
	
	public function delete(){
		if ($this->primary_key == null || $this->primary_key == '') {
			echo 'Can not use delete() method without a set primary_key for ' . $this->table_name;
			return;
		}
		// DELETE FROM table WHERE col = val
		$final_statement = 'DELETE FROM `' . $this->table_name . '` WHERE `' . $this->primary_key . '` = "' . $this->raw_fields[$this->primary_key] . '";';
		$this->link->query($final_statement);
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
