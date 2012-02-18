<?php

class FileUpload {

	const KEY_POSTED_FILE = 'posted_file';

	public $destination_path;
	public $destination_filename;
	public $destination_dir;
	public $destination_chmod;
	
	public $destination_dir_create;
	public $destination_dir_umask = 0750;
	
	public $file_input_name;
	public $max_size;
	public $overwrite_existing_file = false;
	public $extension_case_sensitive = false;
	public $allow_missing_extension  = false;
	public $skip_move_uploaded_file  = false;
	public $required = true;
	
	protected $_Allowed_extensions = array();
	protected $_Allowed_mime_types = array();

	protected $_Disallowed_extensions = array();
	protected $_Disallowed_mime_types = array();

	protected $_Posted_file;

	public function __construct() {
		
		
	}

	public function __get( $what ) { 
		
		if ( $what == self::KEY_POSTED_FILE ) {
			return $this->get_posted_file();
		}
		
	}

	public function get_posted_file() {
	
		if ( !$this->_Posted_file ) {
			
			LL::require_class('File/PostedFile');
			
			$this->_Posted_file = new PostedFile();
			$this->_Posted_file->input_name = $this->file_input_name;
			
		}
		
		return $this->_Posted_file;
	}

	public function get_destination_dir() {
		
		try {
			
			return dirname($this->get_destination_path());
			
		}
		catch( Exception $e ) {
			throw $e;
		}

	}
	
	public function get_destination_path() {
		
		try {
			if ( $this->destination_path ) {
				return $this->destination_path;
			}
			else {
				LL::Require_class('File/FilePath');
			
				if ( !$this->destination_filename ) {
					throw new Exception( __CLASS__ . '-missing_destination_filename' );
				}
			
				if ( $this->destination_dir ) {
					return FilePath::Append_slash($this->destination_dir) . $this->destination_filename;
				}
				else {
					return $this->destination_filename;
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	public function allow_mime_type( $type ) {
		
		$this->_Allowed_mime_types[] = strtolower($type);
		
	}

	public function disallow_mime_type( $type ) {
		
		$this->_Disallowed_mime_types[] = strtolower($type);
		
	}
	
	public function is_mime_type_allowed( $type ) {
		
		try {
			if ( !$type ) {
				return false;
			}
			
			$type = strtolower($type);
			
			if ( count($this->_Allowed_mime_types) > 0 ) {
				if ( !in_array($type, $this->_Allowed_mime_types) ) {
					return 0;
				}
			}
			
			if ( count($this->_Disallowed_mime_types) > 0 ) {
				if ( in_array($type, $this->_Disallowed_mime_types) ) {
					return 0;
				}
			}
			
			return true;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_allowed_mime_type_list() {
		
		if ( is_array($this->_Allowed_mime_types) ) {
			return join( ', ', $this->_Allowed_mime_types );
		}
		
		return null;
		
	}

	public function get_disallowed_mime_type_list() {
		
		if ( is_array($this->_Disallowed_mime_types) ) {
			return join( ', ', $this->_Disallowed_mime_types );
		}
		
		return null;
		
	}

	public function is_extension_allowed( $ext ) {
		
		try {

			$ext = ltrim( $ext, '.' );
			
			if ( !$ext ) {
				if ( !$this->allow_missing_extension ) {
					return false;
				}
			}
			
			if ( !$this->extension_case_sensitive ) {
				$ext = strtolower($ext);
			}
			
			if ( count($this->_Allowed_extensions) > 0 ) {
				if ( !in_array($ext, $this->_Allowed_extensions) ) {
					return 0;
				}
			}
			
			if ( count($this->_Disallowed_extensions) > 0 ) {
				if ( in_array($ext, $this->_Disallowed_extensions) ) {
					return 0;
				}
			}
			
			return true;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function allow_extension( $ext ) {
		
		return $this->allow_file_extension($ext);
	}

	public function allow_file_extension( $ext ) {
		
		$ext = ltrim( $ext, '.' );
		
		if ( !$this->extension_case_sensitive ) {
			$ext = strtolower($ext);
		}
		
		$this->_Allowed_extensions[] = $ext;
		
	}

   public function disallow_extension( $ext ) {

       return $this->disallow_file_extension($ext);
   }

   public function disallow_file_extension( $ext ) {

		
		$ext = ltrim( $ext, '.' );
		
		if ( !$this->extension_case_sensitive ) {
			$ext = strtolower($ext);
		}
		
		$this->_Disallowed_extensions[] = $ext;
		
	}

	public function get_allowed_extension_list() {
		
		if ( is_array($this->_Allowed_extensions) ) {
			return join( ', ', $this->_Allowed_extensions );
		}
		
		return null;
		
	}

	public function get_disallowed_extension_list() {
		
		if ( is_array($this->_Disallowed_extensions) ) {
			return join( ', ', $this->_Disallowed_extensions );
		}
		
		return null;
		
	}

	public function process( $options = null ) {   

		try {
			LL::require_class('File/PostedFile');
			
			$destination_path = $this->get_destination_path();
			
			//$destination_path	= sanitize_filepath($destination_path);

			$file = new PostedFile($this->file_input_name);

			if ( !$file->was_posted() ) {
				if ( $this->require_upload ) {
					throw new NoFilePostedException( __CLASS__ . '-no_posted_upload_file', "\$this->file_input_name: {$this->file_input_name}" );
				}
				else {
					return true;
				}
			}

			if ( $file->error ) {
				throw new UserDataException( self::Upload_error_num_to_string($file->error) );
			}

			if ( !$file->name ) {
				throw new Exception( __CLASS__ . '-no_posted_upload_filename', "\$this->file_input_name: {$this->file_input_name}" );
			}
            
            //--------------------------------------
            // Check to see that file type is valid.
            //--------------------------------------
			$pathinfo  = pathinfo($file->name)  ;
			$extension = ltrim( $pathinfo['extension'], '.' );

			if ( !$this->is_extension_allowed($extension) ) {
                if ( $allowed_extension_list = $this->get_allowed_extension_list() ) {
                	throw new UserDataException( __CLASS__ . "-invalid_extension_allowed %{$allowed_extension_list}%" );
                }
                else if ( $disallowed_extension_list = $this->get_disallowed_extension_list() ) { 
                	throw new UserDataException( __CLASS__ . "-invalid_extension_disallowed %{$disallowed_extension_list}%" );
                }
                else {
                	throw new UserDataException( __CLASS__ . "-invalid_extension" );
                }
            }

            if ( !$this->is_mime_type_allowed($file->type) ) {
                if ( $allowed_type_list = $this->get_allowed_mime_type_list() ) {
                	throw new UserDataException( __CLASS__ . "-invalid_type_allowed %{$allowed_type_list}%" );
                }
                else if ( $disallowed_type_list = $this->get_disallowed_mime_type_list() ) { 
                	throw new UserDataException( __CLASS__ . "-invalid_type_disallowed %{$disallowed_type_list}%" );
                }
                else {
                	throw new UserDataException( __CLASS__ . "-invalid_type" );
                }
            }

            //--------------------------------------------
            // Only allow files up to a certain size in KB  
            //--------------------------------------------*/
            if ( $this->max_size ) {                      
                
				$file_size = $file->size;
                
                if ( $file_size > ($this->max_size * 1000) ) {
					throw new UserDataException( __CLASS__ . '-upload_too_big %{$max_size}%' );
                }
            }
             
             //------------------------------------
             // Check to see if file exists
             //------------------------------------
             if ( file_exists($destination_path) ) {
             	if ( $this->overwrite_existing_file ) {
             		
             		if ( !unlink($destination_path) ) {
						throw new Exception( __CLASS__ . '-unlink_failed', "\$destination_path: {$destination_path}" );
					}
             	}
                else {  
					throw new UserDataException( __CLASS__ . '-file_exists' );
                }
             }


			$destination_dir = $this->get_destination_dir();

			if ( !is_dir($destination_dir) ) {
				if ( $this->destination_dir_create ) {
					LL::Require_class('File/FileOperation');
					FileOperation::Create_path( $destination_dir, $this->destination_dir_path );
				}
				else {
					throw new DestinationDirNonExistentException( __CLASS__ . "-destination_dir_nonexistent %{$destination_dir}%" );
				}
			}
            
			 if ( !$this->skip_move_uploaded_file ) {                             
				if ( $upload_return = move_uploaded_file($file->tmp_name, $destination_path) ) {
             		if ( $this->destination_chmod ) {
             			if ( !chmod($destination_path, $this->destination_chmod) ) {
	    	        	 	throw new Exception ( __CLASS__ . '-upload_failed' );	
		             	}
        	     	}
        	     	
	             	return $destination_path;
    	         }
        	     else {
            	 	throw new Exception ( __CLASS__ . '-upload_failed' );	
             	}
             	
			 }
			 
			 return true; 
             
		}
		catch( Exception $e ) {
			throw $e;
		}
}

	public static function Upload_error_num_to_string( $error_int ) {
	
		$error_str = null;
	
		switch ( $error_int ) {  
			case 1:
           		$error_str = __CLASS__ . '-upload_too_large_for_php_limit';
           		break;
    		case 2:
           		$error_str = __CLASS__ . '-upload_too_large_for_form';
           		break;
    		case 3:
           		$error_str = __CLASS__ . '-upload_partial';
           		break;
    		case 4:
    		default:
           		$error_str = __CLASS__ . '-upload_unknown_error';
           		break;
 		}
 	
 		return $error_str;
	}
	
}

class DestinationDirNonExistentException extends Exception {
	

}

class NoFilePostedException extends Exception {
	

}
?>