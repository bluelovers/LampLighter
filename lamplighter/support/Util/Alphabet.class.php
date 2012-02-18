<?php

class Alphabet {

	const KEY_LETTER_KEY_DEFAULT = 'letter';

	public static function Get_letter_array( $options = null ) {
	
	    return array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                        'V', 'W', 'X', 'Y', 'Z' );
		
	}
	
	public static function Get_template_array( $options = null ) {
		
		$key_name = ( array_val_is_nonzero($options, 'key') ) ? $options['key'] : self::KEY_LETTER_KEY_DEFAULT;		
		$assoc 	  = array();
		
		foreach ( self::Get_letter_array() as $letter ) {
		
			$assoc[][$key_name] = $letter;
			
		}
		
		return $assoc;
	}

}
?>