<?php

class LL {

	static $Suffix_config_file;
	static $Suffix_common_config = 'common'; 

	static $Suffix_controller_name = 'Controller';
	static $Suffix_component_name = 'Component';
	
	static $Included_files = array();
	static $Class_paths	   = array();
	
	static $DB_close_on_quit = true;
	static $Suppress_messages = false;
	static $Debug_level;
	
	static $Include_file_extensions = array( 'php', 'conf.php', 'class.php', 'inc.php' );
	static $Class_file_extension = '.class.php';
	static $Interface_file_extension = '.int.php';
	static $Function_paths;


	public static function Add_function_path( $which_path ) {

		if ( !is_array(self::$Function_paths) ) {
			self::$Function_paths = array();
		}

		if ( !in_array($which_path, self::$Function_paths) ) {
			self::$Function_paths[] = $which_path;
		}
		
	}

	public static function Add_class_path( $which_path ) {

		if ( !is_array(self::$Class_paths) ) {
			self::$Class_paths = array();
		}

		if ( !in_array($which_path, self::$Class_paths) ) {
			self::$Class_paths[] = $which_path;
		}
		
	}

	public static function Require_model( $model_name, $options = null ) {
		
		$options['require'] = true;
		
		return self::Include_model( $model_name, $options );
	}

	public static function Include_model( $model_name, $options = null ) {

		$options_1['require'] = false;		
		$ret = self::Include_class( "{$model_name}Model", $options_1 );
		
		if ( !$ret ) {
			$ret = self::Include_class( $model_name, $options );
		}
		
		return $ret;
		
	}

	public static function Require_controller( $controller_name, $options = null ) {
		
		$options['require'] = true;
		
		return self::Include_controller( $controller_name, $options );
	}

	public static function Include_controller( $controller_name, $options = null ) {
		
		if ( substr($controller_name, 0 - strlen(self::$Suffix_controller_name)) != self::$Suffix_controller_name ) {
			$controller_name .= self::$Suffix_controller_name;
		}
		
		return self::Include_class( $controller_name, $options );
	}

	public static function Require_component( $component_name, $options = null ) {
		
		$options['require'] = true;
		
		return self::Include_component(  $component_name, $options );
	}

	public static function Include_component( $component_name, $options = null ) {
		
		if ( !isset($options['search_path']) || !$options['search_path'] ) {
			$options['search_path'] = constant('APP_BASE_PATH') 
										. DIRECTORY_SEPARATOR 
										. 'controllers'
										. DIRECTORY_SEPARATOR 
										. 'components';
		}
		
		if ( substr($component_name, 0 - strlen(self::$Suffix_component_name)) != self::$Suffix_component_name ) {
			$component_name .= self::$Suffix_component_name;
		}
		
		return self::Include_class( $component_name, $options );
	}



	public static function Include_class( $class_reference, $options = array() ) {
		
		if ( !isset($options['file_extension']) ) {
			$options['file_extension'] = $file_extension = self::$Class_file_extension;
					
		}
		
		return self::Include_file($class_reference, $options);
	}


	public static function Include_interface( $class_reference, $options = array() ) {
		
		if ( !isset($options['file_extension']) ) {
			$options['file_extension'] = $file_extension = self::$Interface_file_extension;
					
		}
		
		return self::Include_file($class_reference, $options);
	}

	public static function Require_file( $file, $options = null ) {

		$options['require'] = true;
		return self::Include_file( $file, $options );

	}

	public static function Include_file( $class_reference, $options = null ) {

		try { 


			$as_full_path       = ( isset($options['as_full_path']) && $options['as_full_path'] ) ? true : false;
			$includes_extension = ( isset($options['includes_extension']) && $options['includes_extension'] ) ? true : false;
			
			$include_files   = array();
		
			if ( !$as_full_path ) {

				//
				// Generate our real file name
				//				
					
				$file_name	    = basename($class_reference);
				
				if ( isset($options['file_extension']) ) {
					if ( substr($file_name, 0 - strlen($options['file_extension'])) == $options['file_extension'] ) {
						$includes_extension = true;
					}
				}
				
				if ( !$includes_extension ) {
					
					if ( isset($options['file_extension']) ) {
						if ( $file_extension = $options['file_extension'] ) {
							$file_extension = ( substr($file_extension, 0, 1) != '.' ) ? '.' . $file_extension : $file_extension;
							$file_name .= $file_extension;
						}
					}
				}
				
				$path_parts = pathinfo($class_reference);
				
				if ( $path_parts['dirname'] && $path_parts['dirname'] != '.' ) {
					$subdirs    = preg_replace('/(\\|\/)]/', DIRECTORY_SEPARATOR, $path_parts['dirname']) . constant('DIRECTORY_SEPARATOR');
				}
				else {
					$subdirs = null;
				}
					
				$relative_file_path = $subdirs . $file_name;
				
				if ( isset($options['search_path']) ) {
					$search_paths = array( $options['search_path'] );
				}
				else {
					$search_paths = self::$Class_paths;
				}
				
				if ( is_array($search_paths) ) {
					foreach( $search_paths as $cur_path ) {			

						if ( substr($cur_path, 0, -1) != constant('DIRECTORY_SEPARATOR') ) {
							$cur_path .= constant('DIRECTORY_SEPARATOR');
						}

						$file_path = $cur_path . $relative_file_path;
				
						$include_files[] = $file_path;
						
						
						/* Fuse compatibility */
						
						global $_FUSE_FILE_NAME_REWRITES;
						$legacy_file_name = null;
						
						if ( isset($_FUSE_FILE_NAME_REWRITES[$file_name]) ) {
							$legacy_file_name = $_FUSE_FILE_NAME_REWRITES[$file_name];
						}
						else {
							if ( strtolower(substr($file_name, 0, 4)) == 'fuse' ) {
								$legacy_file_name = substr($file_name, 4);
							}
							
						}
							
						if ($legacy_file_name) {						
							$include_files[] = dirname($file_path) . DIRECTORY_SEPARATOR . $legacy_file_name;
						}
							
					}			
				}
				 
			}
			else {
				$include_files[] = $class_reference;
			}

			if ( !array_val_is_nonzero($options, 'allow_duplicate') && in_array($file_name, self::$Included_files) ) {
				return true;
			}

			if ( count($include_files) <= 0 )  {
				if ( array_val_is_nonzero($options, 'require') ) {
					trigger_error( "No class paths found. Could not include: {$file_name}", E_USER_ERROR );
					exit(1);
				}
				else {
					return 0;
				}
			}

			$file_located = false;

			foreach ( $include_files as $cur_file ) {
			
				if ( (!ini_get('open_basedir') && !ini_get('safe_mode')) || (defined('FUSE_IGNORE_SAFE_MODE_WORKAROUNDS') && constant('FUSE_IGNORE_SAFE_MODE_WORKAROUNDS') != 0 )) {
					if ( file_exists($cur_file) ) {
						
						if ( include_once($cur_file) ) {
							$file_located = true;
							break;
						}
					}
				}
				else {
					if ( @include_once($cur_file) ) {
						$file_located = true;
						break;
					}
				}

			}
		
			if ( !$file_located ) {
				if ( array_val_is_nonzero($options, 'require') ) {		
					throw new Exception( "Couldn't include necessary file: {$file_name}" );
				}
				else {
					return 0;
				}
			}
			
			self::$Included_files[] = ( $as_full_path ) ? $cur_file : $file_name;

			return true;

		}
		catch (Exception $e ) {
			
			if ( isset($options['require']) && $options['require'] ) {
				
				$msg = $e->getMessage() . Config::Get('output.message_newline') .  str_replace("\n", Config::Get('output.message_newline'), $e->getTraceAsString() );
				trigger_error( $msg, E_USER_ERROR );
				exit(1);
			}
			
			throw $e;
			
			
		}	

	}

	public static function Require_class( $class_reference, $options = null ) {
		
		$options['require'] = true;
		
		return self::Include_class( $class_reference, $options );
		
	}

	public static function Require_interface( $file, $options = array() ) {
		
		$options['require'] = true;
		
		return self::Include_interface( $file, $options );
		
	}

	public static function Require_library( $file_name, $context = '', $file_prefix = '', $includes_full_path = 0, $includes_file_extension = 0, $is_class = 0, $show_errors = 1) {

		$my_location      = @getenv('SCRIPT_NAME') . ' - ' . __CLASS__ . '::' . __FUNCTION__ . ':';
		$prefix_separator = ( defined('FUSE_LIBRARY_PREFIX_SEPARATOR') ) ? constant('FUSE_LIBRARY_PREFIX_SEPARATOR') : '-';
		$context_prefix   = NULL;

		$prefix_explicit  = 0;

		if ( $is_class ) {
			$file_extension = ( defined('FUSE_CLASS_EXTENSION') ) ? constant('FUSE_CLASS_EXTENSION') : '.class.php';
		}
		else {
			$file_extension = ( defined('FUSE_LIBRARY_EXTENSION') ) ? constant('FUSE_LIBRARY_EXTENSION') : '.inc.php';
		}

		$file_extension = ( substr($file_extension, 0, 1) != '.' ) ? '.' . $file_extension : $file_extension;

		if ( !$file_prefix ) {
			if ( !$is_class ) {
				$file_prefix = ( defined('FUSE_LIBRARY_FILE_PREFIX') ) ? constant('FUSE_LIBRARY_FILE_PREFIX') : 'functions';
			}
		}
		else { 
			$prefix_explicit = 1;
		}
		
		$dir_slash_regexp   = preg_quote( DIRECTORY_SEPARATOR, '/' );
		$include_files      = array();
		$fuse_file_path     = '';
		$file_name          = ( !$includes_file_extension) ? $file_name . $file_extension : $file_name;


		if ( !$includes_full_path ) {

			$file_name_only = basename( $file_name );

			$path_info = pathinfo( $file_name );
			$sub_folder = ( $path_info['dirname'] != '.' ) ? $path_info['dirname'] : '';

			$lowest_sub_folder = ( preg_match("/{$dir_slash_regexp}?(.*){$dir_slash_regexp}$/", $sub_folder, $matches) ) ? $matches[1] : '';

			if ( $context ) {
				$sub_folder = $context . DIRECTORY_SEPARATOR . $sub_folder;
				//$lowest_sub_folder = $context;
				if ( !$prefix_explicit ) {
					$context_prefix = $context . $prefix_separator;
				}
			}

			//$sub_folder = ( preg_match("/(.*){$dir_slash_regexp}/", $file_name, $matches) ) ? $matches[0] : '';
			$sub_folder = preg_replace("/^{$dir_slash_regexp}*/", '', $sub_folder);
			$sub_folder = preg_replace("/{$dir_slash_regexp}*\s*$/", '', $sub_folder);

			if ( DIRECTORY_SEPARATOR != '/' ) {
				$sub_folder = preg_replace("/\//", DIRECTORY_SEPARATOR, $sub_folder);
			}

			if ( $file_prefix ) {

				if ( !$prefix_explicit ) {
					if ( $lowest_sub_folder ) {
						$context_prefix = $lowest_sub_folder . $prefix_separator;	
					}
				}
				
				$file_name_only  = $file_prefix . $prefix_separator . $context_prefix . $file_name_only;

			}
	
			$file_name = $file_name_only;

			if ( in_array($file_name, self::$Included_files) ) {
				return true;
			}
	
			if ( count(self::$Function_paths) > 0 ) {
				foreach( self::$Function_paths as $cur_path ) {			

					$file_path = ( $sub_folder ) ? $cur_path . DIRECTORY_SEPARATOR . $sub_folder : $cur_path;
					$file_path = $file_path . DIRECTORY_SEPARATOR . $file_name;
					
					$include_files[] = $file_path;
										
				}
	
			}

		}
		else {
			if ( in_array($file_name, self::$Included_files) ) {
				return true;
			}
			$include_files[] = $file_name;
			//$file_path = $file_name;
		}

		if ( count($include_files) <= 0 )  {
			trigger_error( "Couldn't include necessary function file: {$file_name}", E_USER_ERROR );
			exit(1);
		}


		$file_located = false;

		foreach ( $include_files as $cur_file ) {
			
			if ( !ini_get('safe_mode') || (defined('FUSE_IGNORE_SAFE_MODE_WORKAROUNDS') && constant('FUSE_IGNORE_SAFE_MODE_WORKAROUNDS') != 0 )) {
				if ( file_exists($cur_file) ) {
					if ( include_once($cur_file) ) {
						$file_located = true;
					}
				}
			}
			else {
				if ( @include_once($cur_file) ) {
					$file_located = true;
				}
			}

		}

		
		
		if ( !$file_located ) {
			
			trigger_error( "Couldn't include necessary function file: {$file_name}", E_USER_ERROR );
			exit(1);
		}

		self::$Included_files[] = ( $includes_full_path ) ? $cur_file : $file_name;

		return true;

	}

	public static function Raise_error( $message = '', $internal_message = '', $location = '', $error_level = 0, $dont_quit = 0 ) {

		try { 
			$message .= Config::Get('output.newline') . $internal_message;
		}
		catch( Exception $e ) {
			throw new LegacyException( $e );
		}

	}

	function Get_error_message_newline() {

		return Config::Get('output.message_newline');

	}

	public static function Translate( $message, $options = array() ) {
		if ( !is_array($options) ) {
			$null_if_no_translation = $options;
			$options = array();
			$options['null_if_no_translation'] = $null_if_no_translation;
		}

		self::Require_class( 'Message/MessageTranslator' );
		
		$translator = new MessageTranslator();
		
		return $translator->translate($message, $options);
		
                                        
	}        

	public static function Include_config( $name, $options = null ) {

		try {
			return self::Include_env_config($name, $options);
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Require_env_config( $name, $options = null ) {
	
		if ( !self::Include_env_config($name, $options) ) {
			trigger_error( "Could not include configuration: {$name}. Try creating config/" . APP_ENVIRONMENT . "/{$name}-" . APP_ENVIRONMENT . '.conf.php', E_USER_ERROR );
			exit;	
		}
	
		return true;
	}

	public static function Include_env_config( $name, $options = null ) {
		
		try {

			$env_config_base_path = null;
			$ext_specified = false;
			$file_found = false;
			
			if ( defined('APP_ENVIRONMENT') ) {
		
				$env = constant('APP_ENVIRONMENT');
				$env_suffix = $env;
				$env_config_base_path = constant('APP_CONFIG_PATH') . DIRECTORY_SEPARATOR . $env;

			}			
			
			$common_config_base_path = constant('APP_CONFIG_PATH');
			
			foreach( array('.conf.php') as $ext ) {
			
					// 
					// Make sure our extension has a leading .
					//
					if ( substr($ext, 0, 1) != '.' ) {
						$ext = ".{$ext}";
					} 
			
					$filename = $name;
										
					$filename .= self::$Suffix_config_file;
					$filename .= '-' . $env_suffix . $ext;
					$filepath = $env_config_base_path . DIRECTORY_SEPARATOR . $filename;

					if ( is_readable($filepath) ) {
						// db-devel.conf.php
						if ( include($filepath) ) {
							$file_found = true;
						}
					}
				
					// db-common.conf.php
					$filename = $name . self::$Suffix_config_file . '-' . self::$Suffix_common_config . $ext;
					$filepath = $common_config_base_path . DIRECTORY_SEPARATOR . $filename;
					if ( is_readable($filepath) ) {
						if ( include($filepath) ) {
							$file_found = true;
						}
					}
					else {
						// db.conf.php
						$filename = $name . $ext;
						$filepath = $common_config_base_path . DIRECTORY_SEPARATOR . $filename;
						if ( is_readable($filepath) ) {
							if ( include($filepath) ) {
								$file_found = true;
							}
						}							
					}
						
				
			}
			
			if ( $file_found ) {
				return true;
			}
			
			return 0;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	// Deprecated
	public static function Set_message_newline( $newline ) {

		Config::Set('output.message_newline', $newline);
		

	}

	public static function Get_message_newline() {

		if ( Config::Get('output.text_only') ) {
			return "\n";
		}
		else {
			return Config::Get('output.message_newline');
		}
	}

	public static function Class_library_from_location_reference( $class_location ) {
		
		$dn = dirname($class_location);
		if ( $dn != '.' ) {
			return $dn;
		}
		
		return null;
		
	}
	
	public static function Class_name_from_location_reference( $class_location ) {
		
		return basename($class_location);
		
	}

	public static function Start() {
	
		try {
			
			self::Include_env_config('FUSE'); /* Fuse Compatibility */
			self::Include_env_config('LL'); 
			self::Require_class('Logger/LogEvent');
			
		}
		catch( Exception $e ) {
			
			trigger_error( "LampLighter start error: " . $e->getMessage(), E_USER_ERROR );
			exit;
		}
		
	}

	public static function Shutdown() {

	}

   public static function Call_hook( $module_name, $method_name ) {
    	
    	try {
    		
    		if ( self::Hook_exists($module_name, $method_name) )	{
    		
    			$class_name = self::Hook_get_class_name($module_name);
    				
				$parameters = func_get_args();
   				
   				//
   				// Remove first two parameters, pass the rest to the hook
   				//
   				array_shift($parameters);
   				array_shift($parameters);
    			
    			return call_user_func_array( array($class_name, $method_name), $parameters );
    		}
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    	
    }
    
 	
	public static function Hook_exists( $module_name, $method_name ) {
		
		try {
			
			$class_name = self::Hook_get_class_name($module_name);
			
			if ( LL::Include_class($class_name) ) {
    			
    			return method_exists($class_name, $method_name);
    		}

			return false;   
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Hook_get_class_name( $module_name ) {
		
		try {
			
			return 'Hook_' . underscore_to_camel_case($module_name);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Set_session_message( $message ) {
		
		if ( !isset($_SESSION) ) {
			session_start();
		}
		$_SESSION['LL']['session_message'] = $message;
		
	}

	
	public static function Get_session_message() {
		
		if ( !isset($_SESSION) ) {
			session_start();
		}
		$message = null;
			
		if ( isset($_SESSION['LL']['session_message']) && $_SESSION['LL']['session_message'] ) {
			$message = $_SESSION['LL']['session_message'];
		}
				
		return $message;
		
	}

	public static function Clear_session_message() {
		
		if ( !isset($_SESSION) ) {
			session_start();
		}
			
		if ( isset($_SESSION['LL']['session_message']) && $_SESSION['LL']['session_message'] ) {
			$_SESSION['LL']['session_message'] = null;
		}
				
	}	
	
	
} // End class

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init' . DIRECTORY_SEPARATOR . 'Main-init.inc.php');



?>
