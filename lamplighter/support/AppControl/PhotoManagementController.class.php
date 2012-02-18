<?php

LL::require_class('AppControl/DataController');

class PhotoManagementController extends DataController {

	const SUFFIX_PHOTO_MANAGER_CLASS_NAME = 'PhotoManager';
	const SUFFIX_PHOTO_CLASS_NAME = 'Photo';
	
	const SUFFIX_COUNT = '_count';
	
	static $Suffix_id_field = '_id';
	static $Suffix_key_field = '_key';
	static $Key_id = 'id';
	
	public $form_tag_attributes = array('enctype' => 'multipart/form-data');
	public $reference_id;
	public $reference_class;
	public $photo_dir_name = 'photos';
	
	protected $_Param_name_photo_result = 'photos';
	protected $_Param_name_photo_count    = 'photo_count';
	protected $_Param_name_reference_iterator;
	protected $_Param_name_reference;
	
	protected $_Setting_key_default_reference_id;
	
	protected $_Reference_class;
	protected $_Reference_model;
	protected $_Reference_id_field;
	
	protected $_Default_to_newest_reference = false;
	
	protected $_Allow_reference_add = false;
	protected $_Input_name_for_new_reference;
	protected $_New_reference_field_name = 'name';
	
	protected $_Photo_manager;

	public function get_default_reference_id_setting_key() {
		
		try {
			
			if ( !$this->_Setting_key_default_reference_id ) {
			
				$id_field = $this->get_reference_id_field_name();
				$this->_Setting_key_default_reference_id = 'default_' . $id_field;
			}
		
			return $this->_Setting_key_default_reference_id;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_fresh_reference_model( $options = null ) {

		try {
		
			$options['force_new'] = true;
			return $this->get_reference_model($options);

		}
		catch( Exception $e ) {
			throw $e;
		}
			
	}

	public function get_reference_model( $options = null ) {
		
		try {
			if ( !$this->_Reference_model || array_val_is_nonzero($options, 'force_new') ) {
				
				$class 	    = $this->get_reference_class();
				$class_name = $this->get_reference_class_name();

				LL::require_class($class);
				
				$this->_Reference_model = new $class_name;
				
				$id_field    = $this->get_reference_id_field_name();
				
				if ( $this->$id_field && is_numeric($this->$id_field)) {
					$this->_Reference_model->id = $this->$id_field;
				}
				else {
					//
					// See if any of our params give us a unique key
					// field for our reference model 
					// (e.g. an ID to tell us which row we're referencing)
					//
					if ( is_array($params = $this->get_params()) ) {
						
						$reference_id_found = false;
						foreach( $params as $key => $val ) {

							if ( $this->_Reference_model->is_unique_key($key) ) {
								
								$this->_Reference_model->$key = $val;
								$reference_id_found = false;
							}
							else {
								$model_key = $this->_Reference_model->column_key_by_name($key);
								if ( $this->_Reference_model->is_unique_key($model_key) ) {
									$this->_Reference_model->$model_key = $val;
									$reference_id_found = false;
								}
							}
							
							if ( $reference_id_found ) {
								break;
							}
												
						}
					}
				}
			
				/*
				$key_field    = $this->get_reference_key_field_name();
			
				if ( $this->$key_field && is_valid_index_key($this->$key_field)) {
					$this->_Reference_model->key = $this->$key_field;
					$this->$id_field = $this->_Reference_model->id;
				}
				*/
				
			}
			
			return $this->_Reference_model;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_reference_class() {
		
		if ( $this->reference_class ) {
			$this->_Reference_class = $this->reference_class;
		}
		else {
			$this->_Reference_class = $this->_Reference_class;
		}
		
		if ( !$this->_Reference_class ) {
			$this->_Reference_class = $this->get_controller_name();
			$this->_Reference_class = $this->strip_photo_class_name_suffix($this->_Reference_class);
		} 

		return $this->_Reference_class;
		
	}

	public function get_reference_class_name() {
		
		try {
			
			return LL::class_name_from_location_reference($this->get_reference_class());
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function strip_photo_class_name_suffix( $str ) {
		
		if ( substr($str, 0 - strlen(self::SUFFIX_PHOTO_CLASS_NAME)) == self::SUFFIX_PHOTO_CLASS_NAME ) {
			$str = substr($str, 0, strlen($str) - strlen(self::SUFFIX_PHOTO_CLASS_NAME) );			
		}
		
		return $str;
		
	}
	
	public function get_reference_param_name() {

		if ( !$this->_Param_name_reference ) {
			
			$model = $this->get_reference_model();
			$this->_Param_name_reference = $model->table_name;
			
			//$this->_Param_name_reference = strtolower($this->strip_photo_class_name_suffix($this->get_reference_class_name()));
			//$this->_Param_name_reference = camel_case_to_underscore($this->_Param_name_reference);
		}

		return $this->_Param_name_reference;		
		
		
	}
	
	public function get_reference_id_field_name() {
		
		if ( !$this->_Reference_id_field ) {
			$model = $this->get_reference_model();
			$this->_Reference_id_field = $model->foreign_key_column_name();
		}

		return $this->_Reference_id_field;
		
	}

	public function get_reference_key_field_name() {
		
		if ( !$this->_Reference_key_field ) {
			$this->_Reference_key_field = $this->get_reference_param_name() . self::$Suffix_key_field;
		}

		return $this->_Reference_key_field;		
	}
	

	public function get_reference_iterator_param_name() {
		
		if ( !$this->_Param_name_reference_iterator ) {
			$this->_Param_name_reference_iterator = pluralize($this->get_reference_param_name());
		}

		return $this->_Param_name_reference_iterator;		
	}

		
	public function add( $options = null ) {
		try {

		    $txn_started = false;
		    $options = $this->get_method_options( 'add', $options );
			$reference_name = $this->get_reference_param_name();
			$reference 	    = $this->get_fresh_reference_model();
			
			$var_name_count = depluralize($reference_name) . self::SUFFIX_COUNT;
			
			$this->$var_name_count = $reference->count_all();
			if ( $this->is_postback() ) {
				
				
				$db   = $this->get_db_interface();
				$form = $this->get_form();
				
				if ( !$db->in_transaction() ) {
					$db->start_transaction();
					$txn_started = true;
				}
				
				$new_reference_input = ( $this->_Input_name_for_new_reference ) ? $this->_Input_name_for_new_reference : "new_{$reference_name}"; 
				
				if ( $this->_Allow_reference_add && $form->get($new_reference_input) ) {
					
					$field = $this->_New_reference_field_name;
					$reference->$field = $form->get($new_reference_input);
					
					if ( !$reference->record_exists() ) {
						$reference->add();
					}
					
				}
				else {
					
					$foreign_key = $reference->foreign_key_column_name();
					$hash_key = $this->model->get_hashtable_key();
					
					if ( !$reference->is_uniquely_identified() ) {
						$reference->id = $form->get("{$hash_key}[" . $foreign_key . ']');
					}
													
				}

				if ( !$reference->is_uniquely_identified() ) {
					throw new Exception( __CLASS__ . '-missing_reference_id' );
				}
				
				$photo_manager = $this->get_photo_manager();
				
				$setup_options = $options;
				$setup_options['method'] = __FUNCTION__;
				
				$this->setup_photo_manager($photo_manager, $setup_options);
				
				/*
				$photo_subdir = strtolower(pluralize(studly_caps_to_underscore($this->strip_photo_class_name_suffix($this->get_controller_name()))));
				
				if ( !$photo_manager->photo_base_path ) {
					$photo_manager->photo_base_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . $this->photo_dir_name . DIRECTORY_SEPARATOR . $photo_subdir;
				}

				if ( !$photo_manager->photo_base_link ) {
					$photo_manager->photo_base_link = constant('SITE_BASE_URI') . "/{$this->photo_dir_name}/" . $photo_subdir;
				}
				*/
				
				if ( !$photo_manager->process_new_photos($reference->id) ) {
					throw new UserDataException( 'Error processing photos' );
				}
				
				if ( $txn_started ) {
					$db->commit();
				}
				
				//$this->postback_render_success(__FUNCTION__, $options);
				
			}
			else {
				$this->render_form();
			}
			
			
		}
		catch( Exception $e ) {
			
			if ( $txn_started ) {
				$db->rollback();
			}
			
			$render_on_fail = $this->get_flag_render_on_fail($options);
			
			$is_ajax = ( isset($options['is_ajax']) ) ? $options['is_ajax'] : $this->is_ajax();
				
			if ( $render_on_fail && !$is_ajax ) {
				$this->set_message( $e->getMessage() );
				$this->render();
				exit;
			}
			else {
				throw $e;
			}
		}
	}


	public function before_delete() {
		
		if ( !$this->reference_id ) {
			$ref_field = $this->get_reference_id_field_name();
			$this->reference_id = $this->model->$ref_field;
		}
		
	}

	public function delete( $options = null ) {
		
		try {
			
			$photo_manager = $this->get_photo_manager();
			//$reference_id_field = $this->get_reference_id_field();
			
			if ( $this->model && $this->model->record_exists() ) {
				
				$photo_manager->delete_photo_by_db_image_id($this->model->id);
			
				$filename_field = $photo_manager->db_filename_field;
				
				if ( $this->model->$filename_field ) {
					$this->clear_cache_by_photo_filename( $this->model->$filename_field );					
				}
			}
			
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}			
		
	}
	
	public function after_delete() {
		
			if ( $this->render_on_success ) {
					
					$controller_link = strtolower($this->get_controller_name());
					
					$this->redirect( "{$controller_link}/list/" . $this->reference_id );
			}
		
	}

	/*
	public function get_photo_manager() {
		
		try {
			if ( !$this->_Photo_manager ) {
				
				$reference_class      = $this->get_reference_class();
				$reference_class_name = $this->get_reference_class_name();
				
				$photo_manager_ref    = $reference_class . self::SUFFIX_PHOTO_MANAGER_CLASS_NAME;
				$photo_manager_name   = $reference_class_name . self::SUFFIX_PHOTO_MANAGER_CLASS_NAME; 
				
				LL::require_class( $photo_manager_ref );
				$this->_Photo_manager = new $photo_manager_name;
			}
			
			return $this->_Photo_manager;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	*/
	
	public function get_photo_manager( $options = null ) {
		
		try {
			
			if ( !$this->_Photo_manager ) {
				
				$reference_class      = $this->get_reference_class();
				$reference_class_name = $this->get_reference_class_name();
				
				$photo_manager_ref    = $reference_class . self::SUFFIX_PHOTO_MANAGER_CLASS_NAME;
				$photo_manager_name   = $reference_class_name . self::SUFFIX_PHOTO_MANAGER_CLASS_NAME; 
				
				
				if ( LL::Include_class($photo_manager_ref) ) {
				
					$this->_Photo_manager = new $photo_manager_name;
				}
				else {
					LL::Require_class('Image/PhotoManager');
					
					$this->_Photo_manager = new PhotoManager();
					$this->_Initialize_photo_manager( $this->_Photo_manager, $options );

				}

				
				$this->setup_photo_manager( $this->_Photo_manager, $options );
			}
			
			
			return $this->_Photo_manager;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	protected function _Initialize_photo_manager( $photo_manager, $options = null ) {
		
		$ref_model = $this->get_reference_model();
		
		$photo_manager->set_db_interface($ref_model->get_db_interface());
		$photo_manager->set_uploaded_file_chmod( 0755 );
		
		$dir_range_cfg_var =  __CLASS__ . '.enable_dir_ranges';
		 
		if ( Config::Is_set($dir_range_cfg_var) ) {
			$photo_manager->enable_dir_ranges = Config::Get( $dir_range_cfg_var );
		}
		else {
			$photo_manager->enable_dir_ranges = false;
		}
		
		$photo_manager->require_ref_id = true; 
		$photo_manager->enable_photo_db = true; 
		$photo_manager->auto_overwrite_when_full = false; 
		$photo_manager->max_album_photos = 0; 
		$photo_manager->merge_referenced_photo_table = false;
		$photo_manager->max_batch_upload = 3; 
		$photo_manager->file_input_name    = $ref_model->get_form_input_key() . '_photo_file';
		
		$photo_manager->db_photo_name_field = null;
		$photo_manager->db_photo_table = $this->model->get_table_name();
		$photo_manager->db_filename_field = $this->model->db_field_name('filename'); 
		$photo_manager->db_photo_id_field = $this->model->get_id_column(); 
		$photo_manager->db_ref_id_field = $this->get_reference_model()->foreign_key_column_name();

		$photo_manager->add_allowed_image_type( IMAGETYPE_JPEG ); 
		$photo_manager->add_allowed_image_type( IMAGETYPE_GIF ); 
		$photo_manager->add_allowed_image_type( IMAGETYPE_PNG ); 	

		$photo_subdir = $this->get_photo_subdir_name();
				
		$public_photo_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $this->photo_dir_name . DIRECTORY_SEPARATOR . $photo_subdir;				

		if ( is_dir($public_photo_path) ) {
			$photo_manager->photo_base_path = $public_photo_path;
		}
		else {
			$photo_manager->photo_base_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . $this->photo_dir_name . DIRECTORY_SEPARATOR . $photo_subdir;
		} 
		
		$photo_manager->photo_base_link = constant('SITE_BASE_URI') . "/{$this->photo_dir_name}/" . $photo_subdir;
		
	}


	public function get_photo_subdir_name() {
		
		return strtolower(pluralize(studly_caps_to_underscore($this->strip_photo_class_name_suffix($this->get_controller_name()))));
	}


	public function setup_photo_manager( $manager, $method = null ) {
		
	}

	public function gallery( $options = null ) {
		
		try {

			$template    = $this->get_template();
			$reference   = $this->get_reference_model();
			
			$template->add_iterator( $this->get_reference_iterator_param_name(), $photo_album->fetch_all($options) );
			
			if ( !$this->photo_album_id ) {
				$this->photo_album_id = $this->get_default_photo_album_id();
			}

			$this->render();

		}
		catch( Exception $e ) {
			throw $e;
		}
			
	}

	public function show_list() {
		
		try {
			
			$template    = $this->get_template();
			$reference 	 = $this->get_reference_model();
			$db			 = $this->get_db_interface();
			
			if ( $reference->id ) {
				
				$options['where'] = "{$this->model->table_name}.{$reference->db_field_name_id}={$reference->id}";

				/*
				$reference = $this->get_fresh_reference_model();
				
				$template->add_array( $reference->get_record() );
				
				$photo_manager = $this->get_photo_manager();
				
				$photo_res = $photo_manager->fetch_db_photos_by_ref_id($reference->id);
				
				$template->add_db_result( $this->_Param_name_photo_count, $db->num_rows($photo_res) );
				$template->add_db_result( $this->_Param_name_photo_result, $photo_res ); 
				*/
			}
			
			parent::show_list( $options );
			
			//$this->render();

			
		}
		catch( Exception $e ) {
			$this->header_allow_duplicate(false);
			$this->footer_allow_duplicate(false);

			throw $e;
		}		
	}
    
    public function set_default_reference_id() {
    	try {
    		
    		$id_field = $this->get_reference_id_field_name();
    		
    		if ( $this->$id_field && is_numeric($this->$id_field) ) {
    			LL::require_class('Settings/SettingsManager');
    			
    			SettingsManager::Update_setting($this->get_default_reference_id_setting_key(), $this->$id_field);
    		}

			$this->postback_redirect_success( __FUNCTION__ );
    		
    	}
		catch( Exception $e ) {
			throw $e;
		}
    	
    }
    
    public function get_default_reference_id() {
    	
    	try {
    		
    			LL::require_class('Settings/SettingsManager');
    			
    			$default_id = SettingsManager::Get_setting($this->get_default_reference_id_setting_key());
    			
    			if ( !$default_id && $this->_Default_to_newest_reference ) {
    				$reference = $this->get_reference_model();
    				
    				$reference = $reference->fetch_single( array('order_by' => "{$reference->table_name}.{$reference->db_field_name_id} DESC", 'limit' => 1) );
					$default_id = $reference->id;
    			}
    			
    			return $default_id;

    	}
		catch( Exception $e ) {
			throw $e;
		}
    	
    }

	public function photo_cache_base_path() {
		
		if ( Config::Get('photo.cache_path') ) {
    		$cache_base_path = Config::Get('photo.cache_path');
    	}
    	else {
    		$cache_base_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'cache';
    	}
    	
    	return $cache_base_path ;
		
	}
	
	/**
	 * 
	 * For a user_profiles table, this method 
	 * will return 'photos/user_profiles'
	 * 
	 */
	public function photo_relative_path() {
		
		return $this->photo_dir_name
					. DIRECTORY_SEPARATOR 
					. $this->get_photo_subdir_name();
									
		
	}

	public function photo_cache_relative_path() {
		
		return $this->photo_relative_path();
		
	}

	public function cache_base_path_by_photo_filename( $filename ) {
		
		try {
			
			LL::Require_class('File/FilePath');
    	    	
	    	return $this->photo_cache_base_path()
	    				  . DIRECTORY_SEPARATOR
	    				  . $this->photo_cache_relative_path()
	    				  . DIRECTORY_SEPARATOR 
	    				  . FilePath::Range_dir_name($filename, $this->get_dir_range_options());
    		
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function clear_cache_by_photo_filename( $filename ) {
		
		try {
			
			if ( $filename && self::Photo_filename_is_sane($filename) ) {
				
				
				LL::Require_class('File/FilePath');
				
				$cache_path = $this->cache_base_path_by_photo_filename($filename);
				$base_filename = FilePath::Strip_extension($filename); 
				
				$files = glob($cache_path . DIRECTORY_SEPARATOR . "{$base_filename}.*" );
				
				foreach( $files as $cur_path ) {
					
					if ( is_file($cur_path) ) {
						if ( !unlink($cur_path) ) {
							throw new WriteException( "files-couldnt_unlink %{$cur_path}%" );
						}
					}
					
				}
			}			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function Photo_filename_is_sane( $filename ) {
	
		if ( $filename != '.' 
				&& $filename != '..' 
					&& strpos($filename, '/') === false 
						&& strpos($filename, '\\') === false ) {
							
							return true;
							
		}
		
		return false;
		
	}
	
	public function get_range_dir_options() {
	
		return array('increment', $this->photo_manager->dir_range_increment);
	
	}
	

    public function get_image_src( $options = array() ) {
    	
    	Config::Set('debug.silent', true);
    	LL::Require_class('File/FilePath');
    	
    	if ( !isset($options['base_path']) || !$options['base_path'] ) {
    		$image_base_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'public';
    		if ( !is_dir($image_base_path) ) {
    			$image_base_path = constant('APP_BASE_PATH') ;	
    		}
    	}
    	else {
    		
    		$image_base_path = $options['base_path'];
    	}    	 
    	 
    	$cache_base_path = $this->photo_cache_base_path();
		
		$cache_enabled  = $this->param_is_set('cache_enabled') && $this->cache_enabled == 0 ? false : true;

		if ( isset($options['resize']) ) {
			$resize_options = $options['resize'];
		}
		else {
			$resize_options = array();
		}

		if ( $this->crop ) {
			$resize_options['crop'] = array('type' => $this->crop);
		}


    	
    	if ( !isset($options['relative_path']) || !$options['relative_path'] ) {
    		
    		$image_relative_path = $this->photo_relative_path();
									
	
			if ( $this->photo_manager->enable_dir_ranges ) {
				
				$image_relative_path .= DIRECTORY_SEPARATOR
										. FilePath::Range_dir_name($this->filename,  
											$this->get_dir_range_options() 
										  );
			}
			
			$image_relative_path .= DIRECTORY_SEPARATOR . $this->filename;
    	}
    	else {
    		$image_relative_path = $options['relative_path'];
    	}
    							 
		$cache_relative_path = $image_relative_path;

		$image_full_path = $image_base_path 
						. DIRECTORY_SEPARATOR
						. $image_relative_path
						. DIRECTORY_SEPARATOR 
						. $this->filename;
    	
    	
    	$pathinfo = pathinfo($image_full_path);
    	
    	$width  = ( isset($resize_options['width']) &&  $resize_options['width']) ? $resize_options['width'] : $this->width;
    	$height = ( isset($resize_options['height']) && $resize_options['height'] ) ? $resize_options['height'] : $this->height;
    	 
    	if ( $width || $height ) {
    		
    		$cache_filename = FilePath::Strip_extension(basename($image_full_path)); 
    		
    		if ( $this->width && is_numeric($this->width) ) {
    			$cache_filename .= '.' . $this->width;
    		}

    		if ( $this->height && is_numeric($this->height) ) {
    			$cache_filename .= '.' . $this->height;
    		}
    		
    		if ( $this->crop && preg_match('/^[A-Za-z0-9\-_]+$/', $this->crop) ) {
				$cache_filename .= '.' . $this->crop;    			
    		}
    		
    		if ( isset($options['cache_filename_append']) && $options['cache_filename_append'] ) {
				$cache_filename .= $options['cache_filename_append'];    			
    		}
    		
    		$cache_filename .= '.' . $pathinfo['extension'];
    	
    		$cache_full_path = $cache_base_path 
    						. DIRECTORY_SEPARATOR
    						. $cache_relative_path
    						. DIRECTORY_SEPARATOR
    						. $cache_filename;
			
			
			//
			// Check if we have a cached file for this photo,
			// and also make sure that the cached version is newer than 
			// the image itself
			//
			if ( $cache_enabled && is_readable($cache_full_path) 
					&& filemtime($cache_full_path) >= filemtime($image_full_path) ) {
						
				$image_full_path = $cache_full_path;
			}
			else {
				
				LL::Require_class('Image/ImageManip');
				LL::Require_class('File/FileOperation');
				
				$manip = new ImageManip;
				
				$cache_range_path = $cache_base_path 
    									. DIRECTORY_SEPARATOR
    									. $cache_relative_path;
    									
    			if ( !is_dir($cache_range_path) ) {
    				FileOperation::Create_path($cache_range_path);
    			}

				if ( !$height || !$width ) {    									
					$dim = $manip->get_reproportioned_dimensions( $image_full_path, $this->width, $this->height );    									
				}
				else {
					$dim['width'] = $width;
					$dim['height'] = $height;
					
				}
				
				$manip->resize_image( $image_full_path, $cache_full_path, $dim['width'], $dim['height'], 100, $resize_options );

				$image_full_path = $cache_full_path;	
			}
    	}

		//if ( $this->crop ) {
		//	$manip->resize_image( $image_full_path, $cache_full_path, $dim['width'], $dim['height'], 100, $resize_options );
		//}
    	
    	$image_info = @getimagesize($image_full_path);
    	$image_data = @file_get_contents($image_full_path);
    	
    	if ( $image_info && $image_data ) {
	    	$headers = array();
	    	$headers[] = 'Content-type: ' . $image_info['mime'];
    		$headers[] = 'Content-length: ' . strlen($image_data);
    		
    		$this->static_cache_headers = $headers;
    		
    		foreach( $headers as $header ) {
    			header($header);
    		}
    		
    		echo $image_data;
    	}
    		
    	exit;
    			    
    	
    }
	

    
}

/* Fuse compatibility */
class FusePhotoController extends PhotoManagementController {
	
}
?>