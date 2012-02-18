<?php

//
// IMPORTANT:
//
// This has not yet been tested!
// Don't use it, unless I forgot to take this message out! 
// -JK 2007-Feb-28
//
//


LL::require_class('AppControl/ControllerAuth');

class ControllerAuth_DB extends ControllerAuth { 

	static $Table_controllers 		 = 'controllers';
	static $Table_controller_actions = 'controller_actions';

	static $Field_name_controller_id   			 = 'controller_id';
	static $Field_name_controller_name 			 = 'controller_name'; 
	static $Field_name_controller_global_access  = 'controller_global_access';
	static $Field_name_controller_requires_login = 'controller_requires_login';
	
	static $Field_name_action_id	 			 = 'action_id';
	static $Field_name_action_method 			 = 'action_method';
	static $Field_name_action_global_access 	 = 'action_global_access';
	static $Field_name_action_requires_login 	 = 'action_requires_login';
	

	public static function controller_method_has_auth_entry( $method_name, $controller_id ) {
	
		try {
			
			$action_id = self::controller_action_id_by_method_name( $method_name, $controller_id );
			
			if ( $action_id ) {
				return true;
			}
			
			return 0;
			
		}	
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public static function Action_auth_details_by_id( $action_id, $controller_id ) {

		try {
			if ( !$action_id || !is_numeric($action_id) ) {
				throw new Exception( 'general-non_numeric_value', "\$action_id: {$action_id}");
			}
			
			return self::Controller_action_details_by( self::$Field_name_action_id, $action_id, $controller_id );
			
		}
		catch(Exception $e) {
			throw $e;
		}
	}

	public static function Action_auth_details_by_method_name( $method_name, $controller_id ) {

		try {
			if ( !$method_name || !self::Method_name_is_valid($method_name) ) {
				throw new Exception( __CLASS__ . '-invalid_method_name', "\$method_name: {$method_name}");
			}
			
			return self::Controller_action_details_by( self::$Field_name_action_method, $method_name, $controller_id );
			
		}
		catch(Exception $e) {
			throw $e;
		}
	}

	public static function Controller_action_details_by( $field_name, $value, $controller_id ) {

		try {
			
			$db = LL::global_db_object();
			
			if ( !$field_name || !$db->is_valid_field_name($field_name) ) {
				throw new Exception ( __CLASS__ . '-invalid_field_name', "\$field_name: {$field_name}");
			}
			
			if ( !$value ) {
				throw new Exception ( 'general-missing_parameter %$value% ');
			}
			
			if ( !$controller_id || !is_numeric($controller_id) ) {
				throw new Exception( 'general-non_numeric_value', "\$controller_id: {$controller_id}");
			}
			
			$value = $db->parse_if_unsafe($value);
			$quote = ( is_numeric($value) ) ? '' : '\'';
			
			$query = $db->new_query_obj();
			
			$query->select( self::$Table_controller_actions . '.*' );
			$query->select( self::$Table_controllers . '.*' );
			$query->from  ( self::$Table_controller_actions );
			$query->join  ( 'INNER JOIN ' . self::$Table_controllers . 
								' ON ' . self::$Table_controllers . '.' . self::$Field_name_controller_id . '=' . 
										 self::$Table_controller_actions . '.' . self::$Field_name_controller_id 
						   );
			$query->where ( self::$Table_controller_actions . ".{$field_name}={$quote}{$value}{$quote}" );
			$query->where ( self::$Table_controllers . '.' . self::$Field_name_controller_id . "={$controller_id}" );
			
			$sql_query = $query->generate_sql_query();										 
			
			if ( !$result = $db->query($sql_query) ) {
				throw new SQLQueryException( $sql_query );
			}
									
			if ( $db->num_rows($result) > 0 ) {
				return $db->fetch_unparsed_assoc($result);
			}
			
			return null;
		}
		catch (Exception $e) {
			throw $e;
		}

	}	
	
	public static function Controller_auth_details_by_id( $controller_id ) {
		
		try {
			
			if ( !$controller_id || !is_numeric($controller_id) ) {
				throw new Exception( 'general-non_numeric_value', "\$controller_id: {$controller_id}");
			}
			
			return self::Controller_auth_details_by( self::$Field_name_controller_id, $controller_id );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Controller_auth_details_by_name( $controller_name ) {
		
		try {
			if ( !$controller_name || !self::Controller_name_is_valid($controller_name) ) {
				throw new Exception( __CLASS__ . '-invalid_controller_name', "\$controller_name: {$controller_name}");
			}	
			
			return self::Controller_auth_details_by( self::$Field_name_controller_name, $controller_name );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function Controller_auth_details_by( $field_name, $value ) {
		
		try {
			
			$db = LL::global_db_object();
			
			if ( !$field_name || !$db->is_valid_field_name($field_name) ) {
				throw new Exception ( __CLASS__ . '-invalid_field_name', "\$field_name: {$field_name}");
			}
			
			if ( !$value ) {
				throw new Exception ( 'general-missing_parameter %$value% ');
			}
	
			$query = $db->new_query_obj();			
			$value = $db->parse_if_unsafe($value);
			$quote = ( is_numeric($value) ) ? '' : '\'';
			
			$query->select( self::$Table_controllers . '.*' );
			$query->from  ( self::$Table_controllers );
			$query->where ( self::$Table_controllers . ".{$field_name}={$quote}{$value}{$quote}" );
		
			$sql_query = $query->generate_sql_query();
		
			if ( !$result = $db->query($sql_query) ) {
				throw new SQLQueryException( $sql_query );
			}
									
			if ( $db->num_rows($result) > 0 ) {
				return $db->fetch_unparsed_assoc($result);
			}
			
			return null;
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}

	public static function Validate_method_access( ControllerMethod $method, $options = null ) {
		
		try {

			$controller_name = self::Controller_name_from_method_object($method);
			$auth_details 	= self::Controller_auth_details_by_name($controller_name);

			if ( !$auth_details ) {
				if ( defined('CONTROLLER_AUTH_CONTROLLER_REQUIRE_EXPLICIT_ENTRY') ) {
					trigger_error( "Missing controller entry for {$controller_name}", E_USER_ERROR );
					exit(1);
				}
				else {
					//
					// This controller has no auth entries in the database, 
					// so without CONTROLLER_AUTH_CONTROLLER_REQUIRE_EXPLICIT_ENTRY, 
					// assume that this is a publicly accessible controller
					//
					return true;
				}
			}
			else {

				$redirect_to  = self::Get_redirect_page();			
				$options[self::KEY_CONTROLLER_AUTH_DETAILS] = $auth_details;

				if ( $active_user = self::Active_user_from_options_hash($options) ) {
					if ( self::User_has_method_access($active_user, $method, $options) ) {
						return true;
					}
				}

				FuseUser::user_permission_redirect( $active_user, $redirect_to );
				exit(0);
				
			}
			
			return false;
				
			
		}
		catch (Exception $e) {
			FuseUser::user_permission_redirect( $active_user, $redirect_to );
			exit(0);
		}
	}
	
	public static function Controller_name_from_method_object( ControllerMethod $method, $options = null ) {
		try {

			if ( $method->parent_controller_name ) {
				$controller_name = $method->parent_controller_name;
			}
			else {
				$parent_controller = $method->parent_controller;
			
				if ( !self::Controller_object_is_valid($parent_controller) ) {
					throw new Exception( __CLASS__ . '-invalid_controller' );
					return 0;
				}
			
				if ( !($controller_name = $parent_controller->get_name()) ) {
					throw new Exception( __CLASS__ . '-missing_controller_name' );
					return 0;
				}
			}
			
			return $controller_name;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public static function Controller_id_from_method_object( ControllerMethod $method, $options = null ) {
		try {
			
			$controller_id = null;
			
			if ( isset($options[self::KEY_CONTROLLER_AUTH_DETAILS]) ) {
				$auth_details = $options[self::KEY_CONTROLLER_AUTH_DETAILS];
			}
			else {
				$controller_name = self::Controller_name_from_method_object($method, $options);
				$auth_details = self::Controller_auth_details_by_name($controller_name);
			}
			
			if ( isset($auth_details[self::$Field_name_controller_id]) ) {
				$controller_id = $auth_details[self::$Field_name_controller_id];
			}
			
			return $controller_id;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function User_has_method_access( FuseUser $active_user, ControllerMethod $method, $options = null ) {

		try {
			
			if ( !self::Method_object_is_valid($method) ) {
				throw new Exception( __CLASS__ . '-invalid_method_object' );
			}
			
			$controller_id = self::Controller_id_from_method_object($method);
			
			if ( !$controller_id || !is_numeric($controller_id) ) {
				throw new Exception( 'general-non_numeric_value', "\$controller_id: {$controller_id}");
			}

			if ( array_key_is_nonzero($options, self::KEY_FORCE_LOGIN) ) {
				if ( !$active_user || !$active_user->validate_login() ) {
				    return 0;
				}
			}

			$auth_details = self::Controller_action_details_by_method_name($method->name, $controller_id);
			
			if ( !$auth_details ) {
				if ( defined('CONTROLLER_AUTH_METHOD_REQUIRE_EXPLICIT_ENTRY') ) {
					trigger_error( "Missing method entry for {$method_name}", E_USER_ERROR );
					exit(1);
				}
				else {
					//
					// This page has no auth entries in the database, 
					// so without CONTROLLER_AUTH_CONTROLLER_REQUIRE_DB_ENTRY, 
					// assume that this is a publicly accessible controller
					//
					return true;
				}
			}

			//
			// Make sure the user has access to the controller this action is in.
			//
			
			if ( !self::$Field_name_controller_name || !isset($auth_details[self::$Field_name_controller_id]) || !$auth_details[self::$Field_name_controller_name] ) {
				trigger_error( "Missing required DB field - self::\$Field_name_controller_name: {self::$Field_name_controller_name}", E_USER_ERROR );
				exit(1);
			}
	
			if ( !self::User_has_controller_access_by_name($auth_details[self::$Field_name_controller_name], $options) ) {
				return 0;
			}

			//
			// Find out if this page requires a login
			//
			if ( !self::$Field_name_action_requires_login || !isset($auth_details[self::$Field_name_action_requires_login]) ) {
				trigger_error( "Missing required DB field - self::\$Field_name_controller_requires_login: {self::$Field_name_controller_requires_login}", E_USER_ERROR );
				exit(1);
			}

			//
			// If this page requires a login, make sure the user is validated.
			//
			if ( $auth_details[self::$Field_name_action_requires_login] || array_val_is_nonzero($options, self::KEY_FORCE_LOGIN) ) {
				if ( !$active_user || !$active_user->validate_login() ) {
		        	return 0;
				}
			}
			else {
				//
				// If the action doesn't require a login,
				// then it's open to the public
				//
				return true;
			}

			if ( !self::$Field_name_action_id || !isset($auth_details[self::$Field_name_action_id]) || !$auth_details[self::$Field_name_action_id] ) {
				trigger_error( "Missing db field name: action_id", E_USER_ERROR );
				exit(1);
			}

			$permission_options[self::KEY_ACTION_AUTH_DETAILS] = $auth_details;

	        if ( !self::User_has_explicit_action_id_permission($active_user, $auth_details[self::$Field_name_action_id], $permission_options) ) {
	                return 0;
	        }
			else {
				return true;
			}

	        return 0;
        	
		}
		catch( Exception $e ) {
			throw $e;
		}	

	}

	public static function Validate_controller_access_by_name( $controller_name, $options = null ) {

		try {

			$redirect_to  = self::Get_redirect_page();

			if ( $active_user = self::Active_user_from_options_hash($options) ) {
				if ( self::User_has_controller_access_by_name($active_user, $controller_name, $options) ) {
					return true;
				}
			}
			
			FuseUser::user_permission_redirect( $active_user, $redirect_to );
			exit(0);
			
		}
		catch (Exception $e) {
			FuseUser::user_permission_redirect( $active_user, $redirect_to );
			exit(0);
		}
				
		
	}

	public static function User_has_controller_access_by_name( $active_user, $controller_name, $options = null ) {
	
		try {
			
			if ( !$controller_name || !self::Controller_name_is_valid($controller_name) ) {
				throw new Exception ( __CLASS__ . '-invalid_controller_name' );
			}
			
			if ( array_key_is_nonzero($options, self::KEY_FORCE_LOGIN) ) {
				if ( !$active_user || !$active_user->validate_login() ) {
				    return 0;
				}
			}

			if ( isset($options[self::KEY_CONTROLLER_AUTH_DETAILS]) ) {
				$auth_details = $options[self::KEY_CONTROLLER_AUTH_DETAILS];
			}
			else {
				$auth_details = self::Controller_auth_details_by_name( $controller_name );
			}
			
			if ( !$auth_details ) {
				if ( defined('CONTROLLER_AUTH_CONTROLLER_REQUIRE_EXPLICIT_ENTRY') ) {
					trigger_error( "Missing controller entry for {$controller_name}", E_USER_ERROR );
					exit(1);
					
				}
				else {
					//
					// This page has no auth entries in the database, 
					// so without CONTROLLER_AUTH_CONTROLLER_REQUIRE_DB_ENTRY, 
					// assume that this is a publicly accessible controller
					//
					return true;
				}
			}
			else {
				
				//
				// Find out if this controller requires a login
				//
				
				if ( !self::$Field_name_controller_requires_login || !isset($auth_details[self::$Field_name_controller_requires_login]) ) {
					trigger_error( "Missing required DB field - self::\$Field_name_controller_requires_login: {self::$Field_name_controller_requires_login}", E_USER_ERROR );
					exit(1);
				}
				
				//
				// If this controller requires a login, make sure the user is validated.
				//
				if ( $auth_details[$Field_name_controller_requires_login] || array_val_is_nonzero($options, self::KEY_FORCE_LOGIN) ) {
					if ( !$active_user || (!$active_user->is_logged_in() && !$active_user->validate_login()) ) {
						return 0;
					}
				}
				else {
					//
					// If the controller doesn't require a login,
					// then it's open to the public
					//
					return true;
				}
				
				
				if ( !self::$Field_name_controller_id || !isset($auth_details[self::$Field_name_controller_id]) || !$auth_details[self::$Field_name_controller_id] ) {
					trigger_error( "Missing required DB field - self::\$Field_name_controller_id: {self::$Field_name_controller_id}", E_USER_ERROR );
					exit(1);
				}

				$permission_options[self::KEY_CONTROLLER_AUTH_DETAILS] = $auth_details;

	        	if ( !self::User_has_explicit_controller_id_permission($active_user, $auth_details[self::$Field_name_controller_id], $permission_options) ) {
	                return 0;
	        	}
				else {
					return true;
				}

	        	return 0;
				
			}
		}
		catch (Exception $e) {
			throw $e;
		}	
		
	}



		public static function User_has_explicit_controller_id_permission( $active_user, $controller_id, $options = null ) {

		try {
			
			if ( !$controller_id || !is_numeric($controller_id) ) {
				throw new Exception( 'general-non_numeric_value', "\$controller_id: {$controller_id}");
			}

			if ( !self::User_object_is_valid($active_user) ) {
				throw new Exception( __CLASS__ . '-invalid_user_object');
			}
			
			if ( isset($options[self::KEY_CONTROLLER_AUTH_DETAILS]) ) {
				$auth_details = $options[self::KEY_CONTROLLER_AUTH_DETAILS];
			}
			else {
				$auth_details = self::Controller_auth_details_by_id( $controller_id );
			}
			
			if ( !self::$Field_name_controller_global_access || !isset($auth_details[self::$Field_name_controller_global_access]) ) {
				trigger_error( "Missing required DB field: \$Field_name_controller_global_access", E_USER_ERROR );
				exit(1);
			}
			
			if ( $auth_details[self::$Field_name_controller_global_access] ) {
				if ( !$active_user->has_restriction(self::PRIV_KEY_CONTROLLER, $controller_id) ) {
					return true;
				}
			}
			
			if ( $active_user->has_privilege(self::PRIV_KEY_CONTROLLER, $controller_id) ) {
				return true;
			}			
			
			return 0;
			
		}
		catch ( Exception $e ) {
			throw $e;
		}
		
	}

	public static function User_has_explicit_action_id_permission( $active_user, $action_id ) {
		
		try {
			if ( !$action_id || !is_numeric($action_id) ) {
				throw new Exception( 'general-non_numeric_value', "\$action_id: {$action_id}");
			}

			if ( !self::User_object_is_valid($active_user) ) {
				throw new Exception( __CLASS__ . '-invalid_user_object');
			}
			
			if ( isset($options[self::KEY_ACTION_AUTH_DETAILS]) ) {
				$auth_details = $options[self::KEY_ACTION_AUTH_DETAILS];
			}
			else {
				$auth_details = self::Action_auth_details_by_id( $action_id );
			}
		
			if ( !self::$Field_name_action_global_access || !isset($auth_details[self::$Field_name_action_global_access]) ) {
				trigger_error( "Missing required DB field: \$Field_name_action_global_access", E_USER_ERROR );
				exit(1);
			}
			
			if ( $auth_details[self::$Field_name_action_global_access] ) {
				if ( !$active_user->has_restriction(self::PRIV_KEY_CONTROLLER_METHOD, $action_id) ) {
					return true;
				}
			}
			
			if ( $active_user->has_privilege(self::PRIV_KEY_CONTROLLER_METHOD, $action_id) ) {
				return true;
			}			
			
			return 0;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


} //End class

?>
