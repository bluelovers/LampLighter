<?php

class TimeHelper {

    static function basic_time_format_to_php_time_format( $format ) {
		
		$format 	 = strtolower($format);
		$use_12_hour = false;
		
		if ( strpos($format, 'a') !== false ) {
			$use_12_hour = true;
		}
			
		if ( $use_12_hour ) {
			$format = preg_replace('/(?<!\\\)h{2}/', 'X', $format);
			$format = preg_replace('/(?<!\\\)h{1,1}/', 'g', $format);
			$format = str_replace( 'X', 'h', $format );
		}
		else {
			$format = preg_replace('/(?<!\\\)h{2}/', 'X', $format);
			$format = preg_replace('/(?<!\\\)h{1,1}/', 'G', $format);
			$format = str_replace( 'X', 'H', $format );
		}

		$format = preg_replace('/(?<!\\\)m+/', 'i', $format);	
		$format = preg_replace('/(?<!\\\)s+/', 's', $format);	
		$format = preg_replace('/(?<!\\\)a+/', 'A', $format);	
		
		return $format;
		
	}
	
}
?>