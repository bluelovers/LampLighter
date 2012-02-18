<?php

function has_value ($var) {

        if ( "$var" != '' ) {
                return true;
        }
        else {
                return false;
        }
         
         
}       


function studly_caps_to_underscore( $value, $options = null ) {

	return camel_case_to_underscore( $value, $options );

}

function camel_case_to_underscore( $value, $options = null ) {

	if ( is_array($value) ) {
		
		$count = 0;
		if ( count($value) ) {

			foreach( $value as $cur_key => $cur_value ) {
				if ( !is_numeric($cur_key) ) {
					$original_key = $cur_key;
					$cur_key      = camel_case_to_underscore($cur_key);
					
					if ( array_val_is_nonzero($options, 'convert_hash_values') ) {
						$value[$cur_key] = camel_case_to_underscore($cur_value, $options);
					}
					else {
						$value[$cur_key] = $value[$original_key];
					}
	
					if ( $original_key != $cur_key ) {
						unset($value[$original_key]);
					}
					
					if ( is_array($value[$cur_key]) ) {
						$value[$cur_key] = camel_case_to_underscore( $value[$cur_key], $options  );
					}

				}
				else {
					if ( isset($value[$count]) ) {
						$value[$count] = camel_case_to_underscore($value[$count], $options);
						$count++;
					}
				}
			}
		}
	}
	else {
	
		if ( preg_match('/^([A-Z]+)[a-z]/', $value, $matches) ) {
			//
			// Assume that if our value starts with a long string of caps,  
			// the 2nd to last capital is actually a new word, so split it there
			// (e.g. DNSRecord => dns_record )
			//
			$caps_part   = $matches[1];
			$first_word  = strtolower(substr($caps_part, 0, strlen($caps_part) - 1));
			$second_word = substr($value, strlen($caps_part) - 1 ); 
			
			$value = $first_word . $second_word;
		}

		$value = preg_replace('/(.+)([A-Z])(?=[a-z])/', "\\1_\\2", $value);
		$value = preg_replace('/(?<=[a-z])([A-Z])/', "_\\1", $value);

		$skip_digits = false;

		if ( array_val_is_nonzero($options, 'keep_number_positioning') ) {
			$skip_digits = true;
		}
		else {
			if ( defined('STUDLY_CAPS_TO_UNDERSCORE_IGNORE_DIGITS') && (constant('STUDLY_CAPS_TO_UNDERSCORE_IGNORE_DIGITS') == 0)) {
				$skip_digits = true;
			}

		}

		if ( !$skip_digits ) {
			$value = preg_replace('/([^0-9_])([0-9]+)/', "\\1_\\2", $value);
			$value = preg_replace('/([0-9]+)([^0-9_])/', "\\1_\\2", $value);
		}

		if ( !array_val_is_nonzero($options, 'allow_consecutive_underscores') ) {
			$value = preg_replace('/_{2,}/', '_', $value);
		}

		$value = strtolower($value);
		//$value = substr($value, 1);
	}

	return $value;

}

function is_valid_key( $key ) {

	return is_valid_index_key( $key );
}

function is_valid_index_key( $key ) {

        if ( !$key || preg_match('/[^A-Za-z0-9_\-]/', $key) ) {
                return false;
        }
                 
        return true;

}

function underscore_to_studly_caps( $value, $id_is_caps = 1, $unset_original = 1, $convert_hash_values = 0 ) {

	return underscore_to_camel_case($value, $id_is_caps, $unset_original, $convert_hash_values );
}

function underscore_to_camel_case( $value, $id_is_caps = 1, $unset_original = 1, $convert_hash_values = 0 ) {

        if ( is_array($value) AND count($value) ) {
                foreach( $value as $cur_key => $cur_value ) {
                        if ( !is_numeric($cur_key) ) {
                                $original_key = $cur_key;
                                $cur_key      = underscore_to_camel_case($cur_key);
                                if ( $convert_hash_values ) {
                                        $value[$cur_key] = underscore_to_camel_case($cur_value, $id_is_caps, $unset_original, $convert_hash_values );
                                }
                                else {
                                        $value[$cur_key] = $value[$original_key];
                                }

                                if ( $unset_original ) {
                                        if ( $original_key != $cur_key ) {
                                                unset($value[$original_key]);
                                        }
                                }
                        }
                }
        }
        else {

                //$value = str_replace( 'id', 'ID', $value );
                if ( $id_is_caps ) {
					$value = preg_replace('/_id($|_)/', "_ID\\1", $value);
                }

                preg_match_all('/_([a-zA-Z]){1}/', $value, $matches);

                for ( $j=0; $j < count($matches[0]); $j++ ) {

                        $entire_match  = $matches[0][$j];
                        $letter_match  = $matches[1][$j];

                        $value  = str_replace( $entire_match, strtoupper($letter_match), $value );
                }

        }

        return $value;
}

function str_to_array( $string ) {

	$str_array = array();
                        
        for ( $j=0; $j<strlen($string); $j++ ) {
        	$str_array[] = substr($string, $j, 1);
        }

        return $str_array;

}

function singularize( $str ) {
	
	return depluralize($str);
	
}

function depluralize( $str ) {

	if ( strtolower(substr($str, -3)) == 'ies' ) {
		$str = substr($str, 0, -3);
		$str .= 'y';
	}
	else {
		if ( strtolower(substr($str, -3)) == 'zes' ) {
			$str = substr($str, 0, -3);
		}
		if ( strtolower(substr($str, -4)) == 'ches' ) {
			$str = substr($str, 0, -2);
		}
		else {
		
			if ( strtolower(substr($str, -4)) == 'sses' ) {
				$str = substr($str, 0, -2);
			}
			else if ( strtolower(substr($str, -1)) == 's' ) {
				if ( strtolower(substr($str, -2) != 'ss') ) {
					$str = substr($str, 0, -1);
				}
			}
		}
	}

	return $str;

}

function pluralize( $str ) {

	$uppercase = false;
	$ucfirst   = false;
	$first_ltr = substr($str, 0, 1);

	if ( strtoupper($str) == $str ) {
		$uppercase = true;
	}

	if ( strtoupper($first_ltr) == $first_ltr ) {
		$ucfirst = true;
	}
	

	if ( substr(strtolower($str), -6) == 'person' ) {
	
		$str = substr_replace($str, 'people', -6);
	}
	else {
		if ( strtolower(substr($str, -1)) == 'y' ) {
			$str = substr($str, 0, -1);
			$str .= 'ies';
		}
		else {

			if ( substr(strtolower($str), -1) == 'z' ) {
				$str .= 'zes';
			}
			else if ( substr(strtolower($str), -2) == 'ch' ) {
				$str .= 'es';
			}
			else {

				if ( substr(strtolower($str), -1) != 's') {
					$str .= 's';
				}
				else if ( substr(strtolower($str), -2) == 'ss' ) {
					$str .= 'es';
				}
			}	
			
			/*
			 * 
			 if ( substr($str, -2) != 'es' ) {
				if ( substr($str, -1) == 's') {
					$str .= 'es';
				}
				else {
					$str .= 's';
				}
			}
			*/
		}
	}
	
	if ( $uppercase ) {
		$str = strtoupper($str);
	}
	else if ( $ucfirst ) {
		$str = ucfirst($str);
	}

	return $str;

}

function array_val_is_nonzero( &$arr, $key ) {
	
	if ( is_array($arr) && isset($arr[$key]) && $arr[$key] ) {
		return true;
	}
	
	return 0;
}
	

?>
