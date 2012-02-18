<?php

function field_key_by_field_name( $field_name, $prefix = null ) {
	
	$field_key = $field_name;
	
	if ( substr($field_name, 0, strlen($prefix)) == $prefix ) {
		$field_key = substr( $field_name, strlen($prefix) );
	}
	return $field_key;
	
}

function field_caption_by_field_name( $field_name, $prefix = null ) {
	
	$field_key = field_key_by_field_name($field_name, $prefix);
	
	$caption = ucfirst(str_replace('_', ' ', $field_key));
	
	return $caption;
		
}

?>
