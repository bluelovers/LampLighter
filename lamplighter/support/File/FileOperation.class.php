<?php

class FileOperation {

	public static function Copy_path( $src_path, $dst_path, $options = null ) {
		
		try {
			
			static $recursion = 0;
			$recursion_limit = 64;
			$failed_files = array();
			
			LL::require_class('File/FilePath');
			
			$src_path = FilePath::Expand_path($src_path);
			$dst_path = FilePath::Expand_path($dst_path);
			
			if ( !is_dir($dst_path) ) {
				$path_above = FilePath::Strip_lowest_dir_name($dst_path);
				
				if ( !is_dir($path_above) ) {
					throw new PathNonexistentException( $path_above );
				}
				else {
					
					if (isset($options['dir_mode'])) {
						$success = mkdir($dst_path, $options['dir_mode']);
					} else {
						$success = mkdir($dst_path);
					}
					
					if ( !$success ) {
						throw new Exception ( __CLASS__ . '-couldnt_mkdir', "\$dst_path: {$dst_path}");
					}
						
				}
			}
			
			if ( !is_dir($src_path) ) {
				throw new Exception( __CLASS__ . '-source_path_nonexistent', "\$src_path: {$src_path}");
			}
			
			$src_dir = dir($src_path);
			
			while ( false !== ($cur_filename = $src_dir->read()) ) {
				if ( $cur_filename != '..' && $cur_filename != '.' ) {
					
					$cur_src_path = FilePath::Append_slash($src_path) . $cur_filename;
					$cur_dst_path = FilePath::Append_slash($dst_path) . $cur_filename;
					 
					if ( is_dir($cur_src_path) && $cur_src_path != $cur_dst_path ) {
						if ( $recursion >= $recursion_limit ) {
							throw new Exception ( 'general-recursion_limit_reached' );
						}
					
						$recursion++;
						self::Copy_path($cur_src_path, $cur_dst_path );
						$recursion--;
					}
					else {
						
						if ( file_exists($cur_dst_path) ) {
							if ( !isset($options['overwrite']) || $options['overwrite'] == false ) {
								$failed_files[] = $cur_src_path;
								continue;
							}
						}
						
						if( !copy($cur_src_path, $cur_dst_path) ) {
							if ( array_val_is_nonzero($options, 'insist_all_files') ) {
								throw new Exception( __CLASS__ . '-couldnt_copy', "\$cur_src_path: {$cur_src_path}, \$cur_dst_path: {$cur_dst_path}" );
							}
							else {
								$failed_files[] = $cur_src_path;
							}
						}
						
					}
				}
			}
			
			return $failed_files;
		}
		catch( Exception $e ) {
			throw $e;
		} 
		
	}

	public static function Create_path( $full_path, $mode = null, $options = null ) {
                
        try { 

			LL::Require_class('File/FilePath');
		
			$prefix_delimiter = 0;
			$delimiter = ( isset($options['delimiter']) ) ? $options['delimiter'] : FilePath::Get_path_slash();
        	$base_path = null;
			$orig_umask = null;
        
        	if ( $mode === null ) {
               	if ( defined('MKPATH_MODE') ) {
               		$mode = constant('MKPATH_MODE');
               	}
        	}
        	else {
        		$orig_umask = umask(0);
        	}

			if ( isset($options['base_path']) ) {
				$base_path = $options['base_path'];
			}
		
			if ( $base_path && (substr($full_path, 0, strlen($base_path)) == $base_path)) {
			
				$full_path = substr($full_path, strlen($base_path) );
			
				if ( substr($base_path, -1) != $delimiter ) {
					$prefix_delimiter = true;
				}
			
				$full_path = ltrim($full_path, $delimiter);
			
			}
	        else {
        
				if ( substr($full_path, 0, 1) == $delimiter ) {
					$full_path = substr($full_path, 1);
					$prefix_delimiter = 1;		
				}
    	    }

			$paths = explode( $delimiter, $full_path );

    	    if ( count($paths) <= 0 ) {
        	        if ( $orig_umask !== null ) {
        	        	umask( $orig_umask );
        	        }
        	        throw new Exception( __CLASS__ . '-no_mkpath_path' );
	        }
    	    else {
        	
        		$path_string = $base_path;
                $path_string .= ($prefix_delimiter) ? $delimiter : '';

                foreach( $paths as $cur_path ) {   

                       $path_string .= $cur_path;

                        if ( "$cur_path" != '' && !FilePath::Is_windows_drive_name($cur_path) && !is_dir($path_string) ) {

								if ( $mode !== null ) {
                                	if ( !mkdir($path_string, $mode) ) {
										umask( $orig_umask );
                                    	throw new Exception( __CLASS__ . "-couldnt_mkpath %$path_string%" );
                                	}
								}
								else {
                                	if ( !mkdir($path_string) ) {
										throw new Exception( __CLASS__ . "-couldnt_mkpath %$path_string%" );
                                	}
									
								}
                 
                        }
			
						$path_string = FilePath::Append_slash($path_string, $options);

                }               
        	}       
               
            if ( $orig_umask !== null ) {
				umask( $orig_umask );
            }

        	return true;
        }
        catch( Exception $e ) {
        	throw $e;
        }
                
}     


}
?>