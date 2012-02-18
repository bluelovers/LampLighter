<?php

LL::Require_class('HTML/TemplateHelper');

class FormCommon extends TemplateHelper {

	public static function Get_form_value_for_repopulate( $input_name, $options = null ) {
		
		$options['for_repopulate'] = true;
		
		return self::Get_form_value( $input_name, $options );
	}

	public static function Get_form_value( $input_name, $options = null ) {
		
		
		$input_value 		 = null;
		$input_value_found   = false;
		$input_name_explicit = false;
		$model       		 = null;
		
		if ( isset($options[self::$Key_input_value]) ) {
			$input_value = $options[self::$Key_input_value];
			
			
			if ( array_val_is_nonzero($options, 'for_repopulate') && !array_val_is_nonzero($options, 'allow_html_tags') ) {
				$input_value = htmlspecialchars($input_value);
			}
		}
		else {


			
			if ( isset($options[self::$Key_input_name]) ) {
				$input_name_explicit = true;
				$input_name = $options[self::$Key_input_name];
			}
			
			/*
			else {
				if ( isset($options[self::$Key_model_object]) ) {
					$model = $options[self::$Key_model_object];
					$input_name = $model->form_field_name_by_field_key($field_key);
				}
				
			}		
			*/
			
			
			$controller = self::get_calling_controller();
		
			
		
			if ( $controller && ($controller->is_postback()) ) {
				
				if ( $controller->form_repopulate ) {
					$input_value = $controller->get_form_value_for_repopulate($input_name, $options);
					$input_value_found = true;
				}
			}
			else {

				/*
				$name_arr = array( $input_name, $field_key );
				
				foreach( $name_arr as $cur_param_name ) {
					
					if ( !$cur_param_name) {
						continue;
					}
					
					if ( isset($_GET[$cur_param_name]) ) {
						$input_value = $_GET[$cur_param_name];
					
						if ( array_val_is_nonzero($options, 'for_repopulate')) {
							$input_value = htmlspecialchars($input_value);
						}
						
						$input_value_found = true;
						break;
					
					}
					else {
					*/
						if ( $controller && $controller->param_is_set($input_name) ) {
		
							
		
							$input_value = $controller->get_param($input_name);
						
							if ( array_val_is_nonzero($options, 'for_repopulate') && !array_val_is_nonzero($options, 'allow_html_tags') ) {
								$input_value = htmlspecialchars($input_value);
							}
						
							$input_value_found = true;
							//break;
						}
					//}
				//}
				
				if ( !$input_value_found ) {
					if ( isset($options[self::$Key_model_object]) ) {
					
						$model = $options[self::$Key_model_object];
				
						if ( $model ) {
			
							$model_key = $model->field_key_from_form_input_name($input_name);
			
							if ( null === ($input_value = $model->get_form_value_for_repopulate($model_key, $options)) ) {
								
								$input_value = $model->$model_key;
								
								if ( array_val_is_nonzero($options, 'for_repopulate') && !array_val_is_nonzero($options, 'allow_html_tags') ) {
									$input_value = htmlspecialchars($input_value);
								}
							}
						}
					}
				}
			}
		}
		
		if ( $input_value ) {
			if ( isset($options['date_format']) && $options['date_format'] ) {
				$input_value = date($options['date_format'], strtotime($input_value) );
			}
		}
		
		return $input_value;
	}

	public static function Input_id_by_options( $options ) {
	
		static $id_indexes = array();
	
		if ( !($input_id = self::Input_id_specified($options)) ) {
			if ( !isset($options[self::$Key_model_object]) ) {	
				if ( isset($options[self::KEY_CLASS_NAME])) {
					$options[self::$Key_model_object] = self::Load_class($options[self::KEY_CLASS_NAME]);
				}
			}
			
			if ( isset($options[self::$Key_model_object]) && $model = $options[self::$Key_model_object] ) {
				$input_name  = self::Input_name_from_option_hash($options);
				$input_id = $model->html_field_id_by_input_name($input_name);
			}
			else {
				if ( $input_name  = self::Input_name_from_option_hash($options) ) {
					
					$index = null;
										
					if ( strpos($input_name, '[') !== false ) {
						//
						// Input name is an array
						//
						if ( preg_match('/\[([0-9]+)\]/', $input_name, $matches) ) {
							$index = $matches[1]; 
						}
						
						list( $input_name, $discard ) = explode('[', $input_name);
						
					}
					
					if ( $index === null ) {
						if ( isset($id_indexes[$input_name]) ) {
							$index = $id_indexes[$input_name] + 1;
						}
						else {
							$index = 1;
						}
					}
										
					$id_indexes[$input_name] = $index;
					
					if ( $index > 1 ) {
						$input_id = $input_name . '_' . $index;
					}
					else {
						$input_id = $input_name;
					}
						
				}
				
			}
		}	
	
		return $input_id;
	}

	public static function Input_id_specified( $options = array() ) {

		if ( isset($options['input_id']) && $options['input_id'] ) {

			return $options['input_id'];
		}
		
		if ( isset($options['id']) && $options['id'] ) {
			return $options['id'];
		}
		
		if ( is_array($options) && isset($options['html']) && is_array($options['html']) && isset($options['html']['id']) ) {
			return $options['html']['id'];
		}

		
		return false;		
	}
		
}
?>