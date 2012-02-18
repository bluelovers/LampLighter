<?php

if ( !defined('FORM_CLASS_INCLUDED') ) {

LL::Require_class('Form/FormInputType');

define ('FORM_CLASS_INCLUDED', 1 );
define ('FORM_CLASS_NAME', 'InputForm');

//---------------------
// Parse instructions
//---------------------
define ('FORM_DEFAULT_PARSE', 0 );
define ('FORM_PARSE', 1);
define ('FORM_DONT_PARSE', 2 );
define ('FORM_IGNORE_PARSE', 2 );
define ('FORM_PARSE_IF_UNSAFE', 3 );

//-------------
// Parse flags
//-------------
define ('FORM_PARSE_HTML', 1 );
define ('FORM_UNPARSE_HTML', 2 );
        
define ('FORM_PARSE_HTML_CHARS', 4);
define ('FORM_STRIP_TAGS', 8);
define ('FORM_CONVERT_TAGS_ONLY', 16 );

//-------------
// Input types
// Deprecated in favor of class constants
//-------------
if ( !defined('FORM_INPUT_TYPE_TEXT') ) {
	define ('FORM_INPUT_TYPE_TEXT', 1);
	define ('FORM_INPUT_TYPE_TEXTBOX', 1);
	define ('FORM_INPUT_TYPE_HIDDEN', 1);
	define ('FORM_INPUT_TYPE_RADIO', 2);
	define ('FORM_INPUT_TYPE_CHECKBOX', 3);
	define ('FORM_INPUT_TYPE_LISTBOX', 4);
	define ('FORM_INPUT_TYPE_SELECT', 4);
	define ('FORM_INPUT_TYPE_DROPDOWN', 4);
	define ('FORM_INPUT_TYPE_FILE', 5);
	define ('FORM_INPUT_TYPE_TEXTBOX_ARRAY', 6);
	define ('FORM_INPUT_TYPE_LISTBOX_ARRAY', 7);
}

//
// Note: this class extends FormInputType 
// for backward compatibility - the INPUT_TYPE_x 
// constants used to live here.
//

class InputForm extends FormInputType {

	const KEY_DEPENDENCY_NAME = 'dependency_name';
	const KEY_DEPENDENCY_TYPE = 'dependency_type';
	const KEY_MESSAGE = 'message';


	public $messages_grouped = false;
	public $alert_delimiter = '\n'; 
	
	public $javascript_validation_enabled = true; //This is not checked in the class,
	                                              // but is used by the app controller

	var $required_inputs; 
	var $required_radio_buttons;
	var $required_checkboxes;
	var $require_standard_char;
	var $ignore_script_check;
	var $repopulate_htmlize = 1;

	var $_confirm_checkboxes;
	var $_removes_requirements_arr;

	var $mail_inputs;
	var $ignore_parse;
	var $friendly_names;
	var $errors;
	var $missing_values;
	var $num_missing_values;
	var $form_name;

	var $missing_array_caption    = 'all values for';
	var $missing_input_caption    = 'Please enter ';
	var $missing_radio_caption    = 'Please select ';
	var $missing_dropdown_caption = 'Please select ';
	var $missing_checkbox_caption = 'Please check ';
	var $constraints_not_met_caption = 'The following field\'s constraints are not properly set: ';

	var $missing_input_message;

	var $save_javascript;
	var $javascript_directory;
	var $javascript_link;
	var $write_timeout = 3; //in seconds
	var $force_javascript_save;
	var $force_validation_regenerate;
	var $validation_filename;
	var $validation_script_hook_true  = 'validation_hook_true'; //the name of the function the javascript should also return the value of
	var $validation_script_hook_false = 'validation_hook_false'; //call this function when javascript returns false
	var $return_on_missing_value = 1;

	var $input_name_key    = 'i_name';
	var $input_type_key    = 'i_type';
	var $input_negate_key  = 'i_negate';
	var $input_message_key = 'i_msg';	

	var $requirement_scope_key = 'scope';
	var $null_key = '[-formnull-]';

	var $dependency_key 	   = 'dependencies';
	var $dependency_target_key = 'dep_target';
	var $dependency_name_key   = 'dep_name';
	var $dependency_type_key   = 'dep_type';
	var $dependency_value_key  = 'dep_val';
	var $dependency_negate_key = 'dep_negate';
	var $error_on_preemptive_dependency = true;

	var $mail_subject_default;

	var $input_type_text     = 1;
	var $input_type_radio    = 2;
	var $input_type_checkbox = 3;
	var $input_type_listbox  = 4;
	var $input_type_file	 = 5;	

	var $clear_all_inputs = '[--all--]';
	var $validation_script;

	var $use_hook_functions = 1;
	var $auto_refocus       = 1;
	var $parse_values       = 1;
	var $zero_is_null       = 0;

	var $_allow_input_pipes      = 0;
	var $_parse_double_backslash = 0;
	var $_escape_backslash	     = 1;
	var $_php_extension;
	var $db_studly_convert	= 0;

	var $setup_complete	= 0;
	var $form_validated	= 0;

	var $_Error_level_user    = 1;
	var $_Error_level_general = 2;
	var $_Error_level_warn    = 3;

	var $_Verbose = 0;
	var $debug_level;
	var $show_javascript_errors = 0;

	var $_general_messages;
	var $quick_init        = 1;

	var $check_input_called = 0;
	var $checkbox_confirm_message = 'Are you sure you want to select ';

	var $template_ref;
	var $generate_script_called = 0;

	var $_dataset;
	var $_alt_dataset = 0;

	var $_Completed_setups;

	var $_Chmod_javascript_file = 0755;

	var $_Key_form_input_is_array = 'i_is_array';
	var $_Key_form_input_type = 'i_type';

	var $_Map_input_field_name = array();

	var $_JS_input_array_index_reference = '[j]';
	var $_JS_var_cur_input = 'cur_input';
	var $_JS_function_form_input_by_name = '__Form_input_by_name';	

	function InputForm( $form_name = '' ) {

		$this->init_InputForm( $form_name );

		return true;
	}

	function init_InputForm( $form_name = '' ) {

		$this->check_input_called = 0;

		$this->_Completed_setups = array();
		$this->_general_messages = array();
		$this->ignore_parse 	= array();
		$this->required_inputs  = array();
		$this->friendly_names 	= array();
		$this->missing_values 	= array();

		$this->ignore_script_check    = array();
		$this->required_radio_buttons = array();
		$this->required_checkboxes    = array();
		$this->require_standard_char  = array();

		$this->quick_init = ( defined('FORM_QUICK_INIT') ) ? constant('FORM_QUICK_INIT') : $this->quick_init;

		if ( !$this->quick_init ) {

			$this->repopulate_htmlize	= ( defined('FORM_REPOPULATE_HTMLIZE') ) ? constant('FORM_REPOPULATE_HTMLIZE') : $this->repopulate_htmlize;
			$this->constraints_not_met_caption	= ( defined('FORM_CONSTRAINTS_NOT_MET_CAPTION') ) ? constant('FORM_CONSTRAINTS_NOT_MET_CAPTION') : $this->constraints_not_met_caption;
			$this->missing_input_caption	= ( defined('FORM_MISSING_INPUT_CAPTION') ) ? constant('FORM_MISSING_INPUT_CAPTION') : $this->missing_input_caption;
			$this->missing_radio_caption	= ( defined('FORM_MISSING_RADIO_CAPTION') ) ? constant('FORM_MISSING_RADIO_CAPTION') : $this->missing_radio_caption;
			$this->missing_dropdown_caption	= ( defined('FORM_MISSING_DROPDOWN_CAPTION') ) ? constant('FORM_MISSING_DROPDOWN_CAPTION') : $this->missing_dropdown_caption;
			$this->missing_checkbox_caption	= ( defined('FORM_MISSING_CHECKBOX_CAPTION') ) ? constant('FORM_MISSING_CHECKBOX_CAPTION') : $this->missing_checkbox_caption;

			$this->auto_refocus 		= ( defined('FORM_AUTO_REFOCUS') ) ? constant('FORM_AUTO_REFOCUS') : $this->auto_refocus;
			$this->show_javascript_errors 	= ( defined('FORM_SHOW_JAVASCRIPT_ERRORS') ) ? constant('FORM_SHOW_JAVASCRIPT_ERRORS') : $this->show_javascript_errors;
			$this->use_hook_functions 	= ( defined('FORM_USE_HOOK_FUNCTIONS') ) ? constant('FORM_USE_HOOK_FUNCTIONS') : $this->use_hook_functions;
			$this->parse_values		= ( defined('FORM_PARSE_VALUES') ) ? constant('FORM_PARSE_VALUES') : $this->parse_values;

			$this->write_timeout	        = ( defined('FORM_WRITE_TIMEOUT') ) ? constant('FORM_WRITE_TIMEOUT') : $this->write_timeout;
			$this->_allow_input_pipes       = ( defined('FORM_ALLOW_INPUT_PIPES') ) ? constant('FORM_ALLOW_INPUT_PIPES') : $this->allow_input_pipes;
			$this->_parse_double_backslash  = ( defined('FORM_PARSE_DOUBLE_BACKSLASH') ) ? constant('FORM_PARSE_DOUBLE_BACKSLASH') : $this->_parse_double_backslash;
			$this->_escape_backslash  	= ( defined('FORM_ESCAPE_BACKSLASH') ) ? constant('FORM_ESCAPE_BACKSLASH') : $this->_escape_backslash;
			$this->zero_is_null	        = ( defined('FORM_ZERO_IS_NULL') ) ? constant('FORM_ZERO_IS_NULL') : $this->zero_is_null;
			$this->standard_char_class      = ( defined('STANDARD_CHAR_CLASS') ) ? constant('STANDARD_CHAR_CLASS') : $this->standard_char_class;
			$this->clear_all_inputs	        = ( defined('FORM_CLEAR_ALL_INPUTS') ) ? constant('FORM_CLEAR_ALL_INPUTS') : $this->clear_all_inputs;

			$this->checkbox_confirm_message = ( defined('FORM_CHECKBOX_CONFIRM_MESSAGE') ) ? constant('FORM_CHECKBOX_CONFIRM_MESSAGE') : $this->checkbox_confirm_message;

		}

		$this->return_on_missing_value  = ( defined('FORM_RETURN_ON_MISSING_VALUE') ) ? constant('FORM_RETURN_ON_MISSING_VALUE') : $this->return_on_missing_value;
		$this->submit_disabled_caption  = ( defined('FORM_SUBMIT_DISABLED_CAPTION') ) ? constant('FORM_SUBMIT_DISABLED_CAPTION') : '';
		$this->disable_submit_button 	= ( defined('FORM_DISABLE_SUBMIT_BUTTON') ) ? constant('FORM_DISABLE_SUBMIT_BUTTON') : 0;


		$this->debug_level		= ( defined('FORM_DEBUG_LEVEL') ) ? constant('FORM_DEBUG_LEVEL') : $this->_Error_level_user;

		$this->save_javascript	       = ( defined('FORM_SAVE_JAVASCRIPT') ) ? constant('FORM_SAVE_JAVASCRIPT') : $this->save_javascript;
		$this->javascript_directory    = ( defined('FORM_JAVASCRIPT_DIRECTORY') ) ? constant('FORM_JAVASCRIPT_DIRECTORY') : '';
		$this->javascript_directory    = ( defined('FORM_JAVASCRIPT_BASE_PATH') ) ? constant('FORM_JAVASCRIPT_BASE_PATH') : $this->javascript_directory;

		if ( $this->javascript_directory ) {
			$this->save_javascript = 1;
		}

		$this->javascript_link         = ( defined('FORM_JAVASCRIPT_LINK') ) ? constant('FORM_JAVASCRIPT_LINK') : '';
		$this->javascript_link		   = ( defined('FORM_JAVASCRIPT_BASE_URI') ) ? constant('FORM_JAVASCRIPT_BASE_URI') : $this->javascript_link;
		
		$this->_php_extension	       = ( defined('FORM_PHP_EXTENSION') ) ? constant('FORM_PHP_EXTENSION') : 'php';
		$this->db_studly_convert       = ( defined('FORM_DB_STUDLY_CONVERT') ) ? constant('FORM_DB_STUDLY_CONVERT') : $this->db_studly_convert;

		
		$this->force_validation_regenerate = ( defined('FORM_FORCE_VALIDATION_REGENERATE') ) ? constant('FORM_FORCE_VALIDATION_REGENERATE') : 0;
		$this->force_javascript_save	   = $this->force_validation_regenerate;
		
		
		$this->allow_html	       = ( defined('FORM_ALLOW_HTML') ) ? constant('FORM_ALLOW_HTML') : 0;
		$this->_Chmod_javascript_file  = ( defined('FORM_CHMOD_JAVASCRIPT_FILE') ) ? constant('FORM_CHMOD_JAVASCRIPT_FILE') : $this->_Chmod_javascript_file;
		

		//
		// Deprecated use of input type comments. For historical reasons.
		//
		$this->input_type_text = constant('FORM_INPUT_TYPE_TEXT');
		$this->input_type_radio = constant('FORM_INPUT_TYPE_RADIO');
		$this->input_type_checkbox = constant('FORM_INPUT_TYPE_CHECKBOX');
		$this->input_type_listbox = constant('FORM_INPUT_TYPE_LISTBOX');
		$this->input_type_file = constant('FORM_INPUT_TYPE_FILE');

		if ( Config::Get('form.messages_grouped') ) {
			$this->messages_grouped = Config::Get('form.messages_grouped');
		}

		if ( Config::Get('form.alert_delimiter') ) {
			$this->alert_delimiter = Config::Get('form.alert_delimiter');
		}

		if ( $form_name ) {
			$this->set_form_name( $form_name );
		}

		return true;
	}

	function add_friendly_name( $input_name, $friendly_name ) {

		$this->friendly_names[$input_name] = $friendly_name;
		return true;

	}

	function set_friendly_name( $input_name, $friendly_name ) {
	
		return $this->add_friendly_name( $input_name, $friendly_name );
		
	}

	function parse_value( $value, $parse_flags = 0 ) {
		
		if ( !$this->allow_html && (($parse_flags & FORM_UNPARSE_HTML) == 0) ) {
			$parse_flags = $parse_flags | constant('FORM_CONVERT_TAGS_ONLY');
		}	

		if ( function_exists('post_value_parse_hook') AND $this->use_hook_functions ) {
			$value = call_user_func('post_value_parse_hook', $value);
		}
		else {
			
			$value = ( get_magic_quotes_gpc() && !$this->_alt_dataset ) ? $value : addslashes($value);
		
                        if ( ($parse_flags & FORM_PARSE_HTML_CHARS) > 0 ) {
                                $value = htmlspecialchars($value);  
                        }       
                        else {  
                                if ( ($parse_flags & FORM_STRIP_TAGS) > 0 ) {
                                        $value = strip_tags( $value );
                                }
                                else {  
                                        if ( ($parse_flags & FORM_CONVERT_TAGS_ONLY) > 0 ) {
                                                $value = str_replace('<', '&lt;', $value);
                                                $value = str_replace('>', '&gt;', $value);
                                        }
                                }
                        }
                
                        if ( ($parse_flags & FORM_UNPARSE_HTML) > 0 ) {
                                $value = $this->html_decode($value);
                        }

			//if ( !$this->_allow_input_pipes ) {
			//	$value = str_replace( '|', '', $value );
			//}
			//if ( $this->_escape_backslash ) {
			//	$value = str_replace ('\\', '\\', $value );
			//}

			//if ( $this->_parse_double_backslash ) {
			//	$value = str_replace( '\\\\', '', $value );
			//}

		}


		return $value;

	}

	function unparse_value( $value, $parse_flags = 0 ) {

		if ( function_exists('post_value_parse_hook') AND $this->use_hook_functions ) {
			$value = call_user_func('post_value_parse_hook', $value);
		}
		else {
			if ( is_array($value) ) {
				if ( count($value) > 0 ) {
					foreach( $value as $inner_key => $inner_val ) {
						$value[$inner_key] = $this->unparse_value($inner_val, $parse_flags);
					}
				}
			}
			else {
				
				$value = ( get_magic_quotes_gpc() || $this->_alt_dataset ) ? stripslashes($value) : $value;
	
				if ( ($parse_flags & FORM_PARSE_HTML_CHARS) > 0 ) {
					$value = htmlspecialchars($value);
				}
				else {
					if ( !$this->allow_html || ($parse_flags & FORM_UNPARSE_HTML) > 0 ) {
						$value = $this->html_decode($value);
					}
				}
			}

		}

		return $value;


	}


	function ignore_parse( $input_name ) {

		$this->ignore_parse[] = $input_name;
		return true;
	}

	function unignore_parse( $input_name ) {

		if ( count($this->ignore_parse) ) {
			for ($j=0; $j<count($this->ignore_parse); $j++ ) {
				if ( $this->ignore_parse[$j] == $input_name ) {
					$this->ignore_parse[$j] = '';
					break;
				}
			}
		}

		return true;
	}

	function get_required_parsed( $input_name, $parse_flags = 0 ) {

		return $this->get_required_parsed_value( $input_name, $parse_flags );
	}

	function get_required_parsed_value( $input_name, $parse_flags = 0) {

		if ( !$this->has_required_input($input_name) ) {
			$this->set_general_message( $this->constraints_not_met_caption . $input_name );

			if ( !$this->get_value($input_name) ) {
				$this->set_missing_value( $input_name );
			}

			$this->generate_missing_input_message();
		}

		return $this->get_parsed_value( $input_name, $parse_flags );		

	}

        function get_required_unparsed($input_name, $flags = 0 ) {

                if ( !$this->has_required_input($input_name) ) {
                        $this->set_general_message( $this->constraints_not_met_caption . $input_name );

                        if ( !$this->get_value($input_name) ) {
                                $this->set_missing_value( $input_name );
                        }

                        $this->generate_missing_input_message();
                }

                return $this->get_unparsed( $input_name, $flags );

        }

	function get_required_value( $input_name, $parse_flags = 0) {

		return $this->get_required( $input_name, $parse_flags );
	
	}

	function get_required( $input_name, $parse_flags = 0 ) {
		
		try { 
			if ( !$this->has_required_input($input_name) ) {
				throw new Exception( $this->constraints_not_met_caption  );
			}
	
			return $this->get_value($input_name, FORM_DEFAULT_PARSE, $parse_flags = 0 );
		}
		catch( Exception $e ) {
			throw $e;
		}			
	}

	function get_parsed( $input_name, $parse_flags = 0 ) {

		return $this->get_value( $input_name, constant('FORM_PARSE'), $parse_flags  );
	}

	function get_parsed_value( $input_name, $parse_flags = 0 ) {

		return $this->get_value( $input_name, constant('FORM_PARSE'), $parse_flags  );

	}

	function get_db_safe_value( $input_name, $parse_flags = 0 ) {

		return $this->get_value( $input_name, constant('FORM_PARSE'), $parse_flags  );

	}

	function get_value( $input_name, $parse = 0, $parse_flags = 0 ) {
		
		return $this->get( $input_name, $parse, $parse_flags );
	}

	function get_unparsed( $input_name, $parse_flags = 0 ) {

		return $this->unparse_value($this->get($input_name, constant('FORM_DONT_PARSE')), $parse_flags);
	}

	public function input_name_is_array_reference( $name ) {

		LL::require_class('Util/ArrayString');

		return ArrayString::String_contains_array_key_reference($name);
		
	}

	
	public function split_array_reference( $ref ) {

		$first_bracket = strpos($ref, '[');

        $arr_name = substr($ref, 0, $first_bracket);
        $key_str  = trim(substr($ref, $first_bracket), '[]');

		return array($arr_name, $key_str);		

	}
	

	function unset_field( $input_name ) {
		
		$dataset = $this->get_dataset();

		if ( $this->input_name_is_array_reference($input_name) ) {
			
			LL::require_class('Util/ArrayString');
			
			$arr_name   = ArrayString::Extract_array_name_from_string($input_name);
			$key_arr    = ArrayString::Extract_array_keys_from_string_as_array($input_name);
			
			ArrayString::Unset_array_value_by_keys($dataset[$arr_name], $key_arr);
			
			//list( $arr_name, $key_name ) = $this->split_array_reference($key);
			//$dataset[$arr_name][$key_name] = $value;
			
		}
		else {
			if ( isset($dataset[$input_name]) ) {
				unset($dataset[$input_name]);
			}
		}
		
		$this->set_dataset($dataset);
		
	}

	public function __get( $what ) {
		
		return $this->get( $what );
		
	}

	function get( $input_name, $parse = 0, $parse_flags = 0 ) {

		$post_value = null;

		if ( !has_value($input_name) ) {
        	trigger_error( 'No input name passed to ' . __FUNCTION__, E_USER_WARNING );
            return false;
        }

                if ( !is_scalar($input_name) ) {
                        trigger_error( "Invalid input name '{$input_name}' passed to " . __FUNCTION__, E_USER_WARNING );
                        return false;
                }

		if ( !$parse || $parse == FORM_DEFAULT_PARSE ) {
			if ( $this->parse_values ) {
				$parse = constant('FORM_PARSE');
			}
			else {
				$parse = constant('FORM_DONT_PARSE');
			}
		}

		$dataset = $this->get_dataset();

		if ( $this->input_name_is_array_reference($input_name) ) {
			
			LL::require_class('Util/ArrayString');

			$arr_name   = ArrayString::Extract_array_name_from_string($input_name);
			$key_arr    = ArrayString::Extract_array_keys_from_string_as_array($input_name);
			
			if ( isset($dataset[$arr_name]) && !is_array($dataset[$arr_name]) ) {
				trigger_error( "Could not find {$arr_name} array in the form", E_USER_WARNING);
			}
			else {
				$post_value = ArrayString::Find_array_value_by_keys($dataset[$arr_name], $key_arr);
			}
			
			//list( $arr_name, $key_name ) = $this->split_array_reference($input_name);
			//$post_value = ( isset($dataset[$arr_name][$key_name]) ) ? $dataset[$arr_name][$key_name] : NULL;
			
		}
		else {
			$post_value = ( isset($dataset[$input_name]) ) ? $dataset[$input_name] : NULL;
		}
		
		if ( $parse != constant('FORM_DONT_PARSE') AND !in_array($input_name, $this->ignore_parse) ) {

			if ( is_array($post_value) ) {
	
				for ( $j=0; $j < count($post_value); $j++ ) {
					if ( isset($post_value[$j]) && $post_value[$j] ) {
						$post_value[$j] = $this->parse_value( $post_value[$j], $parse_flags );
					}
				}
	
		
	
			}
			else {
				if ( $this->has_value($post_value) ) {
					$post_value = $this->parse_value($post_value, $parse_flags);
				}
			}

			return $post_value;
		}
		else {
			if ( is_array($post_value) ) {
				if ( count($post_value) > 0 ) {
					return $post_value;
				}
				else {
					return array();
				}
			}
			else {
				return $post_value;
			}				
		}
	
		return NULL;	

	}
	
	function set( $key, $value ) {

		$dataset =& $this->get_dataset();

		if ( $this->input_name_is_array_reference($key) ) {
			
			LL::require_class('Util/ArrayString');
			
			$arr_name   = ArrayString::Extract_array_name_from_string($key);
			$key_arr    = ArrayString::Extract_array_keys_from_string_as_array($key);
			
			ArrayString::Set_array_value_by_keys($dataset[$arr_name], $key_arr, $value);
			
			//list( $arr_name, $key_name ) = $this->split_array_reference($key);
			//$dataset[$arr_name][$key_name] = $value;
			
		}
		else {
			$dataset[$key] = $value;
		}


	}

	function check_required_values() {
		//deprecated, call check_required_inputs() instead.

		return $this->check_required_input();


	}

	function check_dependencies( $dependency_array ) {
	


		foreach( $dependency_array as $dependency_name => $dependency ) {

			$is_file_input    = false;
			$dependency_value = $dependency[$this->dependency_value_key];
			$dependency_type  = $dependency[$this->dependency_type_key];
			$dependency_negate = $dependency[$this->dependency_negate_key];

			//echo "checking for {$dependency_negate}$dependency_value for $dependency_name<BR/>";

			//$dependency_negate = ( $dependency_negate ) ? '' : '!';
			//echo "dependency name: $dependency_name negate: $dependency_negate<BR/>";

			if ( !$this->_alt_dataset ) {

				if ( $dependency_type == constant('FORM_INPUT_TYPE_FILE') ) {
					$is_file_input = true;
					$input_array =& $_FILES;
				}
				else {
					$is_file_input = false;
					$input_array =& $_POST;
				}
			}
			else {
				$input_array =& $this->get_dataset();
			}

			if ( $is_file_input ) {
				$dependency_check_var = ( isset($input_array[$dependency_name]['name']) ) ? $input_array[$dependency_name]['name'] : null;
			}
			else {
				$dependency_check_var = ( isset($input_array[$dependency_name]) ) ? $input_array[$dependency_name] : null;
			}

			if ( !$this->has_value($dependency_value) ) {
				if ( !$dependency_negate ) {
					
					if ( !$dependency_check_var ) { 
						return false;
					}
				}
				else {
					if ( $dependency_check_var ) {
						return false;
					}
				}
			}
			else {
				if ( !$dependency_negate ) {
					if ( $dependency_check_var != $dependency_value ) {
						return false;
					}
				}
				else {
					if ( $dependency_check_var == $dependency_value ) {
						return false;
					}
				}

			}
		}

		return true;

	}



	function check_required_fields() {

		return $this->check_required_input();

	}

	function check_required_inputs() {
	
		return $this->check_required_input();
	}

	function check_required_input() {
	
		$this->check_input_called = 1;
		$num_missing_values = 0;

		if ( $this->post_requirements_removed() ) {
			return true;
		}

		if ( count($this->required_inputs) ) {
			foreach( $this->required_inputs as $cur_input ) {

				$input_name = $this->get_input_name($cur_input);
				
				
				if ( $dependencies = $this->get_dependencies($cur_input) ) {
					if ( !$this->check_dependencies($dependencies) ) {
						continue;
					}
				}	

				if ( $this->get_input_type($cur_input) == constant('FORM_INPUT_TYPE_FILE') ) {
					if ( !$_FILES[$input_name]['name'] ) {
						if ( !$this->value_marked_as_missing($input_name) ) {
							$this->set_missing_value( $input_name );
						}
					}
				}
				else {						
					$input_val = $this->get_value($input_name);

					
					if ( !is_array($input_val) ) {
						$input_val = array($input_val);
					}
					
					
					foreach( $input_val as $cur_val ) {
						
						if ( !$this->has_value($cur_val) ) {
							if ( !$this->value_marked_as_missing($input_name) ) {
								$this->set_missing_value( $input_name );
							}
						}
					}
				}

			}
		}
			
		
		
		if ( $this->num_missing_values ) {
			return false;
		}
		else {
			return true;
		}

	}

	
	function post_requirements_removed() {

		if ( count($this->_removes_requirements_arr) ) {
	
			$dataset = $this->get_dataset();

			foreach ( $this->_removes_requirements_arr as $cur_remover ) {

				$negate = $cur_remover[$this->input_negate_key];
				$name	= $cur_remover[$this->input_name_key];

				if ( $negate ) {
					if ( !isset($dataset[$name]) || !$dataset[$name] ) {
						return true;
					}
				}
				else {
					if ( isset($dataset[$name]) && $dataset[$name] ) {
						return true;
					}
				}
	
			}
		}

		return false;
	}
	

	function get_missing_values() {

		return $this->get_missing_inputs();
	}

	function get_missing_inputs() {

		return $this->missing_values;


	}

	function get_friendly_name( $input_name ) {
	
		if ( isset($this->friendly_names[$input_name]) && $this->friendly_names[$input_name] ) {
			return $this->friendly_names[$input_name];
		}
		else {
			return $input_name;
		}

		return $input_name;
	}
	
	public function friendly_name_by_input_name( $input_name ) {
		
		return $this->get_friendly_name($input_name);
	}
	
	function format_js_message( $message ) {

		$message = str_replace ("'", "\'", $message);

		return $message;

	}



	function parse_negated_input_name($input_name, $dont_parse_name = 0 ) {

		$negation_operator = NULL;

		if ( strpos($input_name, '!') === 0 ) {

			$negation_operator = '!';
			$input_name = substr($input_name, 1);

			//if ( preg_match('/^!/', $input_name) ) {
			//$input_name = preg_replace('/^!/', '', $input_name);
		}
		
		if ( !$dont_parse_name ) {
			$input_name = $this->parse_input_name($input_name);
		}

		return array( $negation_operator, $input_name );

	}

	function get_input_name( $input_array ) {

		if ( is_array($input_array) ) {
			if ( isset($input_array[$this->input_name_key]) ) {
				return $input_array[$this->input_name_key];
			}
			else {
				return NULL;
			}
		}
		else {
			return $input_array;
		}

	}

	function get_input_negation( $input_array ) {

		if ( is_array($input_array) ) {
			if ( isset($input_array[$this->input_negate_key]) ) {
				return $input_array[$this->input_negate_key];
			}
			else {
				return NULL;
			}
		}
		else {
			return false;
		}

	}
	function get_input_type( $input_array ) {

		if ( is_array($input_array) ) {
			if ( isset($input_array[$this->input_type_key]) ) {
				return $input_array[$this->input_type_key];
			}
		}

		return false;
	}


	function generate_input_if_statement( $input_name, $input_type, $negation = '', $required_value = '', $is_dependency = 0 ) {

		$tabs = NULL;
		$javascript 	= "//--------{$input_name}-------//\n\n";
		$negation 	= ( $negation ) ? '!' : '';
		$input_is_array = $this->get_input_setup_value( $input_name, $this->_Key_form_input_is_array );
		$value_compare  = '\'\'';
		$value_quote    = '';

		if ( !$input_name OR !$input_type ) {
			LL::raise_error('generate_input_if_statement called with out input_name and/or input type.', '', $_SERVER['PHP_SELF'], $this->_Error_level_warn );
			return false;
		}

		$input_var_ref = $this->_JS_var_cur_input;

		//
        // Checkboxes ignore required value stuff and only use true or false.
        //
        if ( $input_type != constant('FORM_INPUT_TYPE_CHECKBOX') && $this->has_value($required_value) ) { 

			if ( $required_value === $this->null_key ) {

				$value_quote = '';
				$value_compare = 'null';
			}
			else {
				
				if ( is_numeric($required_value) && !is_string($required_value) ) {
					$value_quote = '';
					$value_compare = $required_value;
				}
				else {
		           	
		           	$value_quote   = '\'';
					$value_compare = $this->format_js_message($required_value);
				}

				
			}
		}
		else {			
			
			if ( !$this->zero_is_null ) {

				$value_quote = '';

				//$required_value_compare = '\'\'';
			}

			if ( $is_dependency ) {
				$negation = ( $negation ) ? '' : '!';
			}

		}

		if ( $input_type == constant('FORM_INPUT_TYPE_HIDDEN') || $input_type == constant('FORM_INPUT_TYPE_TEXT') 
			|| $input_type == constant('FORM_INPUT_TYPE_FILE') || $input_type == constant('FORM_INPUT_TYPE_TEXTBOX_ARRAY') ) {

			//if ( $this->has_value($required_value) ) {
			//$value_statement = " == {$value_quote}{$value_compare}{$value_quote};
			//}
			
			if ( $input_is_array ) {
				$javascript .=  "for ( j = 0; j < {$this->_JS_var_cur_input}.length; j++ ) {
							if ( {$negation}({$this->_JS_var_cur_input}[j].value == {$value_quote}{$value_compare}{$value_quote} ) ) { \n";
	
			}
			else {
				$javascript .= "if ( {$this->_JS_var_cur_input} = {$this->_JS_function_form_input_by_name}('{$input_name}') ) {
							if ( {$negation}({$this->_JS_var_cur_input}.value == {$value_quote}{$value_compare}{$value_quote} ) ) { \n";
				
			}

			return $javascript;

		}
		else if ( $input_type == constant('FORM_INPUT_TYPE_RADIO') ) {
                
				$javascript .= "
						var radio_single = 0;
						var found_radio_val = 0;
						var radio_val = ''
						var radio_button = null;
						var radio_index = 0;

						if( radio_button = {$this->_JS_function_form_input_by_name}('{$input_name}') ){
	
							if ( typeof(radio_button.length) != 'undefined' ) {
			
								if ( radio_button.length <= 1 ) {
									radio_single = 1;
								}
							}

							if ( radio_button.checked == true ) {
								found_radio_val = 1;
							}
						}

			
						if ( !radio_single ) {
							
							while ( radio_button = {$this->_JS_function_form_input_by_name}('{$input_name}', radio_index) ) {
								
								if ( radio_button.checked == true ) {
									found_radio_val= 1;
									break;
								}
	
								radio_index++
							}
						}\n";

				if ( !$this->has_value($required_value) ) {
					$javascript .= "\t\t\tif ( {$negation}(!found_radio_val) ) {\n";
				}
				else {

					$alert_msg = $this->get_missing_input_alert_by_name( $input_name );

					$compare = ( $negation ) ? '!=' : '==';

					if ( !$is_dependency ) {
	                                        $javascript .= "\t\t\tif ( !found_radio_val ) {\n";
        	                                $javascript .= "\t\t\t\t" . $this->js_alert_code($alert_msg) . "\n";
        	                                //$javascript .= "\t\t\t\talert( '{$alert_msg}' );\n";
                	                        $javascript .= "\t\t\t\t" . $this->get_false_validation_hook_script() . "\n";
                        	                $javascript .= "\t\t\t\treturn false;\n";
                                	        $javascript .= "\t\t\t}\n";
                                        	$javascript .= "\t\t\telse {\n";
					}
					else {
		                                $javascript .= "\t\t\tif ( found_radio_val ) {\n";
					}

					$javascript .= "\t\t\t\tradio_val = radio_button.value;\n";
					$javascript .= "\t\t\t\tif ( radio_val {$compare} {$value_quote}{$value_compare}{$value_quote} ) {\n";
				}
                                 
                }
		else if ( $input_type == constant('FORM_INPUT_TYPE_CHECKBOX') ) {

			$value_compare = ( $negation ) ? 'true' : 'false'; 

			$javascript .= "if ( {$this->_JS_var_cur_input} = {$this->_JS_function_form_input_by_name}('{$input_name}') ) {
						if ( ({$this->_JS_var_cur_input}.checked == {$value_compare}) ) { \n";

		}
		else if ( $input_type == constant('FORM_INPUT_TYPE_LISTBOX') || $input_type == constant('FORM_INPUT_TYPE_LISTBOX_ARRAY')) {

			$javascript .= "if ( {$this->_JS_var_cur_input} = {$this->_JS_function_form_input_by_name}('{$input_name}') ) {\n";

			if ( $input_is_array ) {
				$input_index_ref = '[j]';
				$javascript .=  "{$tabs}for ( j = 0; j < {$this->_JS_var_cur_input}.length; j++ ) {\n";
			}
			else {
				$input_index_ref = '';
			}

			//if ( {$this->_JS_var_cur_input} = {$this->_JS_function_form_input_by_name}('{$input_name}') ) {
				
			$javascript .= "
						if ( {$this->_JS_var_cur_input}.options.length > 0 ) {
							if ( {$negation}({$this->_JS_var_cur_input}{$input_index_ref}.options[{$this->_JS_var_cur_input}{$input_index_ref}.selectedIndex].value == {$value_quote}{$value_compare}{$value_quote}) ) {
					";
			
		}
		else {
			trigger_error( "Invalid type FORM_INPUT_TYPE constant for input name: {$input_name} in InputForm", E_USER_WARNING );
		}
		
	
		return $javascript;

	}

	public function js_alert_code_by_input( $cur_input, $options = array() ) {

		$input_name 	  = $this->get_input_name( $cur_input );
		$input_type	  	  = $this->get_input_type( $cur_input );
		$friendly_name	  = $this->friendly_name_by_input_name( $input_name );

		$msg = $this->message_caption_by_input_type($input_type) . $friendly_name; 

		return $this->js_alert_code($msg, $options);

	}

	function js_alert_code( $msg, $options = array() ) {

		if ( !isset($options['parse']) || $options['parse'] == true ) {
			$msg = $this->format_js_message($msg);
		}

		if ( !isset($options['quote']) || $options['quote'] == true ) {
			$msg = '\'' . $msg . '\''; 
		}
		
		if (class_exists('Config',false) && Config::Get('form.alert_callback') ) {
			return Config::Get('form.alert_callback') . "({$msg});";
		}
		else {
			return "alert( {$msg} );";
		}
		
		
	}

	function generate_dependency_script( $dependencies, $tabs="\t\t" ) {

		if ( count($dependencies) ) {

			$dependency_condition = '';
			$javascript = '';

			foreach ( $dependencies as $dependency_name => $cur_dependency ) {
		
				$dependency_target   = $cur_dependency[$this->dependency_target_key];
				$dependency_type     = $cur_dependency[$this->dependency_type_key];
				$dependency_value    = $cur_dependency[$this->dependency_value_key];			
				$dependency_negation = $cur_dependency[$this->dependency_negate_key];
	
				if ( !$dependency_type ) {
					LL::raise_error( "Dependency '{$dependency_name}' for '{$dependency_target}' has no type set. Skipping.", '',  __LINE__, $this->_Error_level_warn );
					continue;
				}

				if ( isset($cur_dependency['conjunction']) ) {
					if ( $conjunction = $cur_dependency['conjunction'] ) {

						switch( $conjunction ) {
							case 'or':
								$dependency_condition .= ' || ';
								break;
							case 'and':
								$dependency_condition .= ' && ';
								break;
							case 'default':
								$dependency_condition .= " {$conjunction} ";
						}
					}
				}

				//if ( !$dependency_value ) {

				$dependency_condition = $this->generate_input_if_statement( $dependency_name, $dependency_type, $dependency_negation, $dependency_value, 1 );
				$javascript .= $tabs . $dependency_condition;
							
			}
			
		} //if dependencies

		return $javascript;
	}

	function regenerate_validation_script() {

		$this->generate_script_called = 0;
		$this->generate_validation_script();

	}

	function get_false_validation_hook_script() {

		return "if ( typeof({$this->validation_script_hook_false}) == 'function' ) { {$this->validation_script_hook_false}(); }\n"; 
	}

	function generate_validation_script() {

		if ( !$this->form_name ) { 
			LL::raise_error( 'generate_validation_script called without form_name set.', '', 'generate_validation_script: ' . __LINE__, $this->_Error_level_warn );
			return false;
		}

		$validation_script = '';
		$overall_closing_brace_count = 0;
		$this->generate_script_called = 1;

		$false_validation_hook_script = $this->get_false_validation_hook_script();
		$true_validation_hook_script = "if ( typeof({$this->validation_script_hook_true}) == 'function' ) { if ( {$this->validation_script_hook_true}() ) { return true; } else { {$false_validation_hook_script}\n return false; } }\n";

		if ( count($this->required_inputs) ) {

			list( $removes_all_script, $add_braces ) = $this->generate_remove_requirement_script();


			$validation_script .= $removes_all_script;
			$overall_closing_brace_count += $add_braces;

			if ( count($this->_confirm_checkboxes) ) {
				foreach ( $this->_confirm_checkboxes as $checkbox_info ) {

					$checkbox_name = $checkbox_info[$this->input_name_key];

					if ( !$this->is_requirement_remover($checkbox_name) ) {
						$validation_script .= $this->generate_checkbox_confirmation_script($checkbox_name) . "\n";
					}
				}
			}


			foreach( $this->required_inputs as $cur_input ) {


				$num_braces 	  = 0;
				$add_braces	  = 0;
				$extra_tabs	  = '';
				$input_name 	  = $this->get_input_name( $cur_input );
				$input_type	  	  = $this->get_input_type( $cur_input );
				$input_negate	  = $this->get_input_negation($cur_input); // ) ? 0 : 1; //Note: things seem backwards here for a reason.
				$input_is_array   = $this->get_input_setup_value( $input_name, $this->_Key_form_input_is_array );


				//
				// If this input has been set for no script valdidation, just continue.
				//
                                if ( $this->input_ignored_in_script($input_name) ) {
                                        continue;
                                }

				if ( $this->zero_is_null ) {
					$input_negate	  = ( $this->get_input_negation($cur_input) ) ? 0 : 1; //Note: things seem backwards here for a reason.
				}

				$removes_all_script = '';


				if ( !$friendly_name = $this->get_friendly_name($input_name) ) {
					$friendly_name = $input_name;
				}

				if ( $dependencies = $this->get_dependencies($cur_input) ) {
					$validation_script .= $this->generate_dependency_script( $dependencies );
					for ( $j=0; $j < $this->count_dependencies($cur_input); $j++ ) {
						$extra_tabs .= "\t";						
					}
				}

				

				// --------------------
				// Required Text Field
				//---------------------
				if ( $input_type == constant('FORM_INPUT_TYPE_TEXT') OR $input_type == constant('FORM_INPUT_TYPE_FILE') 
					|| $input_type == constant('FORM_INPUT_TYPE_TEXTBOX_ARRAY') ) {
					
				
					$if_condition = $this->generate_input_if_statement( $input_name, $input_type, $input_negate );
					$validation_script .= "{$extra_tabs}\t\t{$if_condition}\n";


					$validation_script .= $this->js_code_invalid_input_action( $cur_input );

					//$validation_script .= "{$extra_tabs}\t\t\t{$false_validation_hook_script}\n";
					//$validation_script .= "{$extra_tabs}\t\t\treturn false;\n";

				}		

				//------------------------
				// Required Radio Button
				//------------------------
				else if ( $input_type == constant('FORM_INPUT_TYPE_RADIO') ) {

					$validation_script .= $this->generate_input_if_statement( $input_name, $input_type, $input_negate );

					$validation_script .= $this->js_code_invalid_input_action( $cur_input );

					//$validation_script .= "\t\t\talert('" . $this->format_js_message($this->missing_radio_caption) 
					//				. $this->format_js_message($friendly_name) . "');\n";
					//$validation_script .= "{$extra_tabs}\t\t\t{$false_validation_hook_script}\n";
					//$validation_script .= "\t\t\treturn false;\n";
					
				}

				//-------------------------
				// Required Checkbox
				//------------------------
				else if ( $input_type == constant('FORM_INPUT_TYPE_CHECKBOX') ) {

					$if_condition = $this->generate_input_if_statement( $input_name, $input_type, $input_negate );

					$validation_script .= "\t\t" . $if_condition;
					$validation_script .= $this->js_code_invalid_input_action( $cur_input );
					 
					//$validation_script .= "\t\t\talert('" . $this->format_js_message($this->missing_checkbox_caption) 
					//				. $this->format_js_message($friendly_name) . "');\n";

					//$validation_script .= "{$extra_tabs}\t\t\t{$false_validation_hook_script}\n";
					//$validation_script .= "\t\t\treturn false;\n";
				}
				//-------------------------
				// Required Listbox
				//-----------------------
				else if ( $input_type == constant('FORM_INPUT_TYPE_LISTBOX') || $input_type == constant('FORM_INPUT_TYPE_LISTBOX_ARRAY') ) {

					if ( $input_is_array ) {
						$alert_caption = "{$this->missing_dropdown_caption}{$this->missing_array_caption} ";
						$input_index_ref = $this->_JS_input_array_index_reference;
					}
					else { 
						$alert_caption = $this->missing_dropdown_caption;				
						$input_index_ref = null;
					}

					$if_condition = $this->generate_input_if_statement( $input_name, $input_type, $input_negate );
					$validation_script .= "{$extra_tabs}\t\t{$if_condition}\n";

					$validation_script .= $this->js_code_invalid_input_action( $cur_input );

					/*
					$validation_script .= "{$extra_tabs}\t\t\talert('" . $this->format_js_message($alert_caption) 
								. $this->format_js_message($friendly_name) . "');\n";

					if ( $this->auto_refocus ) {
						$validation_script .= "if ( typeof({$this->_JS_var_cur_input}.focus) != 'undefined' ) {
										try { {$this->_JS_var_cur_input}.focus(); } catch(e) {} 
								       }\n";
					}

					$validation_script .= "{$extra_tabs}\t\t\t{$false_validation_hook_script}\n";
					$validation_script .= "{$extra_tabs}\t\t\treturn false;\n";
					*/
				}


				
				$num_braces = $this->count_closing_if_braces_by_input_type( $input_type );
	                        $num_braces += $this->count_closing_dependency_braces( $cur_input );
				//echo "Num braces for $input_name: $num_braces<BR/>";
				$validation_script .= $this->generate_closing_if_braces( $num_braces, $extra_tabs );

				$validation_script .= "//--------end {$input_name}-------//\n\n";


			}

		}


		if ( $validation_script ) {

			//-----------------------------------------------------
			// Check for any inputs that eliminate all requirements
			//-----------------------------------------------------
			list( $removes_all_script, $add_braces ) = $this->generate_remove_all_requirement_script();
			
			$validation_script = $removes_all_script . $validation_script;
			$overall_closing_brace_count += $add_braces;
			
			if ( !$this->show_javascript_errors ) {
				$validation_script = "\ttry {\n" . $validation_script;
			}

			$script_start = '<script language="javascript" type="text/javascript">';
			$script_start .= "\nfunction {$this->_JS_function_form_input_by_name}(name, index) { 

                     try {

							var input_ret;
							var input_collection = document.getElementsByName(name);

							if ( !isNaN(index) ) {
								input_ret = input_collection[index];
							}
							else {
								if ( input_collection.length > 1 ) {
									input_ret = input_collection;
								}
								else {
									input_ret = input_collection[0];
								}
							}

							return input_ret;
                      }
                      catch (e) {
                            return null;
                      }

					}\n\n";

			$function_start = "function validate_{$this->form_name}() {\n\n";
			$function_start .= "\tvar {$this->_JS_var_cur_input};\n";
			$function_start .= "\tvar input_messages = new Array();\n\n";
							

			$validation_script = $function_start . $validation_script;
			$validation_script = $script_start . $validation_script;

			$validation_script .= $this->generate_closing_if_braces($overall_closing_brace_count);

			if ( method_exists($this, 'js_validation_hook') ) {
				$validation_script .= $this->js_validation_hook() . "\n";
			}

			if ( $this->messages_grouped ) {
				
				$false_hook = $this->get_false_validation_hook_script();
				
				$msg_code = $this->js_alert_code( "input_messages.join('{$this->alert_delimiter}')", array('parse' => false, 'quote' => false) );
				$validation_script .= "if ( input_messages.length > 0 ) {
											{$msg_code}
											{$false_hook}						               
											return false;
										}";

			}

			//
			// If we have another Javascript function to call on true, call it now.
			//
			$validation_script .= $true_validation_hook_script;


			if ( $this->disable_submit_button ) {
				if ( $this->submit_button_name ) {
					if ( $this->submit_disabled_caption ) {
						$this->submit_disabled_caption = str_replace('\'', '\\\'', $this->submit_disabled_caption);
						$validation_script .= "\n\t\tdocument.{$this->form_name}.{$this->submit_button_name}.value='{$this->submit_disabled_caption}';\n";
					}

					$validation_script .= "\n\t\tdocument.{$this->form_name}.{$this->submit_button_name}.disabled=true;\n";
				}
			}


			if ( !$this->show_javascript_errors ) {
				$validation_script .= "\t}\n";
				$validation_script .= "\tcatch(e) {\n\t\treturn true;\n\t}\n";
			}


			$validation_script .= "\treturn true;\n";
			$validation_script .= "}\n";
			

			$validation_script .= "</SCRIPT>\n";


			$this->validation_script = $validation_script;

			if ( $this->save_javascript ) {
	
				if ( !$this->save_validation_script() ) {
					LL::raise_error('Error saving javascript.', '', 'generate_validation_script: ' . __LINE__, $this->_Error_level_warn );
					$this->_remove_js_lockfile();
				}
			}

		}
		else {

			$no_script = "function validate_{$this->form_name}() {\n";
			$no_script .= $true_validation_hook_script;
			$no_script .= "return true; }\n";

			if ( $this->save_javascript ) {


				if ( !$this->save_validation_script($no_script) ) {
					LL::raise_error('Error saving javascript.', '', 'generate_validation_script: ' . __LINE__, $this->_Error_level_warn );
					$this->_remove_js_lockfile();
				}
			}

			$validation_script = $no_script;
			

		}


		return $validation_script;
	

	}

	public function js_code_invalid_input_action( $cur_input, $options = array() ) {

		$script = '';
		$msg = null;
		
		if ( isset($options['message']) ) {
			$msg = $options['message'];
		}
		else {
			$input_name 	  = $this->get_input_name( $cur_input );
			$input_type	  	  = $this->get_input_type( $cur_input );
			$friendly_name	  = $this->friendly_name_by_input_name( $input_name );
			
			$msg = $this->format_js_message($this->message_caption_by_input_type($input_type) . $friendly_name);
			
		}

		if ( $this->messages_grouped ) {
			
			$msg = $this->format_js_message($msg);
			$script .= "input_messages.push('{$msg}');\n";
			
		}
		else {
			$script .= $this->js_alert_code($msg) . "\n";
		
			if ( $this->auto_refocus ) {
				$script .= "if ( typeof({$this->_JS_var_cur_input}.focus) != 'undefined' ) {
							try { {$this->_JS_var_cur_input}.focus(); } catch(e) {} 
					       }\n";
			}

		
			$false_hook = $this->get_false_validation_hook_script();
			$script .= "\n{$false_hook}\n";
		
			$script .= "return false;";
		}
		
		return $script;
	}

	function is_confirmation_required( $input_name ) {

		if ( count($this->_confirm_checkboxes) ) {
			foreach( $this->_confirm_checkboxes as $cur_box ) {
				 if ( $cur_box[$this->input_name_key] == $input_name ) {
					return true;
				}
			}
		}

		return false;

	}

	function is_requirement_remover ( $input_name ) {

                if ( count($this->_removes_requirements_arr) ) {

                        foreach( $this->_removes_requirements_arr as $cur_remover ) {

                                $remover_name   = $cur_remover[$this->input_name_key];

				if ( $remover_name == $input_name ) {
					return true;
				}
			}
		}

		return false;

	}


	public function save_validation_script( $script = '' ) {

		try { 
			$script = ( $script ) ? $script : $this->validation_script;
	
			if ( !$this->generate_script_called ) {
				$script = ( $script ) ? $script : $this->generate_validation_script();
			}
	
			if ( $script ) {
	
				if ( !$this->javascript_directory || !is_dir($this->javascript_directory) ) {
					trigger_error( 'nonexistent or invalid form javascript_directory set. Define FORM_JAVASCRIPT_BASE_PATH or set $this->javascript_directory', E_USER_WARNING );
					return false;
				}
				else {
					$filename     = $this->get_javascript_filename();
					$file_path    = $this->javascript_directory . DIRECTORY_SEPARATOR . $filename;
	
					if ( $this->javascript_file_expired() OR !file_exists($file_path) OR $this->force_javascript_save ) {
						
						LL::require_class('File/FilePath');
	
						$save_error_message = 'Error writing javascript file';
						$script = preg_replace('/<\s*script\s+language\s*=\s*["\']*Javascript[^>]+\s*>/i', '', $script);
						$script = preg_replace('/<\s*\/script\s*>/i', '', $script);
						
						ignore_user_abort(true);
	
						$tmp_file_path   = FilePath::Append_slash($this->javascript_directory) . uniqid('FF_');
			
						if ( !($tmp_fp = fopen($tmp_file_path, "w+")) ) {
							ignore_user_abort(false);
							throw new FileInaccessibleException("Could not open temp file: {$tmp_file_path}" );
						}
	
						if ( !fwrite($tmp_fp, $script) ) {
							ignore_user_abort(false);
							@fclose($tmp_fp);
							throw new FileInaccessibleException("Could not write to temp file {$tmp_file_path}" );
						}
	
						@fclose($tmp_fp);
	
						if ( !@rename($tmp_file_path, $file_path) ) {
			        		// Delete the file if it already existed (this is needed on Windows,
					        // because it cannot overwrite files with rename() 
							@unlink( $file_path );
							if ( !@rename($tmp_file_path, $file_path) ) {
								ignore_user_abort(false);
								throw new FileInaccessibleException( "Could not rename temp file {$tmp_file_path}"  );
							}
						}
						
	                    if ( $this->_Chmod_javascript_file ) {
	                    	chmod($file_path, $this->_Chmod_javascript_file);
	                    }					
					}
				}
			}
	
			@unlink($tmp_file_path);
	
	
			ignore_user_abort(false);
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_javascript_chmod( $chmod ) {
		
		$this->_Chmod_javascript_file = $chmod;
		
	}

	function javascript_file_expired() {

		$filename     = $this->get_javascript_filename();
		$file_path    = $this->javascript_directory . DIRECTORY_SEPARATOR . $filename;

		if ( file_exists($file_path) ) {
			$script_mtime = filemtime($_SERVER['SCRIPT_FILENAME']);
			$js_mtime = filemtime( $file_path );

			if ( $script_mtime > $js_mtime ) {
				return true;
			}
		}	
		
		return false;		

	}

	function validation_script_exists() {

		if ( $this->javascript_file_expired() OR !file_exists($this->get_javascript_filename()) OR $this->force_javascript_save ) {

			return false;
		}

		return true;

	}

	function get_validation_filename() {

		return $this->get_validation_link();

	}

	function get_js_validation_filename() {

		return $this->get_validation_link();

	}

	function get_validation_link() {

		if ( !$this->validation_script_exists() OR $this->force_validation_regenerate ) {

			$this->generate_validation_script();

			if ( !$this->save_javascript ) {

				if ( !$this->save_validation_script() ) {
					LL::raise_error('Error saving javascript.', '', 'generate_validation_script: ' . __LINE__, $this->_Error_level_warn );
					$this->_remove_js_lockfile();
				}

			}
		}

		return $this->get_javascript_filename();
		

	}


	function _remove_js_lockfile() {

		try { 
			if ( !$this->javascript_directory ) {
				throw new MissingParameterException('remove_js_lockfile called without javascript_directory set. Define FORM_JAVASCRIPT_DIRECTORY or set $this->javascript_directory' );
			}
			else {
				$lockfile = $this->javascript_directory . DIRECTORY_SEPARATOR . $this->get_javascript_filename() . '.lock';
				if ( file_exists($lockfile) ) {
					if ( !unlink($lockfile) ) {
						throw new FileInaccessibleException( "Couldn't delete lock file: $lockfile" );
					}
				}
	
			}
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function get_javascript_filename() {

		if ( $this->validation_filename ) {
			return $this->validation_filename;
		}
		else {

			$filename  = basename($_SERVER['PHP_SELF'], ".{$this->_php_extension}");

            if ( !$filename ) {
	            $filename = 'index';
            }

			$filename  = $filename . '-' . $this->form_name . '.js';
		
			return $filename;		
		}

	}

	function get_javascript_filepath() {

		return $this->javascript_directory . DIRECTORY_SEPARATOR . $this->get_javascript_filename();
	}

	function get_validation_script_linkpath() {

		return $this->javascript_link . '/' . $this->get_javascript_filename();

	}

	function validation_script_link() {

		return $this->javascript_link . '/' . $this->get_javascript_filename();

	}


	function generate_checkbox_confirmation_script( $input_name, $tabs = "\t\t" ) {
		
		$confirmation_message = $this->_confirm_checkboxes[$input_name][$this->input_message_key];
		$input_name 	      = $this->_confirm_checkboxes[$input_name][$this->input_name_key];
		$negation 	      = $this->_confirm_checkboxes[$input_name][$this->input_negate_key];

		$javascript = "
				if ( {$this->_JS_var_cur_input} = {$this->_JS_function_form_input_by_name}('{$input_name}') ) {
					if ( {$negation}({$this->_JS_var_cur_input}.checked == true) ) {
						if ( !confirm('{$confirmation_message}') ) {
							$this->_JS_var_cur_input.checked = false;
							return false;
						}
					}
				}";

		/*
		$javascript = "{$tabs}if ( {$negation}(document.{$this->form_name}.{$input_name}.checked == true) ) {\n";
		$javascript .= "{$tabs}\tif ( !confirm('{$confirmation_message}') ) {\n";
		$javascript .= "{$tabs}\t\tdocument.{$this->form_name}.{$input_name}.checked = false;\n";
		$javascript .= "{$tabs}\t\treturn false;\n";
		$javascript .= "{$tabs}\t}\n";
		$javascript .= "{$tabs}}\n";
		*/
		
		return $javascript;

	}

	function generate_closing_if_braces( $brace_count, $tab_count = '' ) {

		$brace_string = '';
		$tabs = '';
		if ( is_numeric($tab_count) ) {
			for ( $j = 0; $j < $tab_count; $j++ ) {
				$tabs .= "\t";
			}
		}

		for ( $j = 0; $j < $brace_count; $j++ ) {

			$brace_string .= $tabs . '}' . "\n";
			$tabs = preg_replace("#^\\t#", '', $tabs);
		}

		return $brace_string;

	}

        function count_closing_if_braces_by_input_type( $input_type ) {

		$num_braces = 0;

 		if ( $input_type == constant('FORM_INPUT_TYPE_TEXT') OR $input_type == constant('FORM_INPUT_TYPE_FILE') ) {
			$num_braces += 2;
                }  
		else if ( $input_type == constant('FORM_INPUT_TYPE_TEXTBOX_ARRAY') ) {
			$num_braces += 3;
		}
                else if ( $input_type == constant('FORM_INPUT_TYPE_RADIO') ) {
                        $num_braces += 1;
                }
		else if ( $input_type == constant('FORM_INPUT_TYPE_CHECKBOX') ) {
			$num_braces += 2;
		}
		else if ( $input_type == constant('FORM_INPUT_TYPE_LISTBOX') ) {
			$num_braces += 3;
		}
		else if ( $input_type == constant('FORM_INPUT_TYPE_LISTBOX_ARRAY') ) {
			$num_braces += 4;
		}
        
                return $num_braces;
        }

	function count_closing_dependency_braces( $input_array ) {

		$num_braces = 0;
	    if ($dependencies = $this->get_dependencies($input_array) ) {
        	foreach ( $dependencies as $dependency_name => $dependency_options ) {

                $dependency_type = $dependency_options[$this->dependency_type_key];
				$num_braces += $this->count_closing_if_braces_by_input_type( $dependency_type );

                if ( isset($dependency_options['dep_val']) && $dependency_type == constant('FORM_INPUT_TYPE_RADIO') ) {
                	$num_braces++;
                }

			}
		}

		return $num_braces;

	}

	function value_marked_as_missing( $input_name ) {
		
		return in_array( $input_name, $this->missing_values );

	}

	function set_missing_value( $input_name, $message = '' ) {

		$this->missing_values[] = $input_name;
		$this->num_missing_values++;

		return true;
	}

	function set_general_message( $message ) {
	
		$this->_general_messages[] = LL::Translate($message);

	}

	function get_general_messages( $newline = '<BR />' ) {

		$g_msgs = '';

		if ( count($this->_general_messages) ) {
			foreach($this->_general_messages as $cur_message) {
				$g_msgs .= $cur_message . $newline;
			}
		}

		return $g_msgs;

	}

	function missing_input_message( $message_heading = '' ) {

		return $this->generate_missing_input_message( $message_heading ); 

	}

	function get_missing_input_message( $message_heading = '' ) {

		return $this->generate_missing_input_message( $message_heading ); 
	

	}

	function generate_missing_value_message( $message_heading = '') {

		return $this->generate_missing_input_message( $message_heading );
	}

	function generate_missing_input_message( $message_heading = '', $newline = '<BR />' ) {

		$missing_values_message = '';
		$this->missing_input_message = '';

		$missing_values = $this->get_missing_values();


		if ( count($missing_values) ) {

			foreach( $missing_values as $input_name ) {
	
				//$input_message = ( $extra_message = $this->get_input_extras_message($input_name) ) ? $extra_message : $this->get_friendly_name($input_name);

				$missing_values_message .= ( $missing_values_message ) ? $newline : '';
				$missing_values_message .= $this->get_friendly_name($input_name);
			}

			$missing_values_message = ( !$message_heading ) ? 'The following fields were not filled out completely:<BR/>' . $missing_values_message : $message_heading . $missing_values_message;

		}

		//
		// The following is deprecated, but here for backward compatibility.
		// 'general messages' are included in missing value messages because
		// the old way of determining bad constraints was to check missing_input_message(). 
		// Instead, you should use messages_generated()
		//
		//
		//if ( $g_msgs = $this->get_general_messages() ) {
		//	$missing_values_message .= ( $missing_values_message ) ? $newline . $g_msgs : $g_msgs;
		//}

		$this->missing_input_message = $missing_values_message;

		return $missing_values_message;


	}

    function get_missing_input_alert_by_name( $input_name ) {
                                
	      if ( !$friendly_name = $this->get_friendly_name($input_name) ) {
           $friendly_name = $input_name;
          }
                                 
          $input_type = $this->required_inputs[$input_name][$this->input_type_key];
       
       	return $this->message_caption_by_input_type($input_type) . $friendly_name;

    }

	function messages_generated() {

		return $this->get_messages();

		/*
		if ( count($this->get_missing_values()) > 0 ) {
			return true;
		}

		if ( count($this->get_general_messages()) > 0 ) {
			echo "SDF";

			return true;
		}
		
		if ( method_exists($this, 'invalid_input_message') ) {
			if ( $this->invalid_input_message() ) {
				return true;
			}
		}

		return false;
		*/
	}

	function get_messages( $newline = '<BR />' ) {

		$all_messages = '';
		
		$all_messages .= ( $msgs = $this->get_general_messages() ) ? $msgs . $newline : '';

		if ( method_exists($this, 'invalid_input_message') ) {

			//
			// invalid_input_message() will bubble up and call
			// missing_input_message(), so we don't need to call it if 
			// invalid_input_message() is being called.
			//

			if ( $msgs = $this->invalid_input_message() ) {
				$all_messages .= $msgs;
			}
		}
		else {
			$all_messages = ( $msgs = $this->get_missing_input_message() ) ? $msgs . $newline : '';
		}

		$newline = preg_quote($newline, '/');
		$all_messages = preg_replace("/{$newline}\$/", '', $all_messages);

		return $all_messages;


	}

	function has_value ($var) {
	
		if ( is_scalar($var) ) {

		        if ( "$var" != "") {
        		        return true;
	        	}
		}
		elseif ( is_array($var) ) {
			if ( count($var) > 0 ) {
				return true;
			}	
		}

	        return false;
	}

	function parse_form_input( $input_value, $parse_flags = 0 ) {
 
		return $this->parse_value($input_value, $parse_flags );

		/*
	        if ( is_array($input_value) ) {
        	        return $this->do_gpc_parse($input_value);
	        }
        	else {
                
	                if ( $this->_alt_dataset || (!get_magic_quotes_gpc() AND !defined('GPC_PARSED')) ) {
        	                $input_value = addslashes($input_value);
	                }  
         
        	        if ( !defined('ALLOW_INPUT_PIPES') ) {
	                        $input_value = str_replace('|', '', $input_value);
        	        }

                	return $input_value;
	        }
        
        	return false;
		*/
	}
        
	function do_gpc_parse( $cur_arr ) {
                
	                if ( is_array($cur_arr) ) {
        	                while ( list($k, $v) = each ($cur_arr) ) {
        
                	                if ( is_array($cur_arr[$k]) ) {
        
	                                        while ( list($inner_k, $inner_v) = each ($cur_arr[$k]) ) {
         
        	                                        $cur_arr[$k][$inner_k] = $this->parse_form_input($inner_v);
                                                
                	                                //-------------------------------------------------
                        	                        // If register globals is on, parse $$key value too
                                	                //-------------------------------------------------
                                        	        global $$k;
                                                	if ( isset($$k[$inner_k]) ) $$k[$inner_k] = $this->parse_form_input( $inner_v );
	                                        }
        	         
                	                        @reset($cur_arr[$k]);
                        	        }     
                                	else {
	                                        $cur_arr[$k] = $this->parse_form_input($v);
        	                                global $$k;
                	                        if ( isset($$k) ) $$k = $this->parse_form_input( $v );
                        	        }
	                        }
        	                @reset( $cur_arr );
	                }
			else {
				return $this->parse_form_input( $cur_arr );
			}                

        	        return $cur_arr;
	}             

	function reparse_post_data( $parse_flags = 0 ) {


		if ( count($_POST) > 0 ) {

			foreach( $_POST as $post_key => $post_val ) {
				if ( is_array($post_val) ) {
					if ( count($post_val) > 0 ) {
						foreach ( $post_val as $inner_post_val ) {
							$post_val[$inner_post_val] = $this->unparse_value($post_val[$inner_post_val], $parse_flags);
							//$post_val[$inner_post_val] = ( get_magic_quotes_gpc() ) ? stripslashes($post_val[$inner_post_val]) : $post_val[$inner_post_val];
						}
					}
				}
				else {
					//$post_val = ( get_magic_quotes_gpc() ) ? stripslashes($post_val) : $post_val;
					$post_val = $this->unparse_value( $post_val, $parse_flags );
				}

				$_POST[$post_key] = $post_val;
			}
		}

		return true;

	}

	function html_decode( $value ) {

	   return strtr( $value, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));

	}

	function &repopulate_template( &$template, $clear_field = '', $input_array = '' ) {

		$this->template_ref =& $template;
		return $this->repopulate_form( $clear_field, 1, $input_array );

	}

	function &repopulate_form( $clear_field = '', $use_template, $input_array = '' ) {

	       $true_ret   = true;
	       $parse_flags = ( $this->repopulate_htmlize ) ? FORM_PARSE_HTML_CHARS : 0;
	       //$this->reparse_post_data( $parse_flags );

	       $input_array = ( !$input_array ) ? $this->get_dataset() : $input_array;

		
		if ( $use_template ) {

			$template =& $this->template_ref;

			if ( is_array($input_array) ) {
				foreach ( $input_array as $key=>$value ) {
					
					if ( is_array($value) ) {
						foreach( $value as $arr_key => $arr_val ) {
							$arr_val = $this->parse_form_text($arr_val);
		
							$template_arr[$arr_key] = $arr_val;

							
							//$template->add_param( "{$key}[{$arr_key}]", $arr_val );
						}

						$template->add_param($key, $template_arr);
					}
					else {
						$value = $this->parse_form_text($value);

						if ( $clear_field == $this->clear_all_inputs ) {
							$template->add_param( $key, '' );
						}
						else {

							if ( is_array($clear_field) ) {
								if ( !in_array($key, $clear_field) ) {
									$template->add_param( $key, $value );
								}
								else {
									$template->add_param( $key, '' );
								}
							}
							else {
								if ( $key != $clear_field ) {
									$template->add_param( $key, $value );
								}
								else {
									$template->add_param( $key, '' );
								}
					        	}
						}
					}
				}
			}
		}
		else {

			foreach( $input_array as $key => $value ) {

				$value = $this->parse_form_text($value);

				if ( $clear_field == $this->clear_all_inputs ) {
					$$key = '';			
				}

				if ( is_array($clear_field) ) {
					if ( !in_array($key, $clear_field) ) {
						$$key = $value;
					}
					else {
						$$key = '';
					}
				}
				else {
					if ( $key != $clear_field ) {
						$$key = $value;
					}
					else {
						$$key = '';
					}
				}

				if ( $$key ) {
					$parse_flags = ( $this->repopulate_htmlize ) ? FORM_PARSE_HTML_CHARS : 0;
					$$key = $this->unparse_value( $$key, $parse_flags );
				}
			}
		}

				
		return $true_ret;
	}


        function repopulate_template_from_db( &$template, $input_array, $name_map = '', $parse_data = 1) {

                return $this->repopulate_template_from_db_arr( $template, $input_array, $name_map, $parse_data );
        }

	function repopulate_template_from_db_arr( &$template, $input_array, $name_map = '', $parse_data = 1) {

		$this->template_ref =& $template;
		return $this->repopulate_from_db( $input_array, 1, $name_map, $parse_data );

	}

	function &apply_name_map( &$which_array, $name_map ) {

		if ( $which_array && $name_map && count($which_array) AND count($name_map) ) {

			foreach( $name_map as $db_name => $form_name ) {

				$which_array[$form_name] = isset( $which_array[$db_name] ) ? $which_array[$db_name] : null;
				
				if ( $form_name != $db_name ) {
					unset($which_array[$db_name]);
				}
			}

		}

		return $which_array;

	}

	function repopulate_template_from_session( &$template, $session = '', $name_map = '' ) {

		$this->template_ref =& $template;
		
		$input_array = ( is_object($session) ) ? $session->session_to_assoc_arr() : $_SESSION;

		$input_array = $this->apply_name_map( $input_array, $name_map ); //deprecated
		$input_array = $this->apply_name_map( $input_array, $this->_Map_input_field_name );

		return $this->repopulate_from_array( $input_array, 1 );

	}

	function &repopulate_from_db( $input_array, $use_template = 0, $name_map = '', $parse_data = 1 ) {
				
		$my_location = $_SERVER['PHP_SELF'] . ' -repopulate_from_db()';

		$input_array = $this->apply_name_map( $input_array, $name_map );
		$input_array = $this->apply_name_map( $input_array, $this->_Map_input_field_name );

		if ( $this->db_studly_convert ) {
			if ( function_exists('camel_case_to_underscore') ) {
				$input_array = call_user_func('camel_case_to_underscore', $input_array);
			}
			else {
				trigger_error( 'db_studly_convert is set, but I cannot find function camel_case_to_underscore()', E_USER_WARNING );
			}
		}

		return $this->repopulate_from_array( $input_array, $use_template  );

	}

	function parse_form_text( $value, $parse_flags = 0 ) {

		$parse_flags = $parse_flags | FORM_PARSE_HTML_CHARS;

	        if ( is_array($value) AND count($value) ) {
        	        foreach( $value as $cur_key => $cur_value ) {
					$value[$cur_key] = $this->unparse_value($value[$cur_key], $parse_flags );
	                }
			return $value;

        	}

		return $this->unparse_value( $value, $parse_flags );

	}

	function parse_form_textbox( $value ) {

		return $this->parse_form_text($value);

	}


	function &repopulate_template_from_array( &$template, $input_array ) {

		$this->template_ref =& $template;
		return $this->repopulate_from_array( $input_array, 1 );

	}

	function &repopulate_from_array( $input_array, $use_template = 0 ) {

		if ( $use_template ) {

			return $this->repopulate_template( $this->template_ref, '', $input_array);
		}
		else {
			return $this->repopulate_form( '', '', $input_array);
		}
	}


	function set_required_field( $input_name ) {
	//VERY DEPRECATED alias to set_required_textbox (doesn't support dependencies)

		return $this->set_required_textbox($input_name);

	}

	//
	//function set_required_email( $input_name, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {
	//
	//	$this->set_required_input( $input_name, $this->input_type_text, $depends_on, $dependency_type, $dependency_value );
	//	$this->set_email_input( $input_name );
	//
	//		return true;
	//
	//}

	function set_required_checkbox ( $checkbox_name, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {

		return $this->set_required_input( $checkbox_name, constant('FORM_INPUT_TYPE_CHECKBOX'), $depends_on, $dependency_type, $dependency_value );		

	}

	function set_required_file_input ( $input_name, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {

		return $this->set_required_input( $input_name, constant('FORM_INPUT_TYPE_FILE'), $depends_on, $dependency_type, $dependency_value );		

	}

	function set_required_radio ( $radio_name, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {

		return $this->set_required_input( $radio_name, constant('FORM_INPUT_TYPE_RADIO'), $depends_on, $dependency_type, $dependency_value );		

	}

	function set_required_dropdown( $dropdown_name ) {

		return $this->set_required_listbox($dropdown_name);

	}

	function set_required_listbox ( $listbox_name, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {

		return $this->set_required_input( $listbox_name, constant('FORM_INPUT_TYPE_LISTBOX'), $depends_on, $dependency_type, $dependency_value );		

	}
	
	function set_required_dropdown_arr( $input_name ) { 

		return $this->set_required_listbox_arr( $input_name );

	}

	function set_required_listbox_arr( $input_name ) {

		$input_info = array();
		$input_info[$this->_Key_form_input_type]     = constant('FORM_INPUT_TYPE_LISTBOX_ARRAY');
		$input_info[$this->_Key_form_input_is_array] = true;

		return $this->set_required_input( $input_name, $input_info );

	}

	function set_required_textbox( $textbox_name, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {

		return $this->set_required_input( $textbox_name, constant('FORM_INPUT_TYPE_TEXT'), $depends_on, $dependency_type, $dependency_value );
		

	}

	function set_required_text( $textbox_name, $depends_on = '', $dependency_type = '', $dependency_value = '') {

		return $this->set_required_textbox( $textbox_name, $depends_on, $dependency_type, $dependency_value);

	}

	function set_required_textbox_arr( $input_name ) {

		$input_info = array();
		$input_info[$this->_Key_form_input_type]     = constant('FORM_INPUT_TYPE_TEXTBOX_ARRAY');
		$input_info[$this->_Key_form_input_is_array] = true;

		return $this->set_required_input( $input_name, $input_info );

	}

	function set_required_hidden( $hidden_name, $depends_on = '', $dependency_type = '', $dependency_value = '') {

		return $this->set_required_textbox( $hidden_name, $depends_on, $dependency_type, $dependency_value);
		
	}

	function set_required_input( $input_name, $input_info = null, $depends_on = '', $dependency_type = '', $dependency_value = '' ) {

		if ( $input_info && !is_array($input_info) ) {
			//
			// This is a deprecated, but still supported call to this method
			// where the second parameter was the input type. 
			//
			$input_type = $input_info;
			$input_is_array = 0;
		}
		else {
			$input_type     = $input_info[$this->_Key_form_input_type];
			$input_is_array = ( isset($input_info[$this->_Key_form_input_is_array]) && $input_info[$this->_Key_form_input_is_array] ) ? 1 : 0;
		}

		list( $negation, $input_name ) = $this->parse_negated_input_name($input_name);

		$this->required_inputs[$input_name][$this->input_name_key]           = $input_name;
		$this->required_inputs[$input_name][$this->input_type_key]           = $input_type;
		$this->required_inputs[$input_name][$this->input_negate_key]         = $negation;
		$this->required_inputs[$input_name][$this->_Key_form_input_is_array] = $input_is_array;

		if ( $depends_on ) {
			$this->add_dependency( $input_name, $depends_on, $dependency_type, $dependency_value );
		}

		return true;
	}

	function has_required_input( $input_name = '' ) {

		if ( $input_name ) {
			if ( is_array($input_name) ) {
				foreach( $input_name as $cur_input_name ) {
					if ( !$this->required_inputs[$cur_input_name] ) {
						return false;
					}

					if ( $dependencies = $this->get_dependencies($cur_input_name) ) {
						if ( !$this->check_dependencies($dependencies) ) {
							return false;
						}
					}	

				}

				return true;
			}
			else {

				if ( isset($this->required_inputs[$input_name]) && $this->required_inputs[$input_name] ) {

					if ( $dependencies = $this->get_dependencies($input_name) ) {
						if ( !$this->check_dependencies($dependencies) ) {
							return false;
						}
					}	
					
					return true;
				}
			}
		}
		else {
			if ( count($this->required_inputs) ) {
				return true;
			}
		}

		return false;

	}

	function add_dependency( $input_name, $dependency_name, $dependency_type, $dependency_value = '' ) {

		return $this->add_requirement_dependency( $input_name, $dependency_name, $dependency_type, $dependency_value );
	}

	function add_requirement_dependency( $input_name, $dependency_name, $dependency_type, $dependency_value = '' ) {
	//----------------------------------------------------------------------------------------------------------
	//	Dependency Structure:
	//
	//	required_inputs
	//	  |-> input_name
	//	      |-> 'dependencies'
	//	          |-> dependency_name
	//		      |-> negate ( is dependency negated )
	//		      |-> dependency name 
	//		      |-> dependency type (determined by type constants defined in constructor
	//		      |-> dependency target (what field depends on this? )
	//		      |-> dependency value (dependency must not only be set, but must have a specific value
	//----------------------------------------------------------------------------------------------------------

		list( $negation, $parsed_name ) = $this->parse_negated_input_name( $dependency_name );

		if ( !isset($this->required_inputs[$input_name]) ) {
			if ( $this->error_on_preemptive_dependency ) {
				trigger_error( "Dependency added for: {$input_name}, but {$input_name} is not required.", E_USER_WARNING );
			}
		}
		
		if ( !$dependency_type || !is_numeric($dependency_type) ) {
			trigger_error( "Invalid depdency type \"$dependency_type\" in " . __METHOD__, E_USER_WARNING );
		}
		
		$this->required_inputs[$input_name][$this->dependency_key][$parsed_name][$this->dependency_negate_key] = $negation;
		$this->required_inputs[$input_name][$this->dependency_key][$parsed_name][$this->dependency_name_key]  = $parsed_name;
	        $this->required_inputs[$input_name][$this->dependency_key][$parsed_name][$this->dependency_type_key]  = $dependency_type;
		$this->required_inputs[$input_name][$this->dependency_key][$parsed_name][$this->dependency_value_key] = $dependency_value;
		$this->required_inputs[$input_name][$this->dependency_key][$parsed_name][$this->dependency_target_key] = $input_name;

		return true;		

	}

	function get_dependencies( $input_array ) {

		if ( is_array($input_array) ) {

			$input_dependency = ( isset($input_array[$this->dependency_key]) ) ? $input_array[$this->dependency_key] : null;

			if ( $input_dependency AND is_array($input_dependency) ) {
				return $input_dependency;
			}
		
		}

		return false;

	}


	function count_dependencies( $input_array ) {
	
		$dependencies = $input_array[$this->dependency_key];
		$count	      = 0;

		if ( !count($dependencies) ) {
			return 0;
		}
		else {
			foreach( $input_array[$this->dependency_key] as $cur_dependency ) {
				$count++;
			}
		}

		return $count;

	}

	function set_required_value( $input_name ) {
	//------------------------------------------------------------
	// Alias to set_required_textbox (for backward compatibility)
	//------------------------------------------------------------

		return $this->set_required_textbox( $input_name );
	}

	function get_name() { 

		return $this->form_name;

	}

	function set_form_name( $new_form_name = '' ) {

		if ( $new_form_name ) {
			$this->form_name = $new_form_name;
		}
		
		return $this->form_name;

	}

	function checkbox_requires_confirmation( $input_name, $message = '' ) {
		
		list( $negation, $input_name )= $this->parse_negated_input_name( $input_name );

		if ( !$message ) {
			$message = $this->checkbox_confirm_message . $this->get_friendly_name($input_name) . '?';
		}

		$this->_confirm_checkboxes[$input_name][$this->input_negate_key] = $negation;
		$this->_confirm_checkboxes[$input_name][$this->input_message_key] = $message;
		$this->_confirm_checkboxes[$input_name][$this->input_name_key] = $input_name;



		return true;

	}

	function negates_all_required_fields( $input_name, $input_type, $required_value = '') {

		return $this->removes_requirements( $input_name, $input_type, $required_value, 1 );
	}

	function removes_all_requirements( $input_name, $input_type, $required_value = '') {

		return $this->removes_requirements( $input_name, $input_type, $required_value, 1 );

	}

	function removes_requirements( $input_name, $input_type, $required_value = null, $all_encompassing = 0 ) {

		list ( $negation, $input_name ) = $this->parse_negated_input_name($input_name);


		$this->_removes_requirements_arr[$input_name][$this->input_name_key]        = $input_name;
		$this->_removes_requirements_arr[$input_name][$this->input_type_key]        = $input_type;
		$this->_removes_requirements_arr[$input_name][$this->input_negate_key]      = $negation;
		$this->_removes_requirements_arr[$input_name][$this->requirement_scope_key] = $all_encompassing;
		$this->_removes_requirements_arr[$input_name][$this->dependency_value_key]  = $required_value;

		return true;

	}

	function generate_remove_all_requirement_script() {

		return $this->generate_remove_requirement_script(1);

	}

	function generate_remove_requirement_script( $all_encompassing = 0 ) {

		$javascript = '';
		$num_braces = 0;

		if ( count($this->_removes_requirements_arr) ) {
		
			foreach( $this->_removes_requirements_arr as $cur_remover ) {

				if ( $cur_remover[$this->requirement_scope_key] != $all_encompassing ) {
					continue;
				}
			
				$remover_name   = $cur_remover[$this->input_name_key];		
				$remover_type   = $cur_remover[$this->input_type_key];		
				$remover_negate = $cur_remover[$this->input_negate_key];		
				$required_value = $cur_remover[$this->dependency_value_key];

				//Reverse negation since we're looking NOT to continue if value is TRUE.
				//$negate 	= ( $remover_negate ) ? 0 : 1; 

				//$required_value = ( !$required_value AND $remover_type = constant('FORM_INPUT_TYPE_CHECKBOX') ) ? 'true' : $required_value;

				if ( $this->is_confirmation_required($remover_name) ) {
					$javascript .= $this->generate_checkbox_confirmation_script($remover_name) . "\n";
				}
		
				
				$javascript .= $this->generate_input_if_statement( $remover_name, $remover_type, $negate, $required_value) ;
				$javascript .= "\n";
				$num_braces += $this->count_closing_if_braces_by_input_type( $remover_type );
			}
		}

		return array( $javascript, $num_braces );

	}


	function set_name( $new_form_name = '' ) {
	//---------------------------
	// Alias to set_form_name()
	//---------------------------

		return $this->set_form_name( $new_form_name );

	}

	function parse_input_name( $input_name ) {

		return preg_replace('[^A-Za-z0-9\-_]', '_', $input_name);

	}

	function parse_proper_name( $name ) {
                
	        $name_regexp = defined('PROPER_NAME_CHARACTER_CLASS') ? constant('PROPER_NAME_CHARACTER_CLASS') : 'A-Za-z0-9\-_\s\.';

        	$name = preg_replace("/[^{$name_regexp}]/", '', $name );
         
	        return $name;
        
	}

        function ignore_script_check( $input_name ) {
                                        
                $this->ignore_script_check[] = $input_name;
                                 
        }   

	function input_ignored_in_script( $input_name ) {

		if ( in_array($input_name, $this->ignore_script_check) ) {
			return true;
                }

		return false;

	}	

	function set_validation_filename( $filename ) {

		$this->validation_filename = $filename;

		return true;
	}

	function set_dataset( $new_dataset, $set_alt_dataset = 1 ) {
		
		if ( $new_dataset ) {
			if ( is_object($new_dataset) ) {

				foreach( get_object_vars($new_dataset) as $cur_var => $cur_value ) {
					$data_hash[$cur_var] = $cur_value;
				}
				$this->_dataset = $data_hash;
			}
			else {
				$this->_dataset = $new_dataset;
			}

			if ( $set_alt_dataset ) {
				$this->_alt_dataset = 1;
			}
		}
	}

	function &get_dataset() {

		if ( !$this->_dataset || !count($this->_dataset) ) {
			$this->set_dataset($_POST, 0);
		}

		return $this->_dataset;

	}

	function reset_dataset() {

		$this->_dataset     = array();
		$this->_alt_dataset = 0;
	}

	function setup_complete( $which_setup = null ) {

                if ( $which_setup === null ) {
                        return $this->setup_complete;
                }
                else if ( $which_setup === true || $which_setup === 1 ) {    
                        $this->setup_complete = 1;
                }
                else if ( $which_setup === false || $which_setup === 0 ) {
                        $this->setup_complete = 0;
                }
		else {
			trigger_error( 'improper call to setup_complete in InputForm. Use add_completed_setup() and has_completed_setup() when specifying a particular setup type.', E_USER_WARNING );
		}

		return $this->setup_complete;
	}

	function add_completed_setup( $which_setup ) {

		if ( !is_array($this->_Completed_setups) ) {
			$this->_Completed_setups = array();
		}

		if ( !in_array($which_setup, $this->_Completed_setups) ) {
			$this->_Completed_setups[] = $which_setup;
		}
	}


        function is_setup_complete( $which_setup ) {

                return $this->has_completed_setup($which_setup);

        }

        function has_completed_setup( $which_setup ) {

                if ( is_array($this->_Completed_setups) && in_array($which_setup, $this->_Completed_setups) ) {
                        return true;
                }

                return false;

        }


	function set_value( $key, $value ) {

		$dataset = $this->get_dataset();
		$dataset[$key] = $value;

	}

	function form_validated ( $yesno = '' ) {

		if ( $yesno ) {
			$this->form_validated = $yesno;
		}

		return $this->form_validated;

	}

	function is_input_required( $input_name ) {

		if ( isset($this->required_inputs[$input_name]) ) {
			return true;
		}

		return false;

	}
	
	function get_input_setup_by_name( $input_name ) {

		if ( isset($this->required_inputs[$input_name]) ) {
			return $this->required_inputs[$input_name];
		}

		return null;
	}

	function get_input_setup_value( $input_name, $setup_key ) {

		if ( $input_setup = $this->get_input_setup_by_name($input_name) ) {
			if ( isset($input_setup[$setup_key]) ) {
				return $input_setup[$setup_key];
			}
			else {
				return null;
			}
		}

		return false;

	}

	function add_field_name_input_mapping( $data_field, $input_name ) {

		$this->_Map_input_field_name[$data_field] = $input_name;

	}

	function get_field_name_input_mapping( $data_field ) {

		if ( isset($this->_Map_input_field_name[$data_field]) ) {
			return $this->_Map_input_field_name[$data_field];
		}

		return null;
	}


	public function message_caption_by_input_type( $input_type, $options = array() ) {
		
		switch( $input_type ) {
			case self::INPUT_TYPE_RADIO:
					$caption = $this->missing_radio_caption; 
					break;
			case self::INPUT_TYPE_CHECKBOX:
					$caption = $this->missing_checkbox_caption; 
					break;
			case self::INPUT_TYPE_DROPDOWN:
					$caption = $this->missing_dropdown_caption; 
					break;
			case self::INPUT_TYPE_TEXT:
			default:
					$caption = $this->missing_input_caption; 
					break;					

		}
		
		return $caption;
		
		
	}

	public static function Input_type_name_to_constant( $name ) {

		return FormInputType::Constant_by_name( $name );
	
	}

}

}


?>
