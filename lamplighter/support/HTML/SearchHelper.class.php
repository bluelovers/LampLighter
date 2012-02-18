<?php

LL::Require_class('HTML/FormOptionsHelper');

class SearchHelper extends FormOptionsHelper {

	static $Search_prefix = 'search_';

	public static function Setup_search_input( $options ) {
	
		if ( !isset($options[self::$Key_input_name]) ) {
			
			if ( isset($options[self::KEY_CLASS_NAME]) ) {
				$model = self::Load_class($options[self::KEY_CLASS_NAME]);
				$options[self::$Key_input_name] = $model->db_field_name_by_key($options[self::$Key_field_key]);
			}
			
		}

		$options[self::$Key_input_name] = self::$Search_prefix . $options[self::$Key_input_name];
		
		return $options;
		
	}

	public static function Text_field( $class_or_opts, $field_key = null, $options = array() ) {

		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		$options = self::Setup_search_input( $options );

		return parent::Text_field( $options );
	}

	public static function Text_area( $class_or_opts, $field_key = null, $options = array() ) {

		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		$options = self::Setup_search_input( $options );

		return parent::Text_Area( $options );
	}
    
	public static function Hidden_field( $class_or_opts, $field_key = null, $options = array() ) {

		if ( is_scalar($class_or_opts) ) {
			$options[FormCommon::KEY_CLASS_NAME] = $class_or_opts;
			$options[FormCommon::$Key_field_key] = $field_key;

		}
		else {
			$options = $class_or_opts;
		}

		$options = self::Setup_search_input( $options );

		return parent::Hidden_field( $options );
	}    

	public static function Select_all ( $class, $field_key_text, $field_key_value, $options = null, $html_options = null ) {

		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key_value;

		
		$options = self::Setup_search_input( $options );
		
			
		return parent::Select_all( $class, $field_key_text, $field_key_value, $options, $html_options );
	
	}
    
    public static function Select_by_method( $class, $method, $params = null, $field_key_value, $field_key_text, $options = null, $html_options = null ) {
    
		$options[FormCommon::KEY_CLASS_NAME] = $class;
		$options[FormCommon::$Key_field_key] = $field_key_value;

    
    	$options = self::Setup_search_input( $options );
	
		return parent::Select_by_method( $class, $method, $params, $field_key_value, $field_key_text, $options, $html_options );
    }
}
?>