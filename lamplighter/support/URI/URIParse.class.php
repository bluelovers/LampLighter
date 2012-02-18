<?php

class URIParse {

	public static function Format_http_link( $uri ) {
		
		return format_http_link( $uri );
		
	}

	public static function Strip_scheme( $uri ) {
		
		if ( strpos($uri, '://') !== false ) {
    		list( , $uri ) = explode('://', $uri, 2);
    	}
		
		return $uri;
	}

    public static function Extract_domain_name( $uri, $num_subdomains = 1, $options = null ) {
    	
    	$domain_name = '';
    	
    	if ( ($char_index = strpos($uri, '?')) !== false ) {
    		$uri = substr($uri, 0, $char_index);
    	}
    	
    	if ( ($char_index = strpos($uri, '#')) !== false ) {
    		$uri = substr($uri, 0, $char_index);
    	}
    	
    	if ( strpos($uri, '://') !== false ) {
    		list( $discard, $uri ) = explode('://', $uri, 2);
    	}

    	if ( strpos($uri, '/') !== false ) {
    		list( $uri, $discard ) = explode('/', $uri, 2);
    	}

    	
    	$uri = strrev($uri); //start from the end of the string
    						 
    	$parts = explode('.', $uri);
    	
    	if ( isset($options['enforce_subdomain_count']) && $options['enforce_subdomain_count'] ) {
    		if ( count($parts) < $num_subdomains ) {
    			return null;
    		}
    	}
    	
    	for( $j = 0; $j <= $num_subdomains; $j++ ) {
    	
    		if ( isset($parts[$j]) ) {

	    		if ( $domain_name ) {
    				$domain_name .= '.';
    			}

    			$domain_name .= $parts[$j];
    		}
    		else {
    			break;
    		}
    	}
    	
    	return strrev( $domain_name );
    	
    }
    
    public static function Strip_query_string( $uri, $options = null ) {
    	
		if ( ($qs_loc = strpos($uri, '?')) !== false ) {
			return substr($uri, 0, $qs_loc);
		}
    	
    	return $uri;
    }
    
	public static function Full_uri_to_relative_link( $uri, $options = null ) {

       	list( , $uri ) = explode('://', $uri, 2);
        list( , $uri ) = explode('/', $uri, 2);

        return '/' . $uri;

    	/*
		$protocol = null;
		
		preg_match('/^([A-Za-z0-9]+):\/\//', $uri, $matches);
		
		if ( isset($matches[1]) ) {
			$protocol = $matches[1];
		}
		
		$scheme_len    = strlen($matches[0]);
		$uri_no_prefix = substr($uri, $scheme_len);
		
		preg_match('/^[^\/]+/', $uri_no_prefix, $matches);
		
		$server_name = $matches[0];
		
		$server_string = "{$protocol}://{$server_name}";
		$server_strlen = strlen($server_string);

		if ( substr($uri, 0, $server_strlen) == $server_string ) {
			$uri = substr($uri, $server_strlen);
		}

		return $uri;		
		*/
	}
	
	public static function URI_friendly_string( $string, $options = array() ) {
		
		if ( array_key_exists('delimiter', $options) ) {
			$delimiter = $options['delimiter'];
		}
		else {
			$delimiter = '-';
		}
		
		$string = str_replace('\'', '', $string);
		$string = str_replace('.', '', $string);
		$string = preg_replace('/[^A-Za-z0-9_\-]/', $delimiter, $string);
		$string = preg_replace('/(-){2,}/', $delimiter, $string);
		$string = trim($string, "{$delimiter} ");
		
		if ( !isset($options['lowercase']) || $options['lowercase'] == true ) {
			$string = strtolower($string);
		}
		
		return $string;
	}

	public static function Create_hyperlinks_in_string( $string ) {
 		
 		$output_value = " {$string} ";

		preg_match_all("#(?<![\"/>])((https?)://|www\.)([=;~%&\?\/:\.A-Za-z0-9\-_]+)(\s)#si", $output_value, $hyperlink_matches);

		for ( $j = 0; $j < count($hyperlink_matches[0]); $j++ ) {
				
			$link_prefix = $hyperlink_matches[1][$j];
			$protocol = $hyperlink_matches[2][$j];				
			$link_name = $hyperlink_matches[3][$j];

			$orig_link_name = $link_name;

			$pre_character = '';
			$post_character = $hyperlink_matches[4][$j];

			if ( false === strstr($link_prefix, '://') ) {

				 $link_prefix = "http://{$link_prefix}";
				 $link_name = 'www.' . $link_name;
			}
			else {
				$link_name = $link_prefix . $link_name;
			}

			//in case a keyword makes it in...
			$link_url = strip_tags($link_url);
			$link_url = $link_prefix . $orig_link_name;

			$link_url = preg_replace('/\.(\/?)$/', '\\1', $link_url );
			
			$attr_string = null;
			
			if ( isset($options['attr']) && $options['attr'] ) {
				if ( is_scalar($options['attr']) ) {
					$attr_string = $options['attr'];
				}
				else {
					foreach( $options['attr'] as $name => $val ) {
						$attr_string .= " {$name}=\"{$val}\" ";
					}
				}
			}
			
			$output_value = str_replace( $hyperlink_matches[0][$j], "{$pre_character}<a {$attr_string} href=\"{$link_url}\">{$link_name}</a>{$post_character}", $output_value );

		}

		$output_value = trim($output_value);
		
 		return $output_value;
 	}
    
}
?>