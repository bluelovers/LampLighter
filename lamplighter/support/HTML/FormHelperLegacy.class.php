<?php

LL::require_class('HTML/TemplateHelper');

class FormHelper extends TemplateHelper {

	const KEY_DATE_FORMAT = 'date_format';

	static $Key_input_value = 'value';
	

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

		
	public static function Text_field( $class, $field_key = null, $options = null, $html_options = null ) {

		$input_id = null;
		
		if ( is_array($class) ) {
			$options = $class;
		}
		else {
			$model = self::Load_class($class);
			$options[self::$Key_model_object] = $model;
			$options[self::$Key_field_key]    = $field_key;
		}

		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}

		
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		
		$options[self::$Key_field_key]    = $field_key;
		$options[self::$Key_model_object] = $model;
				
		$input_name  = self::Input_name_from_option_hash($options);
		
		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			if ( $model ) {
				$input_id = $model->html_field_id_by_input_name($input_name);
			}
		}
		
		$input_value = self::Get_form_value_for_repopulate($input_name, $options);

		$html = "<input type=\"text\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$input_value}\"{$html_options}>";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	function password_field( $class, $field_key = null, $options = null, $html_options = null ) {
		
		$model = self::Load_class($class);
		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		
		$options[self::$Key_field_key]    = $field_key;
		$options[self::$Key_model_object] = $model;
		
		$input_name  = self::Input_name_from_option_hash($options);

		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			$input_id = $model->html_field_id_by_input_name($input_name);
		}
		
		if ( array_val_is_nonzero($options, 'repopulate_value') ) {
			$input_value = self::Get_form_value_for_repopulate($input_name, $options);
		}
		else {
			$input_value = null;
		}

		$html = "<input type=\"password\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$input_value}\"{$html_options}>";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}
	
	public function hidden_field( $class, $field_key = null, $options = null, $html_options = null ) {
		
		$model = self::Load_class($class);
		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		
		$options[self::$Key_field_key]    = $field_key;
		$options[self::$Key_model_object] = $model;
		
		
		$input_name  = self::Input_name_from_option_hash($options);
		$input_value = self::Get_form_value_for_repopulate($input_name, $options);

		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			$input_id = $model->html_field_id_by_input_name($input_name);
		}

		$html = "<input type=\"hidden\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$input_value}\"{$html_options}>";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}	
	
	public static function Input_id_by_options( $options ) {
	
		if ( !($input_id = self::Input_id_specified($options)) ) {
			if ( isset($options[self::$Key_model_object]) && $model = $options[self::$Key_model_object] ) {
				$input_name  = self::Input_name_from_option_hash($options);
				$input_id = $model->html_field_id_by_input_name($input_name);
			}
		}	
	
		return $input_id;
	}

	function text_area( $class, $field_key = null, $options = null, $html_options = null ) {
		
		if ( is_array($class) ) {
			$options = $class;
		}
		else {
			$model = self::Load_class($class);
			$options[self::$Key_model_object] = $model;
			$options[self::$Key_field_key]    = $field_key;
		}
		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
				
		$input_name  = self::Input_name_from_option_hash($options);
		$input_value = self::Get_form_value_for_repopulate($input_name, $options);

		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			if ( $model ) {
				$input_id = $model->html_field_id_by_input_name($input_name);
			}
		}

		$html = "<textarea id=\"{$input_id}\" name=\"{$input_name}\"{$html_options}>{$input_value}</textarea>";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	function rich_text_area( $class, $field_key = null, $options = null, $html_options = null ) {
		
		LL::require_class('HTML/JavascriptHelper');
		
		$controller = self::get_calling_controller();
		
		if ( is_array($class) ) {
			$options = $class;
		}
		else {
			$model = self::Load_class($class);
			$options[self::$Key_field_key]    = $field_key;
			$options[self::$Key_model_object] = $model;

		}
		
		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}
		
		$options['allow_html_tags'] 	   = true;
		
		if ($html_options) $html_options = " {$html_options}";
		
		$input_name  = self::Input_name_from_option_hash($options);
		
		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			if ( $model ) {
				$input_id = $model->html_field_id_by_input_name($input_name);
			}
		}

		$input_value = self::Get_form_value($input_name, $options);
		
		
		$input_value = str_replace(array("\n", "\r"), array('', ''), $input_value);
		
		$input_value = JavascriptHelper::Literal($input_value, array('return_output' => true) );

		$width      = isset($options['width']) ? $options['width'] : '100%';
		$frame_name = isset($options['frame_name']) ? $options['frame_name'] : 'frame_rte';
		$css_file   = isset($options['css_file']) ? $options['css_file'] : 'example.css';
		$base_uri	= isset($options['base_uri']) ? $options['base_uri'] : SITE_BASE_URI . '/script/richtext';

		$html = '';
		
		$html .= "\n<script type=\"text/javascript\">rteBaseURI='{$base_uri}';</script>\n";
		
		$html .= "<script src=\"{$base_uri}/js/richtext.js\" type=\"text/javascript\"></script>";
		$html .= "<script src=\"{$base_uri}/js/config.js\" type=\"text/javascript\"></script>";

		$html .= '<script>';
		$html .= "rteWidth='{$width}';";
		$html .= "rteName='{$frame_name}';";
		$html .= "rteFormName='{$input_name}';";
		$html .= "initRTE({$input_value}, '{$css_file}');";
		$html .= '</script>';		
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	
	function tinymce_area( $class, $field_key = null, $options = array(), $html_options = null ) {
		
		$html_options = 'class="mceEditor"';

		if ( is_array($options) && array_key_exists('init_file', $options) && $options['init_file'] ) {
			$init_file = $options['init_file'];
		} 
		else {
			$init_file = constant('SITE_BASE_URI') . '/script/tiny_mce/tiny_mce_init.js';
		}

		if ( is_array($options) && array_key_exists('js_file', $options) && $options['js_file'] ) {
			$script_file = $options['js_file'];
		} 
		else {
			$script_file = constant('SITE_BASE_URI') . '/script/tiny_mce/tiny_mce.js';
		}
		
		$sub_options = $options;
		$sub_options[self::$Key_return_output] = true;
				
		$html = self::Text_area( $class, $field_key, $sub_options, $html_options );
		
		$html .= "<script type=\"text/javascript\">js_SITE_BASE_URI = '" . constant('SITE_BASE_URI') . "';</script>\n";
		$html .= "<script type=\"text/javascript\" src=\"{$script_file}\"></script>\n";
		$html .= "<script type=\"text/javascript\" src=\"{$init_file}\"></script>\n";
		 
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
		
	}

	function file_input( $class, $field_key = null, $options = null, $html_options = null ) {
		
		$model = self::Load_class($class);
		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		
		$options[self::$Key_field_key]    = $field_key;
		$options[self::$Key_model_object] = $model;
		
		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			$input_id = $model->html_field_id_by_field_key($field_key);
		}

		if ( isset($options[self::$Key_input_name]) ) {
			$input_name = $options[self::$Key_input_name];
		}
		else { 
			//
			// By default for file inputs, 
			// id and input name are both table_name_field
			//
			$input_name = $input_id;
		}
		
		$html = "<input type=\"file\" id=\"{$input_id}\" name=\"{$input_name}\"{$html_options}>";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	public static function Photo_file_input( $class, $options = array(), $html_options = null ) {
		
		static $photo_indexes = array();
		
		if ( !isset($photo_indexes[$class]) ) {
			$photo_indexes[$class] = 1;
		}
		
		$model = self::Load_class($class);
		
		if ( is_scalar($options) ) {
			$html_options = $options;
			$options = array();
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		
		//$options[self::$Key_field_key]    = $field_key;
		$options[self::$Key_model_object] = $model;
		
		if ( !($input_id = self::Input_id_specified($options, $html_options)) ) {
			$input_id = $model->get_form_input_key() . '_photo_file' . $photo_indexes[$class];
		}

		if ( isset($options[self::$Key_input_name]) ) {
			$input_name = $options[self::$Key_input_name];
		}
		else { 
			//
			// By default for file inputs, 
			// id and input name are both table_name_field
			//
			$input_name = $input_id;
		}
		
		//$input_value = self::Get_form_value_for_repopulate($field_key, $options);

		$html = "<input type=\"file\" id=\"{$input_name}\" name=\"{$input_name}\" {$html_options} />";
		
		$photo_indexes[$class]++;
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}
	
	public static function Input_id_specified( $options = array(), $html_options = null ) {
		
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