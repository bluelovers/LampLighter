<?php

class JavascriptHelper {

	const KEY_LITERAL_STRINGS_ONLY = 'as_string';

    static function Literal( $val, $options = null ) {
    	
    	try {
    		
    		$js_val = null;
    		$quote  = null;
    		
    		if ( array_val_is_nonzero($options, self::KEY_LITERAL_STRINGS_ONLY) ) {
    			$quote = '\'';
    			if ( !$val && !is_numeric($val) ) {
    				$js_val = '';
    			}
    			else {
    				$js_val = $val;
    			}	
    		}
    		else {
	    		if ( $val === false ) {
    				$js_val = 'false';
    			}
	    		else if ( $val === true ) {
    				$js_val = 'true';
    			}
    			else if ( $val === null ) {
    				//$quote = '\'';
    				$js_val = 'null';
    			}
    			else {
	    			if ( !is_numeric($val) ) {
    					$quote = '\'';
    				}
    			
    				$js_val = $val;
    			}
    		}
    		
    		$js_val = str_replace('\'', '\\\'', $js_val);
    		
    		$final = "{$quote}{$js_val}{$quote}";
    		
    		if ( array_val_is_nonzero($options, 'return_output') ) { 
    			return $final;
    		}
    		else {
    			echo $final;
    		} 
    		
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    	
    }
}
?>