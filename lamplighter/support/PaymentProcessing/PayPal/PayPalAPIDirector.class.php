<?php

class PayPalAPIDirector {
	
	const ACK_SUCCESS    		   = 'Success';
	const ACK_SUCCESS_WITH_WARNING = 'SuccessWithWarning';
	
	public $paypal_sdk_root_path;
	public $cert_file_path;
	public $cert_password;
	
	public $api_username;
	public $api_password;
	public $api_signature;
	public $environment = 'Sandbox';
	public $path_slash = '/';
	
	protected $_Profile;
	protected $_Profile_handler;
	protected $_Caller;
	
	public function __construct() {
		
		$this->paypal_sdk_root_path = ( defined('PAYPAL_SDK_ROOT_PATH') ) ? constant('PAYPAL_SDK_ROOT_PATH') : $this->paypal_sdk_root_path;
		
		
		$this->cert_file_path	    = ( defined('PAYPAL_API_CERT_FILE_PATH') ) ? constant('PAYPAL_API_CERT_FILE_PATH') : $this->cert_file_path;
		$this->api_signature	    = ( defined('PAYPAL_API_SIGNATURE') ) ? constant('PAYPAL_API_SIGNATURE') : $this->api_signature;
		
		$this->api_username 		= ( defined('PAYPAL_API_USERNAME') ) ? constant('PAYPAL_API_USERNAME') : $this->api_username;
		$this->api_password 		= ( defined('PAYPAL_API_PASSWORD') ) ? constant('PAYPAL_API_PASSWORD') : $this->api_password;
		
		$this->environment			= ( defined('PAYPAL_API_ENVIRONMENT') ) ? constant('PAYPAL_API_ENVIRONMENT') : $this->environment;
		
		 if ( strpos(strtolower(php_uname()), 'windows') !== false ) {
		 	$this->path_slash = '\\';
		 }
		 
		 $this->require_sdk_base();
	}	
	
	public function require_request_base() {
		
		try { 
			return $this->require_sdk_base();
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
		
	public function require_sdk_base() {
		
		if ( !$this->paypal_sdk_root_path ) {
			trigger_error( 'No SDK root path specified', E_USER_ERROR);
		}
		
		if ( !is_dir($this->paypal_sdk_root_path) ) {
			trigger_error( 'SDK root path does not exist: ' . $this->paypal_sdk_root_path);
		}
		
		require_once( $this->paypal_sdk_root_path . $this->path_slash . 'PayPal.php' );
		require_once( $this->paypal_sdk_root_path . $this->path_slash . "PayPal{$this->path_slash}Profile{$this->path_slash}Handler{$this->path_slash}Array.php" );
		require_once( $this->paypal_sdk_root_path . $this->path_slash . "PayPal{$this->path_slash}Profile{$this->path_slash}API.php" );
		
	}
	
	public function get_profile_handler() {
		
		if ( !$this->_Profile_handler ) {
	
			$this->require_request_base();
	
			if ( !$this->api_username ) {
				throw new Exception( __CLASS__ . '-missing_api_username' );
			}

			if ( !$this->api_signature ) {
				if ( !$this->cert_file_path ) {
					throw new Exception( __CLASS__ . '-missing_cert_file' );
				}
			}

			$profile['username'] = $this->api_username;
			$profile['subject'] = null;
			$profile['environment'] = $this->environment;
			$profile['certificateFile'] = $this->cert_file_path;
			$profile['signature'] = $this->api_signature;
			
			$this->_Profile_handler = ProfileHandler_Array::getInstance($profile);
			
		}
		
		return $this->_Profile_handler;
	}
	
	function set_profile( $profile ) {
		
		$this->_Profile = $profile;
		
	}
	
	function get_profile() {
		
		if ( !$this->_Profile ) {
			
			try {
				$profile_handler = $this->get_profile_handler();
			}
			catch (Exception $e) {
				throw $e;
			}
	
			$this->_Profile =& APIProfile::getInstance($this->api_username, $profile_handler);

			if ( PEAR::isError($this->_Profile) ) {
				throw new Exception( 'PayPal Profile Error: ' . $this->_Profile->getMessage() );
			}

			$this->_Profile->setAPIUsername($this->api_username); 
			$this->_Profile->setAPIPassword($this->api_password);
			
			if ( $this->api_signature ) {
				$this->_Profile->setSignature($this->api_signature);
			}
			else {
				$this->_Profile->setCertificateFile($this->cert_file_path);
			}
			
			$this->_Profile->setEnvironment($this->environment);
		}
		
		return $this->_Profile;
		
	}
	
	function get_caller() {
		
		try { 
			if ( !$this->_Caller ) {
			
				$this->_Caller =& PayPal::getCallerServices($this->get_profile());

				if ( PayPal::isError($this->_Caller) ) {
					throw new Exception ( __CLASS__ . '-couldnt_create_caller', $this->_Caller->getMessage() );
				}
			
			}
		
			return $this->_Caller;
		}
		catch( Exception $e ) {
			throw $e;
		}
			
	}

}
?>
