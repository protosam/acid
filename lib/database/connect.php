<?php
// Quick Proto DB abstraction kit
define('PROTO_PATH', realpath(dirname(__FILE__)));

class Proto {

	public $linker = null;
	private $current_row_data = array();
	private $current_row_data_function = array();
	private $current_row_data_modified = array();
	private $fetch_data = array();
	private $actions = array();
	public $table = null;
	public $open_table = null;
	public $join_fragment = '';
	public $limit_fragment = '';
	public $primary_key = null;
	public $last_query = null;
	public $last_update = null;
	public $last_query_result = null;
	public $prepared = array();

	public function __construct($host = null, $user = null, $pass = null, $db = null, $port = null, $socket = null) {
		// Make sure we can support anonymous functions...
		if (version_compare(PHP_VERSION, '5.3.0') <= 0)
			die('amysqli needs at least php 5.3.');

		if ($host != null) {
			// Setup MySQL linker
			$this->linker = new mysqli($host, $user, $pass, $db, $port, $socket);
		}
	}

	function __call($method, $arguments) {
		// findBy*() special function
		if (preg_match('/^findBy/', $method)) {
			$method = $this->convert_camel_case(preg_replace('/^findBy/', '', $method));
			$value = $this->linker->escape_string($arguments[0]);
			$this->find('WHERE `' . $this->open_table . '`.`' . $method . '` = "' . $value . '"');
			return;
		}


		// this is the get and set methods... without camelcase
		if (preg_match('/^[s|g]et_/', $method)) {
			if ($method[0] == "g") {
				$method = preg_replace('/^get_/', '', $method);

				if (array_key_exists($method, $this->current_row_data)) {
					return $this->current_row_data[$method];
				}
			}


			if ($method[0] == "s" && isset($arguments[0])) {
				$method = $this->convert_camel_case(preg_replace('/^set/', '', $method));
				// Update the data we check
				$this->current_row_data[$method] = $arguments[0];
				// Used so that we don't over do it when using save()
				$this->current_row_data_modified[$method] = $arguments[0];
				// Used to do simple 1 parameter functions
				if (isset($arguments[1]))
					$this->current_row_data_function[$method] = $arguments[1];
			}
			return false;
		}

		// this is the get and set methods... if nothing else pans out for us
		if (preg_match('/^[s|g]et/', $method)) {

			if ($method[0] == "g") {
				$method = $this->convert_camel_case(preg_replace('/^get/', '', $method));

				if (array_key_exists($method, $this->current_row_data)) {
					return $this->current_row_data[$method];
				}
			}


			if ($method[0] == "s" && isset($arguments[0])) {
				$method = $this->convert_camel_case(preg_replace('/^set/', '', $method));
				// Update the data we check
				$this->current_row_data[$method] = $arguments[0];
				// Used so that we don't over do it when using save()
				$this->current_row_data_modified[$method] = $arguments[0];
				// Used to do simple 1 parameter functions
				if (isset($arguments[1]))
					$this->current_row_data_function[$method] = $arguments[1];
			}
			return false;
		}

		echo 'Soft Error: Method not found in ' . get_class($this) . '->' . $method . '<br>';
		// var_dump(debug_backtrace()); // can provide more data later.
	}

	// Taken from active table: https://github.com/OwlManAtt/activephp/blob/master/active_table/active_table.class.php
	protected function convert_camel_case($studly_word) {
		$simple_word = preg_replace('/([a-z0-9])([A-Z])/', '\1_\2', $studly_word);
		$simple_word = strtolower($simple_word);

		return $simple_word;
	}

// end convert_camel_case
	// This will begin using our mapped out table file
	public function openTable($table) {
		// load our tables file. It may include functions for special uses
		if (file_exists(PROTO_PATH . '/' . $table . '.php')) {

			require_once(PROTO_PATH . '/' . $table . '.php');
			$tmp_new = new $table();

			$tmp_new->linker = $this->linker;

			// override currently open table, if one is defined in our table class
			// otherwise, just open the $table
			if ($tmp_new->table != null) {
				$tmp_new->open_table = $tmp_new->table;
			} else {
				$tmp_new->open_table = $table;
			}

			return $tmp_new;
		} else {
			echo 'Table class not found.';
			return false;
		}
	}

	// Used to sanitize data going through find()
	// WHERE fragment will look like find('WHERE variable="{value}"')
	// Usage would be like $proto->prepare('value', 'Actual Thing');
	// YAY Sanitization! :)
	public function prepare($var, $val = null) {
		if (is_array($var) && count($var) > 0) {
			foreach ($var as $key => $val) {
				$this->prepared[$key] = $val;
			}
		} elseif ($val != null) {
			$this->prepared[$var] = $val;
		}
	}

	public function rawQuery($query, $autonext = true) {
		// Sanitize that shizzzzz if there are things to fill in from $this->prepared
		if (count($this->prepared) > 0) {
			foreach ($this->prepared as $key => $val) {
				if (!strpos($query, '"{' . $key . '}"') && !strpos($query, "'{" . $key . "}'")) {
					// remove quoted stuff...
					$query = str_replace('"{' . $key . '}"', '{' . $key . '}', $query);
					$query = str_replace("'{" . $key . "}'", '{' . $key . '}', $query);

					// add the sanitized string
					$val_sanitized = $this->linker->escape_string($val);
					$query = str_replace('{' . $key . '}', '"' . $val_sanitized . '"', $query);
				} else {
					// add the sanitized string
					$val_sanitized = $this->linker->escape_string($val);
					$query = str_replace('{' . $key . '}', $val_sanitized, $query);
				}
			}
			// Empty this out now!
			$this->prepared = array();
		}

		$this->last_query = $query;
		$this->last_query_result = $this->linker->query($query);

		if (!$this->last_query_result) {
			echo "MySQL Error: " . $this->linker->error . "<br>\n";
		}

		if ($autonext && $this->count() > 0)
			$this->next();
	}

	// Used with MySQL fragments to find data in the open table
	// Note: this is not a sanitized function...
	public function find($query = '') {

		// Sanitize that shizzzzz if there are things to fill in from $this->prepared
		if (count($this->prepared) > 0) {
			foreach ($this->prepared as $key => $val) {
				if (!strpos($query, '"{' . $key . '}"') && !strpos($query, "'{" . $key . "}'")) {
					// remove quoted stuff...
					$query = str_replace('"{' . $key . '}"', '{' . $key . '}', $query);
					$query = str_replace("'{" . $key . "}'", '{' . $key . '}', $query);

					// add the sanitized string
					$val_sanitized = $this->linker->escape_string($val);
					$query = str_replace('{' . $key . '}', '"' . $val_sanitized . '"', $query);
				} else {
					// add the sanitized string
					$val_sanitized = $this->linker->escape_string($val);
					$query = str_replace('{' . $key . '}', $val_sanitized, $query);
				}
			}
			// Empty this out now!
			$this->prepared = array();
		}
		// add our MySQL fragments to our select statement.
		$query = 'SELECT * FROM `' . $this->open_table . '` ' . $this->join_fragment . ' ' . $query . ' ' . $this->limit_fragment;

		// We are now documenting our last query for the debugger
		$this->last_query = $query;

		// Executing our query through the linker...
		$this->last_query_result = $this->linker->query($query);

		if (!$this->last_query_result) {
			echo "MySQL Error: " . $this->linker->error . "<br>\n";
		}

		// Get the total and make us have rows... if we have some, lets look at the first one
		if ($this->count() > 0)
			$this->next();
	}

	// Gives us a count of the number of rows from the last query
	public function count() {
		if (!$this->last_query_result)
			return 0;
		return $this->last_query_result->num_rows;
	}

	public function countPages($per_page) {
		return ceil($this->count() / $per_page);
	}

	public function page($page_num, $per_page) {
		$page_num--;
		$start = $page_num * $per_page;
		$end = $start + $per_page;
		$this->limit_fragment = 'LIMIT ' . $start . ', ' . $end;
	}

	// iterates to the next row in our last query set
	public function next() {
		$this->current_row_data = $this->last_query_result->fetch_assoc();
		$this->current_row_data_modified = array();

		// return false if we're done providing data
		if ($this->current_row_data == NULL) {
			// clean up variables
			$this->current_row_data = array();
			$this->current_row_data_modified = array();
			// the fuck off result
			return false;
		}

		// returns true if we were successful
		return true;
	}

	// used to delete current record
	public function delete() {
		if ($this->primary_key == null || $this->primary_key == '') {
			echo 'Can not use proto::delete() method without a set primary_key for ' . $this->open_table;
			return;
		}
		// DELETE FROM table WHERE col = val
		$final_statement = 'DELETE FROM `' . $this->open_table . '` WHERE `' . $this->primary_key . '` = "' . $this->current_row_data[$this->primary_key] . '";';
		$this->linker->query($final_statement);
	}

	// Used to both insert and update
	public function save() {
		// New data needs to be inserted
		if (!array_key_exists($this->primary_key, $this->current_row_data)) {
			// INSERT INTO $tablename (col1, col2, col3) VALUES (val1, val2, val3);
			$final_statement = "INSERT INTO `" . $this->open_table . "` ";

			$fields = '`' . $this->primary_key . '`';
			$values = 'null';
			foreach ($this->current_row_data as $field => $value) {
				$value = $this->linker->escape_string($value);



				$fields .= ', `' . $field . '`';


				// simple function support. 
				if (isset($this->current_row_data_function[$field]))
					$values .= ', ' . $this->current_row_data_function[$field] . '("' . $value . '")';
				else
					$values .= ', "' . $value . '"';
			}
			$final_statement .= "(" . $fields . ") VALUES (" . $values . ");";

			$err_check = $this->linker->query($final_statement);
			if (!$err_check) {
				echo "MySQL Error: " . $this->linker->error . "<br>\n";
			}


			// now we will return only this one
			$this->find('WHERE `' . $this->primary_key . '` = "' . $this->linker->insert_id . '"');
			// We will be saving this based on the primary key
		} else {
			// UPDATE $tablename SET col1 = val, col2 = val WHERE $primary_key = $this->get($primary_key)
			$final_statement = "UPDATE `" . $this->open_table . "` SET";
			$changed_fields = count($this->current_row_data_modified);
			$builds = 1;

			foreach ($this->current_row_data_modified as $field => $value) {
				$value = $this->linker->escape_string($value);

				// simple function support
				if (isset($this->current_row_data_function[$field]))
					$final_statement .= ' `' . $this->open_table . '`.`' . $field . '` = ' . $this->current_row_data_function[$field] . '("' . $value . '")';
				else
					$final_statement .= ' `' . $this->open_table . '`.`' . $field . '` = "' . $value . '"';

				if ($builds != $changed_fields)
					$final_statement .= ',';

				$builds++;
			}

			$final_statement .= ' WHERE `' . $this->primary_key . '` = "' . $this->current_row_data[$this->primary_key] . '";';
			$this->linker->query($final_statement);
		}
	}

	// This helps us make a quick lazy form. It's intended to generate copy/paste html
	public function prototypeForm($excludes = array()) {
		$query = 'SHOW FULL COLUMNS FROM ' . $this->open_table;
		$this->linker->query($query);

		// We are now documenting our last query for the debugger
		$this->last_query = $query;

		// Executing our query through the linker...
		$this->last_query_result = $this->linker->query($query);

		// Get the total and make us have rows... if we have some, lets look at the first one
		if ($this->count() > 0) {
			// string that we will be inserting our form into.
			$build_form = '';

			while ($this->next()) {
				$field_name = $this->get_Field();

				// Lets parse the Type field into useful data
				$typing = str_replace("(", " ", $this->get_Type());
				$typing = str_replace(")", "", $typing);
				$arr = explode(" ", $typing, 2);
				$type = $arr[0];
				if (isset($arr[1])) {
					$values = $arr[1];
				} else {
					$values = null;
				}
				if (!in_array($field_name, $excludes))
					switch ($type) {
						case 'int':
						case 'varchar':
							$base = '<input type="text"{next_fragment}>';

							if ($values != null) {
								$base = str_replace('{next_fragment}', ' maxlength="' . $values . '"{next_fragment}', $base);
							}
							$base = str_replace('{next_fragment}', ' name="' . $field_name . '"{next_fragment}', $base);

							$base = str_replace('{next_fragment}', '', $base);

							$build_form .= $field_name . "\n";
							$build_form .= $base . "\n\n";
							break;
						case 'text':
							$base = '<textarea name="' . $field_name . '"></textarea>';

							$build_form .= $field_name . "\n";
							$build_form .= $base . "\n\n";
							break;
						case 'enum':
							preg_match_all("/'(.*?)'/", $values, $value_arr);
							$value_arr = $value_arr[1];

							$build_form .= $field_name . "\n";
							$build_form .= '<select name=' . $field_name . '>' . "\n";
							foreach ($value_arr as $v) {
								$build_form .= '<option value="' . $v . '">' . $v . '</option>' . "\n";
							}
							$build_form .= '</select>' . "\n\n";
						default:
							$base = '<textarea name="' . $field_name . '"></textarea>';

							$build_form .= $field_name . "\n";
							$build_form .= $base . "\n\n";
					} // switch($type)
			} // while($this->next())

			if ($build_form != '')
				$build_form . '<input type="submit" value="submit">';

			return '<pre>' . htmlentities($build_form) . '</pre>';
		} // if($this->count() > 0)

		return false;
	}

	public function close() {
		$this->linker->close();
	}

}


$db = new Proto($CONF['database']['hostname'], $CONF['database']['username'], $CONF['database']['password'], $CONF['database']['database']);
if ($db->linker->connect_error)
	fallout("Error connecting to MySQL");


function db_close()
{
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	$db->close();
}
