<?php

LL::require_class('HTML/TemplateHelper');
LL::require_class('HTML/FormCommon');

class FormInput extends FormCommon {

	const KEY_REFERENCED_BY_MODEL = 'for_model';
	const KEY_OPTION_EMPTY_VALUE_TEXT = 'empty_value_text';
	const KEY_OPTIONS_BEFORE = 'options_before';
	const KEY_OPTIONS_AFTER = 'options_after';

	static $Key_html_option_is_selected = 'selected';
	static $Key_html_option_value_selected = 'selected_value';
	static $Key_html_option_text_field  = 'text_field';
	static $Key_html_option_value_field = 'value_field';

	public static function Text_field( $options = array() ) {

		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}
		
		
		$html_options = self::Html_attrs_from_params( $options );
		$input_name   = self::Input_name_from_option_hash($options);
		
		$input_id = FormCommon::Input_id_by_options($options);
		$input_value = FormCommon::Get_form_value_for_repopulate($input_name, $options);

		$html = "<input type=\"text\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$input_value}\"{$html_options} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	public static function Password_field( $options = array() ) {
		
		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		$input_name   = self::Input_name_from_option_hash($options);
		$input_id 	  = FormCommon::Input_id_by_options($options);
		$input_value  = FormCommon::Get_form_value_for_repopulate($input_name, $options);

		$html = "<input type=\"password\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$input_value}\"{$html_options} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}
	
	public static function Hidden_field( $options = array() ) {
		
		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		$input_name  = self::Input_name_from_option_hash($options);
		$input_value = FormCommon::Get_form_value_for_repopulate($input_name, $options);
		$input_id = FormCommon::Input_id_by_options($options);

		$html = "<input type=\"hidden\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$input_value}\"{$html_options} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}	
	

	public static function Text_area( $options = array() ) {
		
		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
				
		$input_name  = self::Input_name_from_option_hash($options);
		$input_value = FormCommon::Get_form_value_for_repopulate($input_name, $options);
		$input_id = FormCommon::Input_id_by_options($options);

		$html = "<textarea id=\"{$input_id}\" name=\"{$input_name}\"{$html_options}>{$input_value}</textarea>";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}
	
	public static function Tinymce_area( $options = array() ) {
		
		if ( !isset($options['html']['class']) ) {
			$options['html']['class'] = 'mceEditor';
		}
		
		if ( array_key_exists('init_file', $options) && $options['init_file'] ) {
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
				
		$html = self::Text_area( $sub_options  );
		
		$html .= "<script type=\"text/javascript\">js_SITE_BASE_URI = '" . constant('SITE_BASE_URI') . "';</script>\n";
		$html .= "<script type=\"text/javascript\" src=\"{$script_file}\"></script>\n";
		$html .= "<script type=\"text/javascript\" src=\"{$init_file}\"></script>\n";
		 
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
		
	}

	public static function File_field( $options = null ) {
		
		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}		

		$html_options = self::Html_attrs_from_params( $options );
		$input_name   = self::Input_name_from_option_hash($options);
		
		$input_id = FormCommon::Input_id_by_options($options);
		
				
		$html = "<input type=\"file\" id=\"{$input_id}\" name=\"{$input_name}\"{$html_options} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}


	/*
	 * Deprecated
	 */
	 
	public static function File_input( $class, $field_key = null, $options = null, $html_options = null ) {
		
		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}		

		$html_options = self::Html_attrs_from_params( $options );
		$input_name   = self::Input_name_from_option_hash($options);
		
		$input_id = FormCommon::Input_id_by_options($options);
		
				
		$html = "<input type=\"file\" id=\"{$input_id}\" name=\"{$input_name}\"{$html_options} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	public static function Photo_file_input( $options = array() ) {
		
		static $photo_indexes = array();
		$class = null;

		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
			$class=$options[self::KEY_CLASS_NAME];
			
			if ( !isset($photo_indexes[$class]) ) {
				$photo_indexes[$class] = 1;
			}
			
		}
		else {
			$photo_indexes['default'] = 1;
		}
				
		
		$html_options = self::Html_attrs_from_params( $options );
		
		if ( !($input_id = FormCommon::Input_id_specified($options, $html_options)) ) {
			if ( $model ) {
				$input_id = $model->get_form_input_key() . '_photo_file' . $photo_indexes[$class];
			}
			else {
				$input_id = 'photo_file' . $photo_indexes['default'];
			}
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
		
		$html = "<input type=\"file\" id=\"{$input_name}\" name=\"{$input_name}\" {$html_options} />";
		
		if ( $class ) {
			$photo_indexes[$class]++;
		}
		else {
			$photo_indexes['default']++;
		}
		
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}

	public static function Select_tag_open ( $field_name, $options = null, $html_options = null ) {
		
		$html_options = self::Html_attrs_from_params( $options, $html_options );
		$select_id   = FormCommon::Input_id_by_options($options); 
		
		$html = "<select id=\"{$select_id}\" name=\"{$field_name}\"{$html_options}>";

		if ( isset($options[self::KEY_OPTION_EMPTY_VALUE_TEXT]) ) {
			$html .= self::Option_for_select( $options[self::KEY_OPTION_EMPTY_VALUE_TEXT], '', $options );
			$html .= "\n";
		}
		
		if ( isset($options[self::KEY_OPTIONS_BEFORE]) && is_array($options[self::KEY_OPTIONS_BEFORE]) ) {
			foreach( $options[self::KEY_OPTIONS_BEFORE] as $key => $val ) {
				$html .= self::Option_for_select( $key, $val, $options );
				$html .= "\n";
			}			
		}
		
		if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
			return $html;
		}

		echo $html;		
	}
	
	public static function Select_tag_close( $options = null ) {

		$html = '';

		if ( isset($options[self::KEY_OPTIONS_AFTER]) && is_array($options[self::KEY_OPTIONS_AFTER]) ) {
			foreach( $options[self::KEY_OPTIONS_AFTER] as $key => $val ) {
				$html .= self::Option_for_select( $key, $val, $options );
				$html .= "\n";
			}			
		}


		$html .= "</select>\n";

		if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
			return $html;
		}

		echo $html;

	}

	public static function Option_for_select( $text = null, $value = null, $options = null ) {
	
		if ( isset($options[self::$Key_html_option_is_selected]) && $options[self::$Key_html_option_is_selected] ) {
			$selected = ' selected="selected"';			
		} 
		else {
			$selected = null;
		}
		
		$value = htmlspecialchars($value);
		$tag = "<option value=\"{$value}\"{$selected}>{$text}</option>";
		
		if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
			return $tag;			
		}
		else {
			echo $tag;
		}
		
	}


	public static function Checkbox( $options = array() ) {

		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}		
		
		$checked_html = '';
		$input_name    = self::Input_name_from_option_hash($options);
		$default_value = $options[self::$Key_input_value];
		$html_options  = self::Html_attrs_from_params( $options, $html_options );
		$input_id 	   = FormCommon::Input_id_by_options($options);
		
		$options[self::$Key_input_value] = null; //we don't want get_form_value_for_repopulate 
												  //to respect the default value
												  
		$active_value  = FormCommon::Get_form_value_for_repopulate($input_name, $options);

		if ( isset($options['selected_value']) ) {
			if ( strval($default_value) == strval($options['selected_value']) ) {
				$checked_html = ' checked="checked" ';
			}
		}
		else {
			if ( !isset($options['html']['checked']) ) {
				if ( strval($active_value) === strval($default_value) ) {
					$checked_html = ' checked="checked" ';
				} 	
			}
		}		
		
		$html = "<input type=\"checkbox\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$default_value}\"{$html_options}{$checked_html} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}
	
	public static function Radio_button( $options = array() ) {

		static $index = null;
		static $last_input_name = null;
		
		if ( isset($options[self::KEY_CLASS_NAME]) ) {
			
			$model = self::Load_class($options[self::KEY_CLASS_NAME]);
			$options[self::$Key_model_object] = $model;
			
		}		
		
		$input_name    = self::Input_name_from_option_hash($options);
		$input_id 	   = FormCommon::Input_id_by_options($options);
		
		if ( !$input_id ) {
			$input_id = $input_name; //will have an index appended to it
		}
		
		$html_options  = self::Html_attrs_from_params( $options, $html_options );
		
		$default_value = $options[self::$Key_input_value];
		
		$options[self::$Key_input_value] = null; //we don't want get_form_value_for_repopulate 
												  //to respect the default value
												  
		$active_value  = FormCommon::Get_form_value_for_repopulate($input_name, $options);

		if ( $last_input_name ) {
			if ( $input_name != $last_input_name ) {
				$index = 1;
			}
			else {
				$index++;
			}
		}
		else {
			$index = 1;
		}

		$last_input_name = $input_name;
		$input_id .= '_' . $index;
		$checked_html = '';
		
		if ( isset($options['selected_value']) ) {
			if ( strval($default_value) == strval($options['selected_value']) ) {
				$checked_html = ' checked="checked" ';
			}
		}
		else {
			if ( !isset($options['html']['checked']) ) {
				if ( strval($active_value) === strval($default_value) ) {
					$checked_html = ' checked="checked" ';
				} 	
			}
		}
		
		$html = "<input type=\"radio\" id=\"{$input_id}\" name=\"{$input_name}\" value=\"{$default_value}\"{$html_options}{$checked_html} />";
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		
		echo $html;
	}
	


}
?>