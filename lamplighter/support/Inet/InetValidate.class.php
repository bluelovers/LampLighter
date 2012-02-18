<?php

class InetValidate {

	const KEY_REQUIRE_DNS_LOOKUP = 'require_dns_lookup';
	
	const DOMAIN_NAME_MAX_LENGTH = 255;

	public static function Domain_name_is_sane( $domain_name ) {
		
		try {
			
			if ( !$domain_name ) {
				return 0;
			}
			
			if ( strlen($domain_name) > self::DOMAIN_NAME_MAX_LENGTH ) {
				return 0;
			}
			
			if ( preg_match('/^[A-Za-z0-9]([A-Za-z0-9\-]+)?\.([A-Za-z0-9\-\.]+)?[A-Za-z0-9]$/', $domain_name) ) {
				return true;
			}

			return 0;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
    
    public static function Get_valid_email_address_regexp() {
    	
    	return '^[^<>\(\)\[\]\,;:\s@\"]+@[^<>\(\)\[\]\,;:\s@\"]+(\.[^<>\(\)\[\]\\.,;:\s@\"])+';
    	
    }
    
    public static function Email_address_is_sane( $email, $options = array() ) {
    	
 		return preg_match( '/' . self::Get_valid_email_address_regexp() . '/', $email);   	
    }
}

?>