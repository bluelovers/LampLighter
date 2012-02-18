<?php

class URIReflector {

	public static function Get_active_uri( $options = null ) {
		
		if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) !== 'off' ) {
			$scheme = 'https';
		}
		else {
			$scheme = 'http';
		}
			
		$uri = $scheme . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		
		if ( isset($options['strip_query_string']) && $options['strip_query_string'] ) {
			if ( ($qs_pos = strpos($uri, '?')) !== false ) {
				$uri = substr($uri, 0, $qs_pos);
			}			
		}
		
		return $uri;
	}

	public static function Scheme_is_http( $uri, $options = null ) {
		
		$uri = strtolower($uri);
		
		if ( substr($uri, 0, 7) == 'http://' ) {
			return true;
		}
		
		return 0;
		
	}

	public static function Scheme_is_https( $uri, $options = null ) {
		
		$uri = strtolower($uri);
		
		if ( substr($uri, 0, 8) == 'https://' ) {
			return true;
		}
		
		return 0;
		
	}

	public static function Scheme_is_http_or_https( $uri, $options = null ) {
		
		if ( self::Scheme_is_http($uri, $options) || self::Scheme_is_https($uri, $options) ) {
			return true;
		}
		

		return 0;
		
	}

}
?>