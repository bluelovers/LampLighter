<?php

class AuthSession {

	protected $_User_obj;
	protected $_Is_authenticated = false;

	public function get_user_object() {
		
		try {

			if ( !$this->_User_obj ) {
				
				LL::Require_class('Auth/AuthLoader');
				$this->_User_obj = AuthLoader::Load_user_object();
								
			}
			
			return $this->_User_obj;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function validate( $options = array() ) {
		
		return $this->authenticate( $options );
		
	}
	
	public function authenticate( $options = array() ) {
		
		try {
			
			
			$this->_Is_authenticated = false;
			
			
			LL::Require_class('Auth/UserLogin');
			
			$login = new UserLogin();
			$login->set_user_object( $this->get_user_object() );
			
			try { 
				if ( $login->validate( $options ) ) {
					$this->_Is_authenticated = true;
					return true;
				}
			}
			catch( LoginInvalidException $e ) {
				//Do nothing - simply leave session as unauthenticated
			}
			
			return 0;
			
		}
		catch( Exception $e ) {
			$this->_Is_authenticated = false;
			throw $e;
		}
		
	}
	
	public function end() {
		
		try {
			$this->_Is_authenticated = false;
			
			if ( !headers_sent() ) {
				$this->unset_login_cookie();
			}	
			
			$this->_User_obj = null;
			$this->_Is_authenticated = false;
			
		}
		catch( Exception $e ) {
			$this->_Is_authenticated = false;
			
		}
	}

	public function unset_login_cookie() {

		try {
			
			$expiration = time() - 3600;
			
			$cookie_name   = Config::Get_required('auth.login_cookie_name');
			$uid_index     = Config::Get_required('auth.login_cookie_index_user_id');
			$digest_index  = Config::Get_required('auth.login_cookie_index_password_digest');
			$update_index  = Config::Get_required('auth.login_cookie_index_update_time');
			
			setcookie("{$cookie_name}[{$uid_index}]", '', $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));
			setcookie("{$cookie_name}[{$digest_index}]", '', $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));
			setcookie("{$cookie_name}[{$update_index}]", '', $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));
			
			setcookie(Config::Get_required('auth.login_cookie_name'), '', $expiration, Config::Get_required('auth.login_cookie_path'), Config::Get_required('auth.login_cookie_domain'));
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}
			

	}
	
	public function is_authenticated() {
		
		try {
			
			return $this->_Is_authenticated;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

}
?>