<?php


if ( !defined('FORM_VALIDATOR') ) {

define('FORM_VALIDATOR', 1);
define('FORM_VALIDATOR_CLASS_NAME', 'InputFormValidator');

LL::Require_class('Form/InputForm');

class InputFormValidator extends InputForm {

	const KEY_NEGATE = 'negate';
	const KEY_DELIMITER = 'delimiter';
	const KEY_MESSAGE   = 'message';
	
        var $standard_char_class = 'A-Za-z0-9~!@#$%^&*()-_+=?><.,";:[]\' ';

        var $bad_char_message        = 'The character: %C is not valid for the field \'%I\'.';
        var $non_numeric_message     = 'The field \'%I\' must be a number.';
	var $regexp_mismatch_message = 'That is an invalid value for field \'%I\'';
	var $null_value_message      = 'You must enter a value for \'%I\'';
	var $invalid_value_heading   = 'There was a problem with your input:';
	var $input_max_len_message   = 'The field \'%I\' is too long. Please use fewer characters.';
	var $input_min_len_message   = 'The field \'%I\' must be at least %L characters long.';
	var $invalid_email_message   = 'The field \'%I\' must contain a valid email address.';
	var $needs_alnum_message     = 'The field \'%I\' must contain only letters or numbers.';
	var $needs_alnumspace_message     = 'The field \'%I\' must contain only letters, numbers, and spaces.';
	var $needs_match_message     = 'The fields for \'%I\' must match.';

	var $input_match_requirements; 
	var $require_regexp_match;
	var $require_numeric_input;
	var $require_standard_char;
	var $invalid_input;
	var $_Require_callback;

	var $regexp_mismatch_on_null  = 0;
	var $nonstandard_char_on_null = 0;
	var $non_numeric_on_null      = 0;

	var $individual_input_errors  = 0;
	var $error_on_preemptive_callback_result = true;

	var $key_name    = 'name';
	var $key_message = 'msg';
	var $key_regexp  = 'reg';
	var $key_negate  = 'neg';
	var $key_flags   = 'fl';
	var $key_max_len = 'mxl';
	var $key_min_len = 'mnl';
	var $key_exact_len = 'xtl';
	var $key_check_if_null = 'cn';
	var $key_error_level = 'el';

	var $key_validator_dep	      = 'deps';
	var $key_validator_deps	      = 'deps';
	var $key_validator_dep_type   = 'dt';
	var $key_validator_dep_negate = 'dn';

	var $check_input_failed = 0;
	var $length_restrictions;

	var $_Validation_hook_callbacks = NULL;

	var $_Key_validation_hook_expected_result = 'vher';
	var $_Key_validation_hook_object_ref      = 'vhor';

	function InputFormValidator( $form_name = '' ) {

		$this->init_InputForm($form_name);
		$this->init_InputFormValidator();		
		return true;

	}

	function init_InputFormValidator() {

		if ( !$this->quick_init ) {

			$this->standard_char_class     = ( defined('STANDARD_CHAR_CLASS') ) ? constant('STANDARD_CHAR_CLASS') : $this->standard_char_class;
			$this->bad_char_message        = ( defined('FORM_BAD_CHAR_MESSAGE') ) ? constant('FORM_BAD_CHAR_MESSAGE') : $this->bad_char_message;
			$this->non_numeric_message     = ( defined('FORM_NON_NUMERIC_MESSAGE') ) ? constant('FORM_NON_NUMERIC_MESSAGE') : $this->non_numeric_message;
			$this->regexp_mismatch_message = ( defined('FORM_REGEXP_MISMATCH_MESSAGE') ) ? constant('FORM_REGEXP_MISMATCH_MESSAGE') : $this->regexp_mismatch_message;

			$this->regexp_mismatch_on_null  = ( defined('FORM_REGEXP_MISMATCH_ON_NULL') ) ? constant('FORM_REGEXP_MISMATCH_ON_NULL') : $this->regexp_mismatch_on_null;
			$this->nonstandard_char_on_null = ( defined('FORM_NONSTANDARD_CHAR_ON_NULL') ) ? constant('FORM_NONSTANDARD_CHAR_ON_NULL') : $this->nonstandard_char_on_null;
			$this->non_numeric_on_null      = ( defined('FORM_NON_NUMERIC_ON_NULL') ) ? constant('FORM_NON_NUMERIC_ON_NULL') : $this->non_numeric_on_null;
			
			$this->invalid_value_heading    = ( defined('FORM_INVALID_VALUE_HEADING') ) ? constant('FORM_INVALID_VALUE_HEADING') : $this->invalid_value_heading;
			$this->individual_input_errors  = ( defined('FORM_INDIVIDUAL_INPUT_ERRORS') ) ? constant('FORM_INDIVIDUAL_INPUT_ERRORS') : $this->individual_input_errors;
		
		}

		$this->_Require_callback = array();
		$this->_Validation_hook_callbacks = array();

		$this->input_match_requirements = array();

		$this->length_restrictions = array();
		$this->check_input_failed = 0;
		$this->invalid_input = array();
	}


	function contains_nonstandard_char( $value, $multiline_ok = 1 ) {
                        
        	$standard_char_class = $this->standard_char_class;

	        $standard_char_class = preg_quote($standard_char_class, '#');

	        $flags   = ( $multiline_ok ) ? 'm' : '';
      		$line_br = ( $multiline_ok ) ? '\\n\\r' : '';   

        	if ( preg_match("#[^{$standard_char_class}{$line_br}\\t]#{$flags}", $value, $matches) ) {
                	return $matches[0];
	        }
        
        	return false;
 
	}

	function require_callback( $input_name, $function_name, $message, $error_level, $dep_name = null, $dep_type = null ) {
	
		$match_info[$this->key_name] = $input_name;
		$match_info[$this->key_negate] = false;
		$match_info['function_name'] = $function_name;
		$match_info[$this->key_message] = $message;
		$match_info[$this->key_error_level] = $error_level;
		$match_info[$this->key_validator_dep] = $this->get_validator_dep_array($dep_name, $dep_type);

		$this->_Require_callback[$input_name] = $match_info;
		

	}

	function require_not_callback( $input_name, $function_name, $message, $error_level, $dep_name = null, $dep_type = null ) {
	
		$match_info[$this->key_name] = $input_name;
		$match_info[$this->key_negate] = true;
		$match_info['function_name'] = $function_name;
		$match_info[$this->key_message] = $message;
		$match_info[$this->key_error_level] = $error_level;
		$match_info[$this->key_validator_dep] = $this->get_validator_dep_array($dep_name, $dep_type);

		$this->_Require_callback[$input_name] = $match_info;
		

	}

	function require_regexp_match( $input_name, $regexp, $dep_name = '', $dep_type = '', $message = '', $negate = 0, $delimiter = '/' ) {
		
		//
		// Strip the given delimiter, so we can always use a slash
		//
        $last_delim = strrchr( $regexp, $delimiter );
        $flags      = substr(strrchr($regexp, $delimiter), 1);
        
        $regexp     = substr($regexp, 0, strlen($regexp) - strlen($flags));
                                
        $delimiter  = preg_quote($delimiter, '/');
        $regexp     = preg_replace("/^{$delimiter}/", '', $regexp);
        $regexp     = preg_replace("/{$delimiter}$/", '', $regexp);		

		// preferred way of passing options, with the third parameter being a hash
		// other parameters are kept for backward compatibility only
		if ( is_array($dep_name ) ) { 
			
			$options = $dep_name;

			$negate    = isset($options[self::KEY_NEGATE]) ? $options[self::KEY_NEGATE] : 0;
			$delimiter = isset($options[self::KEY_DELIMITER]) ? $options[self::KEY_DELIMITER] : '/';
			$message   = isset($options[self::KEY_MESSAGE]) ? $options[self::KEY_MESSAGE] : null;				
		}

		$match_info = array();
		$match_info[$this->key_name]    = $input_name;
		$match_info[$this->key_regexp]  = $regexp;
		$match_info[$this->key_message] = $message;
		$match_info[$this->key_negate]  = $negate;
		$match_info[$this->key_flags]   = $flags;

		//$this->require_regexp_match[$input_name] = array( $regexp, array($message, $flags, $negate) );
		//$this->require_regexp_match[] = $match_info;
		$this->require_regexp_match[$input_name] = $match_info;
		
		return true;
		
	}

	function require_manual_date( $input_name, $format = null, $require_double_digits = false ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FORM_VALIDATOR_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		require_library('date');

		if ( !$format ) {
			$format = 'mm/dd/yyyy';
		}

		if ( !($date_regexp = get_date_format_regexp($format, $require_double_digits, '/')) ) {
			trigger_error( "Invalid date format string in {$my_location}" . __LINE__ );
		}
		else {
			$friendly_name = $this->get_friendly_name( $input_name );
			$this->require_regexp_match( $input_name, "/^{$date_regexp}$/", NULL, NULL, LL::Translate("InputFormValidator-invalid_date_format %{$friendly_name}% %{$format}%") );
	        }

	}

	function disallow_char( $input_name, $char, $dep_name = '', $dep_type ='', $message = '' ) {
		
		return $this->disallow_chars( $input_name, $char, $dep_name, $dep_type, $message );
	}

        function disallow_chars( $input_name, $char_list, $dep_name = '', $dep_type ='', $message = '' ) {  
                
		$char_list = preg_quote($char_list, '/');
		$char_list = preg_replace('/(.)/', "\\1|", $char_list, strlen($char_list) -1);

                $regex_list = '(' . $char_list . ')';
		
                                                                      
                return $this->require_regexp_match( $input_name, $regex_list, $dep_name, $dep_type, $message, 1 );
                
        }

	function require_char_class( $input_name, $char_class, $dep_name = '', $dep_type = '', $message = '') {

		$char_class = preg_replace('/^\[/', '', $char_class );
		$char_class = preg_replace('/\]$/', '', $char_class );
		
		$match_info = array();
		$match_info[$this->key_name]    = $input_name;
		$match_info[$this->key_regexp]  = "[^{$char_class}]";
		$match_info[$this->key_message] = $message;
		$match_info[$this->key_negate]  = 1;
		$match_info[$this->key_validator_dep] = $this->get_validator_dep_array($dep_name, $dep_type);

		//$this->require_regexp_match[$input_name] = array( $regexp, array($message, $flags, $negate) );
		$this->require_regexp_match[$input_name] = $match_info;
		
		return true;


	}

	function require_standard_chars( $input, $dep_name = '', $dep_type = '', $message = '' ) {
	
		return $this->require_standard_char($input, $dep_name, $dep_type, $message);	

	}

	function get_validator_dep_array( $dependency_name, $dependency_type ) {

		$dep_array = array();

		if ( $dependency_name ) {

			if ( is_array($dependency_name) ) {
				foreach ( $dependency_name as $cur_dep_name ) {
		
					
					list( $negate, $cur_dep_name ) = $this->parse_negated_input_name( $cur_dep_name, $dependency_type );
					$dep_array[$cur_dep_name] = array( $this->key_validator_dep_type => $dependency_type, 
								      $this->key_validator_dep_negate => $negate );
				}
			}
			else {
					list( $negate, $dependency_name ) = $this->parse_negated_input_name( $dependency_name, $dependency_type );
					$dep_array[$dependency_name] = array( $this->key_validator_dep_type => $dependency_type, 
								      $this->key_validator_dep_negate => $negate );
			}
		}

		return $dep_array;

	}

	function validator_dependencies_hold( $dep_array ) {

		if ( count($dep_array) > 0 ) {

			foreach( $dep_array as $dep_name => $dep_info ) {

				if ( !$dep_info[$this->key_validator_dep_negate] ) {
					if ( !$this->get_value($dep_name) ) {
						return false;
					}	
				}
				else {
					if ( $this->get_value($dep_name) ) {
						return false;
					}	
				}			
			}
		}

		return true;


	}

	function require_standard_char( $input_name, $dependency = '', $dependency_type = '', $message = '' ) {

		if ( is_array($input_name) ) {
			foreach ( $input_name as $cur_input_name ) {

				$this->require_standard_char[$cur_input_name] = array( $this->key_name => $cur_input_name, 
									$this->key_message         => $message, 
									$this->key_validator_dep   => $this->get_validator_dep_array($dependency, $dependency_type) );
			}
		}		
		else {
			$this->require_standard_char[$input_name] = array( $this->key_name    => $input_name, 
								$this->key_message => $message, 
								$this->key_validator_dep   => $this->get_validator_dep_array($dependency, $dependency_type) );

		}

		return true;
	

	}      



	function set_max_length( $input_name, $length, $dep_name = '', $dep_type = '', $message = '') {

		return $this->require_max_length( $input_name, $length, $dep_name, $dep_type, $message );

	}

	function require_max_length( $input_name, $length, $dep_name = NULL, $dep_type = NULL, $message = NULL) {

		if ( $length ) {

			/*
                        if ( !$message ) {
				$message = LL::Translate( "InputFormValidator-invalid_max_len %{$input_name}% %{$length}%" );
                        }
			*/

			$this->length_restrictions[] = array( $this->key_name => $input_name,
							      $this->key_message => $message,
							      $this->key_max_len => $length,
							      $this->key_validator_dep   => $this->get_validator_dep_array($dep_name, $dep_type) 
							);
		}

		/*
		else {
			if ( count($this->length_restrictions) ) {
				$count = 0;
				foreach( $this->length_restrictions as $cur_restr ) {
					$cur_name = $cur_restr[$this->key_name];
			
					if ( $cur_name == $input_name ) {
						$this->length_restrictions[$count] = '';
						break;
					}
				
					$count++;
				}
			}
		}
		*/
	

	}


	function set_min_length( $input_name, $length, $dep_name = '', $dep_type = '', $message = '') {

		return $this->require_min_length( $input_name, $length, $dep_name, $dep_type, $message );

	}

	function require_min_length(  $input_name, $length, $dep_name = NULL, $dep_type = NULL, $message = NULL ) {

		if ( $length ) {

			/*
                        if ( !$message ) {
				$message = LL::Translate( "InputFormValidator-invalid_min_len %{$input_name}% %{$length}%" );
                        }
			*/

			$this->length_restrictions[] = array( $this->key_name => $input_name,
							      $this->key_message => $message,
							      $this->key_min_len => $length,
							      $this->key_validator_dep   => $this->get_validator_dep_array($dep_name, $dep_type) 
							);
		}

	}

	function require_exact_length( $input_name, $length, $dep_name = NULL, $dep_type = NULL, $message = NULL ) {

                if ( $length ) {

						if ( is_array($dep_name) ) {
							//
							// New way of setting parameters - options hash as 3rd param
							//
							$options = $dep_name;
							$dep_name = ( isset($options[self::KEY_DEPENDENCY_NAME]) ) ? $options[self::KEY_DEPENDENCY_NAME] : null;
							$dep_type = ( isset($options[self::KEY_DEPENDENCY_TYPE]) ) ? $options[self::KEY_DEPENDENCY_TYPE] : null;
							$message  = ( isset($options[self::KEY_MESSAGE]) ) ? $options[self::KEY_MESSAGE] : null;
						}
		
						$this->length_restrictions[] = array( $this->key_name => $input_name,
                                                              $this->key_message => $message,
                                                              $this->key_exact_len => $length,
                                                              $this->key_validator_dep   => $this->get_validator_dep_array($dep_name, $dep_type)
                                                        );
                }

	}

	function require_email( $input, $dep_name = '', $dep_type = '', $message = '' ) {
		
		return $this->require_valid_email_address( $input, $dep_name, $dep_type, $message );
		
	}

	function require_valid_email_address( $input, $dep_name = null, $dep_type = null, $message = null ) {

		if ( !$message ) {
			$message = $this->invalid_email_message;
		}

		return $this->require_regexp_match( $input, '/^[^<>\(\)\[\]\,;:\s@\"]+@[^<>\(\)\[\]\,;:\s@\"]+(\.[^<>\(\)\[\]\\.,;:\s@\"])+/', $dep_name, $dep_type, $message );

	}

	function require_alnum ( $input, $dep_name = '', $dep_type = '', $message = '' ) {

		if ( !$message ) {
			$message = $this->needs_alnum_message;
		}
		
		return $this->require_char_class( 'A-Za-z0-9', $input, $dep_name, $dep_type, $message );

	}

	function require_alnumspace ( $input, $dep_name = '', $dep_type = '', $message = '' ) {

		if ( !$message ) {
			$message = $this->needs_alnumspace_message;
		}

		
		return $this->require_char_class( 'A-Za-z0-9\s', $input, $dep_name, $dep_type, $message );

	}

	function require_number( $input, $dep_name = '', $dep_type = '', $message = '' ) {

		return $this->require_numeric_input( $input, $dep_name, $dep_type, $message );

	}

	function require_numeric_input( $input, $dep_name = '', $dep_type = '', $message = '' ) {

		if ( is_array($input) ) {
			foreach ( $input as $input_name ) {
				
				$this->require_numeric_input[$input_name] = array( $this->key_name    => $input, 
									$this->key_message => $message,
									$this->key_validator_dep   => $this->get_validator_dep_array($dep_name, $dep_type) );

			}
		}
		else {
				$this->require_numeric_input[$input] = array( $this->key_name    => $input, 
									$this->key_message => $message,
									$this->key_validator_dep   => $this->get_validator_dep_array($dep_name, $dep_type) );



		}

		return true;

	}

	function check_standard_char_input() {

		if ( count($this->require_standard_char) ) {
		
			foreach( $this->require_standard_char as $input_name => $cur_requirement ) {

				$input_name   = $cur_requirement[$this->key_name];
				$message      = $cur_requirement[$this->key_message];
				$dependencies = $cur_requirement[$this->key_validator_deps];


				if ( !$this->validator_dependencies_hold($dependencies) ) {
					continue;
				}

				if ( !$this->has_value($this->get_value($input_name)) ) {
					if ( $this->nonstandard_char_on_null ) {
						$this->set_null_value_message($input_name, $message);
					}
					else {
						continue;
					}
				}

				else if ( $bad_char = $this->contains_nonstandard_char($this->get_value($input_name)) ) {

					$message = $this->get_input_message( $input_name, $message, $this->bad_char_message, $bad_char );

					$this->invalid_input[] = array($input_name, $message);

					if ( $this->individual_input_errors ) {
						return false;
					}
				}
				
			}
		}

		return true;

	}


	function validate_input() {
		
		try {	
		 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FORM_VALIDATOR_CLASS_NAME') . '::' . __FUNCTION__ . ':';
	
			//if ( !$this->check_input_called ) {
			if ( !$this->check_required_input() ) {
				$this->check_input_failed = 1;
				return false;
			}
			//}
	
	
			$this->invalid_input = array();
	
			if ( !$this->check_standard_char_input() ) {
				return false;
			}
	
	
			if ( count($this->require_numeric_input) ) {
	
				foreach( $this->require_numeric_input as $input_name => $cur_requirement ) {
	
					$input_name   = $cur_requirement[$this->key_name];
					$message      = $cur_requirement[$this->key_message];
					$dependencies = $cur_requirement[$this->key_validator_deps];
	
					if ( !$this->validator_dependencies_hold($dependencies) ) {
						continue;
					}
	
					$input_val = $this->get_value($input_name);
	
					if ( !is_array($input_val) ) {
						$input_val = array( $input_val );
					}
	
					foreach( $input_val as $cur_val ) {
	
						if ( !$this->has_value($cur_val) ) {
							if ( $this->non_numeric_on_null) {
								$this->set_null_value_message($input_name, $message);
							}
							else {
								continue;
							}
						}
	
						else if ( !is_numeric($cur_val) ) {
	
							$message = $this->get_input_message( $input_name, $message, $this->non_numeric_message );
	
							$this->invalid_input[]     = array($input_name, $message);
							$invalid_numeric_matches[] = $input_name;
	
							if ( $this->individual_input_errors ) {
								return false;
							}
	
							break;
						}
	
					}
				}
				
	
			}
			
			if ( count($this->require_regexp_match) > 0 ) {
	
				foreach ( $this->require_regexp_match as $input_name => $cur_requirement ) {
					
					$input_name = isset($cur_requirement[$this->key_name]) ? $cur_requirement[$this->key_name] : NULL;
					$regexp     = isset($cur_requirement[$this->key_regexp]) ? $cur_requirement[$this->key_regexp] : NULL;
					$flags      = isset($cur_requirement[$this->key_flags]) ? $cur_requirement[$this->key_flags] : NULL;
					$inverse    = isset($cur_requirement[$this->key_negate]) ? $cur_requirement[$this->key_negate] : NULL;
					$message    = isset($cur_requirement[$this->key_message]) ? $cur_requirement[$this->key_message] : NULL;
					$dependencies = isset($cur_requirement[$this->key_validator_deps]) ? $cur_requirement[$this->key_validator_deps] : NULL;
	
					if ( !$this->validator_dependencies_hold($dependencies) ) {
						continue;
					}
	
					//
					// Remember, regexp was already parsed & quoted in require_regexp_match(), 
					// so we can pass it right to preg_match().
					//
	
					$input_val = $this->get_value($input_name);
			
					if ( !is_array($input_val) ) {
						$input_val = array($input_val);
					}
	
					foreach( $input_val as $cur_val ) {
						if ( !$this->has_value($cur_val) ) {
							if ( $this->regexp_mismatch_on_null ) {
								$this->set_null_value_message($input_name, $message);
							}
							else {
								continue;
							}
						}
	
						else {
							$bad_match = 0;
	
							if ( $inverse ) { 
	
								if ( preg_match("/{$regexp}/{$flags}", $cur_val) ) {
									$bad_match = 1;
								}
							}
							else { 
								if ( !preg_match("/{$regexp}/{$flags}", $cur_val) ) {
									$bad_match = 1;
								}
							}
	
							if ( $bad_match > 0 ) {
								
								$message = $this->get_input_message( $input_name, $message, $this->regexp_mismatch_message );
								
								$this->invalid_input[] = array($input_name, $message);
	
								if ( $this->individual_input_errors ) {
									return false;
								}
	
								break;
							}
						}
					}
				}
	
			}
	
			if ( count($this->_Require_callback) > 0 ) {
	
				foreach( $this->_Require_callback as $input_name => $callback_info ) {
	
					$found_callback = false;
					$input_name 	= isset($callback_info[$this->key_name]) ? $callback_info[$this->key_name] : NULL;
					$message    	= isset($callback_info[$this->key_message]) ? $callback_info[$this->key_message] : NULL;
					$function_name  = isset($callback_info['function_name']) ? $callback_info['function_name'] : NULL;
					$error_level	= isset($callback_info[$this->key_error_level]) ? $callback_info[$this->key_error_level] : NULL;
					$deps		= isset($callback_info[$this->key_validator_deps]) ? $callback_info[$this->key_validator_deps] : NULL;
					$negate		= isset($callback_info[$this->key_negate]) ? $callback_info[$this->key_negate] : NULL;
	
					if ( $deps ) {
						if ( !$this->validator_dependencies_hold($deps) ) {
							continue;
						}
					}
	
					//echo "$function_name<br />";
					if ( is_array($function_name) ) {
						$obj    = $function_name[0];
						$method = $function_name[1];
	
						if ( !method_exists($obj, $method) ) {
							trigger_error( 'InputForm require_callback couldn\'t find method: ' . $method, E_USER_WARNING );
							return false;
						}
						else {
							$found_callback = true;
						}
					}
					else {
	
						if ( !function_exists($function_name) ) {
							trigger_error( 'InputForm require_callback couldn\'t find function: ' . $function_name, E_USER_WARNING );
							return false;
						}
						else {
							$found_callback = true;
						}
					}
	
					if ( $found_callback ) {
						
						//echo "calling $function_name for $input_name with " . $this->get($input_name) . "<BR><BR>";
	
						$callback_res = call_user_func($function_name, $this->get($input_name));
						$callback_requirement_failed = false;
	
						if ( $negate ) {
							//
							// Negated in this context means to return FALSE if our callback returned TRUE
							//
							if ( $callback_res ) {
								$callback_requirement_failed = true;
							}
						}
						else {
							if ( !$callback_res ) {
								$callback_requirement_failed = true;
							}
						}
		
						if ( $callback_requirement_failed ) { 
						
							if ( $error_level == constant('ERROR_LEVEL_USER') ) {
								$this->invalid_input[] = array($input_name, $message);
							}
							else {
								throw new Exception( $message );
							}
	
							if ( $this->individual_input_errors ) {
								return false;
							}
	
						}
	
					}
	
				}
	
	
			}
	
	
			if ( count($this->length_restrictions) ) {
	
				foreach( $this->length_restrictions as $input_name => $cur_restr ) {
	
					
	
					$input_name = isset($cur_restr[$this->key_name]) ? $cur_restr[$this->key_name] : NULL;		
					$message    = isset($cur_restr[$this->key_message]) ? $cur_restr[$this->key_message] : NULL;
					$max_len    = isset($cur_restr[$this->key_max_len]) ? $cur_restr[$this->key_max_len] : NULL;		
					$min_len    = isset($cur_restr[$this->key_min_len]) ? $cur_restr[$this->key_min_len] : NULL;
					$exact_len  = isset($cur_restr[$this->key_exact_len]) ? $cur_restr[$this->key_exact_len] : NULL;
					$input_val  = $this->get_value($input_name);
					$dependencies = isset($cur_restr[$this->key_validator_deps]) ? $cur_restr[$this->key_validator_deps] : NULL;
					$friendly_name = $this->get_friendly_name($input_name);
	
					if ( $dependencies ) {
						if ( !$this->validator_dependencies_hold($dependencies) ) {
							continue;
						}
					}
	
					if ( !is_array($input_val) ) {
						$input_val = array($input_val);
					}
	
					foreach( $input_val as $cur_val ) {
	
						if ( $this->has_value($cur_val) ) {
	
							if ( $exact_len > 0 ) {
								if ( strlen($cur_val) != $exact_len ) {
	
		                				        if ( !$message ) {
										$message = LL::Translate( "InputFormValidator-wrong_exact_len %{$friendly_name}% %{$exact_len}%" );
						                        }
	
									$this->invalid_input[] = array($input_name, $message);
	
									if ( $this->individual_input_errors ) {
										return false;
									}
	
									break;
								}
	
							}
							else {
								if ( $max_len > 0 ) {
	
									if ( strlen($cur_val) > $max_len ) {
	
		                					        if ( !$message ) {
											$message = LL::Translate( "InputFormValidator-invalid_max_len %{$friendly_name}% %{$max_len}%" );
							                        }
				
										$this->invalid_input[] = array($input_name, $message);
			
										if ( $this->individual_input_errors ) {
											return false;
										}
					
										break;
									}
	
								}
	
								if ( $min_len > 0 ) {
									if ( strlen($cur_val) < $min_len ) {
		
		                					        if ( !$message ) {
											$message = LL::Translate( "InputFormValidator-invalid_min_len %{$friendly_name}% %{$min_len}%" );
							                        }
	
										//$message = $this->get_input_message( $input_name, $message, $this->input_min_len_message );
			
										$this->invalid_input[] = array($input_name, $message);
	
										if ( $this->individual_input_errors ) {
											return false;
										}
	
										break;
									}
			
								}
							}
							
						}
					}
				}
			}
	
			if ( count($this->input_match_requirements) > 0 ) {
	
				foreach( $this->input_match_requirements as $key1 => $key2 ) {
					if ( $this->get($key1) != $this->get($key2) ) {
						
						$message = $this->get_input_message( $key1, '', $this->needs_match_message );
						
						$this->invalid_input[] = array($key1, $message);
	
						if ( $this->individual_input_errors ) {
							return false;
						}
					}
	
				}		
	
			}
	
			if ( count($this->_Validation_hook_callbacks) > 0  ) {
	
				foreach( $this->_Validation_hook_callbacks as $func_name => $callback_setup ) {
	
					$callback_message = NULL;
					$expected_result  = NULL;
					$expected_result_found = false;
					$callback_success = false;
	
					if ( isset($callback_setup[$this->_Key_validation_hook_object_ref]) ) {
						$full_callback = array( $callback_setup[$this->_Key_validation_hook_object_ref], $func_name );
					}
					else {
						$full_callback = $func_name;
					}
	
					if ( !is_callable($full_callback) ) {
						throw new Exception( LL::Translate('InputFormValidator-invalid_validation_callback') );
					}
	
					$callback_result = call_user_func( $full_callback, $this );
	
					if ( isset($callback_setup[$this->_Key_validation_hook_expected_result]) ) {
						 $expected_result = $callback_setup[$this->_Key_validation_hook_expected_result];
						 $expected_result_found = true;
					}
	
					if ( $expected_result_found ) {
						if ( $callback_result === $expected_result ) {
							$callback_success = true;
						}
					}
					else {
						if ( $callback_result ) {
							$callback_success = true;
						}
					}
		
					if ( !$callback_success ) {
						return false;					
					}
					
				}
	
			}
	
			if ( count($this->invalid_input) > 0 ) {
				return false;
			}			
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}	

	function add_form_validation_callback( $callback ) {
	
		$object_ref = NULL;
		$func_name  = NULL;

		if ( is_array($callback) ) {
			$object_ref = $callback[0];
			$func_name  = $callback[1];	
		}
		else {
			$func_name = $callback;
		}

		$this->_Validation_hook_callbacks[$func_name] = array();

		if ( $object_ref ) {
			$this->_Validation_hook_callbacks[$func_name][$this->_Key_validation_hook_object_ref] = $object_ref;
		}

	}


	function set_form_validation_callback_expected_result( $callback, $result ) {

		if ( is_array($callback) ) {
			$func_name  = $callback[1];	
		}
		else {
			$func_name = $callback;
		}

		if ( !isset($this->Validation_hook_callbacks[$func_name]) ) {
			if ( $this->error_on_preemptive_callback_result ) {
				trigger_error( "Expected result set for: {$func_name} before being added as a callback", E_USER_WARNING );
			}
			else {
				$this->_Validation_hook_callbacks[$func_name][$this->_Key_validation_hook_expected_result] = $result;
			}
		}

	}
	
	function set_null_value_message( $input_name, $message ) {

		$message = $this->get_invalid_input_message( $input_name, $message, $this->null_value_message);
		$this->invalid_input[] = array($input_name, $message);

		return true;
	}


	function get_input_message( $input_name, $message, $replacement_message, $bad_char = '' ) {

		$friendly_name = $this->get_friendly_name($input_name);
		$translated    = 0;
		
		if ( $trans_message = LL::Translate($message, array('null_if_no_translation' => true)) ) {
			$translated = true;
			$message = $trans_message;
		} 

		if ( !$translated ) {
			
			if ( !$message ) {
				$message = $replacement_message;
			}
			
			$message = preg_replace('/(?<!\\\)%I/', $friendly_name, $message);
			

			if ( $bad_char ) {
				$message = preg_replace('/(?<!\\\)%C/', $bad_char, $message);
			}
		}

		return $message;
	}
	
	function get_invalid_input() {

		return $this->invalid_input;

	}

	function invalid_input_message( $newline = '<BR />' ) {

		$invalid_value_message = null;

		
		if ( $this->check_input_failed ) {
			return $this->missing_input_message();
		}
		

		$invalid_values = $this->get_invalid_input();
                
                if ( count($invalid_values) ) {
                
                        foreach( $invalid_values as $cur_value ) {

				list( $input_name, $input_message ) = $cur_value;

                                $invalid_value_message .= ( $invalid_value_message ) ? $newline : '';
                                $invalid_value_message .= $input_message;
                        }        
                }                            
        
		if ( $invalid_value_message ) {
	                $invalid_value_message = $this->invalid_value_heading . $newline . $invalid_value_message;
		}                
                                
                return $invalid_value_message;

	}	

	function require_input_match( $key1, $key2 ) {

		$this->input_match_requirements[$key1] = $key2;

	}

	function js_validation_hook() {
	
		$script = '';
		$tabs   = '';

		$script_hook_false = "if ( typeof(validation_hook_false) == 'function' ) { validation_hook_false(); }\n";
		

		if ( count($this->require_numeric_input) > 0 ) {

			foreach( $this->require_numeric_input as $cur_requirement ) {

				$input_name = $cur_requirement[$this->key_name];
				$message    = $cur_requirement[$this->key_message];
				$message    = $this->get_input_message( $input_name, $message, $this->non_numeric_message );
				//$message    = $this->format_js_message( $message );

				if ( $this->input_ignored_in_script($input_name) ) {
					continue;
				}

				$script .= "if ( {$this->_JS_var_cur_input} = {$this->_JS_function_form_input_by_name}('{$input_name}') ) {\n";
		
				if ( !$this->non_numeric_on_null ) {
					$script .= "\tif ( {$this->_JS_var_cur_input}.value != '' ) {\n";
					$tabs = "\t";
				}

				$script .= "\tif ( isNaN({$this->_JS_var_cur_input}.value) ) {\n";
				
				$script .= $this->js_code_invalid_input_action($input_name, array('message' => $message) );
			
				/*
				$script .= "{$tabs}\t\talert( '$message' );\n";
				$script .= "{$tabs}\t\t{$script_hook_false}\n";
				$script .= "{$tabs}\t\treturn false;\n";
				*/
				
				$script .= "{$tabs}\t}\n";
	
				if ( !$this->non_numeric_on_null ) {
					$script .= "{$tabs} }\n";
				}
	
				$script .= "} \n";

			}

		}

		if ( count($this->input_match_requirements) > 0 ) {

			foreach( $this->input_match_requirements as $key1 => $key2 ) {

				$message    = $this->get_input_message( $key1, '', $this->needs_match_message );
				//$message    = $this->format_js_message( $message );

				
				$script .= "var input1 = null;\n";
				$script .= "var input2 = null;\n";
				
				$script .= "if( input1 = {$this->_JS_function_form_input_by_name}('{$key1}') ) {\n";
				$script .= "if( input2 = {$this->_JS_function_form_input_by_name}('{$key2}') ) {\n";
				$script .= "if ( input1.value != input2.value ) {\n";

				$script .= $this->js_code_invalid_input_action($input_name, array('message' => $message) );
			
				/*
				$script .= "{$tabs}\t\talert( '$message' );\n";
				$script .= "{$tabs}\t\t{$script_hook_false}\n";
				$script .= "{$tabs}\t\treturn false;\n";
				*/
				$script .= "{$tabs}\t}\n";
	
				$script .= "} \n";
				$script .= "} \n";
			}

		}


		return $script;
	}

	function input_marked_as_invalid( $input_name ) {

		if ( is_array($this->invalid_input) && (count($this->invalid_input) > 0) ) {

			foreach ( $this->invalid_input as $info ) {
				if ( $info[0] == $input_name ) {
					return true;
				}
			}
		}

		return 0;
	}
	
	public function apply_setup_array( $given_form_setup, $options = array() ) {
		
		try {

				$data_model = null;
				
				if ( isset($options['data_model']) ) {
					$data_model = $options['data_model'];
				}

				foreach( $given_form_setup as $field_name => $field_options ) {

					if ( isset($field_options['type']) && $field_options['type'] ) {
						$type_constant = self::Input_type_name_to_constant($field_options['type']);
					}
					else { 
						$type_constant = self::INPUT_TYPE_TEXT;
					}


					 if ( $data_model ) {
						 	
					 	$db_field_name = $data_model->db_field_name($field_name);
						 	
					 	if ( $data_model->has_column($db_field_name) ) {
					 		$field_name = $data_model->form_field_name_by_db_field($db_field_name);
					 	}
					 }

					$required = true;
			
					if ( isset($field_options['required']) ) {
						$required = $field_options['required'];
					}
					else {
						if ( isset($field_options['require']) ) {
							$required = $field_options['require'];
						}
					}
					
					if ( $required ) {
						 	
						 $this->set_required_input( $field_name, array( 'i_type' => $type_constant) );
					}
					
					if ( isset($field_options['friendly_name']) ) {
						$this->set_friendly_name( $field_name, $field_options['friendly_name']);
					}
					
					if ( isset($field_options['required_if']) && is_array($field_options['required_if']) ) {
						foreach( $field_options['required_if'] as $dep_name => $dep_options ) {

							if ( $data_model ) {
					 			$db_dep_name = $data_model->db_field_name($dep_name);
					 			if ( $data_model->has_column($db_dep_name) ) {
					 				$dep_name = $data_model->form_field_name_by_db_field($db_dep_name);
					 			}
							 }
							
							$dep_val = ( isset($dep_options['value']) ) ? $dep_options['value'] : null;

							$dep_type = self::Input_type_name_to_constant($dep_options['type']); 

							if ( !$dep_type ) {
								$dep_type = self::INPUT_TYPE_TEXT;
							}


							$this->add_requirement_dependency( $field_name, $dep_name, $dep_type, $dep_val );

					 	}
					 }

					if ( isset($field_options['require_number']) && $field_options['require_number'] ) {
						$this->require_number( $field_name );
					}							

					if ( isset($field_options['regexp']) && $field_options['regexp'] ) {
						$this->require_regexp_match( $field_name, $field_options['regexp'] );
					}							

					if ( isset($field_options['is_email_address']) && $field_options['is_email_address'] ) {
						$this->require_valid_email_address($field_name);
					}						

					if ( isset($field_options['must_match']) && $field_options['must_match'] ) {
						$this->require_input_match($field_name, $field_options['must_match']);
					}	

					if ( isset($field_options['min_length']) && $field_options['min_length'] ) {
						$this->require_min_length($field_name, $field_options['min_length']);
					}

					if ( isset($field_options['max_length']) && $field_options['max_length'] ) {
						$this->require_max_length($field_name, $field_options['max_length']);
					}


				}
				
			
		}
		catch( Exception $e ) { 
			throw $e;
		}
		
		
		
	}
}


} //!defined FORM_VALIDATOR

	
?>
