<?php

class FormDataProcessor {
	
	const KEY_DATA_TYPE = 'datatype';
	
	const DATA_TYPE_DATE 	 = 'date';
	const DATA_TYPE_TIME 	 = 'time';
	const DATA_TYPE_DATETIME = 'datetime';

	const AMPM_VAL_AM = 'AM';
	const AMPM_VAL_PM = 'PM';
	
	protected $_Form_incoming;
	protected $_Form_outgoing;
	protected $_Field_values;
	protected $_Model;
	protected $_Require_model = true;
	protected $_Time_and_date_fields = array();

	public $date_format_default = 'Y-m-d';
	public $time_format_default = 'H:i:s';
	
	public $suffix_month   = '_month';
	public $suffix_day     = '_day';
	public $suffix_year	   = '_year';
	public $suffix_hour    = '_hour';
	public $suffix_minute  = '_minute';
	public $suffix_seconds = '_second';
	public $suffix_ampm	   = '_ampm';
	public $suffix_time	   = '_time';
	public $suffix_date	   = '_date';
	
	public function set_model( $model ) {
		$this->_Model = $model;
	}
	
	public function get_model() {
		return $this->_Model;
	}

	public function set_form( $form ) {
		$this->_Form = $form;
	}
	
	public function get_form_incoming() {
	
		
		if ( !$this->_Form_incoming ) {
			if ( $model = $this->get_model() ) {
				//$this->_Form_incoming = clone $model->get_form();
				$this->_Form_incoming = $model->get_form();
			}
			else {
				if ( $this->_Require_model ) {
					throw new Exception( __CLASS__ . '-missing_model' );
				}
				else {
					LL::require_class('Form/InputFormValidator');
					$this->_Form_incoming = new InputFormValidator();
				}
			}
		} 
		
		return $this->_Form_incoming;
		
	}

	public function get_form_outgoing() {
	
		
		if ( !$this->_Form_outgoing ) {
		
			$row = null;
		
			if ( $model = $this->get_model() ) {
			
				$form = clone $model->get_form();
				$row  = $model->get_record();
				$row  = $model->record_row_to_form_hashtable($row);
				
				$form->set_dataset($row);
			}
			else {
				LL::require_class('Form/InputFormValidator');
				$form = new InputFormValidator();
			}
			
			$this->_Form_outgoing = $form;
			
		}
		
		return $this->_Form_outgoing;
		
	}
	
	public function process_incoming() {
		
		try {
			
			$form_values  = $this->get_form_values();
			$model 		  = $this->get_model();
			
			
			if ( $model ) {
				$field_names  = $model->get_column_names();
			
				foreach( $field_names as $cur_field_name ) {

					$found_time = false;
					$found_date = false;					
					$field_key = $model->column_key_by_name($cur_field_name);
		
					if ( isset($form_values[$field_key . $this->suffix_hour])
						 || isset($form_values[$field_key . $this->suffix_minute])
						 || isset($form_values[$field_key . $this->suffix_seconds])
						 || isset($form_values[$field_key . $this->suffix_time]) ) {

						$found_time = true;

					}

					if ( isset($form_values[$field_key . $this->suffix_month])
						 || isset($form_values[$field_key . $this->suffix_day])
						 || isset($form_values[$field_key . $this->suffix_year]) 
						 || isset($form_values[$field_key . $this->suffix_date]) ) {

						$found_date = true;
						
					
					}

					if ( $found_time || $found_date ) {	

						if ( $found_time && $found_date) {
							$data_type = self::DATA_TYPE_DATETIME;	
						}
						else if ( $found_time ) {
							$data_type = self::DATA_TYPE_TIME;
						}
						else if ( $found_date ) {
							$data_type = self::DATA_TYPE_DATE;
							
						}

						$this->process_time_or_date_field_incoming($field_key, $data_type);
					}
					

				}
						
			}
			
			return $this->get_form_incoming();
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function process_outgoing() {
		
		try {

			/* No processing required here */
			
			return $this->get_form_outgoing();
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}



	function process_time_or_date_field_outgoing( $field_key ) {

		try {
				
				if ( !($model = $this->get_model()) ) {
					throw new Exception( __CLASS__ . '-missing_model' );
				}
				
				if ( !($field_details = $model->time_or_date_field_details($field_key)) ) {
					throw new Exception( __CLASS__ . '-no_time_or_date_field_info', "\$field_name: {$field_name}" );
				}
		
				$field_type = $field_details[self::KEY_DATA_TYPE];
				
				switch($field_type) {
					case self::DATA_TYPE_DATE:
						$this->process_date_field_outgoing($field_key);
						break;
					case self::DATA_TYPE_TIME:
						$this->process_time_field_outgoing($field_key);
						break;
					case self::DATA_TYPE_DATETIME:
						$this->process_datetime_field_outgoing($field_key);
						break;
					default:
						throw new Exception( __CLASS__ . '-unknown_data_type', "\$field_type: {$field_type}" );
				}				
			
						
		}	
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function process_date_field_outgoing( $field_key ) {
		
		try {
			
			if ( $model = $this->get_model() ) {
			
				if ( $db_date = $model->$field_key ) {
						
						$php_format = $this->php_date_format_by_field_key($field_key);
				
						
						$timestamp  = strtotime($db_date);
											
						$this->set_form_value_outgoing( $field_key, date($php_format, $timestamp) );
						$this->set_form_value_outgoing( $field_key . $this->suffix_month, date('n', $timestamp) );
						$this->set_form_value_outgoing( $field_key . $this->suffix_day, date('j', $timestamp) );
						$this->set_form_value_outgoing( $field_key . $this->suffix_year, date('Y', $timestamp) );
					
				}
			
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function process_time_field_outgoing( $field_key ) {
		
		try {
			if ( $model = $this->get_model() ) {
			
				if ( $db_time = $model->$field_key ) {
				
						$php_format = $this->php_time_format_by_field_key($field_key);
					
						//
						// Note we can use any year, month, day combination
						// since we're only interested in stripping
						// out the time
						//
						$timestamp  = strtotime("2007-02-14 {$db_time}");
					
						$this->set_form_value_outgoing( $field_key, date($php_format, $timestamp) );
						$this->set_form_value_outgoing( $field_key . $this->suffix_hour, date('h', $timestamp));
						$this->set_form_value_outgoing( $field_key . $this->suffix_minute, date('i', $timestamp));
						$this->set_form_value_outgoing( $field_key . $this->suffix_seconds, date('s', $timestamp));
						$this->set_form_value_outgoing( $field_key . $this->suffix_ampm, date('A', $timestamp));
					
				}
			
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function php_date_format_by_field_key( $field_key ) {

		LL::Require_class('Data/DataConstraint');
		
		$php_format = $this->date_format_default;
		
		if ( $model = $this->get_model() ) {
				
				$constraint     = $model->data_constraint_by_field_key($field_key, DataConstraint::CONSTRAINT_TYPE_FORMAT);
					
				if ( $constraint ) {
					$given_format = $constraint->get_option(DataConstraint::KEY_GIVEN_FORMAT);
				}

				if ( isset($given_format) && $given_format ) {
					LL::require_class('DateTime/DateHelper');
					$php_format = DateHelper::basic_date_format_to_php_date_format($given_format);
				}
		}
		
		return $php_format;
		
	}

	public function php_time_format_by_field_key( $field_key ) {

		LL::Require_class('Data/DataConstraint');
			
		$php_format = $this->time_format_default;
		
		if ( $model = $this->get_model() ) {
				
				$constraint     = $model->data_constraint_by_field_key($field_key, DataConstraint::CONSTRAINT_TYPE_FORMAT);
					
				if ( $constraint ) {
					$given_format = $constraint->get_option(DataConstraint::KEY_GIVEN_FORMAT);
				}

				if ( isset($given_format) && $given_format ) {
					LL::require_class('DateTime/TimeHelper');
					$php_format = TimeHelper::basic_time_format_to_php_time_format($given_format);
				}
		}
		
		return $php_format;
		
	}
	
	public function process_datetime_field_outgoing( $field_key ) {
				
		if ( $model = $this->get_model() ) {
			
			if ( $db_datetime = $model->$field_key ) {
									
					$date_text_field_key = $field_key . $this->suffix_date;
					$php_date_format = $this->php_date_format_by_field_key($date_text_field_key);

					$time_text_field_key = $field_key . $this->suffix_time;
					$php_time_format = $this->php_time_format_by_field_key($time_text_field_key);

					$php_datetime_format = "{$php_date_format} {$php_time_format}";
					
					//
					// Note: we can use any year, month, day combination
					// since we're only interested in stripping
					// out the time
					//
					
					$timestamp  = strtotime($db_datetime);
					
					$this->set_form_value_outgoing( $field_key, date($php_datetime_format, $timestamp) );
					$this->set_form_value_outgoing( $field_key . $this->suffix_date, date($php_date_format, $timestamp));
					$this->set_form_value_outgoing( $field_key . $this->suffix_time, date($php_time_format, $timestamp));
					
					$this->set_form_value_outgoing( $field_key . $this->suffix_month, date('n', $timestamp) );
					$this->set_form_value_outgoing( $field_key . $this->suffix_day, date('j', $timestamp) );
					$this->set_form_value_outgoing( $field_key . $this->suffix_year, date('Y', $timestamp) );
					
					$this->set_form_value_outgoing( $field_key . $this->suffix_hour, date('h', $timestamp));
					$this->set_form_value_outgoing( $field_key . $this->suffix_minute, date('i', $timestamp));
					$this->set_form_value_outgoing( $field_key . $this->suffix_seconds, date('s', $timestamp));
					$this->set_form_value_outgoing( $field_key . $this->suffix_ampm, date('A', $timestamp));
					
								
			}
			
		}
		
		
	}
	
	public function get_form_values() {
		
		try {
			
			if ( !$this->_Field_values ) {

				if ( !$form = $this->get_form_incoming() ) {
					throw new Exception( __CLASS__ . '-missing_form' );
				}

				
				$dataset = $form->get_dataset();
			
				if ( !($model = $this->get_model()) ) {
					if ( $this->_Require_model ) {
						throw new Exception ( __CLASS__ . '-missing_model' );
					}
					else {
						$this->_Field_values = $dataset;
					}
				}
				else {
					
					$this->_Field_values = $model->get_form_values();
					
				}
			
				if ( !is_array($this->_Field_values) ) {
					throw new Exception( __CLASS__ . '-invalid_field_data' );
				}
			}
			
			return $this->_Field_values;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	/*
	public function set_field_value( $key, $val ) {
		
		//
		// make sure we pull in our original field values first,
		// otherwise they'll get ignored because the
		// _Field_values array will appear to already be populated
		//
		$field_values = $this->get_form_values();
		
		$field_values[$key] = $val;
		
		$this->_Field_values = $field_values; 
	}
	*/

	public function unset_form_value_incoming( $key ) {
		
		try {
			if ( $model = $this->get_model() ) { 
				$model->unset_form_value( $key );
			}
			else {
				$form = $this->get_form_incoming();
				$form->unset_field( $key );
			}
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}


	public function set_form_value_incoming( $key , $val ) {
		
		try {
			if ( $model = $this->get_model() ) { 
				$model->set_form_value( $key, $val );
			}
			else {
				$form = $this->get_form_incoming();
				$form->set( $key, $val );
			}
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function set_form_value_outgoing( $key , $val ) {
		
		try {
			if ( $model = $this->get_model() ) { 
				$model->set_form_value( $key, $val );
			}
			else {
				$form = $this->get_form_outgoing();
				$form->set( $key, $val );
			}
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	function process_time_or_date_field_incoming( $field_key, $field_type = null ) {

		try {
				
				$model = $this->get_model();
				$hashtable_key = $model->get_hashtable_key();
				$form  = $this->get_form_incoming();
				
				if ( !$field_type ) {
					if ( !$model ) {
						throw new Exception( __CLASS__ . '-missing_model' );
					}
				
					if ( !($field_details = $model->time_or_date_field_details($field_key)) ) {
						throw new Exception( __CLASS__ . '-no_time_or_date_field_info', "\$field_name: {$field_name}" );
					}
		
					$field_type = $field_details[self::KEY_DATA_TYPE];
				}
		
				switch($field_type) {
					case self::DATA_TYPE_DATE:
						//$this->set_form_value_incoming( $field_key , $this->process_date_field_incoming($field_key) );
						if ( $processed = $this->process_date_field_incoming($field_key) ) {
							
							$model->$field_key = $processed;
							$form->unset_field("{$hashtable_key}[{$field_key}]");
						}
						
						break;
					case self::DATA_TYPE_TIME:
						//$this->set_form_value_incoming( $field_key, $this->process_time_field_incoming($field_key) );
						if ( $processed = $this->process_time_field_incoming($field_key) ) {
							
							$model->$field_key = $processed;
							$form->unset_field("{$hashtable_key}[{$field_key}]");
														
						}
						break;
					case self::DATA_TYPE_DATETIME:
						//$this->set_form_value_incoming( $field_key, $this->process_datetime_field_incoming($field_key) );
						if ( $processed = $this->process_datetime_field_incoming($field_key) ) {
							$model->$field_key = $processed;
							$form->unset_field("{$hashtable_key}[{$field_key}]");							
						}
						break;
					default:
						throw new Exception( __CLASS__ . '-unknown_data_type', "\$field_type: {$field_type}" );
				}				
			
						
		}	
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	function process_date_field_incoming( $field_key ) {
		
		try {

			
			$form_field_values = $this->get_form_values();

			LL::Require_class('PDO/PDOFactory');
	
			$db    = ( $model = $this->get_model() ) ? $model->get_db_object() : PDOFactory::Instantiate();
			$month = null;
			$day   = null;
			$year  = null;
			$formatted_date = null;
			$timestamp = null;
			
			if ( $this->is_date_field_split($field_key) ) {
			
					$month_key = $field_key . $this->suffix_month;
					$day_key   = $field_key . $this->suffix_day;
					$year_key  = $field_key . $this->suffix_year;
					
					if ( isset($form_field_values[$month_key]) && is_numeric($form_field_values[$month_key]) ) {
						$month = $form_field_values[$month_key];
					}
					
					if ( isset($form_field_values[$day_key]) && is_numeric($form_field_values[$day_key]) ) {
						$day = $form_field_values[$day_key];
						
					}
					
					if ( isset($form_field_values[$year_key]) && is_numeric($form_field_values[$year_key]) ) {
						$year = $form_field_values[$year_key];
					}
					
			}
			else {
				
				if ( isset($form_field_values[$field_key]) ) {
					$timestamp = strtotime($form_field_values[$field_key]);
				}
				else {
					// this could be a text field entry
					// for datetime_date
					$date_field_key = $field_key . $this->suffix_date;
					
					if ( isset($form_field_values[$date_field_key]) ) {
						$timestamp = strtotime($form_field_values[$date_field_key]);
					}
				}
				
				if ( $timestamp ) {
					$month = date('m', $timestamp);
					$day   = date('d', $timestamp);
					$year  = date('Y', $timestamp);
					
				}

			}
			
			if ( !is_numeric($month) ) $month = null;
			if ( !is_numeric($day) ) $day = null;
			if ( !is_numeric($year) ) $year = null;
			
			if ( $month || $day || $year ) {
				return $db->date_format($month, $day, $year);
			}
			else {
				return null;
			}
							
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	function is_date_field_split( $field_key ) {
		
		try {

			$form_field_values = $this->get_form_values();
			
			$suffixes = $this->get_date_suffixes();
			
			foreach( $suffixes as $cur_suffix ) {
				
				if ( isset($form_field_values[$field_key . $cur_suffix]) ) {
					return true;
				}
				
			}
			
			return 0;
							
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function get_date_suffixes() {
	
		return array( $this->suffix_month, $this->suffix_day, $this->suffix_year );
	}

	function process_time_field_incoming( $field_key ) {
		
		try {

			LL::Require_class('PDO/PDOFactory');
	
			$form_field_values = $this->get_form_values();

			$db    = ( $model = $this->get_model() ) ? $model->get_db_object() : PDOFactory::Instantiate();
			$hour    = null;
			$minute  = null;
			$seconds = null;
			$formatted_date = null;
			$timestamp = null;
			
			if ( $this->is_time_field_split($field_key) ) {
				
				
				$hour_key 	  = $field_key . $this->suffix_hour;
				$minute_key   = $field_key . $this->suffix_minute;
				$seconds_key  = $field_key . $this->suffix_seconds;
				$ampm_key  	  = $field_key . $this->suffix_ampm;
					
				if ( isset($form_field_values[$hour_key]) && is_numeric($form_field_values[$hour_key]) ) {
					$hour = $form_field_values[$hour_key];
				}
					
				if ( isset($form_field_values[$minute_key]) && is_numeric($form_field_values[$minute_key]) ) {
					$minute = $form_field_values[$minute_key];
				}
					
				if ( isset($form_field_values[$seconds_key]) && is_numeric($form_field_values[seconds_key]) ) {
					$seconds = $form_field_values[$seconds_key];
				}
					
				if ( isset($form_field_values[$ampm_key]) ) {
					
					if ( $form_field_values[$ampm_key] == self::AMPM_VAL_PM ) {
						if ( $hour < 12 ) {
							$hour += 12;
						}
						else if ($hour == 12){
							$hour = 0;
						}
					}
				}
					
			}
			else {
				if ( isset($form_field_values[$field_key]) ) {
					$timestamp = strtotime($form_field_values[$field_key]);
				}
				else {
					// this could be a text field entry
					// for datetime_time
					$time_field_key = $field_key . $this->suffix_time;
					if ( isset($form_field_values[$time_field_key]) ) {
						$timestamp = strtotime($form_field_values[$time_field_key]);
					}
				}

				if ( $timestamp ) {					
					
					$hour     = date('H', $timestamp);
					$minute   = date('i', $timestamp);
					$seconds  = date('s', $timestamp);
					
				}
				
			}
			
			if ( !is_numeric($hour) ) $hour = null;
			if ( !is_numeric($minute) ) $minute = null;
			if ( !is_numeric($seconds) ) $seconds = null;
			
			if ( $hour || $minute || $seconds ) {
				return $db->time_format($hour, $minute, $seconds);
			}
			else {
				return null;
			}
							
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function process_datetime_field_incoming( $field_key ) {
		
		try {
			$date = $this->process_date_field_incoming($field_key);
			$time = $this->process_time_field_incoming($field_key);
			
			if ( $date || $time ) {
				return "{$date} {$time}";
			}
			else {
				return null;
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	function is_time_field_split( $field_key ) {
		
		try {

			$form_field_values = $this->get_form_values();
			
			$suffixes = array( $this->suffix_hour, $this->suffix_minute, $this->suffix_seconds );
			
			foreach( $suffixes as $cur_suffix ) {
				if ( isset($form_field_values[$field_key . $cur_suffix]) ) {
					return true;
				}
				
			}
			
			return 0;
							
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	

}

?>