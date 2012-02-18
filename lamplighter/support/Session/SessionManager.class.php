<?php

LL::require_class('Class/AbstractParent');

abstract class SessionManager extends AbstractParent {

	

	public static function Instantiate( $driver_name = null, $options = null ) {
		
		try {
			
			self::$Base_class_name = 'SessionManager';
			self::$Base_path = dirname(__FILE__);
			
			return parent::Instantiate($driver_name, $options );
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

}
?>