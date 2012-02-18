<?php

class StaticFile {
	const MAP_FILE_EXTENSION = '.map';
	const CACHE_FILE_EXTENSION = '.cache';
	
	static $URI;
	
	public $options = array();
	public $key;
	
	public function __set( $key, $val ) {
		
		$this->options[$key] = $val;
		
	}
	
	public function __get( $key ) {
		
		if ( isset($this->options[$key]) ) {
			return $this->options[$key];
		}
		
		return null;
	}
	
	public static function Cache_filepath_by_controller_method( $controller, $method, $id = null ) {
		
		try {
			
			return self::Cache_filepath_by_basename( self::Cache_file_basename_by_controller_method($controller, $method, $id) );
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Cache_file_basename_by_controller_method( $controller, $method, $id = null ) {
		
		try {
			
			$id_string = '';
			$basename = $controller . '_' . $method;
			if ( $id ) {
				
				if ( is_scalar($id) ) {
					$id = array($id);
				}
				
				foreach( $id as $cur_id ) {
					
					$wildcard = '';
					
					if ( substr($cur_id, -1) == '*' ) {
						$wildcard = '*';
						$cur_id = substr($cur_id, 0, -1);
					}
					
					//$basename .= '_' . urlencode($cur_id) . $wildcard;
					$id_string .= '_' . $cur_id . $wildcard;
				}
				
				$basename .= md5($id_string);
			}
			
			return $basename;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	
	public static function Flush_by_controller_method( $controller, $method, $id = null ) {
		
		try {
			
			$basename = self::Cache_file_basename_by_controller_method($controller, $method, $id);
			
			return 	self::Flush_by_basename($basename);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Flush_by_basename( $basename ) {

		try {

			if ( $basename ) {

				$cache_base_path = self::Get_cache_base_path();
				$filepath = self::Cache_filepath_by_basename($basename);

				if ( strpos($basename, '*') !== false ) {
				
					$file_glob = $cache_base_path 
								. DIRECTORY_SEPARATOR
				 				. $basename 
				 				. self::CACHE_FILE_EXTENSION;
				
					foreach( glob($file_glob) as $filepath ) {
						unlink($filepath);
						//echo 'REMOVING: ' . $filepath . '<br />'; 
					}
				}
				else {
			
					if ( file_exists($filepath) ) {
						//echo 'REMOVING: ' . $filepath;
						unlink($filepath);
					}
				}
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}
	
	public function flush() {

		try {

			if ( !$this->file_basename ) {
				throw new Exception( 'No filename set for static cache' );
			}
			
			return self::Flush_by_basename($this->file_basename);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	
	public static function Cache_filepath_by_basename( $basename ) {
		
		try {
			
			return self::Get_cache_base_path() . DIRECTORY_SEPARATOR . $basename . self::CACHE_FILE_EXTENSION;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function Get_cache_base_path() {
		
		try { 
			if ( !defined('STATIC_CACHE_BASE_PATH') ) {
				if ( !defined('APP_BASE_PATH') ) {
					throw new Exception( 'STATIC_CACHE_BASE_PATH not defined' );
				}
				else {
					return APP_BASE_PATH . DIRECTORY_SEPARATOR . 'static';
				}
			}
			
			return constant('STATIC_CACHE_BASE_PATH');
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Filename_map_path_by_key( $key ) {
		
		try {
			
			return self::Get_cache_base_path() . DIRECTORY_SEPARATOR . $key . self::MAP_FILE_EXTENSION;
			
		}
		catch( Exception $e ) {
			throw $e; 
		}
	}

	public static function Get_URI() {
				
		if ( self::$URI ) {
			return self::$URI;
		}
		else if ( isset($_SERVER['REQUEST_URI']) ) {
			return $_SERVER['REQUEST_URI'];
		}
			
		return null; 
		
	}
	
	public static function Cache_key_from_URI() {
		
		try {
			return self::Cache_file_basename_from_URI();	
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Cache_file_basename_from_URI() {
		
		try { 
			
			$request_uri = self::Get_URI();

			if ( !$request_uri ) {
				throw new MissingRequestURIException();
			}
			
			
			//if ( ($qs_pos = strpos($request_uri, '?')) !== false ) {
			//	$request_uri = substr($request_uri, 0, $qs_pos);
			//}	
			
			$filename = md5($request_uri);
	
			return $filename;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Cache_file_basename_from_key( $key ) {
		
		try { 
			
			return $key;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Cache_filepath_from_key( $key ) {
		
		try { 
			
			$path = self::Get_cache_base_path();

			return $path . DIRECTORY_SEPARATOR . $key . self::CACHE_FILE_EXTENSION;
	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function Cache_filepath_from_URI() {
		
		try { 
			
			return self::Cache_filepath_from_key( self::Cache_key_from_URI() );
	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function Global_callback_gets_ignored( $options ) { 
		
		if ( (isset($options['ignore_global_callback']) && $options['ignore_global_callback'])
						|| (isset($options['always']) && $options['always']) ) {
							return true;
						}
		
		return false;
		
	}
	
	public static function Load_by_key( $key, $options = array() ) {
		
		try {
			
			if ( $map_contents = self::Get_map_contents_by_key($key) ) {

				if ( !self::Global_callback_gets_ignored($map_contents) ) {

					if ( defined('STATIC_CACHE_CHECK_CALLBACK') ) {
						if ( !call_user_func(constant('STATIC_CACHE_CHECK_CALLBACK')) ) {
							return false;
						}
					}
				}
			
				if ( isset($map_contents['cache_filepath']) ) {
				
					$cache_filepath = $map_contents['cache_filepath'];
				
					if ( isset($map_contents['template_filepaths']) ) {
						if ( !is_array($map_contents['template_filepaths']) ) {
							$map_contents['template_filepaths'] = array($map_contents['template_filepaths']);
						}
						
						foreach( $map_contents['template_filepaths'] as $cur_filepath ) {
							if ( file_exists($cur_filepath) ) {
								if ( filemtime($cur_filepath) > filemtime($map_contents['cache_filepath']) ) {
									//echo 'skipping cache because ' . $cur_filepath . ' is new.';
									return false;
								}
							}
						}
					}

					

					if ( isset($map_contents['check_callbacks']) ) {
						if ( !is_array($map_contents['check_callbacks']) ) {
							$map_contents['check_callbacks'] = array($map_contents['check_callbacks']);
						}
						
						foreach( $map_contents['check_callbacks'] as $callback ) {
							if ( !is_callable($callback) ) {
								trigger_error( 'Invalid callback ' . print_r($callback,true) . ' for static cache check, not using cache', E_USER_WARNING);
								return false;
							}
							else {
								if ( !call_user_func($callback) ) {
									return false;
								}
							}
						}
					}

	    			if ( file_exists($map_contents['cache_filepath']) ) {

						if ( isset($map_contents['headers']) ) {
							if ( !is_array($map_contents['headers']) ) {
								$map_contents['headers'] = array($map_contents['headers']);
							}
							foreach( $map_contents['headers'] as $header ) {
								
								header($header);
							} 
						}
						
						if ( isset($options['return_output']) && $options['return_output'] ) {
							return file_get_contents($map_contents['cache_filepath']);
						}
						else {
							readfile($map_contents['cache_filepath']);
							return true;
						}				
						
	    			}
				}
			} 
			
			return false;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Load_by_URI( $options = array() ) {
	
		try {
			
			return self::Load_by_key( self::Cache_key_from_URI(), $options );
			
		}
		catch ( MissingRequestURIException $mru ) {
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public static function Get_map_contents_by_key( $key ) {
		
		try {
			
			static $contents;
			
			if ( !$contents ) {
				$map_filepath = self::Filename_map_path_by_key($key);
			
				if ( file_exists($map_filepath) ) {
			
					$contents = unserialize(file_get_contents($map_filepath));
				}
			}
			
			return $contents;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Get_map_contents_by_URI() {
		
		try {
			
			return self::Get_map_contents_by_key( self::Cache_key_from_URI() );
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function save_cache_map() {
		
		try {

			if ( !$this->file_basename ) {
				$this->file_basename = $this->key;
			}
			
			if ( !$this->file_basename ) {
				throw new Exception( 'No filename set for static cache' );
			}
			
			$map_path = self::Filename_map_path_by_key($this->key);
			
			$contents['cache_filepath'] = self::Cache_filepath_by_basename($this->file_basename);
			
			if ( is_array($this->options) ) {
				foreach( $this->options as $key => $val ) {
					$contents[$key] = $val;
				}
			}			
			//echo $map_path;
			
			file_put_contents( $map_path, serialize($contents) );
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function write_file( $contents ) {
		
		try {
			
			if ( !$this->file_basename ) {
				throw new Exception( 'No filename set for static cache' );
			}
			
			$cache_path = self::Get_cache_base_path();
			$cache_path .= DIRECTORY_SEPARATOR . $this->file_basename;
			$cache_path .= self::CACHE_FILE_EXTENSION;
			
			file_put_contents($cache_path, $contents);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function save( $contents ) {
		
		try {
			
			if ( !$this->key ) {
				if ( !($this->key = self::Cache_key_from_URI()) ) {
					throw new Exception ('Missing key for static cache');
				}
			}
			
			$this->write_file( $contents );
			$this->save_cache_map();
			
				
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	
}

class MissingRequestURIException extends Exception {
	
}
?>