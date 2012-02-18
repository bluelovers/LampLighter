<?php

class ObjectIterator {

	const KEY_MAINTAIN_ACTIVE_OBJECT = 'maintain_active_object';

	public $index = null;
	public $force_instantiate_on_each = false;

	protected $_Count;	
	protected $_Iterating_class;
	protected $_Iterating_class_name;
	protected $_Iterating_class_library;
	protected $_Active_object;
	//var $_Active_index = null;

	public function __get( $key ) {
		
		if ( $key == 'count' ) {
			return $this->get_count();
		}
		
		return null;
		
	}

	function set_iterating_class_name( $name) {
		
		$this->_Iterating_class_name = $name;
	}
	
	function set_iterating_class_library( $library ) {
		
		$this->_Iterating_class_library = $library;
	}

	public function set_iterating_class( $class ) {
		$this->_Iterating_class = $class;
	}

	public function get_iterating_class_library( ) {
		
		if ( !$this->_Iterating_class_library ) {
			if ( $this->_Iterating_class ) {
				$this->_Iterating_class_library = LL::class_library_from_location_reference($this->_Iterating_class);
			}
		}
		return $this->_Iterating_class_library;
	}
	
	private function _Set_active_index( $index ) {
		$this->index = $index;
	}
	
	function get_active_index() {
		return $this->index;
	}
	
	function get_active_object() {
		
		if ( $this->_Active_object ) {
			return $this->_Active_object;
		}
		
		return null;
		
	}

	function set_active_object( $obj ) {
		$this->_Active_object = $obj;
	}
	
	function set_active_index( $index ) {
		$this->index = $index;
	}

	function load_iterating_class() {
		
		try { 
			$class_name = $this->get_iterating_class_name();
			
			if ( !class_exists($class_name, false) ) {
				$class_library = $this->get_iterating_class_library();
				
				$location_string = ( $class_library ) ? $class_library . DIRECTORY_SEPARATOR . $class_name : $class_name;
	
				LL::require_class($location_string);			
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	

	/*
	public function load_iterating_class_and_set_active() {
		
		try { 
			
			$this->load_iterating_class();			
				
			if ( !$this->_Active_object || $this->force_instantiate_on_each ) {
				
				$class_name = $this->get_iterating_class_name();
				$this->set_active_object( new $class_name );
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	
	}
	*/

	function get_iterating_class_name() {

		if ( !$this->_Iterating_class_name ) {
			if ( $this->_Iterating_class ) {
				$this->_Iterating_class_name = LL::class_name_from_location_reference($this->_Iterating_class);
			}
		}

		return $this->_Iterating_class_name;
	
	}
	
	function reset( $options = null ) {
	
		if ( !array_val_is_nonzero($options, self::KEY_MAINTAIN_ACTIVE_OBJECT) ) {
			$this->_Active_object = null;
		}
		
		$this->index = null;
				
	}
	
	function set_count( $count ) {
		$this->_Count = $count;
	}
	
	function get_count() {
		return $this->_Count;
	}
	
	
}
?>