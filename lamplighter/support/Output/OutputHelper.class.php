<?php

LL::require_class('HTML/TemplateHelper');

class OutputHelper extends TemplateHelper {

	public static function Substr_echo( $value, $start, $len = null, $options = null ) {
		
		try { 
		    
		    
			$ret = substr($value, $start, $len);
			
			if ( array_val_is_nonzero($options, 'more_link') ) {
		    	if ( strlen($value) > strlen($ret) ) {
		    		$ret .= $options['more_link'];
		    	}
		    }
			
			if ( isset($options['return_output']) && $options['return_output'] == true ) {
				return $ret;
			}
			
			echo $ret;
		}
		catch( Exception $e ) {
			echo LL::get_errors();
		}
		
		
	}

	public static function Nl2br_echo( $value ) {
		
		try { 
		    
		    echo nl2br($value);
			
		}
		catch( Exception $e ) {
			echo LL::get_errors();
		}
		
		
	}

}
	
?>