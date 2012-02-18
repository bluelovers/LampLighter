<?php

class TemplateGlobals {

	static $Params = array();
	
	static function add_param( $key, $val ) {
		
		self::$Params[$key] = $val;
		
	}
	
	static function get_param( $key ) {
		
		if ( isset(self::$Params[$key]) ) {
			return self::$Params[$key];
		}
		
		return null;
		
	}
}
?>