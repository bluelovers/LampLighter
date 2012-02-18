<?php

class DateHelper {

    static function basic_date_format_to_php_date_format( $format ) {
		
		$format = strtolower($format);
			
		$format = preg_replace('/(?<!\\\)m{2}/', 'X', $format);
		$format = preg_replace('/(?<!\\\)m{1,1}/', 'n', $format);
		$format = str_replace( 'X', 'm', $format );

		$format = preg_replace('/(?<!\\\)d{2}/', 'X', $format);	
		$format = preg_replace('/(?<!\\\)d{1,1}/', 'j', $format);
		$format = str_replace( 'X', 'd', $format );
			
		$format = preg_replace('/(?<!\\\)y{4}/', 'Y', $format);
		$format = preg_replace('/(?<!\\\)y{2}/', 'y', $format);
		
		return $format;
		
	}
	
}
?>