<?php

LL::require_class('AppControl/ControllerMethod');
LL::require_class('AppControl/ControllerAuth');

class ControllerAuth_Object extends ControllerAuth {
	
	public static function Validate_method_access( ControllerMethod $method, $options = null ) {
		
		try {
			
			LL::Require_class('Auth/AuthSessionManager');
			
			$skip_redirect = false;
			$session = AuthSessionManager::Get_active_session();
			$options[Config::Get_required('auth.key_redirect_uri')]  = self::Get_redirect_page();
			
			$active_user = $session->get_user_object();

			if ( (isset($options['skip_redirect']) && $options['skip_redirect']) || $method->auth_skip_redirect ) {
				$skip_redirect = true;
			}			
			
			if ( self::User_has_method_access($active_user, $method ) ) {
				return true;
			}

			
			if ( (!isset($options['skip_redirect']) || !$options['skip_redirect']) && !$method->auth_skip_redirect ) {
				self::User_permission_redirect( $options );
        		exit(0);
			}
			else {
				if ( $session->is_authenticated() ) {
					throw new UserNoPermissionException();
				}
				else {
					throw new UserLoginRequiredException();
				}
			}
			
			return false;
			
		}
		catch( Exception $e ) {
			
			if ( !$skip_redirect ) {
				self::User_permission_redirect( $options );
        		exit(0);
			}
			else {
				throw $e;
			}
			
		}
		
	}

	public static function User_has_method_access( $active_user, ControllerMethod $method, $options = null ) {
		
		try {
			
			LL::Require_class('Auth/AuthValidator');
			LL::Require_class('Auth/AuthSessionManager');
			LL::Require_class('Auth/UserPrivQueryHelper');
			
			$parent_controller = $method->parent_controller;

			if ( !AuthValidator::User_object_is_valid($active_user) ) {
				throw new Exception( __CLASS__ . '-invalid_user_object');
			}
			
			//
			// Validate controller access
			//
			if ( !self::User_has_controller_access($active_user, $parent_controller, $options) ) {
				return 0;
			}
			
			if ( $method->requires_login ) {
						
				$session = AuthSessionManager::Get_active_session();
			
				if ( !AuthValidator::User_session_object_is_valid($session) || !$session->is_authenticated() ) {
					return false;
				}
			}
			else {
				//
				// If the action doesn't require a login,
				// then it's open to the public
				//
				return true;
			}

			//
			// Validate method access
			//
			$priv_value = self::Priv_value_by_method_object($method);
			
			if ( $method->global_access ) {
				if ( !$active_user->has_restriction(self::PRIV_KEY_CONTROLLER_METHOD, array(UserPrivQueryHelper::KEY_PRIV_VAL => $priv_value)) ) {
					return true;
				}
			}

			if ( $active_user->has_privilege(self::PRIV_KEY_CONTROLLER_METHOD, array(UserPrivQueryHelper::KEY_PRIV_VAL => $priv_value)) ) {
				return true;
			}		
			
			return false;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Validate_controller_access( $controller, $options = null ) {

		try {
			
			LL::Require_class('Auth/AuthSessionManager');
			
			$skip_redirect = false;
			$session = AuthSessionManager::Get_active_session();
			$options[Config::Get_required('auth.key_redirect_uri')]  = self::Get_redirect_page();

			if ( (isset($options['skip_redirect']) && $options['skip_redirect']) || $controller->auth_skip_redirect ) {
				$skip_redirect = true;
			}
			
			$active_user = $session->get_user_object();
			
			if ( self::User_has_controller_access($active_user, $controller, $options) ) {
				return true;
			}
			
			if ( !$skip_redirect ) {
				self::User_permission_redirect( $options );
    	    	exit(0);
			}
			else {
				if ( $session->is_authenticated() ) {
					throw new UserNoPermissionException();
				}
				else {
					throw new UserLoginRequiredException();
				}
			}
			
			return false;
			
		}
		catch( Exception $e ) {
			if ( !$skip_redirect ) {
				self::User_permission_redirect( $options );
        		exit(0);
			}
			else {
				throw $e;
			}
			
		}
	}

	public static function User_has_controller_access( $active_user, $controller, $options = null ) {
		
		try {

			LL::Require_class('Auth/AuthValidator');
			LL::Require_class('Auth/AuthSessionManager');
			LL::Require_class('Auth/UserPrivQueryHelper');
			
			if ( !AuthValidator::User_object_is_valid($active_user) ) {
				throw new Exception( __CLASS__ . '-invalid_user_object');
			}
			
			if ( !self::Controller_object_is_valid($controller) ) {
				throw new Exception ( __CLASS__ . '-invalid_parent_controller');
			}

			if ( !($controller_name = $controller->get_name()) ) {
				throw new Exception ( __CLASS__ . '-missing_controller_name' );
			}
			
			if ( $controller->requires_login ) {
			
				$session = AuthSessionManager::Get_active_session();
				
				if ( !AuthValidator::User_session_object_is_valid($session) || !$session->is_authenticated() ) {
					return false;
				}
				
			}
			else {
				
				//
				// If the action doesn't require a login,
				// then it's open to the public
				//
				return true;
				
			}

			
	
			if ( $controller->global_access ) {
				if ( !$active_user->has_restriction(self::PRIV_KEY_CONTROLLER, array(UserPrivQueryHelper::KEY_PRIV_VAL => $controller_name)) ) {
					return true;
				}
			}
			else {
				if ( $active_user->has_privilege(self::PRIV_KEY_CONTROLLER, array(UserPrivQueryHelper::KEY_PRIV_VAL => $controller_name)) ) {
					return true;
				}
			}

			return false;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Priv_value_by_method_object( ControllerMethod $method ) {
		
		try {
			if ( !($parent_controller_name = $method->parent_controller_name) ) {
				throw new Exception ( 'general-missing_parameter %parent_class_name%' );
			}
			
			if ( !($method_name = $method->name) ) {
				throw new Exception ( 'general-missing_parameter %name%' );
			}
			
			return "{$parent_controller_name}/{$method_name}";
						
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
}
?>