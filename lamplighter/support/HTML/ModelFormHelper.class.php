<?php

LL::require_class('HTML/TemplateHelper');
LL::require_class('HTML/FormInput');
LL::require_class('HTML/FormCommon');

class FormHelper extends TemplateHelper {

	public static function Text_field( $class, $field_key = null, $options = array() ) {

		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key;

		return FormInput::Text_field( $options );
	}

	public static function Password_field( $class, $field_key = null, $options = array() ) {
		
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key;

		return FormInput::Password_field( $options );

	}
	
	public static function Hidden_field( $class, $field_key = null, $options = array()  ) {
		
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key;

		return FormInput::Hidden_field( $options );
	}	
	
	public static function Text_area( $class, $field_key = null, $options = array() ) {
		
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key;

		return FormInput::Text_area( $options );
	}

	public static function Tinymce_area( $class, $field_key = null, $options = array() ) {
		
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key;

		return FormInput::Tinymce_area( $options );
		
	}

	public static function File_input( $class, $field_key = null, $options = array() ) {
		
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key;

		return FormInput::File_input( $options );
		
	}

	public static function Photo_file_input( $class, $options = array() ) {
		
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		
		return FormInput::File_input( $options );
		
	}
	


}
?>