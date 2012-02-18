<?php

class EncryptionManager {

	var $destroy_key = 0;
	var $do_hash_key = 1;

	var $_Algorithm;
	protected $_Key;
	var $_Mode;
	var $_Rand_source;

	var $_Mcrypt_directory = '';
	var $_Mcrypt_mode_directory = '';

	var $_Hashed_key;
	var $_Max_key_length = 32;	

	var $_Trim_decrypted = 1;

	public function __construct() {

		$this->destroy_key = ( Config::Is_set('encryption.destroy_key') ) ? Config::Get('encryption.destroy_key') : $this->destroy_key;
		$this->do_hash_key = ( Config::Is_set('encryption.do_hash_key') ) ? Config::Get('encryption.do_hash_key') : $this->do_hash_key;

		$this->_Rand_source = MCRYPT_DEV_RANDOM;
		$this->_Rand_source = ( Config::Is_set('encryption.rand_source') ) ? Config::Get('encryption.rand_source') : $this->_Rand_source;

		$this->_Mcrypt_directory      = ( Config::Is_set('encryption.mcrypt_directory') ) ? Config::Get('encryption.mcrypt_directory') : $this->_Mcrypt_directory;
		$this->_Mcrypt_mode_directory = ( Config::Is_set('encryption.mcrypt_mode_directory') ) ? Config::Get('encryption.mcrypt_mode_directory') : $this->_Mcrypt_directory;
		$this->_Trim_decrypted	      = ( Config::Is_set('encryption.trim_decrypted') ) ? Config::Get('encryption.trim_decrypted') : $this->_Trim_decrypted;

		$this->set_algorithm( $this->get_default_algorithm() );
		$this->set_mode( $this->get_default_mode() );


	}

	function td_key_too_long( &$td, $key = '' ) {

		if ( !$key ) {
			$key = $this->_Key;
		}

	   	$key_size = mcrypt_enc_get_key_size($td);
		$key = substr(md5($this->_Key), 0, $key_size);

		if ( strlen($key) > $key_size ) {
			return true;
		}

		return false;
	}


	function get_default_algorithm() {

		$algorithm = '';

		if ( Config::Is_set('encryption.default_algorithm') ) {
			  $algorithm = Config::Get('encryption.default_algorithm');
		}
		else {

			if ( defined('MCRYPT_RIJNDAEL_256') ) {
				$algorithm = MCRYPT_RIJNDAEL_256;
			}
			else {
				$algorithm = MCRYPT_BLOWFISH;
			}
		}

		return $algorithm;

	}

	function get_default_mode() {

		try { 
			
			$mode = false;
	
			if ( Config::Is_set('encryption.default_mode') ) {
				  $mode = Config::Get('encryption.default_mode');
			}
			else {
				if ( !($mode = constant('MCRYPT_MODE_ECB')) ) {
					throw new MissingParameterException('mode');
				}
			}
	
			return $mode;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	

	function set_mode( $which_mode ) {

		try { 
			if ( !$which_mode ) {
				throw new MissingParameterException('which_mode');
			}
			else {
				$this->_Mode = $which_mode;
			}
	
			return $this->_Mode;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function set_rand_source( $which_source ) {

		try { 

			if ( !$which_source ) {
				throw new MissingParameterException('rand_source');
			}
			else {
				$this->_Rand_source = $which_source;
			}
	
			return $this->_Rand_source;

		}
		catch( Exception $e ) {
			throw $e;
		}
	
	}	

	function get_rand_source() {

		return $this->_Rand_source;

	}

	function set_algorithm( $which_algorithm ) {

		try { 
	
			if ( !$which_algorithm ) {
				throw new InvalidParameterException('invalid_algorithm' );
			}
			else {
				$this->_Algorithm = $which_algorithm;
			}
	
			return $this->_Algorithm;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	

	function set_key( $which_key ) {

		try { 
	
			if ( !$which_key ) {
				throw new MissingParameterException('key');
			}
			else {
				$this->_Key = $which_key;
			}
	
			return $this->_Key;
		}
		catch( Exception $e ) {
			throw $e;
		}
	
	}

	function &initialize_mcrypt() {
	
		try { 
			
	   		if ( !($td = mcrypt_module_open($this->_Algorithm, $this->_Mcrypt_directory, $this->_Mode, $this->_Mcrypt_mode_directory)) ) {
				throw new Exception ("couldnt_open_module %{$this->_Algorithm}% %{$this->_Mcrypt_directory}%" );
			}
	
		
			//-----------------------------------------------
			// Determine what random source to use for MCrypt
			//-----------------------------------------------
	
			if ( preg_match('/^WIN/i', constant('APPLICATION_OS')) ) {
	
				// Windows can only use MCRYPT_RAND;
				$random_source = MCRYPT_RAND;
			}
			else {
				$random_source = $this->get_rand_source();
			}
			
			if ( !isset($random_source) ) {
				throw new Exception ('no_rand_source' );
			}
	
	
			if ( $random_source == MCRYPT_RAND ) {
				srand(); //Seed the random number generator
			}		
	
			if ( $this->_Mode != MCRYPT_MODE_ECB ) { 
			   	if ( !($iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), $random_source)) ) {
					throw new Exception ('couldnt_create_iv' );
					return false;
				}
			}
			else {
	
				//
				// create_iv hangs on some linux systems, 
				// and since the iv is ignored in ECB mode, 
				// don't bother if _Mode is ECB.
				//
	
				$iv = str_pad( '', 32, '0', STR_PAD_LEFT);	
			}
	
	
			if ( !($key = $this->_Key) ) {
				throw new MissingParameterException('key');
			}
	
			if ( $this->do_hash_key ) {
				if ( !($key = $this->generate_hashed_key($td)) ) {
					throw new Exception('couldnt_hash_key' );
				}
			}
			else {
				if ( $this->td_key_too_long($td) ) {
					throw new Exception('key_too_long' );
				}
			}
	
			if ( mcrypt_generic_init($td, $key, $iv) < 0 ) {
				throw new Exception('couldnt_init_mcrypt');
			}
	
			return $td;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function generate_hashed_key( &$td ) {

	   	$key_size = mcrypt_enc_get_key_size($td);
		$key = substr(md5($this->_Key), 0, $key_size);

		$this->_Hashed_key = $key;

		return $key;

	}

	function encrypt( $data ) {
	
		try { 
	
			$td = $this->initialize_mcrypt();
	
			if ( !($encrypted = mcrypt_generic($td, $data)) ) {
				throw new Exception('mcrypt_failed' );
			}
	
			$this->finish_mcrypt($td);
	
			return $encrypted;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	

	function decrypt( $data, $trim = '' ) {

		try { 

			$td = $this->initialize_mcrypt();
	
			if ( !is_numeric($trim) ) {
				$trim = $this->_Trim_decrypted;
			}
	
			if ( !($decrypted = mdecrypt_generic($td, $data)) ) {
				throw new Exception('decrypt_failed');
			}
	
			$this->finish_mcrypt($td);
	
			if ( $trim ) {
				$decrypted = trim($decrypted);
			}
	
			return $decrypted;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	function finish_mcrypt( &$td ) {

		try { 
			
			if ( $this->destroy_key ) {
				$this->_Key = null;
				$this->_Hashed_Key = null;
			}		
	
			if ( !(mcrypt_generic_deinit($td)) ) {
				throw new Exception('deinit_failed' );
			}
	
		   	mcrypt_module_close($td);
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
}


