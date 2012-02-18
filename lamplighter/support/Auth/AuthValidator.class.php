<?php

class AuthValidator {

	public static function Password_has_valid_format( $password ) {
		
		if ( !$password ) {
			if ( !Config::Get('auth.allow_blank_passwords') ) {
				return 0;
			}
		}
		else {		

			if ( Config::Get('auth.password_min_length') && (strlen($password) < Config::get('auth.password_min_length')) ) {
				return 0;
			}

		}
		
		if ( Config::Get('auth.password_max_length') && (strlen($password) > Config::get('auth.password_max_length')) ) {
			return 0;
		}
		
		
		if ( $password && Config::Get('auth.password_format') ) {
			
			if ( !preg_match("/^" . Config::Get('auth.password_format') . "$/", $password) ) {
				return 0;
			}
		}
		
		return true;
	}

	public static function Username_has_valid_format( $username ) {

		if ( !$username ) {
			return 0;
		}

		if ( Config::Get('auth.username_max_length') && (strlen($username) > Config::Get('auth.username_max_length')) ) {
			return 0;
		}

		if ( Config::Get('auth.username_min_length') && (strlen($username) < Config::Get('auth.username_min_length')) ) {
			return 0;
		}
		
		if ( Config::Get('auth.username_format') ) {
			if ( !preg_match("/^" . Config::Get('auth.username_format') . "$/", $username) ) {
				return 0;
			}
		}
		
		return true;
	}	

	public static function Uid_has_valid_format( $uid ) {
		
		if ( !$uid ) {
			return 0;
		}
		
		if ( Config::Get('auth.uid_format') ) {
			if ( !preg_match("/^" . Config::Get('auth.uid_format') . "$/", $uid) ) {
				return 0;
			}
		}
		else {
			if ( $uid && is_numeric($uid) ) {
				return true;
			}
		}
		
		return 0;
		
	}
	
	public static function Priv_type_key_has_valid_format( $key ) {
		
		return is_valid_index_key($key);
		
	}
	
	public static function User_object_is_valid( $user ) {
		
		try {
		
			$class_name = LL::class_name_from_location_reference(Config::Get_required('auth.user_class'));

			if ( is_a($user, $class_name) || is_subclass_of($user, $class_name) ) {
				return true;
			}
				
			return 0;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function User_session_object_is_valid( $user ) {
		
		try {
		
			$class_name = LL::class_name_from_location_reference(Config::Get_required('auth.user_session_class'));

			if ( is_a($user, $class_name) || is_subclass_of($user, $class_name) ) {
				return true;
			}
				
			return 0;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
}
?>