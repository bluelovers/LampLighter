<?php

LL::require_class('HTML/TemplateHelper');
LL::require_class('HTML/FormOptionsHelper');


class FormDateHelper extends FormOptionsHelper {

	const KEY_TIME_INCLUDE_SECONDS = 'include_seconds';
	const KEY_TIME_USE_24_HOUR	   = 'use_24_hour';
	const KEY_TIME_AM			   = 'AM';
	const KEY_TIME_PM			   = 'PM';
	const KEY_DATE_YEAR_START	   = 'start_year';
	const KEY_DATE_YEAR_END	   	   = 'end_year';
	const KEY_DATE_YEARS_AHEAD	   = 'years_ahead';
	const KEY_DATE_YEARS_BEHIND	   = 'years_behind';
	const KEY_DATE_VALUE		   = 'date';
	
	const SUFFIX_MONTH = '_month';
	const SUFFIX_DAY      = '_day';
	const SUFFIX_YEAR     = '_year';
	const SUFFIX_HOUR     = '_hour';
	const SUFFIX_MINUTE   = '_minute';
	const SUFFIX_SECIONDS = '_seconds';
	const SUFFIX_AMPM	  = '_ampm';
	const SUFFIX_DATE	  = '_date';
	
	static $Key_month_name_display_abbreviation = 'use_short_month';
	static $Key_month_name_display_numbers      = 'use_month_numbers';
	static $Key_month_name_arr		   		    = 'use_month_names';

		
	public static function Select_day( $options = array(), $html_options = null ) {

		$days = array();

 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'day', $options);

	
		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}
	
	
		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options);
		 
		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('d', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key, $options);
			}
		}
		
		for ( $i = 1; $i <= 31; $i++ ) {
			$days[] = str_pad($i, 2, '0', STR_PAD_LEFT);
		}
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		$html  = self::Select_tag_open($field_name, $option_tag_options, $html_options);
		$html .= self::Options_from_array_for_select($days, $option_tag_options);
		$html .= self::Select_tag_close($option_tag_options);
		
		if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Select_month( $options = array(), $html_options = null ) {
		
		$months     = array();
		$month_names = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'month', $options);


		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}

		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options); 

		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('m', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key, $options);
			}
		}


		
		if ( isset($options[self::$Key_month_name_arr]) ) {
			if ( is_array($options[self::$Key_month_name_arr]) ) {
				$month_names = $options[self::$Key_month_name_arr];
			}
		}
		
		
		for ( $i = 1; $i <= 12; $i++ ) {
			
			if ( array_val_is_nonzero($options, self::$Key_month_name_display_numbers) ) {						
				$month_text = $i;
			}
			else if ( array_val_is_nonzero($options, self::$Key_month_name_display_abbreviation) ) {
				$month_text = substr($month_names[$i-1], 0, 3);
			}
			else {
				$month_text = $month_names[$i-1];
			}
			
			$months[$month_text] = str_pad($i, 2, '0', STR_PAD_LEFT);
		}
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		
		
		$html  = self::Select_tag_open($field_name, $option_tag_options, $html_options);
		$html .= self::Options_from_array_for_select($months, $option_tag_options);
		$html .= self::Select_tag_close($option_tag_options);
		
		if ( isset($options[self::$Key_return_output]) && $options[self::$Key_return_output] ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}

	public static function Select_year( $options = array(), $html_options = null ) {
		
		$years      = array();

 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'year', $options);


		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}
	
		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options); 

		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('Y', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key, $options);
			}
		}
		
		$now_year   = date('Y', time());
		$start_year = ( isset($options[self::KEY_DATE_YEAR_START]) ) ? $options[self::KEY_DATE_YEAR_START] : $now_year;
		$end_year   = ( isset($options[self::KEY_DATE_YEAR_END]) ) ? $options[self::KEY_DATE_YEAR_END] : null;

		if ( isset($options[self::KEY_DATE_YEARS_BEHIND]) ) {
			$start_year -= $options[self::KEY_DATE_YEARS_BEHIND];
		}
		
		if ( !$end_year ) {
		
			if ( isset($options[self::KEY_DATE_YEARS_AHEAD]) ) {
				$end_year = $now_year + $options[self::KEY_DATE_YEARS_AHEAD];
			} 
			else { 
				$end_year = $now_year;
			}
		}
		
		$direction = ( $start_year > $end_year ) ? '-' : '+'; 
		$cur_year  = $start_year;
		$num_years = 0;

		do {
			$years[$cur_year] = $cur_year;
			
			if ( $direction == '+' ) {
				$cur_year++;
			}
			else {
				$cur_year--;
			}
			
			$num_years++;
			
			$check_year = ( $direction == '-' ) ? $end_year - 1 : $end_year + 1;
			
			if ( $cur_year == $check_year ) {
				break;
			}
			
		} while ( ($num_years < 1000) ); //num years is a sanity check
		
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		$html  = self::Select_tag_open($field_name, $option_tag_options, $html_options);
		$html .= self::Options_from_array_for_select($years, $option_tag_options);
		$html .= self::Select_tag_close($option_tag_options);
		
		if (array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Select_hour( $options = array(), $html_options = null ) {
		
		$hours      = array();
		
 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'hour', $options);

		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}

	
		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options); 
		
		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('h', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key);
			}
		}
		
		$hour_min = ( array_val_is_nonzero($options, self::KEY_TIME_USE_24_HOUR) ) ? 0 : 1; 
		$hour_max = ( array_val_is_nonzero($options, self::KEY_TIME_USE_24_HOUR) ) ? 23 : 12;
		
		for ( $i = $hour_min; $i <= $hour_max; $i++ ) {
				
			$hour_val = str_pad( $i, 2, '0', STR_PAD_LEFT );
			
			$hours[$hour_val] = $hour_val;
		}
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		$html  = self::Select_tag_open($field_name, $option_tag_options, $html_options);
		$html .= self::Options_from_array_for_select($hours, $option_tag_options);
		$html .= self::Select_tag_close($option_tag_options);
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Select_minute( $options = array(), $html_options = null ) {
		
		$minutes   = array();

 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'minute', $options);

		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}

	
		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options); 
		
		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('i', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key);
			}
		}

		for ( $i = 0; $i <= 59; $i++ ) {
				
			$minute_val = str_pad( $i, 2, '0', STR_PAD_LEFT );
			
			$minutes[$minute_val] = $minute_val;
		}
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		$html  = self::Select_tag_open($field_name, $option_tag_options, $html_options);
		$html .= self::Options_from_array_for_select($minutes, $option_tag_options);
		$html .= self::Select_tag_close($option_tag_options);
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Select_seconds( $options = array(), $html_options = null ) {
		
		$seconds    = array();

 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'seconds', $options);


		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}

	
		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options); 
		
		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('s', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key);
			}
		}
		
		for ( $i = 0; $i <= 59; $i++ ) {
				
			$second_val = str_pad( $i, 2, '0', STR_PAD_LEFT );
			
			$seconds[$second_val] = $second_val;
		}
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		$html  = self::Select_tag_open($field_name, $option_tag_options, $html_options);
		$html .= self::Options_from_array_for_select($seconds, $option_tag_options);
		$html .= self::Select_tag_close($option_tag_options);
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}

	public static function Date_select( $class, $field_key, $options = null, $html_options = null ) {
			
		$html  = '';
		$model = self::Load_class($class);
		
		if ( !is_array($options) ) $options = array();
		
		$sub_options = $options;
		
		$sub_options[self::$Key_model_object] = $model;
		$sub_options[self::$Key_return_output] = true;
		$sub_options[self::KEY_DATE_FORMAT] = null;
		
		$date_value = self::Get_form_value_for_repopulate($field_key, $sub_options);

		//
		// Month
		//
		unset($sub_options[self::$Key_input_name]);
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_MONTH;
		$sub_options[self::KEY_DATE_FORMAT] = 'n';
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		$html .= self::Select_month( $date_value, $sub_options, $html_options );
		
		//
		// Day
		//
		unset($sub_options[self::$Key_input_name]);
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_DAY;
		$sub_options[self::KEY_DATE_FORMAT] = 'j';
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		$html .= self::Select_day( $date_value, $sub_options, $html_options );

		//
		// Year
		//
		unset($sub_options[self::$Key_input_name]);
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_YEAR;
		$sub_options[self::KEY_DATE_FORMAT] = 'Y';
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		$html .= self::Select_year( $date_value, $sub_options, $html_options );
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}

	public static function Month_select( $class, $field_key, $options = null, $html_options = null ) {
			
		$html  = '';
		$model = self::Load_class($class);
		
		if ( !is_array($options) ) $options = array();
		
		$sub_options = $options;
		
		$sub_options[self::$Key_model_object] = $model;
		$sub_options[self::$Key_return_output] = true;
		$sub_options[self::KEY_DATE_FORMAT] = null;
				
		$date_value = self::Get_form_value_for_repopulate($field_key, $sub_options);
		
		//
		// Month
		//
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_MONTH;
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		$sub_options[self::KEY_DATE_FORMAT] = 'n';
		$sub_options[self::KEY_DATE_VALUE] = $date_value;
		
		$html .= self::Select_month( $sub_options, $html_options );
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}

	public static function Year_select( $class, $field_key, $options = null, $html_options = null ) {
			
		$html  = '';
		$model = self::Load_class($class);
		
		if ( !is_array($options) ) $options = array();
		
		$sub_options = $options;
		
		$sub_options[self::$Key_model_object] = $model;
		$sub_options[self::$Key_return_output] = true;
		$sub_options[self::KEY_DATE_FORMAT] = null;
		
		$date_value = self::Get_form_value_for_repopulate($field_key, $sub_options);
		
		//
		// Year
		//
		unset($sub_options[self::$Key_input_name]);
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_YEAR;
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		
		$sub_options[self::KEY_DATE_VALUE] = $date_value;
		
		$html .= self::Select_year( $sub_options, $html_options );
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Month_year_select( $class, $field_key, $options = null, $html_options = null ) {

		$html = self::Month_select( $class, $field_key, $options, $html_options );
		$html .= self::Year_select( $class, $field_key, $options, $html_options );
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
	}
	
	public static function Time_select( $class, $field_key, $options = null, $html_options = null ) {
			
		$html  = '';
		$model = self::Load_class($class);
		
		if ( !is_array($options) ) $options = array();
		
		$sub_options = $options;
		
		$sub_options[self::$Key_model_object] = $model;
		$sub_options[self::$Key_return_output] = true;
		$sub_options[self::KEY_DATE_FORMAT] = null;
		
		
		$date_value = self::Get_form_value_for_repopulate($field_key, $sub_options);
		
		//
		// Hour
		//
		unset($sub_options[self::$Key_input_name]);
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_HOUR;
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		$sub_options[self::KEY_DATE_VALUE] = $date_value;
		
		$html .= self::Select_hour( $sub_options, $html_options );
		
		//
		// Minute
		//
		unset($sub_options[self::$Key_input_name]);
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_MINUTE;
		$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
		$sub_options[self::KEY_DATE_VALUE] = $date_value;
		
		$html .= self::Select_minute( $sub_options, $html_options );

		//
		// Second
		//
		if ( isset($options[self::KEY_TIME_INCLUDE_SECONDS]) && $options[self::KEY_TIME_INCLUDE_SECONDS] ) {
			unset($sub_options[self::$Key_input_name]);
			$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_SECONDS;
			$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
			$sub_options[self::KEY_DATE_VALUE] = $date_value;
		
			$html .= self::Select_seconds( $sub_options, $html_options );
		}
		
		//
		// AMPM
		//
		if ( !isset($options[self::KEY_TIME_USE_24_HOUR]) || !$options[self::KEY_TIME_USE_24_HOUR] ) {
			unset($sub_options[self::$Key_input_name]);
			$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_AMPM;
			$sub_options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options);
			$sub_options[self::KEY_DATE_VALUE] = $date_value;
		
			$html .= self::Select_ampm( $sub_options, $html_options );
		}
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Datetime_select( $class, $field_key, $options = null, $html_options = null ) {
		
		try {
			$inner_options = $options;
			$inner_options[self::$Key_return_output] = true;
		
			$html = self::Date_select( $class, $field_key, $inner_options, $html_options );
			$html .= self::Time_select( $class, $field_key, $inner_options, $html_options );
		
			if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
				return $html;
			}
			else {
				echo $html;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Selection_field_key_by_options( $default_key, $options = array() ) {
	
		if ( !isset($options[self::$Key_field_key]) ) {
		 	if ( isset($options[self::$Key_input_name]) ) {
		 		return $options[self::$Key_input_name]; 
		 	}
		 	else {
		 		return $default_key;
		 	}	
		}
		else {
			return $options[self::$Key_field_key];
		}
		
	}

	public static function Select_ampm( $options = null, $html_options = null ) {
		
		$seconds    = array();

		if ( is_array($options) && array_key_exists(self::KEY_DATE_VALUE, $options) ) {
			$date = $options[self::KEY_DATE_VALUE];
		}

 		$options[self::$Key_field_key] = self::Selection_field_key_by_options( 'ampm', $options);
	
		$field_key  = $options[self::$Key_field_key];
		$field_name = self::Input_name_from_option_hash($options); 
		
		if ( !isset($options[self::KEY_DATE_FORMAT]) || !$options[self::KEY_DATE_FORMAT] ) {
			$options[self::KEY_DATE_FORMAT] = 'A';	
		}
		
		if ( !isset($options[self::$Key_html_option_value_selected]) ) {
			if ( $date ) {
				$options[self::$Key_html_option_value_selected] = date('A', strtotime($date) );
			}
			else {
				$options[self::$Key_html_option_value_selected] =  self::Get_form_value_for_repopulate($field_key, $options);
			}
		}
		
		$ampm[self::KEY_TIME_AM] = self::KEY_TIME_AM;
		$ampm[self::KEY_TIME_PM] = self::KEY_TIME_PM;
		
		$option_tag_options = $options;
		$option_tag_options[self::$Key_return_output] = true;
		
		$html  = self::Select_tag_open($field_name, array(self::$Key_return_output => true), $html_options);
		$html .= self::Options_from_array_for_select($ampm, $option_tag_options);
		$html .= self::Select_tag_close(array(self::$Key_return_output => true));
		
		if ( array_val_is_nonzero($options, self::$Key_return_output) ) {
			return $html;
		}
		else {
			echo $html;
		}
		
	}
	
	public static function Date_field( $class, $field_key, $options = array() ) {
		
		LL::require_class('HTML/FormInput');
		
		$options[self::$Key_model_object] = self::Load_class($class);
		$options[self::$Key_field_key] = $field_key; 
		
		$sub_options = $options;
		$sub_options[self::$Key_field_key] = $field_key . self::SUFFIX_DATE;
		
		if ( !isset($options[self::KEY_DATE_FORMAT]) ) {
			$options[self::KEY_DATE_FORMAT] = 'm/d/Y';	
		}
		
		$options[self::$Key_input_value] = self::Get_form_value_for_repopulate(self::Input_name_from_option_hash($options), $options);
		$options[self::$Key_input_name] = self::Input_name_from_option_hash($sub_options); 
		
		if ( !self::Input_id_specified($options) ) {
			$options['id'] = self::Input_id_by_options( $sub_options );
		}
		
		$options[self::KEY_CLASS_NAME] = $class;
		$options[self::$Key_field_key] = $field_key;
		
		return FormInput::Text_field( $options );
		
	}
    
}

?>