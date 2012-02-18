<?php

LL::Require_file('Auth/Auth.conf.php');
LL::require_class('Auth/AuthConstants');

class UserLogin {

	const SETTING_TYPE_KEY_LOGIN_PAGE_DEFAULT  = 'login_page_default';
	const SETTING_TYPE_KEY_LOGOUT_PAGE_DEFAULT = 'logout_page_default';
	

	const VALIDATION_METHOD_COOKIE = 1;
	const VALIDATION_METHOD_DIGEST = 2;
	const VALIDATION_METHOD_HASH = 4;
	const VALIDATION_METHOD_URL = 8;
	const VALIDATION_METHOD_URI = 8;
	const VALIDATION_METHOD_POST = 16;
	const VALIDATION_METHOD_OBJECT_MEMBERS = 32;

	public $get_key_uid	   = 'uid';
	public $get_key_digest = 'login';

	public $given_username;
	public $given_user_id;
	public $given_password;	
	public $given_password_encrypted;

	public $validation_method;
	public $fail_code;

	public $redirect_disable = false;
	public $redirect_ignore_explicit = false;
	public $redirect_page;
	public $explicit_redirect_only = false;
	public $redirect_transparent = false;
	public $redirect_controller_method = 'login_redirect';
	public $redirect_from_get = true;
	public $redirect_from_post = true;
	
	public $cookie_expiration_time = null;
	
	protected $_Calling_controller;
	protected $_Available_validation_methods = array();
	protected $_User_object;
	protected $_Login_authenticated = false;
	
	//protected $_Session_parent;
	
	public function get_user_object() {
		
		try { 
			
			if ( !$this->_User_object ) {
				LL::Require_class('Auth/AuthLoader');
				$this->_User_object = AuthLoader::Load_user_object();
			}
			
			return $this->_User_object;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
			
		
	}

	//public function set_session_parent( $session ) {
		
	//	$this->_Session_parent = $session;
	//}
 
	public function set_user_object( $user ) {
		
		$this->_User_object = $user;
		
	}

	public function get_reset_user_object() {
		
		try { 
		
			$user = $this->get_user_object();
			$user->reset_record_data();

			return $user;
		}
		catch( Exception $e ) {
			throw $e;
		}				
		
	}

	/*
	public function get_authenticated_user_object() {
		
		try {
			
			if ( !$this->_User_object_authenticated ) {
				throw new Exception ( __CLASS__ . '-user_not_promoted_to_authenticated_status');
			}
			
			return $this->_User_object_authenticated;
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	*/

	public function set_calling_controller( $controller ) {
		
		$this->_Calling_controller = $controller;
		
	}

	/*
	protected function _Promote_user_object_to_authenticated() {
		
		try { 
			
			if ( !$this->_User_object || !$this->_User_object->id ) {
				throw new Exception ( __CLASS__ . '-missing_user_object' );
			}
			
			LL::Require_class('Auth/AuthLoader');
			$this->_User_object_authenticated = AuthLoader::Load_user_object_authenticated();
			$this->_User_object_authenticated->id = $this->_User_object->id; 
			$this->_Login_authenticated = true;
			
			$this->_User_object = null;
			
			return $this->_User_object_authenticated;
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}
			
		
	}
	*/

	public static function Validation_method_constant_is_sane( $method ) {
		
		if ( is_numeric($method) && $method > 0 ) {
			return true;
		}
		
		return 0;
	}

	public function add_available_validation_method( $method ) {

		if ( self::Validation_method_constant_is_sane($method) ) {
			$this->_Available_validation_methods[] = $method;
		}
		else {
			throw new Exception( __CLASS__ . '-validation_method_invalid'); 
		}

	}

	public function require_validation_method( $method ) {

		try { 
			$this->_Available_validation_methods = array();
			$this->add_available_validation_method($method);
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function require_validation_methods( array $methods ) {

		try { 
			foreach( $methods as $method ) {
				$this->add_available_validation_method($method);
			}
		}
		catch( Exception $e ) {
			throw $e;
		}

	}


	function is_validation_method_available( $method ) {
		
		try { 
			if ( $this->_Available_validation_methods && (count($this->_Available_validation_methods) > 0) ) {
				if ( in_array($method, $this->_Available_validation_methods) ) {
					return true;
				}
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


	public function validate( $options = array() ) {
	
		try { 

			if ( $this->given_username || $this->given_user_id ) {
	
				try {
					$this->validate_by_object_members();
					$this->complete_valid_login( $options );
					return true;
				}
				catch( LoginInvalidException $e ) {
					//Do nothing, continue trying to validate other sources
				}
				
			}
			else {
				if ( $this->is_validation_method_available(self::VALIDATION_METHOD_POST) ) {
					try {
			
						$this->validate_by_post();
						$this->complete_valid_login( $options );
						return true;
					}
					catch( LoginInvalidException $e ) {
						//Do nothing, continue trying to validate other sources
					}
					
				}
			
				if ( $this->is_validation_method_available(self::VALIDATION_METHOD_COOKIE) ) {
					
					try { 

						$this->validate_by_cookie();
						$this->complete_valid_login($options);
						return true;
						
					}
					catch( LoginTimedOutException $e ) {
						
						$this->timeout($options);
						exit;
					}
					catch( LoginInvalidException $e ) {
						//Do nothing, continue trying to validate other sources
					}
					
				}


				if ( $this->is_validation_method_available(self::VALIDATION_METHOD_URI) ) {
					try { 
						$this->validate_by_uri();
						$this->complete_valid_login($options);
						return true;
					}
					catch( LoginInvalidException $e ) {
						//Do nothing, continue trying to validate other sources
					}
					
				}
			}

			$this->cleanup_invalid_login($options);
			throw new LoginInvalidException( __METHOD__ );

		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function validate_by_object_members() {
		
		try {
	
			if ( !$this->given_username && !$this->given_user_id ) {
				throw new Exception( __CLASS__ . '-given_username_or_uid_required' );
			}
			else if ( $this->given_username && $this->given_user_id ) {
				throw new Exception( __CLASS__ . '-must_specify_either_given_username_or_uid_not_both' );
			}

			$user = $this->get_reset_user_object(); 
			
			if ( $this->given_username ) {
				$user->name = $this->given_username;
			}
			else if ( $this->given_user_id ) {
				$user->id = $this->given_user_id;			
			} 
			
			if ( $user->record_exists() ) {
				
				if ( $this->given_password ) {

					if ( $user->validate_password($this->given_password) ) {
						$this->validation_method = self::VALIDATION_METHOD_OBJECT_MEMBERS;
						return true;
					}
				}
				else if ( $this->given_password_encrypted ) {
					
					if ( $user->validate_password_digest($this->given_password_encrypted) ) {
						$this->validation_method = self::VALIDATION_METHOD_OBJECT_MEMBERS;
						return true;
					}					
				}
			}
			
			$this->cleanup_invalid_login();
			throw new LoginInvalidException( __METHOD__ );
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	
	}

	public function validate_by_post() {

		try { 
			if ( !Config::Get_required('auth.post_key_username') OR !Config::Get_required('auth.post_key_password') ) {
				throw new Exception( __CLASS__ . '-post_keys_not_set' );
			}

			if ( !isset($_POST[Config::Get_required('auth.post_key_username')]) || !isset($_POST[Config::Get_required('auth.post_key_password')]) ) {
				throw new LoginMissingDataException('username');
			}

			$this->given_username = ( get_magic_quotes_gpc() ) ? stripslashes($_POST[Config::Get_required('auth.post_key_username')]) : $_POST[Config::Get_required('auth.post_key_username')];
			$this->given_password = ( get_magic_quotes_gpc() ) ? stripslashes($_POST[Config::Get_required('auth.post_key_password')]) : $_POST[Config::Get_required('auth.post_key_password')];

			if ( $res = $this->validate_by_object_members() ) {
				$this->validation_method = self::VALIDATION_METHOD_POST;
				return $res;						
			}
			
			throw new LoginInvalidException( __METHOD__ );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function validate_by_uri() {

		try { 
			if ( !$this->get_key_uid OR !$this->get_key_digest ) {
				throw new Exception( __CLASS__ . '-get_keys_not_set' );
			}

			if ( !isset($_GET[$this->post_key_uid]) || !isset($_GET[$this->get_key_uid]) ) {
				throw new LoginMissingDataException('uid');
			}

			$user = $this->get_reset_user_object(); 
			
			$uid    =  ( get_magic_quotes_gpc() ) ? stripslashes($_GET[$this->get_key_uid]) : $_POST[$this->get_key_uid];
			$digest = ( get_magic_quotes_gpc() ) ? stripslashes($_GET[$this->get_key_digest]) : $_POST[$this->get_key_digest];
			
			if ( !$uid ) {
				throw new LoginMissingDataException('uid');
			}

			if ( !$digest ) {
				if ( !Config::Get_required('auth.allow_blank_passwords') ) {
					throw new LoginMissingDataException('digest');
				}
			}

			$user->id = $uid;
			
			if ( $user->get_password_digest() == $digest ) {
				$this->validation_method = self::VALIDATION_METHOD_URI;
				return true;
			}
			
			throw new LoginInvalidException( __METHOD__ );
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function validate_by_cookie() {

		try { 		
			
			$login_cookie_name = Config::Get_required('auth.login_cookie_name');
			$uid_index     = Config::Get_required('auth.login_cookie_index_user_id');
			$digest_index  = Config::Get_required('auth.login_cookie_index_password_digest');
			$time_index = Config::Get_required('auth.login_cookie_index_update_time');

						
			if ( isset($_COOKIE) && (count($_COOKIE) > 0) && isset($_COOKIE[$login_cookie_name]) ) {
				
				if ( !isset($_COOKIE[$login_cookie_name][$uid_index]) ) {
					throw new LoginDataMisingException('uid');			
				}

				if ( !isset($_COOKIE[$login_cookie_name][$digest_index]) ) {
					throw new LoginDataMisingException('digest');	
				}

				
					
				if ( Config::Get_required('auth.login_timeout') > 0 && Config::Get_required('auth.login_timeout_enabled') ) {
					
					$cookie_time = null;
					
					if ( isset($_COOKIE[$time_index]) ) {
						$cookie_time = $_COOKIE[$login_cookie_name][$time_index];
					}
						
					$exp_time = time() - (Config::Get_required('auth.login_timeout') * 60);

					if ( $cookie_time && ($cookie_time > 0) && ($cookie_time <= $exp_time) ) {
						throw new LoginTimedOutException();		
					}

				}

				$user = $this->get_reset_user_object(); 
				
				$uid_field = Config::Get_required('auth.session_uid_field');
				$user->$uid_field = $_COOKIE[$login_cookie_name][$uid_index];
				
				if ( !$user->record_exists() ) {
					throw new LoginUnknownUserException();
				}
				
				$given_digest = $_COOKIE[$login_cookie_name][$digest_index];
				
				if ( Config::Get('auth.enable_encrypted_passwords') ) {
					$pw_compare = $user->password_encrypted;
				}
				else {
					$pw_compare = call_user_func(array(LL::Class_name_from_location_reference(Config::Get_required('auth.user_class')), 'Generate_password_digest'), $user->password, $user->password_salt);
				}
				
				if ( $user->id && $given_digest && ($pw_compare == $given_digest) ) {
					$this->validation_method = self::VALIDATION_METHOD_COOKIE;
					return true;
				}
			}
			
			$this->cleanup_invalid_login();
			throw new LoginInvalidException( __METHOD__ );
			
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function set_login_cookie() {

		try {
			
			$user   = $this->get_user_object();
			$digest = $user->get_password_digest();
			
			if ( !$user || !$digest ) {
				throw new Exception( __CLASS__ . '-missing_data_for_login_cookie' );
			} 
		
			$expiration = self::Determine_login_cookie_expiration_time(array('explicit_interval' => $this->cookie_expiration));

			$cookie_name   = Config::Get_required('auth.login_cookie_name');
			$uid_index     = Config::Get_required('auth.login_cookie_index_user_id');
			$digest_index  = Config::Get_required('auth.login_cookie_index_password_digest');
			$uid_field	   = Config::Get_required('auth.session_uid_field');
		
			setcookie("{$cookie_name}[{$uid_index}]", $user->$uid_field, $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));
			setcookie("{$cookie_name}[{$digest_index}]", $digest, $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));

			$this->refresh_cookie_update_time();			
		}
		catch( Exception $e ) {
			throw $e;
		}
			

	}


	public function refresh_cookie_update_time() {

		try { 
		
			$user   = $this->get_user_object();
			
			$expiration = self::Determine_login_cookie_expiration_time(array('explicit_interval' => $this->cookie_expiration));
			$cookie_name   = Config::Get_required('auth.login_cookie_name');
			$update_index  = Config::Get_required('auth.login_cookie_index_update_time');
			
			setcookie("{$cookie_name}[{$update_index}]", time(), $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public static function Determine_login_cookie_expiration_time( $options = array() ) {

		if ( isset($options['explicit_interval']) && $options['explicit_interval'] !== null ) {
			$interval = $options['explicit_interval'];
		}
		else {
			$interval = Config::Get_required('auth.login_cookie_expiration');
		}

		if ( $interval == AuthConstants::KEY_COOKIE_EXPIRATION_SESSION ) {
			$expiration = 0;
		}
		else {
			$expiration = time() + $interval;
		}
		
		return $expiration;

	}

	public function complete_valid_login() {
		
		//$this->_Promote_user_object_to_authenticated();
		$this->_Login_authenticated = true;
		

		
	}

	public function cleanup_invalid_login() {
		
		try {

			LL::Require_class('Auth/AuthSessionManager');
			
			//
			// Do NOT use Get_active_session() here, otherwise
			// auto validated sessions will get stuck in infinite loop!
			//

			if ( AuthSessionManager::$Active_session ) {
			
				AuthSessionManager::$Active_session->end();
			}
			
			if ( $this->_User_object ) {			
				$this->_User_object->reset_record_data();
			}
			
			//$this->_User_object_authenticated = null;
			
		}
		catch( Exception $e ) {
			$this->_User_object->reset_record_data();
			throw $e;
		}
	}

	public function timeout() {
	
		try { 
			
			$this->cleanup_invalid_login();
			$this->login_timeout_redirect();
		}
		catch ( Exception $e ) {
			throw $e;
		}
		
		
	}

	public function unset_login_cookie() {
		
		try { 

			LL::Require_class('Auth/AuthSessionManager');
			
			$session = AuthSessionManager::Get_active_session();
			$session->unset_login_cookie();
			
		}
		catch( Exception $e ) { 
			throw $e;
		}
		
		
	}

	public function login_timeout_redirect() {

		try { 
			LL::Require_class('URI/QueryString');
	
			if ( Config::Get('auth.login_timeout_uri') ) {
				$timeout_link = Config::Get('auth.login_timeout_uri');
			}
			else {
				$timeout_link = Config::Get_required('auth.login_page_uri'); 
			}
	
			
			if ( $timeout_link && (!isset($_GET[Config::Get_required('auth.login_timeout_qs_key')]) || $_GET[Config::Get_required('auth.login_timeout_qs_key')] == 0) ) {
	
				$timeout_link .= QueryString::Get_leader($timeout_link);
				$timeout_link .= Config::Get_required('auth.login_timeout_qs_key') . '=1';
			
				if ( !headers_sent() ) {
					header("Location: {$timeout_link}" );
				}
				else {
					echo "<script language=\"Javascript\" type=\"text/javascript\">location.replace(\"{$timeout_link}\")";
				}
			}
			else {
				trigger_error( 'Auth-login_timeout', E_USER_ERROR );
				exit;
			}
	
			exit(0);				
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function process_from_post( $options = null ) {

		try { 
			
			$key_redirect = Config::Get_required('auth.key_redirect_uri');
			
			if ( isset($_POST[$key_redirect]) && $_POST[$key_redirect] ) {
				$options[AuthConstants::KEY_EXPLICIT_REDIRECT] = urldecode($_POST[$key_redirect]);
			}
		
			$this->require_validation_method( self::VALIDATION_METHOD_POST );
		
			return $this->process( $options );
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function process( $options = null ) {

		try { 
			if ( !isset($options['force_success']) || !$options['force_success'] ) {
				$this->validate();
			} 
				
			return $this->login_success_process( $options );
			
        }
        catch( Exception $e ) {
        	throw $e;
        }

	}

	public function login_success_process( $options ) {
		
		try {
			
			//if ( $this->is_validation_method_available(self::VALIDATION_METHOD_COOKIE) ) {
			$this->set_login_cookie();
			
			LL::Call_hook('Auth', 'login_after_success', $this->get_user_object());
			
			$this->login_success_redirect( $options );
			

			
			return true;
			
		}
        catch( Exception $e ) {
        	throw $e;
        }

	}
	
	public function login_success_redirect( $options ) {
		
		try {
									
			if ( !$this->redirect_disable ) {
		
				LL::Require_class('URI/URIReflector');

				if ( $redirect_page = $this->login_get_applied_redirect_page($options) ) {
					if ( $this->redirect_transparent || Config::Get('auth.login_redirect_transparent') ) {
				 		$options[AuthConstants::KEY_REDIRECT_TRANSPARENT] = true;
				 	} 
				 	else {
						$options[AuthConstants::KEY_REDIRECT_TRANSPARENT] = false;
				 	}

					$options[AuthConstants::KEY_REDIRECT_CONTROLLER_METHOD] = $this->redirect_controller_method;

					$this->redirect_to( $redirect_page, $options );
				}
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function redirect_to( $redirect_page, $options = null) {

		try {
			LL::Require_class('Auth/AuthRedirector');
			
			$redirector = new AuthRedirector();
			$redirector->set_calling_controller($this->_Calling_controller);

			return $redirector->redirect_to( $redirect_page, $options );
			  
		}
        catch( Exception $e ) {
        	throw $e;
        }
		
		
	}

	function login_get_applied_redirect_page( $options = null ) {

		try { 
			$redirect_page = null;
			$key_redirect  = Config::Get_required('auth.key_redirect_uri');
		
			if ( !$this->redirect_disable ) {

				if ( !$this->redirect_ignore_explicit ) {
					if ( !($redirect_page = $this->redirect_page) ) {
						if ( array_val_is_nonzero($options, AuthConstants::KEY_EXPLICIT_REDIRECT) ) {
							$redirect_page = $options[AuthConstants::KEY_EXPLICIT_REDIRECT];
						}
						else {
							if ( $this->redirect_from_post ) {
								if ( array_val_is_nonzero($_POST, Config::Get_required('auth.key_redirect_uri')) ) {
									$redirect_page = urldecode($_POST[Config::Get_required('auth.key_redirect_uri')]);
								}
							}
							
							if ( !$redirect_page && $this->redirect_from_get ) {
								if ( array_val_is_nonzero($_GET, Config::Get_required('auth.key_redirect_uri')) ) {
									$redirect_page = urldecode($_GET[Config::Get_required('auth.key_redirect_uri')]);
								}
							}
						}
					}
				}

				if ( !$this->explicit_redirect_only ) {
					if ( !$redirect_page ) {
						if ( !($redirect_page = Config::Get('auth.login_redirect_page_default')) ) {
							
								LL::require_class('Settings/SettingsManager');

						        if ( $setting = SettingsManager::Get_setting(self::SETTING_TYPE_KEY_LOGIN_PAGE_DEFAULT) ) {
			        	        		$redirect_page = $setting;
				                }
						}

	        		}		
				}
			}
			
			return $redirect_page;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		

	}

	
}

class LoginInvalidException extends UserLoginRequiredException {
	
	
}

class LoginMissingDataException extends LoginInvalidException {
	
	
}

class LoginTimedOutException extends LoginInvalidException {
	
	
}

class LoginUnknownUserException extends LoginInvalidException {
	
	
}

?>