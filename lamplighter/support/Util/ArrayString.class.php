<?php

//
// This class is used to parse
// strings like 'myarray[key1][key2]' to 
// create the actual array represented by that string
//

class ArrayString {
	
	const KEY_FORMAT_KEY_LITERALS = 'format_key_literals';
	const KEY_CHECK_ISSET_ONLY	  = 'check_isset_only';
	
    public static function input_name_is_array_reference( $name ) {

		return self::String_contains_array_key_reference($name);

    }

    public static function String_contains_array_key_reference( $str, $options = null ) {

		if ( preg_match('/\[[\'"A-Za-z0-9\_]+\]/', $str) ) {
			return true;
		}

		return 0;

	}
	
	public static function Key_reference_string_to_array( $key_string, $options = null ) {
		
		$key_arr   = array();
		$key_index = 0;
		
		if ( ($bracket_pos = strpos($key_string, '[')) !== false ) {
		
			if ( strpos($key_string, '[') != 0 ) {
    			$key_string = substr($key_string, $bracket_pos );
			}	
    	
    		
			$key_split_str = rtrim($key_string, ']');
			$key_split_str = str_replace('[', '', $key_split_str);
		
			$key_arr = explode(']', $key_split_str);

		}
		else {
			$key_arr  = array( $key_string );
		}
		
		return $key_arr;
		
	}
	
	public static function Format_key_literal( $key ) {
			
			$key   = trim ($key, '[]');
			$key   = trim ($key, '\'"');
			$quote = ( is_numeric($key) ) ? null : '\'';

			return $quote . $key . $quote;					
	}
	
	public static function Key_array_to_string( &$key_arr, $options = null ) {
		
		$key_string = '';
		
		if ( is_array($key_arr) ) {
			foreach( $key_arr as $cur_key ) {
				
				if ( array_val_is_nonzero($options, self::KEY_FORMAT_KEY_LITERALS) ) {
					$cur_key = self::Format_key_literal($cur_key);
				}
				
				$key_string .= '[' . $cur_key . ']';		
			}
		}
		else {
			trigger_error( "Warning: invalid array passed to " . __METHOD__ );
			return false;
		}
		
		return $key_string;
		
	}
	
	public static function Extract_array_keys_from_string_as_array( $arr_string ) {
		
		return self::Key_reference_string_to_array(self::Extract_array_keys_from_string($arr_string));
		
	}
	
	public static function Extract_array_keys_from_string( $arr_string ) {
		
		$key_string = null;
		
		if ( ($bracket_pos = strpos($arr_string, '[')) !== false ) {
			
			$key_string = substr($arr_string, $bracket_pos );
			
		}

		return $key_string;

	}
	
	public static function Extract_array_name_from_string( $arr_string ) {
		
		if ( ($bracket_pos = strpos($arr_string, '[')) !== false ) {
			return substr($arr_string, 0, $bracket_pos );
		}
		else {
			return $arr_string;
		}
	}
	
	public static function Set_array_value_by_keys( &$given_arr, $keys, $value ) {
		
		if ( !$keys || count($keys) <= 0 ) {
			trigger_error( "Warning: no array keys passed to " . __METHOD__);
		}
		else if ( is_scalar($keys) ) {
		
			if ( self::String_contains_array_key_reference($keys) ) {
				return self::Set_array_value_by_keys($given_arr, self::Key_reference_string_to_array($keys), $value);
			}
			else { 
				$keys = array( $keys );		
			}
		}
		
		if ( is_array($keys) ) {
			
			if ( $key_string = self::Key_array_to_string($keys, array( self::KEY_FORMAT_KEY_LITERALS => true) ) ) {
				
				eval( '$given_arr' . $key_string . '= $value;' );
			}
			
		}
		else {
			trigger_error( __METHOD__ . ' requires second parameter to be an array or scalar value' );
			return false;
		}
		
		return null;
	}

	public static function Unset_array_value_by_keys( &$given_arr, $keys  ) {
		
		if ( !$keys || count($keys) <= 0 ) {
			trigger_error( "Warning: no array keys passed to " . __METHOD__);
		}
		else if ( is_scalar($keys) ) {
		
			if ( self::String_contains_array_key_reference($keys) ) {
				return self::Unset_array_value_by_keys($given_arr, self::Key_reference_string_to_array($keys), $value);
			}
			else { 
				$keys = array( $keys );		
			}
		}
		
		if ( is_array($keys) ) {
			
			if ( $key_string = self::Key_array_to_string($keys, array( self::KEY_FORMAT_KEY_LITERALS => true) ) ) {
				
				eval( 'unset($given_arr' . $key_string . ');' );
			}
			
		}
		else {
			trigger_error( __METHOD__ . ' requires second parameter to be an array or scalar value' );
			return false;
		}
		
		return null;
	}
	
	public static function Find_array_value_by_keys( &$given_arr, $keys, $options = null ) {
		
		return self::Get_array_value_by_keys( $given_arr, $keys, $options );
	}

	public static function Get_array_value_by_keys( &$given_arr, $keys, $options = null ) {
		
		if ( !is_array($given_arr) ) {
			//trigger_error( 'First parameter to ' . __METHOD__ . ' must be an array', E_USER_WARNING );
			return null;
		}
		
		if ( !$keys || count($keys) <= 0 ) {
			if ( array_val_is_nonzero($options, self::KEY_CHECK_ISSET_ONLY) ) {
				return 0;
			}
			else {
				return null;
			}
		}
		else if ( is_scalar($keys) ) {
			
			if ( !self::String_contains_array_key_reference($keys) ) {
			
				if ( isset($given_arr[$keys]) ) {
					
					if ( array_val_is_nonzero($options, self::KEY_CHECK_ISSET_ONLY) ) {
						return true;	
					}
					else {
						return $given_arr[$keys];
					}
				}
				else {
					if ( array_val_is_nonzero($options, self::KEY_CHECK_ISSET_ONLY) ) {
						return 0;
					}
					else {
						return null;
					}
				}
			}
			else {
				
				return self::Get_array_value_by_keys($given_arr, self::Key_reference_string_to_array($keys), $options);
			}
		}
		else if ( is_array($keys) ) {
			
			if ( count($keys) > 0 ) {
			
			
				$result = ( array_val_is_nonzero($options, self::KEY_CHECK_ISSET_ONLY) ) ? 0 : null; 
				
				if ( $key_string = self::Key_array_to_string($keys, array(self::KEY_FORMAT_KEY_LITERALS => true)) ) {
				
					if ( array_val_is_nonzero($options, self::KEY_CHECK_ISSET_ONLY) ) {
						eval( 'if ( isset($given_arr' . $key_string .') ) $result = true;');
					}
					else {
						eval( 'if ( isset($given_arr' . $key_string .') ) $result = $given_arr' . $key_string . ';');
					}
				
					return $result;
				}
			}
		}
		else {
			trigger_error( __METHOD__ . ' requires second parameter to be an array or scalar value' );
			return false;
		}

	}

	public static function Array_value_isset_by_keys( &$given_arr, $keys, $options = null  ) {
		
		$options[self::KEY_CHECK_ISSET_ONLY] = true;
		
		return self::Get_array_value_by_keys( $given_arr, $keys, $options );
		
	}	
	
}
?>