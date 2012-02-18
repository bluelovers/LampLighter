<?php

class ControllerMixinParent {

	protected $_Controller;
	
	public function set_controller( $controller ) {
		
		$this->_Controller = $controller;
		$this->apply_members_to_controller();
	}
	
	public function __get( $key ) {
		
		return $this->_Controller->$key;
		
	}

	public function __set( $key, $val ) {
		
		$this->_Controller->$key = $val;
		
	}

	public function __call( $method, $params ) {
		
		return call_user_func_array( array($this->_Controller, $method), $params );
		
	}

	public function apply_members_to_controller() {
		
		if ( $this->_Controller ) {
			
			$c_reflector = new ReflectionObject($this->_Controller);
			$me_reflector = new ReflectionObject($this);
			
			$my_properties = $me_reflector->getProperties(ReflectionProperty::IS_PUBLIC);
			
			foreach( $my_properties as $my_property ) {
				
				$prop_name = $my_property->getName();
				
				if ( $c_reflector->hasProperty($prop_name) ) {
					
					$c_prop = $c_reflector->getProperty($prop_name);
					if ( $c_prop->isPublic() ) {
						$this->_Controller->$prop_name = $this->$prop_name;
					}
				}
				
			}
			
		}
		
	}
/*
	public function before_render( ) {
		
	}
	
	public function before_action( ) {
		
	}

	public function after_action( ) {
		
	}	
	
	public function before_add() {
		
	}

	public function after_add() {
		
	}
	
	public function before_edit() {
		
	}

	public function after_edit() {
		
	}
*/
	
}

/* Fuse Compatibility */
class FuseControllerMixin extends ControllerMixinParent {

}
?>