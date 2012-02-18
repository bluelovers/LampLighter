<?php

LL::Require_class('AppManage/ManagementTask');

class ControllerGenerator extends ManagementTask {
	
	public function generate_by_table_name( $table_name, $options = array() ) {
		
		try {
			
			$options['name'] = depluralize($table_name);
			$options['class_name'] = $this->class_name_by_controller_name($this->controller_name_by_table_name($table_name));

			return $this->generate_by_controller_name( $options['name'], $options );				
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function generate_by_controller_name( $name, $options = array() ) {
		
		try {
			
			$options['name'] = $name;
			$options['class_name'] = $this->class_name_by_controller_name($name);
			$path = null;
			
			try { 
				$path = $this->write_controller( $options );
			}
			catch( FileOverwriteException $owe ) {
				if ( isset($options['from_console']) && $options['from_console'] ) {
					echo $owe->getMessage() . "\n";
				}
				else {
					throw $owe;
				}
			}
			
			return $path;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_controller_content( $options = array() ) {
		
		$content = '<?php' . "\n\n";
		
		if ( isset($options['parent_class']) && $options['parent_class']) {
		
			$class_name = LL::Class_name_from_location_reference($options['parent_class']);
			$class_library = LL::Class_library_from_location_reference($options['parent_class']);
		
			if ( !$class_library ) {
				$class_library = 'AppControl';
			}
			
			$class_library .= '/';
		
			$content .= "LL::Require_class('{$class_library}{$class_name}');\n\n";
			$extended_class = $class_name;
		}
		else {
			$content .= "LL::Require_class('AppControl/ApplicationController');\n\n";
			$extended_class = 'ApplicationController';
		}
		$content .= "class {$options['class_name']} extends {$extended_class} {\n\n";
		
		$content .= '}';
		$content .= "\n\n" . '?>';
		
		return $content;
	}
	
	public function class_name_by_controller_name( $controller_name ) {
		
		return ucfirst(underscore_to_camel_case(singularize($controller_name))) . 'Controller';
		
	}

	public function controller_name_by_table_name( $table_name ) {
		
		return ucfirst(underscore_to_camel_case(singularize($table_name)));
		
	}

	public function filename_by_controller_name( $controller_name ) {
		
		return $this->class_name_by_controller_name($controller_name) . '.class.php';
		
	}

	public function filepath_by_controller_name( $name ) {
		
		return constant('CONTROLLER_BASE_PATH') . DIRECTORY_SEPARATOR . $this->filename_by_controller_name($name);
		
	}	
	
	public function write_controller( $options = array() ) {
		
		try {
			if ( !isset($options['name']) || !$options['name']) {
				throw new Exception( "No controller name given");
			}
			
			
			$path = $this->filepath_by_controller_name($options['name']);
			
			if ( !file_exists($path) || (isset($options['overwrite']) && $options['overwrite']) ) {
				file_put_contents( $path, $this->get_controller_content($options) );
			}
			else {
				throw new FileOverwriteException( "{$path} already exists" );
			}
			
			return $path;		
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
		
		
	}
}

?>