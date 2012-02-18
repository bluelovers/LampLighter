<?php

class HTMLParse {

    public function Strip_tags_and_data ( $data, $options = array() ) {

		if ( array_key_exists('allowed_tags', $options) && $options['allowed_tags'] ) {
	    	
	    	$allowed_tags = $options['allowed_tags'];
	    	
	    	if ( is_scalar($allowed_tags) ) {
	    		$allowed_tags = array ( $allowed_tags );
	    	}
    	}
    	
    	
    	preg_match_all( '/<\s*(.*)>.*<\s*\/.*\s*>/smiU', $data, $matches);
    	
	   	if ( count($matches[0]) > 0 ) {
	   		for ( $j = 0; $j < count($matches[0]); $j++ ) {
	    			
	   			list ($tag,) = explode( ' ', $matches[1][$j]);
	   			$tag = trim($tag);
	    			
	   			if ( !is_array($allowed_tags) || !in_array($tag, $allowed_tags) ) {
	   				$data = preg_replace("/<\s*{$tag}.*>.*<\s*\/.*\s*>/smiU", '', $data);
	   			}
	   			
	   		}
	   	}    	
    	
		return $data;
    }
    
   public static function Redirect_links( $data, $where_to, $options = array() ) {
    	try {
	    	
	    	LL::Require_class('URI/QueryString');
	    	
	    	$append_original = false;
	    	$skip_with_onclick = true;
	    	$skip_local = true;
	    	$skip_javascript = true;
	    	$ignore_targets = array( '_new', '_blank' );
			$qs_key = 'location';
				    	
	    	if ( isset($options['append_original']) && $options['append_original'] ) {
				$append_original = true;    		
	    	}
	    	
	    	if ( isset($options['query_string_var']) && $options['query_string_var'] ) {
				$qs_key = $options['query_string_var'];
	    	}
	    	
	    	if ( array_key_exists('ignore_targets', $options) ) {
	    		$ignore_targets = $options['ignore_targets'];
	    		if ( is_scalar($ignore_targets) ) {
	    			$ignore_targets= array($ignore_targets);
	    		}
	    	}
	    	
	    	preg_match_all( '/<\s*a.*href=["\'](.*)["\'].*>/iU', $data, $matches );
	    		
			for ( $j = 0; $j < count($matches[0]); $j++ ) {
				
				$full_match = $matches[0][$j];
				$original_link = $matches[1][$j];
				$new_link = $where_to;
				
				if ( $skip_with_onclick ) {
					if ( stripos($full_match, 'onclick') !== false ) {
						continue;
					}
				}
				
				if ( $skip_local ) {
					if ( substr($original_link, 0, 1) == '#' ) {
						continue;
					}
				}
			
				if ( $skip_javascript ) {
					$test_link = strtolower($original_link);
					if ( substr($test_link, 0, 11) == 'javascript:' ) {
						continue;
					}
				}
			
				if ( $ignore_targets ) {
					foreach( $ignore_targets as $target ) {
						if ( preg_match("/target\s*=\s*[\"']{$target}[\"']/i", $full_match) ) {
							continue 2;
						}
					}	
				}
				
				
				if ( $append_original ) {
					$qs_sep = QueryString::Get_leader( $new_link );
					$new_link .= $qs_sep . $qs_key . '=' . base64_encode($original_link);
				}
	
				$replacement = str_replace( $original_link, $new_link, $full_match );
				$data = str_replace( $full_match,  $replacement, $data );
					
			}    	
			
			return $data;	
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    	
    }
    
}
?>