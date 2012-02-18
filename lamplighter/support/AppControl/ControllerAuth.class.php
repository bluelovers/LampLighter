<?php

class ControllerAuth {

	const PRIV_KEY_CONTROLLER 		 = 'controller';
	const PRIV_KEY_CONTROLLER_METHOD = 'controller_method';
	
	const KEY_ACTIVE_USER = 'active_user';
	const KEY_FORCE_LOGIN = 'force_login';
	
	const KEY_CONTROLLER_AUTH_DETAILS = 'controller_details';
	const KEY_ACTION_AUTH_DETAILS	 = 'action_details';
	
	public static $No_permission_link;
	
	public static function Method_name_is_valid( $name ) {
		
		try {
			
			if ( !$name ) {
				return 0;
			}
			
			if ( preg_match('/[^A-Za-z0-9_]/', $name) ) {
				return 0;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		} 
		
	}

	public static function Controller_name_is_valid( $name ) {
		
		try {
			
			if ( !$name ) {
				return 0;
			}
			
			if ( preg_match('/[^A-Za-z0-9_]/', $name) ) {
				return 0;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		} 
		
	}
	


	public static function Auth_db_tables_enabled() {
	
		if ( defined('CONTROLLER_AUTH_DB_TABLES_ENABLED') && constant('CONTROLLER_AUTH_DB_TABLES_ENABLED') ) {
			return true;
		}
		
		return 0;
		
	}
	
	public static function Active_user_from_options_hash( $options ) {
		
		try {
			
			LL::Require_class('Auth/AuthValidator');
			
			$active_user = null;
			
			if ( isset($options[self::KEY_ACTIVE_USER]) && $options[self::KEY_ACTIVE_USER]) {
				$active_user = $options[self::KEY_ACTIVE_USER];
				
				if ( !AuthValidator::User_object_is_valid($active_user) ) {
					throw new Exception( __CLASS__ . '-invalid_user_object');
				}
				
			}
			
			if ( !$active_user ) {
				
				LL::Require_class('Auth/AuthSessionManager');
				
				$session = AuthSessionManager::Get_active_session();
				$active_user = $session->get_user_object();
				
			}
			
			return $active_user;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}


	public static function Get_redirect_page() {
		
		try {
	
			
        	$redirect_to  = ( isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;

			return $redirect_to;
			
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public static function Controller_object_is_valid( ApplicationController $controller ) {
		
			if ( $controller instanceof ApplicationController ) {
				return true;
			}
			
			return 0;
	}
	
	public static function Method_object_is_valid( ControllerMethod $method ) {
		
			if ( $method instanceof ControllerMethod ) {
				return true;
			}
			
			return 0;
	}
	
	public static function User_has_method_access( $active_user, $method, $options = null ) {
		try {
			if ( self::Auth_db_tables_enabled() ) {
				LL::require_class('AppControl/ControllerAuth_DB');
				return ControllerAuth_DB::User_has_method_access($active_user, $method, $options);
			}
			else {
				LL::require_class('AppControl/ControllerAuth_Object');
				return ControllerAuth_Object::User_has_method_access($active_user, $method, $options);
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function User_has_controller_access( $active_user, $controller_obj, $options = array() ) {
		try {
			if ( self::Auth_db_tables_enabled() ) {
				LL::require_class('AppControl/ControllerAuth_DB');
				return ControllerAuth_DB::User_has_controller_access($active_user, $controller_obj, $options);
			}
			else {
				LL::require_class('AppControl/ControllerAuth_Object');
				return ControllerAuth_Object::User_has_controller_access($active_user, $controller_obj, $options);
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function User_permission_redirect( $options = null ) {
		
		try {
			
			LL::Require_class('Auth/AuthRedirector');
			
			AuthRedirector::Invalid_permission_redirect($options);
			exit(0);
			
		}
		catch( Exception $e ) {
			AuthRedirector::Invalid_permission_redirect($options);
			exit(0);
		}
		
		
	}
}
?>