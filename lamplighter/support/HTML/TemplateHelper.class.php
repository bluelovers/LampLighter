<?php

class TemplateHelper {

	const KEY_FIELD_NAME = 'field_name';
	const KEY_DATE_FORMAT = 'date_format';
	const KEY_HTML = 'html';
	const KEY_FIELD = 'field';
	const KEY_CLASS_NAME = 'class';

	static $Key_return_output = 'return_output';
	static $Key_input_name	= 'name';
	static $Key_model_object  = 'model';
	static $Key_field_key     = 'field'; //deprecated - user KEY_FIELD constant
	static $Key_input_value = 'value';

	static $Calling_template;


	static function Get_calling_template() {
	
		return self::$Calling_template;
		
	}

	static function Set_calling_template( $template ) {
		
		self::$Calling_template = $template;

	}

	static function Get_calling_controller() {
		
		$controller = null;
		
		if ( $template = self::get_calling_template() ) {
			$controller = $template->get_controller();
		}
		
		return $controller;
		
	}
	

	static function load_class( $class_location ) {

		if ( is_object($class_location) ) {
			$obj = $class_location;
		}
		else {
			
			$class_name   = self::class_name_from_class_location($class_location);
			$parent_model = self::get_parent_model( $class_name );

			if ( is_a($parent_model, $class_name) ) {
				$obj = $parent_model;
			}
			else {
				
				LL::Require_class($class_location);
				$obj = new $class_name;
				
				/*
				if ( !method_exists($parent_model, '_Model_object_by_name') || !($obj = $parent_model->_Model_object_by_class($class_location)) ) {
				
					LL::require_model($class_location);
					$obj = new $class_name;
				}
				*/
			}
		}

		return $obj;

	}

	
	static function get_parent_model( $name = null ) {
		
		try {
			if ( $parent_template = self::get_calling_template() ) {
				$parent_controller = $parent_template->get_controller();

				if ( method_exists($parent_controller, 'get_model') ) {		
					return $parent_controller->get_model( array('class_name' => $name) );
				}
			}
			
			return null;
		}
		catch (Exception $e) {
			throw $e;
		}
		
		
	}

	function class_name_from_class_location( $class_location ) {

		return basename($class_location);

	}

	protected function _Input_name_from_option_hash( $options ) {
		
		return self::Input_name_from_option_hash($options);
	}
	
	public static function Input_name_for_model_field( $class_name, $field_key, $options = array() ) {
		
		$model = self::Load_class($class_name);
					
		return $model->input_key_to_hashtable_ref($field_key);
		
	}
	
	public static function Input_name_from_option_hash( $options ) {
		
		$input_name = null;

		//
		// To support deprecated 'input_name' key
		//
		if ( isset($options['input_name']) && !isset($options[self::$Key_input_name]) ) {
			$options[self::$Key_input_name] = $options['input_name'];
		}

		if ( isset($options[self::$Key_input_name]) ) {
			$input_name = $options[self::$Key_input_name];
			
		}
		else {
			if ( isset($options[self::$Key_field_key]) ) {
				if ( !isset($options[self::$Key_model_object]) ) {	
					if ( isset($options[self::KEY_CLASS_NAME])) {
						$options[self::$Key_model_object] = self::Load_class($options[self::KEY_CLASS_NAME]);
					}
				}
				
				if ( isset($options[self::$Key_model_object]) && $options[self::$Key_model_object]) {	
						$model = $options[self::$Key_model_object];
						
						if ( isset($options['for_field']) ) {
							$field_key = $options['for_field'];
						}
						else {
							$field_key = $options[self::$Key_field_key];
						}
						 
						
						$input_name = $model->input_key_to_hashtable_ref($field_key);
				}
				else {
					$input_name = $options[self::$Key_field_key];
				}
			}
		}
		
		return $input_name;
	}
	
	public static function format_http_link( $link ) {
		
		echo format_http_link($link);
		
	}
	
	public static function Html_attrs_from_params( $options = array(), $html_options = null ) {

		$html_attrs = '';

		if ( $html_options ) { 
			$html_attrs = " {$html_options} ";		
		}
		//else {
		if ( is_array($options) ) {

			if ( array_key_exists('html', $options) ) {
			
				if ( is_array($options['html']) ) {
			
					if ( isset($options['id']) && $options['id'] ) {
						unset($options['html']['id']);
					}
			
					foreach( $options['html'] as $attr => $val ) {
						$val = str_replace('"', '&quot;', $val);
						$html_attrs .= "{$attr}=\"{$val}\" ";
						
					}
				}
				else {
					$html_attrs = $options['html'];
				}
			}
			
			if ( array_key_exists('css', $options) ) {
				
				$css_string = '';
				
				if ( is_array($options['css']) ) {
					foreach( $options['css'] as $key => $val ) {
						$css_string .= "{$key}:{$val};";
					}
				}						
				else {
					$css_string = $options['css'];
				}
				$html_attrs .= "style=\"{$css_string}\" ";
			}
			
			$html_attrs = rtrim($html_attrs);
		}

		//}
		
		return " $html_attrs ";
	}

	public static function Get( $key ) {
		
		$template = self::Get_calling_template();
		$val = $template->get_param_val( $key );
		
		if ( $val == null ) {
			
			$controller = self::Get_calling_controller();
			$val = $controller->get_param($key);
			
		} 	
		
		return $val;
		
	}	
}
?>