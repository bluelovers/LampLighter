<?php

LL::require_class('HTML/TemplateHelper');

class URIHelper extends TemplateHelper {

	public static function URI_Friendly( $string, $options = array() ) {
		
		LL::Require_class('URI/URIParse');
		
		$parsed = URIParse::URI_Friendly_string( $string, $options );
		
		if ( array_key_exists('return_output', $options) && $options['return_output']) {
			return $parsed;
		}
		
		echo $parsed;
		
	}

}

?>