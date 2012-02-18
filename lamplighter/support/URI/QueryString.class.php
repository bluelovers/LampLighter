<?php

class QueryString {

	public static function Strip_var( $var_name, $query_string = -1 ) {

        if ( $query_string == -1 ) {
                $query_string = getenv('QUERY_STRING');
        }

        if ( !$var_name ) {
                $var_arr = array();
        }
        else if ( is_scalar($var_name) ) {
                $var_arr = array( $var_name );
        }
        else if ( is_array($var_name) ) {
                $var_arr =& $var_name;
        }
		else {
			trigger_error( 'Invalid var name passed to ' . __FUNCTION__ , E_USER_WARNING );
			return $query_string;
		}

        if ( $query_string && (count($var_arr) > 0) ) {
                foreach( $var_arr as $cur_var_name ) {
                        $cur_var_name = preg_quote($cur_var_name, '/');

                        $query_string = preg_replace("/&*{$cur_var_name}(\=[^&]*)?/", '', $query_string);
                        $query_string = preg_replace( '/^&+/', '', $query_string );
                }
        }

        return $query_string;

	}

	//------------------------------------------------------------------
	// get_query_string_leader determines whether the next query string 
	// variable in a url should be prefixed with ? or &
	//------------------------------------------------------------------
	public static function Get_query_string_leader( $value ) {
        
        return self::Get_leader($value);
	}
        
    public static function Get_leader( $value ) {    
    
        $leader = ( strpos($value, '?') !== false ) ? '&' : '?';

		return $leader;
	}
	
	public static function Remove_from_uri( $uri ) {
		
		if ( ($qs_pos = strpos($uri, '?')) !== false ) {
			$uri = substr( $uri, 0, $qs_pos );
		}

		return $uri;	
		
	}
	
}
?>