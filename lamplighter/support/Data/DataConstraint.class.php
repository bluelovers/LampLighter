<?php

class DataConstraint {

	const KEY_GIVEN_FORMAT = 'format';
	const KEY_FIELD_KEY_2 = 'field_key_2';
	
	const CONSTRAINT_TYPE_FORMAT = 'format_of';
	const CONSTRAINT_TYPE_MATCHING_VALUES = 'matching_values';

	protected $_Key_constraint_type_numericality = 'numericality';
	protected $_Key_constraint_type_uniqueness = 'uniqueness';
	protected $_Key_constraint_type_presence_of = 'presence_of';
	
	protected $_Key_constraint_type_callback = 'callback';
	protected $_Key_constraint_type_length = 'length';
	
	static $Key_constraint_type_date = 'date';
	
	static $Key_require_double_digits = 'require_double_digits'; //for date
	
	protected $_Key_field_name = 'field';
	protected $_Key_field_name_friendly = 'friendly_name';
	protected $_Key_input_type = 'input_type';
	protected $_Key_constraint_type = 'type';
	protected $_Key_constraint_regexp = 'with';
	protected $_Key_constraint_message = 'message';
	protected $_Key_callback = 'callback';
	protected $_Key_callback_library = 'in_library';
	protected $_Key_interval_min = 'min';
	protected $_Key_interval_max = 'max';
	

	protected $_Input_type;
	protected $_Field_name;
	protected $_Field_name_friendly;
	protected $_Field_key;
	protected $_Regexp;
	protected $_Callback;
	protected $_Callback_library;
	protected $_Message;
	protected $_Form;
	protected $_Form_name;
	protected $_Model_object;
	protected $_Min_length;
	protected $_Max_length;
	protected $_Options;
	

	function set_model( &$obj ) {

		$this->_Model_object = $obj;

	}

	function get_model() {

		return $this->_Model_object;

	}

	function apply_constraint_hash( $hash ) {

		//
		// 
		// TODO 
		// This should be done differently. just store the hash
		// as part of the object and use get_option to pull data.
		// keeping separate members/functions for all data 
		// constraint types is proving to be poorly scalable.
		///

		if ( isset($hash[$this->_Key_constraint_regexp]) ) {
			$this->set_regexp( $hash[$this->_Key_constraint_regexp] );
		}

		if ( isset($hash[$this->_Key_constraint_message]) ) {
			$this->set_message( $hash[$this->_Key_constraint_message] );
		}

		if ( isset($hash[$this->_Key_input_type]) ) {
			$this->set_input_type( $hash[$this->_Key_input_type] );
		}

		if ( isset($hash[$this->_Key_field_name_friendly]) ) {
			$this->set_field_name_friendly( $hash[$this->_Key_field_name_friendly] );
		}

		if ( isset($hash[$this->_Key_callback]) ) {
			$this->set_callback($hash[$this->_Key_callback]);
		}

		if ( isset($hash[$this->_Key_callback_library]) ) {
			$this->set_callback_library($hash[$this->_Key_callback_library]);
		}

		if ( $this->_Type == $this->_Key_constraint_type_length ) {
			if ( isset($hash[$this->_Key_interval_max]) ) {
				$this->set_max_length($hash[$this->_Key_interval_max]);
			}
		
			if ( isset($hash[$this->_Key_interval_min]) ) {
				$this->set_min_length($hash[$this->_Key_interval_min]);
			}
		}

		
		$this->set_options( $hash );
		
		return true;

	}

	function apply_to_form( &$form ) {

		$constraint_type = $this->get_type();
		$input_type = ( $this->_Input_type ) ? $this->_Input_type : constant('FORM_INPUT_TYPE_TEXTBOX');
		$field_name = $this->get_field_name();
		$message = $this->get_message();
		$field_name_friendly = $this->get_field_name_friendly();

		if ( $field_name_friendly ) {
			$form->set_friendly_name( $field_name, LL::Translate($field_name_friendly) );
		}

		switch ( $constraint_type ) {
			case $this->_Key_constraint_type_presence_of:
				$form->set_required_input( $field_name, $input_type );
				break;
			case $this->_Key_constraint_type_numericality:
				$form->require_number( $field_name, null, null, $message );
				break;
			case self::CONSTRAINT_TYPE_FORMAT:
				$regexp = $this->get_regexp();
				
				$form->require_regexp_match( $field_name, $regexp, $this->get_options() );
				break;
			case $this->_Key_constraint_type_length:
				$min_length = $this->get_min_length();
				$max_length = $this->get_max_length();
				if ( $min_length !== null ) {
					$form->require_min_length($field_name, $min_length);
				}
				if ( $max_length !== null ) {
					$form->require_max_length($field_name, $max_length);
				}
				break;
			case $this->_Key_constraint_type_callback:
				$callback = $this->get_callback();

				if ( $library = $this->get_callback_library ) {
					LL::require_library( $library );
				}

				$form->require_callback( $field_name, $callback );
				break;
			case self::$Key_constraint_type_date:
				
				$date_format   = $this->get_option(self::KEY_GIVEN_FORMAT);
				$double_digits = ( $this->get_option(self::$Key_require_double_digits) ) ? true : false;
				
				$form->require_manual_date($field_name, $date_format, $double_digits); 
				break;	
			case self::CONSTRAINT_TYPE_MATCHING_VALUES:
				$field_name_2 = $this->field_name_by_key($this->get_option(self::KEY_FIELD_KEY_2));
			
				$form->require_input_match($field_name, $field_name_2);
				break;
				
		}
		
		return true;

	}

	function get_callback() {

		return $this->_Callback;

	}

	function set_callback( $callback ) {

		$this->_Callback = $callback;

	}

	function set_callback_library( $library ) {

		$this->_Callback_library = $library;

	}

	function get_callback_library() {

		return $this->_Callback_library;

	}

	function get_regexp() {

		return $this->_Regexp;

	}

	function set_type( $type ) {
	
		$this->_Type = $type;

	}

	function get_type() {

		return $this->_Type;

	}

	function set_field_name_friendly( $friendly_name ) {

		$this->_Field_name_friendly = $friendly_name;

	}

	function get_field_name_friendly() {

		return $this->_Field_name_friendly;

	}

	function set_field_name( $field_name ) {

		$this->_Field_name = $field_name;

	}

	function set_field_key( $field_key ) {

		$this->_Field_key = $field_key;

	}

	function get_field_key() {

		return $this->_Field_key;

	}

	function get_field_name() {

		if ( !$this->_Field_name ) {

			$model = $this->get_model();

			if ( $field_key = $this->get_field_key() ) {
				$this->_Field_name = $model->input_key_to_hashtable_ref($field_key);
			}
		}

		return $this->_Field_name;

	}

	public function field_name_by_key( $field_key ) {

		if ( $model = $this->get_model() ) {

			return $model->input_key_to_hashtable_ref($field_key);
		}
		
		return $field_key;

	}

	function set_input_type( $type ) {

		$this->_Input_type = $type;

	}

	function set_regexp( $regexp ) {

		$this->_Regexp = $regexp;

	}

	function set_message( $message ) {
	
		$this->_Message = $message;

	}

	function get_message() {

		return $this->_Message;

	}

	function set_min_length($length) {
	
		$this->_Min_length = $length;	
	
	}
	
	function set_max_length($length) {
	
		$this->_Max_length = $length;	
	
	}
	
	function get_min_length() {
	
		return $this->_Min_length;	
	
	}
	
	function get_max_length() {
	
		return $this->_Max_length;	
	
	}
	
	function set_options( $options ) {
		$this->_Options = $options;
	}
	
	function add_option( $option ) {
		$this->_Options[] = $option;
	}
	
	function get_option( $key ) {
		
		if ( isset($this->_Options[$key]) ) {
			return $this->_Options[$key];
		}
		
		return null;
	}
	
	function get_options() {
		
		return $this->_Options;
		
	}

}

?>