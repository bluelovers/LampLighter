<?php

class MarkupTemplateParamManager {

	var $_Params 	    = array();
	var $_Resource_maps = array();

	var $_Key_param_type    = 'type';
	var $_Key_param_name    = 'name';
	var $_Key_param_val     = 'value';
	var $_Key_resource_map  = 'r_map';

	function MarkupTemplateParamManager() {

	}

	function add_array( $key, $val ) {

		return $this->_Add_managed_param( $key, $val, constant('TEMPLATE_PARAM_TYPE_ARRAY') );

	}

	function add_array_by_ref( $key, &$val ) {

		return $this->_Add_managed_param( $key, $val, constant('TEMPLATE_PARAM_TYPE_ARRAY_REF') );

	}

	function add_db_result( $key, &$val ) {

		return $this->_Add_managed_param( $key, $val, constant('TEMPLATE_PARAM_TYPE_DB_RESULT') );

	}

	function add_resource_map( $key, $map ) {

		$param_info = array();

		if ( isset($this->_Params[$key]) ) {
			$param_info = $this->_Params[$key];
		}

		$param_info[$this->_Key_resource_map] = $map;

		$this->_Params[$key] = $param_info;

	}

	function add_param( $key, $val ) {

		return $this->_Add_managed_param( $key, $val, constant('TEMPLATE_PARAM_TYPE_SCALAR') );

	}

	function add_loop( $key, $val ) {

		return $this->_Add_managed_param( $key, $val, constant('TEMPLATE_PARAM_TYPE_LOOP') );

	}

	function _Add_managed_param( $key, &$val, $type ) {

		$param_info = array();

		if ( isset($this->_Params[$key]) ) {
			$param_info = $this->_Params[$key];
		}

		$param_info[$this->_Key_param_name] = $key;
		$param_info[$this->_Key_param_val]  = $val;
		$param_info[$this->_Key_param_type] = $type;

		$this->_Params[$key] = $param_info;

	}

	function get_params() {

		return $this->_Params;

	}

	function get_param( $key ) {

		if ( isset($this->_Params[$key]) ) {
			return $this->_Params[$key][$this->_Key_param_val];
		}

		return null;

	}

	function apply_params_to_template( &$template ) {

		if ( is_array($this->_Params) && (count($this->_Params) > 0) ) {
			foreach( $this->_Params as $key => $info ) {
				$this->_Add_managed_param_to_template( $template, 
								       $info[$this->_Key_param_name],
								       $info[$this->_Key_param_val],
								       $info[$this->_Key_param_type]
								     );

				if ( isset($info[$this->_Key_resource_map]) && $info[$this->_Key_resource_map] ) {
					//echo "adding resource map {$info[$this->_Key_resource_map]} FOR {$info[$this->_Key_param_name]}";
					$template->add_resource_map( $info[$this->_Key_param_name], $info[$this->_Key_resource_map] );
				}
			}
		}

		return $template;

	}

	function merge( &$param_manager ) {

		if ( !method_exists($param_manager, 'get_params') ) {
			trigger_error( 'Invalid param manager passed to ' . __METHOD__, E_USER_WARNING );
		}
		else {
			$new_params = $param_manager->get_params();
		
			$this->_Params = array_merge( $this->_Params, $new_params );
		}

	}

	function _Add_managed_param_to_template( &$template, $name, &$value, $type ) {

		switch( $type ) {
			case constant('TEMPLATE_PARAM_TYPE_DB_RESULT'):
				$template->add_db_result( $name, $value );
				break;
			case constant('TEMPLATE_PARAM_TYPE_LOOP'):
				$template->add_param($name, $value);
				break;
			case constant('TEMPLATE_PARAM_TYPE_ARRAY'):
			case constant('TEMPLATE_PARAM_TYPE_ARRAY_REF'):
				$template->add_array($name, $value);
				break;
			default:
				$template->add_param($name, $value);

		}

		return $template;

	}

}
?>