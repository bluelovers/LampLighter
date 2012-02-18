<?php

LL::Require_class('AppManage/ManagementTask');

class ViewGenerator extends ManagementTask {
	
	public $local_template_base_path;
	
	public function __construct( $options = array() ) {
		
	
		$this->local_template_base_path = constant('APP_BASE_PATH')
									. DIRECTORY_SEPARATOR 
									. 'manage'
									. DIRECTORY_SEPARATOR
									. 'templates';	
									
		parent::__construct();
	}
	
	public function generate_for_table_action( $table_name, $action, $options = array() ) {
		
		try {
			$options['table_name'] = $table_name;
			$path = null;
			
			try {
				LL::Require_class('PDO/PDOFactory');
				$db = PDOFactory::Instantiate();
	
				$field_list = $db->get_column_names($table_name);	
			}
			catch( Exception $e ) {
				//ok if field list/prefix couldn't be found -- table might not exist yet
			}
			
			$options['class_name'] = $this->class_name_by_table_name($table_name);
				
			try { 
				$path = $this->write_view( $options );
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
	
	public function base_path_by_controller_name( $controller_name ) {
		
		return constant('TEMPLATE_BASE_PATH') . DIRECTORY_SEPARATOR . ucfirst($controller_name);		
		
	}
	
	public function filename_by_controller_action( $controller_name, $action ) {
		
		LL::Require_class('HTML/MarkupTemplate');
		$extension = MarkupTemplate::Get_template_file_extension();
		
		if ( substr($extension, 0, 1) != '.' ) {
			$extension = ".{$extension}";
		}
		
		$action_key = strtolower($action);
		$action_key = Config::Get('views.action_key_ucfirst') ? ucfirst($action_key) : $action_key;  
		
		$filename = ucfirst($controller_name) . '-' . $action_key . $extension;
		
		return $filename;
		
	}

	public function filepath_by_controller_action( $controller_name, $action ) {
		
		return $this->base_path_by_controller_name($controller_name) . DIRECTORY_SEPARATOR . $this->filename_by_controller_action($controller_name, $action);
		
	}	
	
	public function local_template_filename_by_action( $action ) {
		
		return $this->local_template_base_path . DIRECTORY_SEPARATOR . 'View-' . ucfirst(strtolower($action)) . '.php';
	}
	
	public function generate_for_controller_action( $controller_name, $action_name, $options ) {
		
		try { 
			$options['controller_name'] = ucfirst($controller_name);
			$options['action_name'] = ucfirst(strtolower($action_name));
		
			return $this->write_view( $options );
		}
		catch( FileOverwriteException $owe ) {
			if ( isset($options['from_console']) && $options['from_console'] ) {
				echo "\n" . $owe->getMessage() . "\n";
			}
			else {
				throw $owe;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function write_view( $options = array() ) {
		
		try {
			$view_contents = null;
			
			if ( !isset($options['action_name']) || !$options['action_name']) {
				throw new Exception( "No action name given");
			}
			
			if ( !isset($options['controller_name']) || !$options['controller_name']) {
				throw new Exception( "No controller name given");
			}
			
			$view_content = '';
			$local_path = $this->local_template_filename_by_action( $options['action_name'] );
			$dest_base_path = $this->base_path_by_controller_name($options['controller_name']);
			$dest_filepath = $this->filepath_by_controller_action($options['controller_name'], $options['action_name']);
			
			
			if ( file_exists($local_path) ) {
				ob_start();
				include($local_path);
				$view_contents = ob_get_clean();
				
			}
			
			if ( !is_dir($dest_base_path) ) {
				if ( !mkdir($dest_base_path) ) {
					throw new Exception("Could not create directory: {$dest_base_path}");
				}
			}
			
			if ( !file_exists($dest_filepath) || (isset($options['overwrite']) && $options['overwrite']) ) {
				file_put_contents( $dest_filepath, $view_contents );
				
			}
			else {
				throw new FileOverwriteException( "-->View NOT written: {$dest_filepath} already exists" );
			}
			
			return $dest_filepath;		
		}
		catch( Exception $e ) {
			throw $e;
		}
		
			
		
	}
}

?>