<?php

if ( !class_exists('FormOptionsHelper', false) ) {

LL::require_class('HTML/FormCommon');
LL::require_class('HTML/FormInput');

class FormOptionsHelper extends FormInput {

	public static function  Options_from_model_iterator_for_select( $iterator, $options = null, $html_options = null ) {

		$html = '';
		if ( is_object($iterator) ) {

			

			$text_field  = ( isset($options[self::$Key_html_option_text_field]) ) ? $options[self::$Key_html_option_text_field] : null;
			$value_field = ( isset($options[self::$Key_html_option_value_field]) ) ? $options[self::$Key_html_option_value_field] : null;


			$iterator->reset();

			while ( $model = $iterator->next() ) {

				if ( strpos($text_field, '{') !== false ) {
					
					$placeholders = array();
					$replacements  = array();
				
					preg_match_all('/\{([A-Za-z0-9\_]+)\}/', $text_field, $matches);
				
					for( $j = 0; $j < count($matches[0]); $j++ ) {
						
						$full_match = $matches[0][$j];
						$field_key  = $matches[1][$j];
						
						$placeholders[] = $full_match;
						$replacements[] = $model->$field_key;
					}
					
					$text = str_replace($placeholders, $replacements, $text_field);	
					
				}
				else {
					$text = ($text_field) ? $model->$text_field : null;
				}
				
				$val  = ($value_field) ? $model->$value_field : null;
				
				if ( isset($options[self::$Key_html_option_value_selected]) && $options[self::$Key_html_option_value_selected] == $val ) {
					$selected = true;
				}
				else {
					$selected = false;
				}
	

				$parsed_text = htmlspecialchars($text);
				$parsed_val  = htmlspecialchars($val);

				$html .= "<option value=\"{$parsed_val}\" label=\"{$parsed_text}\"";

				if ( $selected ) { 
					$html .= ' selected="true"';
				}

				$html .= ">{$parsed_text}</option>\n";

			}
		}
		
		if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
			return $html;
		}

		echo $html;

	}

	function select_by( $class_location, $field_key_value, $field_value, $field_key_text, $options = null, $html_options = null ) {


	}

	public static function Select_by_method( $class, $method, $params = null, $field_key_value, $field_key_text, $options = null, $html_options = null ) {

		try { 
			if ( !$options || !is_array($options) ) {
				$options = array($options);
			}

			$model      = self::Load_class( $class );

			/*
			if ( !isset($options[self::KEY_REFERENCED_BY_MODEL]) ) {
				$caller = self::Get_calling_controller();
				if ( $caller && method_exists($caller, 'get_model') ) {
					$caller_model_name = get_class($caller->get_model());
					$model_name = get_class($model);
					
					if ( $caller_model_name && $caller_model_name != $model_name ) {
						$options[self::KEY_REFERENCED_BY_MODEL] = $caller_model_name;
					}
				}
			}
			*/

			if ( isset($options[self::KEY_REFERENCED_BY_MODEL]) && $options[self::KEY_REFERENCED_BY_MODEL] ) {

				$reference_model = self::Load_class($options[self::KEY_REFERENCED_BY_MODEL]);
				
				$options[self::$Key_model_object] = $reference_model;
				$field_key_value = $model->db_field_name($field_key_value);
				$options[self::$Key_field_key] = $field_key_value;
			
				$options[self::$Key_input_name] = self::Input_name_from_option_hash($options);
				$input_name = $options[self::$Key_input_name];
			
			}
			else {
				$options[self::$Key_model_object] = $model;
				$options[self::$Key_field_key] = $field_key_value;
		
				$input_name = self::Input_name_from_option_hash($options);
			}	

		
			$html  = self::Select_tag_open_for_model( $class, $field_key_value, array_merge($options, array(self::$Key_return_output=>1)), $html_options );
		
			if ( !$params || is_scalar($params) ) {
				$param_arr = array($params);
			}
			else {
				$param_arr = $params;
			}
			
			if ( $iterator = call_user_func_array(array($model, $method), $param_arr) ) {
	
				
				$inner_options[self::$Key_return_output] = true;
				$inner_options[self::$Key_html_option_text_field]  = $field_key_text;
				$inner_options[self::$Key_html_option_value_field] = $field_key_value;
			
				if ( !isset($options[self::$Key_html_option_value_selected]) ) {
					$inner_options[self::$Key_html_option_value_selected] = FormCommon::Get_form_value($field_key_value, $options);
			

				}
				else {
					$inner_options[self::$Key_html_option_value_selected] = $options[self::$Key_html_option_value_selected];
				}
				
				
				$html .= self::Options_from_model_iterator_for_select( $iterator, $inner_options, $html_options );

			}
			
			$close_options = $options;
			$close_options[self::$Key_return_output] = true;
			
			$html .= self::Select_tag_close($close_options);

			if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
				return $html;
			}
			
			echo $html;
		}
		catch( Exception $e ) {
			throw $e;
		}


	}

	public static function Select_tag_open_for_model( $class, $field_key_value, $options = null, $html_options = null ) {

		$obj = self::Load_class($class);

		//$options = $options;
		$options[self::$Key_field_key] = $field_key_value;
		$options[self::$Key_model_object] = $obj;

		$select_name = self::Input_name_from_option_hash($options);
		
		//$select_id   = FormCommon::Input_id_by_options($options); //$obj->html_field_id_by_input_name($select_name);

		//$html = "<select id=\"{$select_id}\" name=\"{$select_name}\" {$html_options}>\n";

		//$html_options .= " id=\"{$select_id}\" "; 

		return self::Select_tag_open( $select_name, $options, $html_options);

		//if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
		//	return $html;
		//}

		//echo $html;

	}


	public static function Select_all ( $class, $field_key_text, $field_key_value, $options = null, $html_options = null ) {
	
		//$params = array( array('return' => 'iterator') );
		
		$params = array( $options );
	
		return self::Select_by_method( $class, 'fetch_all', $params, $field_key_value, $field_key_text, $options, $html_options );
	
	}
	
	public static function Options_for_select( $container, $selected = null ) {

		$options[self::$Key_html_option_value_selected] = $selected;
		
		if ( is_a($container, 'DataModelIterator') ) {
			return self::Options_from_model_iterator_for_select($container, $options );
		}
		else if ( is_array($container) ) {
			return self::Options_from_array_for_select( $container, $options );
		}		
	}

	public static function Options_from_array_for_select( $arr, $options = null ) {
		
		$option_tags = '';
		
		
		if ( is_array($arr) ) {
			
			
			$keys = array_keys($arr);
		
			foreach( $keys as $cur_key ) {
				
				$opt_val  = $arr[$cur_key];
				
				if ( is_array($opt_val) ) {
					
					$inner_keys = array_keys($opt_val);
					
					if ( count($inner_keys) == 1 ) {
						//
						// Array looks like: 
						// 'text' => 'val'
						//
						$opt_text = $inner_keys[0];
						$opt_val = $opt_val[$opt_text];

					}
					else {
						//
						// Array looks like: 
						// ( 'text', 'val' )
						//
						$opt_text = $opt_val[0]; 
						$opt_val = $opt_val[1];
					
					}			
					
					
				}
				else {
					if ( !is_numeric($cur_key) ) {
						$opt_text = $cur_key;
					}
					else {
						$opt_text = $opt_val;
					}
				}
				
				$options[self::$Key_return_output] = true;
				
				if ( isset($options[self::$Key_html_option_value_selected]) ) {
					
					if ( strval($opt_val) == strval($options[self::$Key_html_option_value_selected]) ) {
						$options[self::$Key_html_option_is_selected] = true;
					}
					else {
						$options[self::$Key_html_option_is_selected] = false;
					}
				}
				
				
				$option_tags .= self::Option_for_select( $opt_text, $opt_val, $options );
				$option_tags .= "\n";
			}
		}

		return $option_tags;		
	}
	

	/*
	public static function Checkbox( $class, $field_key, $options = null, $html_options = null ) {
		
		$options[self::$Key_field_key]    = $field_key;
		$options[self::KEY_CLASS_NAME] = $class;

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return parent::Checkbox($options);
	}
	
	public static function Radio_button( $class, $field_key, $options = null, $html_options = null ) {

		$options[self::$Key_field_key]    = $field_key;
		$options[self::KEY_CLASS_NAME] = $class;

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return parent::Radio_button($options);
		

	}
	*/

}

}

?>
