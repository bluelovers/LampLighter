<?php

LL::require_class('HTML/TemplateHelper');
LL::Require_class('HTML/FormCommon');
LL::Require_class('HTML/FormInput');

class FormHelper extends FormCommon {

	public static function Text_field( $class_or_opts, $field_key = null, $options = null, $html_options = null ) {

		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return FormInput::Text_field( $options );
		
	}

	public static function Password_field( $class_or_opts, $field_key = null, $options = array(), $html_options = null ) {
		
		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return FormInput::Password_field( $options );
	}
	
	public static function Hidden_field( $class_or_opts, $field_key = null, $options = array(), $html_options = null ) {

		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return FormInput::Hidden_field($options);
	}	
	

	function Text_area( $class_or_opts, $field_key = null, $options = array() ) {
		
		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		return FormInput::Text_area( $options );
	}

	public static function Rich_text_area( $class, $field_key = null, $options = null, $html_options = null ) {
		
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

	
	public static function Tinymce_area( $class_or_opts, $field_key = null, $options = array() ) {
		
		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		return FormInput::Tinymce_area( $options );		
	}

	public static function File_input( $class_or_opts, $field_key = null, $options = array(), $html_options = null ) {
		
		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}

		return FormInput::File_input( $options );
	}

	public static function Photo_file_input( $class_or_opts, $options = array(), $html_options = null ) {
		
		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;

		}
		else {
			$options = $class_or_opts;
		}

		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return FormInput::Photo_file_input( $options );
	}

	public static function Checkbox( $class_or_opts, $field_key, $options = null, $html_options = null ) {
		
		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}
		
		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return FormInput::Checkbox($options);
	}
	
	public static function Radio_button( $class_or_opts, $field_key, $options = null, $html_options = null ) {

		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}
		
		if ( $html_options ) {
			$options[FormCommon::KEY_HTML] = $html_options;
		}
		
		return FormInput::Radio_button($options);
		

	}

}
?>