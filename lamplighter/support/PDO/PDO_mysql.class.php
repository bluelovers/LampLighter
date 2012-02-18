<?php

LL::Require_class('PDO/PDOParent');

class PDO_mysql extends PDOParent {

	public $column_info_map = array(
		'Type' => 'type', 
		'Field' => 'name', 
		'Null' => 'null', 
		'Default' => 'default', 
		'Extra' => 'extra', 
		'Key' => 'key'
	);
	
	/*
	public $index_info_map = array( 
		'Key_name' => 'name', 
		'Seq_in_index' => 'sequence',
		'Column_name' => 'column', 
		'Collation' => 'collation', 
		'Cardinality' => 'cardinality', 
		'Sub_part' => 'size',
		'Packed' => 'packed', 
		'Null' => 'null', 
		'Type' => 'type', 
		'Comment' => 'comment' 
	
	);
	*/

	/**
	 * Deprecated alias to get_column_names()
	 */
	public function get_field_names( $table  ) {
		try {
			return $this->get_column_names($table);
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_column_names($table) {
	
		try { 	
		
			$column_names = array();
			$columns = $this->get_table_columns($table);
			
			if ( is_array($columns) ) {
				foreach( $columns as $column_name => $column_info ) {
					$column_names[] = $column_name;
				}
			}

			return $column_names;

		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function get_table_columns( $table, $options = array() ) {

		try { 

			$use_cache = true;
		
			if ( isset($options['use_cache']) && $options['use_cache'] == false ) {
				$use_cache = false;
			}
		
			if ( !$this->column_info_use_cache ) {
				$use_cache = false;
			}

			if ( $use_cache ) {
				if ( isset(self::$Cached_column_info[$table]) && self::$Cached_column_info[$table] ) {
					
					Debug::Show('Using column info cache for ' . $table, Debug::VERBOSITY_LEVEL_INTERNAL);
					
					return self::$Cached_column_info[$table];
				}				
			}


			if ( !$this->table_name_is_valid($table) ) {
				throw new InvalidTableNameException();
			}
		
			$sql_query = "SHOW COLUMNS FROM $table";

			$result = $this->query($sql_query);
			
			$rows = $result->fetchAll(PDO::FETCH_ASSOC);
			
			foreach( $rows as $row ) {
				
				$row = $this->map_column_info_row($row);
				
				$type = array();
					
				foreach( $row as $key => $val ) {
					
					if ( $key == 'null' ) {
						if ( strtolower($val) == 'yes' ) {
							$row['null'] = 1;
						}
						else {
							$row['null'] = 0;
						}
					}
					else if ( $key == 'type' ) {
						$type['name'] = $this->column_data_type_name_by_string($val);
						$type['size'] = $this->column_data_type_size_by_string($val);
						$type['modifiers'] = $this->column_data_type_modifiers_by_string($val);
					}
					else if ( $key == 'key' ) {
						if ( $val == 'PRI' ) {
							$row['primary_key'] = 1;
						}
						else {
							$row['primary_key'] = 0;
						}
					}
					
					
					$row['type'] = $type;
					
				}
				
				$columns[$row['name']] = $row;
			}	
			
			self::$Cached_column_info[$table] = $columns;

			return $columns;
		}
		catch( Exception $e ) {
			throw $e;
		}
	
	}

	/*
	public function get_table_indexes( $table, $options = array() ) {
		
		try {
			
			$sql_query = "SHOW INDEXES FROM {$table}";

			$result = $this->query($sql_query);
			$ret = array();
			
			while ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
				
				$key_name = $row['Key_name'];
				
				foreach( $row as $key => $val ) { 
					if ( isset($this->index_info_map[$key]) ) {
						$ret[$key_name][$this->index_info_map[$key]] = $val;
					}
				}
				
				$ret[$key_name]['raw_info'] = $row;					
			}
			
			return $ret;

			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_table_foreign_keys( $table, $options = array() ) {
		
		try {

			$sql_query = "SHOW CREATE TABLE {$table}";

			$result = $this->query($sql_query);
			$ret = array();
			
			$row = $result->fetch(PDO::FETCH_ASSOC);
			
			$ct_statement = $row['Create Table'];
			$ct_lines = explode("\n", $ct_statement);

			$field_allowable_regex = 'A-Za-z0-9_\-';
			
			foreach( $ct_lines as $line ) {
				
				$line = trim($line);
				$fk_info = array();
				
				if ( substr($line, 0, strlen('CONSTRAINT')) == 'CONSTRAINT' ) {
					
					preg_match("/^CONSTRAINT [`\"]([A-Za-z0-9_\-]+)[`\"]\s+FOREIGN KEY\s+\([`\"]([A-Za-z0-9_\-]+)[`\"]\)\s+REFERENCES\s+[`\"]([$field_allowable_regex]+)[`\"]\s+\([`\"]([$field_allowable_regex]+)[`\"]\)/", $line, $fk_matches );

					$fk_info['update_action'] = null;
					$fk_info['delete_action'] = null;
					
					$fk_info['name'] 		= $fk_matches[1];
					$fk_info['column'] = $fk_matches[2];
					$fk_info['reference_table'] = $fk_matches[3];
					$fk_info['reference_column'] = $fk_matches[4];
					
					$fk_action_regex = '(CASCADE|SET NULL|NO ACTION|RESTRICT|SET DEFAULT)';
					
					if ( preg_match("/ON UPDATE {$fk_action_regex}/", $line, $update_matches) ) {
						$fk_info['update_action'] = $update_matches[1];
					}

					if ( preg_match("/ON DELETE {$fk_action_regex}/", $line, $delete_matches) ) {
						$fk_info['delete_action'] = $delete_matches[1];
					}
					
					$ret[$fk_info['name']] = $fk_info;
					
				}
				
			}
				
			return $ret;

			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_table_properties( $table, $options = array() ) {
		
		try {

			$sql_query = "SHOW CREATE TABLE {$table}";

			$result = $this->query($sql_query);
			$ret = array();
			
			$row = $result->fetch(PDO::FETCH_ASSOC);
			
			$ct_statement = $row['Create Table'];
			
			$ct_split = explode(')', $ct_statement);
			$max_index = count($ct_split) - 1;
			
			$property_string = trim($ct_split[$max_index]);
			
			preg_match_all( '/[A-Za-z0-9_\-\s]+=[A-Za-z0-9_\-]+/', $property_string, $matches );
			
			for ( $j = 0; $j < count($matches[0]); $j++ ) {
				
				$key_val_string = trim( $matches[0][$j] );
				
				list( $key, $val ) = explode('=', $key_val_string, 2);
				$ret[$key] = $val;
			}
			
			return $ret;

			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	*/

	public function map_column_info_row( $row ) {
		
		try {
			
			$ret = array();
			
			foreach( $row as $key => $val ) {
				
				if ( isset($this->column_info_map[$key]) ) {
					$ret[$this->column_info_map[$key]] = $val;
				}
				
			}
			
			$ret['raw_info'] = $row;
			
			return $ret;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function column_data_type_name_by_string( $type_string ) {
		
			$column_type = null;
		
			if ( ($paren_pos = strpos($type_string, '(')) !== false ) {
				$type_string = trim(substr($type_string, 0, $paren_pos));
			}
			
			if ( strpos($type_string, ' ') !== false ) {
				$column_type  = strrchr($type_string, ' ');
			}
			else {
				$column_type = $type_string;
			}
			
			return $column_type;
		
	}

	public function column_data_type_modifiers_by_string( $type_string ) {
		
			$modifiers = null;
		
			if ( strpos($type_string, ' ') !== false ) {
				$modifiers  = trim(strstr($type_string, ' '));
			}
			
			return $modifiers;
		
	}

	function column_data_type_size_by_string( $type_string ) {

		$type_info = explode( ' ', $type_string);
		
		$max_chars		 = null;
		$field_modifiers = null;
		$explicit_size   = null;

		if ( strtolower($type_info[0]) != 'national' ) {
			$field_type = strtolower($type_info[0]);
		}
		else {
			$field_type = strtolower($type_info[0] . ' ' . $type_info[1]);
			$type_info  = array_shift($type_info);
		}					
		
		if ( count($type_info) > 1 ) {
			$field_modifiers = trim($type_info[1]);
		}
					
		if ( ($paren_pos = strpos($field_type, '(')) !== false ) {
			$explicit_size = trim(substr($field_type, $paren_pos), '()');
			$field_type    = trim(substr($field_type, 0, $paren_pos));
			
			//if ( strpos($field_type, 'int') === false ) {
			
				if ( strpos($explicit_size, ',') !== false ) {
					$sizes = explode(',', $explicit_size);
					$sizes = array_filter($sizes, 'is_numeric');
				
					if ( count($sizes) > 0 ) {
						//
						// This explicit size is a precision setting
						// like (8,2)
						//
						$explicit_size = $sizes[0];
					}
					else {
						$explicit_size = null;
					}
				}
				else {
					if ( !is_numeric($explicit_size) ) {
						//	
						//not sure what this might be... use max size.
						//
						$explicit_size = null;
					}
				
				}
			//}
			//else {
			
				// per mySQL docs, the size in parentheses for an integer doesn't
				// denote actual value length, just default display length, 
				// so we use the maximum length for integer types
			//	$explicit_size = null; 
			//}
		}

		return $explicit_size;
	}

	public function get_tables( $options = array() ) {
	
		try { 	
		
		 	$tables = array();
			
			$db_name = $this->config_data['db_name'];
			
			if ( !$db_name ) {
				throw new MissingParameterException('db_name');
			}

			$query = "SHOW TABLES FROM {$db_name}";
			
			$result = $this->query($query);
			
			while ( $row = $result->fetch( PDO::FETCH_BOTH ) ) {
				$tables[] = $row[0];
			}
		
			return $tables;

		}
		catch( Exception $e ) {
			throw $e;
		}

	}
    
}
?>