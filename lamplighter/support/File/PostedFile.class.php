<?php

class PostedFile {

   public $input_name;

   public function __construct( $input_name = null ) {
   	
   		if ( $input_name ) {
   			$this->input_name = $input_name;
   		}
   	
   }

	public function was_posted() {
		
		if ( $this->name ) {
			return true;
		}
		
		return 0;
	} 

   	public function __get( $get_key ) {
   	
   		LL::require_class('Util/ArrayString');
   		
   		if ( ArrayString::input_name_is_array_reference($this->input_name) ) {
   			
   			list( $table, $field_key ) = ArrayString::split_array_reference($this->input_name);
   			
   			if ( isset($_FILES[$table]) ) {
   				if ( isset($_FILES[$table][$get_key]) ) {
   					if ( isset($_FILES[$table][$get_key][$field_key]) ) {
   						return $_FILES[$table][$get_key][$field_key];
   					}
   				}	
   			}
   			
   		}
   		else {
   			
   			if ( isset($_FILES[$this->input_name]) ) {
   				if ( isset($_FILES[$this->input_name][$get_key]) ) {
   					return $_FILES[$this->input_name][$get_key];
   				}
   			}
   		}
   	
   		return null;
   }
   
}
?>