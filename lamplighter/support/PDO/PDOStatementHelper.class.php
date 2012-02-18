<?php


class PDOStatementHelper { 

	const BIND_TYPE_FUNCTION = 'f';
	

	public static function PDO_bind_type_by_variable( $var ) {
		
		if ( is_numeric($var) ) {
		
			if ( $var < PHP_INT_MAX && number_format($var, 0) == $var ) {
				return PDO::PARAM_INT;
			}
			else {
				// Double...PDO treats it as string though
				return PDO::PARAM_STR;
			}	
		}
		else {
			return self::PDO_bind_type_by_var_type( gettype($var) );
		}
		
    }		

	public static function PDO_bind_type_by_var_type( $type ) {
		
		switch( $type ) {		
           		case 'integer':
               			return PDO::PARAM_INT;
               			break;
               	case 'boolean':
               			return PDO::PARAM_BOOL;
               			break;
               	case 'NULL':
               			return PDO::PARAM_NULL;
               			break;
           		case 'double':
               	case 'string':
           		default:
               			return PDO::PARAM_STR;
               			break;
		}

		return null;

	}
	
	public static function PDO_bind_type_by_letter( $letter ) {
		
		switch( $letter ) {
			case 'i':
				return PDO::PARAM_INT;
				break;
			case 'b':
				return PDO::PARAM_BOOL;
				break;
			case 'n':
				return PDO::PARAM_INT;
				break;
			case 'f': //function - special case - not for passing to PDO methods
			case 'u': //"unbindable" - special case - not for passing to PDO methods
				return null;
				break;
			case 's':
			default:
				return PDO::PARAM_STR;
				break;	
		}
		
		
	}

	public static function PDO_bind_type_by_field_info( $field_info ) {
		
		$found_type = false;
		
		if ( isset($field_info['type']) ) {
			
			if ( is_numeric($field_info['type']) ) {
				$bind_type = $field_info['type'];
				$found_type = true;
			}
			else {					
				$bind_type = self::PDO_bind_type_by_letter(substr($field_info['type'], 0, 1));
				$found_type = true;
			}
		}
		
		if ( !$found_type ) {
			
			/*
			if ( $type_string = $this->_Parent_db_obj->get_field_data_type_name($table, $field_name) ) {
				$bind_letter = DB_Stmt::Bind_letter_from_field_type_name($type_string); 
			}
			*/
			$bind_type = self::PDO_bind_type_by_variable($field_info['value']);
		}
		
		return $bind_type;
	

	}

}

?>