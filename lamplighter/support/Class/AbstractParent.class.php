<?php

abstract class AbstractParent {

	protected static $Class_extension = 'class.php';
	protected static $Class_name_delimiter = '_';
	
	protected static $Base_class_name;
	protected static $Base_path;
	protected static $Driver_name;
	
	protected static function Instantiate( $driver_name = null, $options = null )  {
    	
    	try {
    		
    		if ( !self::$Base_class_name ) {
    			throw new Exception( __CLASS__ . '-no_base_class_name' );
    		}
    
    		if ( !$driver_name ) {
    			if ( !($driver_name = self::$Driver_name) ) {
    				throw new Exception( __CLASS__ . '-no_driver_name_specified' );
    			}
    		}
    		
    		$full_class_name = self::$Base_class_name . self::$Class_name_delimiter . $driver_name;  
    		$filename =  $full_class_name . '.' . self::$Class_extension;
    		
    		if ( self::$Base_path ) {
    			
    			LL::require_class('File/FilePath');
    			
    			$filepath = FilePath::append_slash(self::$Base_path) . $filename;
    			
    		}
    		else {
    			$filepath = $filename;
    		}    		
    		
    		require_once($filepath);
    		
    		return new $full_class_name;
    		
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    
	}
    
}
?>