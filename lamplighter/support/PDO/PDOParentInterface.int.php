<?php

interface PDOParentInterface {
	
	public function date_format( $month = 0, $day = 0, $year = null );
	public function time_format( $hour, $minute, $second );
	public function datetime_format( $month = 0, $day = 0, $year = 0, $hour = 0, $minute = 0, $second = 0 );
	public function table_name_is_valid( $table_name );	
	
	//
	// @returns metadata about fields
	// e.g. mysql SHOW COLUMNS FROM table
	//
	public function get_table_columns( $table );
	public function get_column_names( $table );
	public function get_tables( $options = array() );
	
	
	//
	// @returns a new SQLQuery object
	//
	public function new_query_obj();
	
	public function parse_if_unsafe( $val );
	
}
?>
