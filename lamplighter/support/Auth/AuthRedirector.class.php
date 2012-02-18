<?php

LL::Require_class('Auth/AuthConstants');
LL::Require_file('Auth/Auth.conf.php');

class AuthRedirector {

	protected $_Calling_controller;

	public static function Get_login_page_uri() {
		
		/*
		if ( !Config::Get('auth.login_timeout_uri') && defined('SITE_BASE_URI') ) {
			Config::set('auth.login_timeout_uri', Config::Get_required('auth.login_page_uri'));
		}
		*/
		
		return Config::Get_required('auth.login_page_uri');
		
	}

	public static function Get_invalid_permission_page_uri() {
		
		if ( !Config::Get('auth.invalid_permission_page_uri') && defined('SITE_BASE_URI') ) {
			Config::Set('auth.invalid_permission_page_uri', constant('SITE_BASE_URI') . '/Auth/No_Permission');
		}
		
		return Config::Get_required('auth.invalid_permission_page_uri');
		
	}

	public function set_calling_controller( $controller ) {
		
		$this->_Calling_controller = $controller;
		
	}
		
	public function redirect_to( $redirect_page, $options = null) {

		try {

			if ( !isset($options['calling_controller']) || !$options['calling_controller'] ) {
				LL::Require_class('Util/Redirect');
				Redirect::To( $redirect_page );
				exit;
			}		
			else {
					
				if ( !array_val_is_nonzero($options, AuthConstants::KEY_REDIRECT_CONTROLLER_METHOD) ) {
					throw new Exception( __CLASS__ . '-missing_login_redirect_controller_method');
				}
				else {
					$method = $options[AuthConstants::KEY_REDIRECT_CONTROLLER_METHOD];
				}
					
				$reflector = new ReflectionObject($options['calling_controller']);
					
				if ( !$reflector->hasMethod($method) ) {
					throw new Exception( __CLASS__ . "-controller_does_not_have_redirect_method %{$method}%");
				}
				else {
					$reflector->$method($redirect_page, $options);
				}							
				
				return true;
			}	
		}
        catch( Exception $e ) {
        	throw $e;
        }
		
		
	}

	public static function Login_page_redirect( $options = null ) {
		
		try {
			
			LL::Require_class('URI/QueryString');
			
			$redirect_page = null;
			
			LL::Call_hook('Auth', 'before_invalid_permission_redirect');
			
			if ( $login_uri = self::Get_login_page_uri() ) {
			
				if ( isset($options[Config::Get_required('auth.key_redirect_uri')]) && $options[Config::Get_required('auth.key_redirect_uri')] ) {
					$redirect_page = $options[Config::Get_required('auth.key_redirect_uri')];
				}
			
				if ( isset($options['message']) && $options['message'] ) {
					$message = $options['message'];
				}
				else {
					$message = Config::Get('auth.message_login_required'); 
				} 
			
				$recursion_qs  = Config::Get_required('auth.key_login_redirected') . '=1';
				$qs_leader 	   = QueryString::Get_leader($login_uri);
					
				$final_uri = $login_uri;
				$final_uri .= $qs_leader . $recursion_qs;
			
				if ( $redirect_page ) {
					//
					// Keep track of redirects to prevent infinite loops
					//
					$final_uri .= '&' . Config::Get_required('auth.key_redirect_uri') . '=' . urlencode($redirect_page);
				}
			
				if ( $message) {
					LL::Set_session_message($message);
					//$final_uri .= '&' . Config::Get_required('auth.key_message') . '=' . urlencode($message);
				}
				
				//
				// Check for redirected=1 at the end of the url. 
				// If it's not there, do the redirect. Otherwise, 
				// error out to prevent infinite loop. This should not happen.
				//
				if ( !isset($_GET[Config::Get_required('auth.key_login_redirected')]) || !$_GET[Config::Get_required('auth.key_login_redirected')] ) {
					self::Redirect_to($final_uri);
					exit;
				}
				else {
					trigger_error('Auth-login_redirect_failed', E_USER_ERROR);
					exit;
				}
				
			}	
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Logout_success_redirect( $options = null ) {
		
		try {
			
			self::Redirect_to( self::Get_logout_redirect() );
						
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Get_logout_redirect( $options = null ) {
		
		try {

			LL::Require_file('Auth/Auth.conf.php');

			if ( isset($options[Config::Get_required('auth.key_redirect_uri')]) ) {
				$redirect_page = $options[Config::Get_required('auth.key_redirect_uri')];
			}
			
			if ( !$redirect_page ) {
				$redirect_page = Config::Get_required('auth.logout_redirect_page_default');
			}
			
			return $redirect_page;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Invalid_permission_redirect( $options = null ) {

		try {
			
			LL::Require_class('Auth/AuthValidator');
			LL::Require_Class('Auth/AuthSessionManager');
			
			LL::Call_hook('Auth', 'before_invalid_permission_redirect');
			
			$session = AuthSessionManager::Get_active_session();
			
			if ( !AuthValidator::User_session_object_is_valid($session) ) {
				trigger_error( 'Invalid user Object', E_USER_ERROR );
				exit;
			}

			if ( !$session->is_authenticated() ) {
				self::Login_page_redirect( $options );	
				exit;
			}
			else {
				if ( !isset($_GET[Config::Get_required('auth.key_login_redirected')]) || !$_GET[Config::Get_required('auth.key_login_redirected')] ) {
				
					LL::Require_class('URI/QueryString');
				
					$redirect_page		= null;
					$recursion_qs 		= Config::Get_required('auth.key_login_redirected') . '=1';
			
					if ( !$invalid_permission_page = self::Get_invalid_permission_page_uri() ) {
						trigger_error( 'No invalid permission page set.', E_USER_ERROR );
						exit;					
					}
				
					$qs_lead = QueryString::Get_leader( $invalid_permission_page );
				
					self::Redirect_to( "{$invalid_permission_page}{$qs_lead}{$recursion_qs}" );
					exit;
				}
				else {
					trigger_error( 'Auth-permission_redirect_failed', E_USER_ERROR );
					exit;
				} 
			}
			
			//
			// Should never get here:
			//
			trigger_error ( "Permission Redirect Failed.", E_USER_ERROR );
			exit;
			
		}
		catch( Exception $e ) {
			trigger_error( $e->getMessage(), E_USER_ERROR );
			exit;
		}
	}
	
}
?>