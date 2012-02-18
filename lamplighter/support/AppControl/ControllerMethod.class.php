<?php

class ControllerMethod {

	public $name;
	public $requires_login = false;
	public $global_access  = false;
	public $parent_controller;
	public $parent_controller_name;
	public $auth_skip_redirect;
	
	/*
	protected $_Options;
	
	public function __set( $key, $val ) {
		
		$this->_Options[$key] = $val;
		
	}
	
	public function __get( $key ) {
		
		if ( isset($this->_Options[$key]) ) {
			return $this->_Options[$key];
		}
		
		return null;
		
	}
	*/
	
}
?>