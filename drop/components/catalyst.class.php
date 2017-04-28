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

	public $join_fragment = null;

	public $raw_fields = array(); // raw fields with unsanitized data
	public $mod_fields = array(); // modified fields with unsanitized data | this is used for efficient row updates

	public $prepared = array(); // prepared data to replace {variables} with in query statements
	public $last_query_result = null; // used by the db $link
	public $last_query = "";

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
		if(isset($this->raw_fields[$var]))
			return $this->raw_fields[$var];
		return null;
	}

	public function findall($order_by = null, $limit = 0, $offset = 0){

		$sql = "select * from `".$this->table_name."` ".$this->join_fragment;
		if($order_by != null){
			if(strpos($order_by, ".") !== false){
				$sql .= " order by $order_by";
			}else{
				$sql .= " order by ".$this->table_name.".$order_by";

			}

		}

		if($limit > 0)
			$sql .= " limit $limit";
		if($offset > 0)
			$sql .= " offset $offset";

		$this->query($sql);
	}

	// fuction for finding results by a single
	public function findby($var, $val, $order_by = null, $limit = 0, $offset = 0){

		if(is_array($var) && is_array($val)){
			$sql = "select * from `".$this->table_name."` ".$this->join_fragment." where ";
			$first = true;
			foreach($var as $k => $v){
				if(is_array($val[$k])){
					$operator = $val[$k][0];
					$value = $val[$k][1];
				}else{
					$operator = "=";
					$value = $val[$k];
				}

				if(is_array($v)){
					$gluecon = $v[0];
					$fieldin = $v[1];
				}else{
					$gluecon = "and";
					$fieldin = $v;
				}

				$this->prepare($fieldin, $value);
				if(strpos($fieldin, ".") !== false)
					$field = $fieldin;
				else
					$field = $this->table_name.".".$fieldin;

				if($first){

					$sql .= "$field ".$operator." '{".$fieldin."}'";
					$first = false;
				}else{
					$sql .= " ".$gluecon." $field ".$operator." '{".$fieldin."}'";
				}
			}
		}else{
			$this->prepare($var, $val);

			if(strpos($var, ".") !== false)
				$field = $var;
			else
				$field = $this->table_name.".".$var;
			$sql = "select * from `".$this->table_name."` ".$this->join_fragment." where $field = '{".$var."}'";
		}
		if($order_by != null){
			if(strpos($order_by, ".") !== false){
				$sql .= " order by $order_by";
			}else{
				$sql .= " order by ".$this->table_name.".$order_by";

			}

		}

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
			// INSERT INTO users SET col1 = val1, col2 = val2
			$final_statement = "INSERT INTO `" . $this->table_name . "` SET ";

			$field_divider = false;
			foreach ($this->raw_fields as $field => $value) {
				if($field_divider)
					$final_statement .= ", ";
				$final_statement .= "`$field` = '".$this->link->escape_string($value)."'";
				$field_divider = true;
			}

			$final_statement .=";";

			$err_check = $this->link->query($final_statement);
			if (!$err_check) {
				echo "MySQL Error: " . $this->link->error . "<br>\n";
			}


			// now we will return only this one
			$this->findby($this->primary_key, $this->link->insert_id, null, 1);
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
			$this->clear();

			// the fuck off result
			return false;
		}
		// returns true if we were successful
		return true;

	}

	public function results(){
		$out = array();

		// Need to ensure we start from the beginning.
		$this->query($this->last_query);

		if($this->count() > 0)
		do {
			array_push($out, clone $this);
		}while($this->next());
		return $out;
	}

	public function getobject($current_only = false){
		if($current_only){
			return $this->raw_fields;
		}

		$obj = array();
		if($this->count() > 0)
		do {
			array_push($obj, $this->raw_fields);
		}while($this->next());

		return $obj;
	}

	// Gives us a count of the number of rows from the last query
	public function count() {
		if (!$this->last_query_result)
			return 0;
		return $this->last_query_result->num_rows;
	}

	public function clear(){
		// clean up variables
		$this->raw_fields = array();
		$this->mod_fields = array();
	}
}
