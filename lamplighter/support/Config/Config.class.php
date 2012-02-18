<?php

class ConfigValueNotFoundException extends Exception {
	
	public function __construct( $key ) {

		$message = "Config-missing_required_value %{$key}%"; 

		parent::__construct( $message, null, constant('ERROR_LEVEL_GENERAL') );

	}
	
	
}

class Config {

    static $Config_vars = array();
    //static $Defaults_loaded = false;
    static $Overwrite = true;
    
    /*
    public static function Load_defaults() {
    	
    	//
    	// This should be moved elsewhere
    	//
    	
    	self::$Defaults_loaded = true;
    	
    }
    */
    
    public static function Set( $key, $val, $options = array() ) {
    	
    	if ( isset(self::$Config_vars[$key]) ) {
    		
    		$overwrite = self::$Overwrite;
    		
    		if ( isset($options['overwrite']) ) {
    			$overwrite = $options['overwrite'];
    		}
    		
    		if ( $overwrite ) {
    			self::$Config_vars[$key] = $val;
    		}
    	}
    	else {
    		self::$Config_vars[$key] = $val;
    	}
    	
    }
    
    public static function Get( $key, $options = array() ) {
    	try { 
    	
    		if ( array_key_exists($key, self::$Config_vars) ) {
    			return self::$Config_vars[$key];
    		}
	    	else {
    			
    			if ( isset($options['require_setting']) && $options['require_setting']) {
					throw new ConfigValueNotFoundException( $key );    			
    			}
    		}
    	
    		return null;
    	}
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }
    
    public static function Is_set( $key ) {
    	
    	if ( array_key_exists( $key, self::$Config_vars ) ) {
    		return true;
    	}
    	
    	return false;
    	
    }
    
    public static function Get_required( $key, $options = array() ) {
    	
    	try { 
    		$options['require_setting'] = true;
    		return self::Get($key, $options);
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    }
}
?>