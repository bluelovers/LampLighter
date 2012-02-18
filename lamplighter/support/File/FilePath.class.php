<?php

class PathNonexistentException extends Exception {
	
	function __construct( $path = null ) {
		
		parent::__construct( 'FilePath-path_nonexistent', "\$path: {$path}" );
		
	}
	
}

class FilePath {

	const KEY_DIR_RANGE_INCREMENT = 'increment';
	const KEY_DIR_RANGE_DELIMITER = 'delimiter';

	static $Dir_range_increment_default = 500;
	static $Dir_range_delimiter_default = '-';

	public static function Add_trailing_path_slash( $dirname, $options = null  ) {

		return self::Append_slash($dirname, $options);

	}
	
	public static function Append_slash( $dirname, $options = null  ) {

		$delimiter = ( isset($options['delimiter']) ) ? $options['delimiter'] : self::Get_path_slash($options);

		if ( $dirname && substr($dirname, -1) != $delimiter ) {
			$dirname .= $delimiter;
		}

		return $dirname;

	}

	public static function Prepend_slash( $dirname, $options = null  ) {

		$delimiter = ( isset($options['delimiter']) ) ? $options['delimiter'] : self::Get_path_slash($options);

		if ( $dirname && substr($dirname, 0, 1) != $delimiter ) {
			$dirname = $delimiter . $dirname;
		}

		return $dirname;

	}

	//
	// Like realpath(), but works on URIs. Also won't prepend drive letter 
	// on windows
	//
	public static function Expand( $which_path, $options = array() ) {

		try { 
	
			$path_slash = self::Get_path_slash($options);
                                        
        	if ( !(false === strpos($which_path, '.')) ) {
                                
            	$expanded_path_arr = array();

				$which_path = self::Strip_double_path_slashes($which_path, $options);
                $path_parts = explode( $path_slash, $which_path );

				$prepended_slash  = ( substr($which_path, 0, 1) == $path_slash ) ? $path_slash : null;
				$appended_slash = ( substr($which_path, -1) == $path_slash ) ? $path_slash : null;
        
                if ( count($path_parts) > 0 ) {
                 
                        foreach ( $path_parts as $cur_path_part ) {
                        
                        	if ( !$cur_path_part || $cur_path_part == $path_slash ) {
                        			continue;
                        		}
                        
                                if ( $cur_path_part == '.' ) {
                                        $cur_path_part = null;
                                }
                                else if ( $cur_path_part == '..' ) {
                                        if ( count($expanded_path_arr) > 1 ) {
                                                array_pop( $expanded_path_arr );
                                        }
                                        else {
                                                throw new Exception ( __CLASS__ . '-couldnt_expand_path_dots' );
                                        }
                                         
                                        $cur_path_part = null;
                                }
                                 
                                if ( $cur_path_part ) {
                                       
                                        $expanded_path_arr[] = $cur_path_part;
                                }
                                 
                        }
                         
                }        
                         
              	$expanded_path = implode( $path_slash, $expanded_path_arr );
				$expanded_path = $prepended_slash . $expanded_path . $appended_slash;                

                return $expanded_path;
                
        	}    
        	else {                  
                return $which_path;
        	}
		}
		catch( Exception $e ) {
			throw $e;
		}
                                
	}
	
	public static function Strip_double_path_slashes( $path, $path_slash = null ) {
		
		$path_slash = self::Get_path_slash($path_slash);
		$reg_slash  = preg_quote( $path_slash, '/' );
		
		return preg_replace( "/{$reg_slash}{2,}/", $path_slash, $path );
		
		
	}	
	
	public static function Get_path_slash( $given = null ) {
		try {
			return self::Get_path_delimiter($given);
		}	
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public static function Get_path_delimiter( $given = null ) {
		
		try { 
			
			if ( is_array($given) ) {
				if ( isset($given['path_slash']) ) {
					$path_slash = $given['path_slash'];
				}
				else if ( isset($given['delimiter']) ) {
					$path_slash = $given['delimiter'];
				}
				else {
					$given = null;
				}
				
			}
			else {
				$path_slash = $given;
			}
			
			
			if ( !$path_slash || !is_scalar($path_slash)  ) {
				return DIRECTORY_SEPARATOR;
			}
			
			return $path_slash;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Is_windows_drive_name( $ref ) {
	
		return preg_match('/^[A-Za-z]:$/', $ref);
	}
	
	public static function Path_to_array( $path ) {
		
		try {
			
			$slash = self::Get_path_slash();
			$path  = self::Expand_path($path);
			
			return explode($slash, $path);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Array_to_path( $arr, $options = null ) {
		
		try {
			
			$slash = self::Get_path_slash();

			if ( !is_array($arr) ) {
				throw new Exception ( __CLASS__ . '-invalid_path_array', "\$arr: {$arr}");
			}
			
			foreach( $arr as $cur_dir ) {
			
				if ( strpos($cur_dir, $slash) !== false ) {
					if ( !array_val_is_nonzero($options, 'allow_slash_in_dir_name') ) {
						throw new Exception( __CLASS__ . '-invalid_dir_name', "\$cur_dir: {$cur_dir}");
					}
				}
				
				if ( $cur_dir == '.' || $cur_dir == '..' ) {
					if ( !array_val_is_nonzero($options, 'allow_relative_path') ) {
						throw new Exception( __CLASS__ . '-invalid_relative_path', "\$cur_dir: {$cur_dir}");
					}
					
				}
				
			}

			return join( $slash, $arr );
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Strip_lowest_dir_name( $path ) {
		
		try {
			
			$slash = self::Get_path_slash();
			
			if ( substr($path, -1) == $slash ) {
				$path = substr($path, 0, -1 );
			}
			
			if ( ($last_dir = strrchr($path, $slash)) !== false ) {
				
				$path = substr($path, 0, 0 - strlen($last_dir) );
				
			}
			
			return $path;
			
		}
		catch( Exception $e ) {
			throw $e;
		}

		
	}
	
	public static function Range_dir_name( $value, $options = null ) {
		
		try {
			
			$increment = ( array_val_is_nonzero($options, self::KEY_DIR_RANGE_INCREMENT) ) ? $options[self::KEY_DIR_RANGE_INCREMENT] : self::$Dir_range_increment_default;
			$delimiter = ( array_val_is_nonzero($options, self::KEY_DIR_RANGE_DELIMITER) ) ? $options[self::KEY_DIR_RANGE_DELIMITER] : self::$Dir_range_delimiter_default;

			$floored = floor( $value / $increment );
        
        	$min_range = ($floored * $increment ) ;
        	$dir_name = "{$min_range}-" . (($min_range + $increment) -1 );

        	return $dir_name;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public static function Expand_path( $path, $options = array () ) {
		
		try { 
			return self::Expand($path, $options);			
		}
		catch( Exception $e ) {
			throw $e;
		} 
                                        
            
		
	}
	
	public static function Strip_extension( $file ) {
	
		return substr( $file, 0, strrpos($file, '.') );	
	
	}

}
?>