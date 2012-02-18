<?php

class QueryValue {

	public static function Get_format( $field_value, $options = array() ) {

		$format = array();

		if ( $field_value === null ) {
			$field_value = 'NULL';
			$comparator = 'IS';
			$quote = '';
		}
		else {
			if ( is_numeric($field_value) ) {
				$quote = '';
			}
			else {
				$quote = '\'';
			}
			
			$comparator = '=';
		}

		if ( isset($options['db']) ) {
			$field_value = $options['db']->parse_if_unsafe($field_value);
		}
		
		$format['quote'] = $quote;
		$format['comparator'] = $comparator;
		$format['value'] = $field_value;

		return $format;

	}

}
?>