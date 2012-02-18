<?php

LL::Require_file('Auth/Auth.conf.php');
LL::require_class('Auth/AuthConstants');

//
class UserLogout {

	protected $_Calling_controller;

	public $redirect_disable = false;
	public $redirect_ignore_explicit = false;
	public $redirect_page;
	public $explicit_redirect_only = false;
	public $redirect_transparent = true;
	public $redirect_controller_method = 'logout_redirect';
	public $redirect_from_get = true;
	public $redirect_from_post = true;
	
	function process( $options = null ) {

		try { 

			LL::Require_class('Auth/AuthSessionManager');
			
			$session = AuthSessionManager::Get_active_session();
			$session->end();

			return $this->_Logout_success_process( $options );
		}
        catch( Exception $e ) {
        	throw $e;
        }

	}

	protected function _Logout_success_process( $options ) {
		
		try {
			
			if ( !$this->redirect_disable ) {
				if ( $redirect_page = $this->logout_get_applied_redirect_page($options) ) {
					if ( $this->redirect_transparent ) {
				 		$options[AuthConstants::KEY_REDIRECT_TRANSPARENT] = true;
				 	} 
				 	else {
						$options[AuthConstants::KEY_REDIRECT_TRANSPARENT] = false;
				 	}

					$options[AuthConstants::KEY_REDIRECT_CONTROLLER_METHOD] = $this->redirect_controller_method;

					$this->redirect_to( $redirect_page, $options );
				}
			}
			
			return true;
			
		}
        catch( Exception $e ) {
        	throw $e;
        }

	}

	public function set_calling_controller( $controller ) {
		
		$this->_Calling_controller = $controller;
		
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

	function logout_get_applied_redirect_page( $options = null ) {

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
									$redirect_page = $_POST[Config::Get_required('auth.key_redirect_uri')];
								}
							}
							
							if ( !$redirect_page && $this->redirect_from_get ) {
								if ( array_val_is_nonzero($_GET, Config::Get_required('auth.key_redirect_uri')) ) {
									$redirect_page = $_GET[Config::Get_required('auth.key_redirect_uri')];
								}
							}
						}
					}
				}

				if ( !$this->explicit_redirect_only ) {
					if ( !$redirect_page ) {
						if ( !($redirect_page = Config::Get('auth.logout_redirect_page_default')) ) {
							
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
?>