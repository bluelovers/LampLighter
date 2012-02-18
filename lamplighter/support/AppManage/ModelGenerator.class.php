<?php

LL::Require_class('AppManage/ManagementTask');

class ModelGenerator extends ManagementTask {
	

	public function generate_for_table( $table_name, $options = array() ) {
		
		try {
			$options['table_name'] = $table_name;
			$path = null;
			
			try {
				LL::Require_class('PDO/PDOFactory');
				$db = PDOFactory::Instantiate();
				
				$field_list = $db->get_column_names($table_name);	
				$options['prefix'] = $this->model_prefix_by_field_list($field_list);
			}
			catch( Exception $e ) {
				//ok if field list/prefix couldn't be found -- table might not exist yet
			}
			
			$options['class_name'] = $this->class_name_by_table_name($table_name);
				
			try { 
				$path = $this->write_model( $options );
			}
			catch( FileOverwriteException $owe ) {
				if ( isset($options['from_console']) && $options['from_console'] ) {
					echo $owe->getMessage() . "\n";
				}
				else {
					throw $owe;
				}
			}
			
			return $path;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function generate_for_all_tables( $options ) {
		
		try {
			
			LL::Require_class('PDO/PDOFactory');
			$db = PDOFactory::Instantiate();
	
			if ( $tables = $db->list_tables() ) {
				foreach( $tables as $table_name ) {
				
					$this->generate_for_table( $table_name, $options);
				
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function model_prefix_by_field_list( $fields ) {
		
		$final_prefix = null;
		
		if ( is_array($fields) ) {
			
			$prefixes = array();
				
			foreach( $fields as $field_name ) {
			
				$offset = 0;
				$uc_pos = 0;	
				$field_check = $field_name;
								
				while ( $uc_pos = strrpos($field_check, '_' ) ) {
					
					$increment = true;
					$prefix = substr($field_name, 0, $uc_pos + 1);
					
					/*
					foreach( $prefixes as $existing_prefix => $count ) {
						if ( $existing_prefix != $prefix ) {
							if ( substr($existing_prefix, 0, strlen($prefix)) == $prefix ) {
								//$increment = false;
							}
						}
					}
					*/
					
					if ( $increment ) {
						if ( isset($prefixes[$prefix]) ) {
							$prefixes[$prefix]++;	
						}
						else {
							$prefixes[$prefix] = 1;
						}
					}
					
					$offset++;
					$field_check = substr($field_check, 0, $uc_pos);
					
				}
			}
			
			if ( is_array($prefixes) ) {
				$max_count = 0;
				foreach ($prefixes as $prefix => $count) {
					if ( $count > 1 && ($count > $max_count) ) {
						$max_count = $count;
						$final_prefix = $prefix;
					}	
				}
			}
		}
		
		return $final_prefix;
	}
	
	public function get_model_content( $options = array() ) {
		
		$content = '<?php' . "\n\n";
		$content .= "LL::Require_class('Data/DataModel');\n\n";

		$content .= "class {$options['class_name']} extends DataModel {\n\n";
		
		if ( isset($options['prefix']) && $options['prefix'] ) {
			$content .= "\t" . 'public $field_name_prefix=\'' . $options['prefix'] . '\';' . "\n\n";
		}
		
		$content .= "\tprotected function _Init() {\n\n";
		$content .= "\t}\n\n";
		$content .= '}';
		$content .= "\n\n" . '?>';
		
		return $content;
	}
	
	public function class_name_by_table_name( $table_name ) {
		
		return ucfirst(underscore_to_camel_case(singularize($table_name)));
		
	}

	public function filename_by_table_name( $table_name ) {
		
		return $this->class_name_by_table_name($table_name) . '.class.php';
		
	}

	public function filepath_by_table_name( $table_name ) {
		
		return constant('DATA_MODEL_BASE_PATH') . DIRECTORY_SEPARATOR . $this->filename_by_table_name($table_name);
		
	}	
	
	public function write_model( $options = array() ) {
		
		try {
			if ( !isset($options['table_name']) || !$options['table_name']) {
				throw new Exception( "No table name given");
			}
			
			$path = $this->filepath_by_table_name($options['table_name']);
			if ( !file_exists($path) || (isset($options['overwrite']) && $options['overwrite']) ) {
				file_put_contents( $path, $this->get_model_content($options) );
			}
			else {
				throw new FileOverwriteException( "{$path} already exists" );
			}
			
			return $path;		
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
		
		
	}
}

?>