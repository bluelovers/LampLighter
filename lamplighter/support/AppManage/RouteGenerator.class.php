<?php

LL::Require_class('AppManage/ManagementTask');

class RouteGenerator extends ManagementTask {
	
	public $route_file_name = 'routes.conf.php';
	
	public function get_route_file_path() {
		
		return constant('APP_BASE_PATH') 
				. DIRECTORY_SEPARATOR
				. 'config'
				. DIRECTORY_SEPARATOR
				. $this->route_file_name;
		
	}
	
	public function get_current_routes() {
		
		return file_get_contents( $this->get_route_file_path() );
		
	}
	
	public function add_route( $route_uri, $route_setup, $options = array() ) {
		
		try { 
		
			if ( $this->route_already_exists($route_uri, $route_setup, $options) ) {
				if ( !isset($options['ignore_duplicates']) || $options['ignore_duplicates'] == false ) {
					throw new Exception( __CLASS__ . '-route_exists: ' . $route_uri );
				}			
			}
		
			$contents = $this->get_current_routes();
			$contents = trim($contents);
			$contents = rtrim($contents, '?>');
			$contents .= $this->get_route_connect_call($route_uri, $route_setup);
			$contents .= '?>';
			
			file_put_contents( $this->get_route_file_path(), $contents);
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}
	
	public function route_already_exists( $route_uri, $route_setup, $options = array() ) {
		
		try {
			$current_routes = $this->get_current_routes();
			$check_route = "'{$route_uri}'";
			
			if ( strpos($current_routes, $check_route) !== false ) {
				return true;
			}
			
			return false;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function get_route_connect_call( $route_uri, $route_setup, $options = array() ) {
		
		try {
			
			$route_string = 'URIRouter::Route_connect(\'' . $route_uri . '\',' . "\n";
			$route_string .= $this->get_route_setup_as_string( $route_setup );
			$route_string .= "\n);\n\n";
			
			return $route_string;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
			
		
	}
	
	public function get_route_setup_as_string( $route_setup ) {
		
		static $recursion = 0;
		$start_tabs = "\t\t";
		$tabs = $start_tabs;
		$string = '';
		

		
		if ( $recursion == 0 ) {
			$string = $start_tabs;
			$tabs .= "\t";
		}
		else {
			for( $j = 0; $j <= ($recursion); $j++ ) {
				$tabs .= "\t";
			}
		}
		
		$string .= 'array( ' . "\n";
		
		
		if ( is_array($route_setup) ) {
			foreach( $route_setup as $key =>$val ) {
				if ( is_array($val) ) {
					$recursion++;
					$string .= "{$tabs}'{$key}' => " . $this->get_route_setup_as_string($val) . "\n{$tabs}), \n";
					$recursion--;
				}		
				else {
					$string .= $tabs . '\'' . $key . '\' => \'' . $val . '\',' . "\n";
				}
			}
			
			$string = rtrim($string, "\n, ");
		}
		
		if ( $recursion == 0 ) {
			$string .= "\n{$start_tabs}" . ') ';
		}
		
		return $string;
	}
}