<?php

LL::require_class('AppControl/DataController');

class DataControllerWithImage extends DataController {

	const KEY_GET_IMAGE_MANAGER = 'image_manager';
	const SUFFIX_IMAGE_MANAGER_CLASS_NAME = 'ImageManager';

	public $form_tag_attributes = 'enctype="multipart/form-data"';
	public $require_image_upload = false;
	public $new_id;
	public $image_filename_field = 'image_filename';
	
	protected $_Image_manager;
	protected $_Image_manager_class;

	public function __get( $key ) {
		
		if ( $key == self::KEY_GET_IMAGE_MANAGER ) {
			return $this->get_image_manager();
		}
		
		return parent::__get( $key );
		
	}
	/*
	public function add( $options = null ) {
		
		if ( $this->is_postback() ) {
			return $this->_Process_data( $options );
		}
		else {
			return parent::add($options);
		}
		
	}
	
	public function edit( $options = null ) {
		
		if ( $this->is_postback() ) {
			return $this->_Process_data( $options );
		}
		else {
			return parent::edit($options);
		}
		
	}
	*/
	
	public function before_before_edit() {
		
		$this->before_before_add();
		parent::before_before_edit();
	}

	public function before_before_add() {
		
		if ( $this->is_postback() ) {
			if ( !$this->model->db->in_transaction() ) {
				$txn_started = true;
				$this->model->db->start_transaction();
			}
		}
		
	}

	public function after_edit() {
		
		try { 
			if ( !$this->method_failed('edit') ) {
				$this->apply_photos();	
				$this->repopulate_form();
			}
			parent::after_edit();
		}
		catch( UserDataException $e ) {
			$this->set_message($e->getMessage());
			$this->render();
			exit;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function apply_photos() {
		
		if ( $this->is_postback() ) {
			$image_manager = $this->get_image_manager();
				
			if ( is_array($this->image_filename_field) ) {

				foreach( $_FILES as $key => $val ) {
						
					if ( in_array($key, $this->image_filename_field) || in_array($this->model->db_field_name($key), $this->image_filename_field) ) {
						
						$image_manager->file_input_name = $key;
						$image_manager->db_filename_field = $this->model->db_field_name($key);	
						$image_manager->dest_filename_suffix = '_' . $key;
						
						if ( !$image_manager->process_new_photos($this->model->id) ) {
							throw new FuseUserDataException( 'Error processing image');
						}		
												
					}
						 
				}
				
			}
			else {
				if ( !$image_manager->process_new_photos($this->model->id) ) {
					throw new FuseUserDataException( 'Error processing image');
				}
			}

			$this->model->get_record( array('force_new' => true) );
				
			if ( $this->model->db->in_transaction() ) {
				$this->model->db->commit();
			}
		}
		
	}

	public function after_add() {
			
		try { 
			
			if ( !$this->method_failed('add') ) {
				$this->apply_photos();	
				$this->repopulate_form();
			}
			parent::after_add();
		}
		catch( UserDataException $e ) {
			$this->set_message($e->getMessage());
			$this->render();
			exit;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

    protected function _Process_data( $options = null ) {
    	
    	try {
    		
    		$txn_started= false;
    		
			if ( $this->is_postback() ) {
			
    			LL::require_class('File/PostedFile');
    			
				$this->skip_postback_render_success = true;
				$this->skip_postback_render_fail    = true;
				
				$db   		   = $this->get_db_interface();
				
				if ( !$db->in_transaction() ) {
					$txn_started = true;
					$db->start_transaction();
				}
				
				if ( $this->get_requested_action() == 'edit' ) {
    				$id = $this->id;
    				$form_repopulate = true;
    				parent::edit();
				}
				else {
					$form_repopulate = false;
					
					$parent_options = $options;
					$parent_options['skip_after_add'] = true;
					
					$id = parent::add($parent_options);
				}
				
				if ( !$id ) {
					throw new Exception( __CLASS__ . '-missing_id' );
				}

				$image_manager = $this->get_image_manager();
				
				if ( !$image_manager->process_new_photos($id) ) {
					throw new UserDataException( 'Error processing image');
				}

				$this->model->get_record( array('force_new' => true) );
				
				if ( $txn_started ) {
					$db->commit();
				}
				
				
				
			}
			else {
				$this->render_form();
			}
    		

			return $id;
					    		
    	}
    	catch ( UserDataException $e ) {
    		
    		if ( $txn_started ) { 
    			$db->rollback();
    		}
    		$this->set_message( LL::get_errors() ); 
    		$this->render_form();
    	}
    	catch( Exception $e ) {
    		
    		
    		if ( $txn_started ) {
    			$db->rollback();
    		}
    		
    		throw $e;
    	}
    	
    }
    
    public function delete() {

		try {
			
			$image_manager = $this->get_image_manager();
			
			if ( !$this->id || !is_numeric($this->id) ) {
				throw new MissingParameterException('id');
			}
			
			if ( !$image_manager->delete_photos_by_ref_id($this->id) ) {
				throw new Exception( __CLASS__ . '-error_deleting_images' );
			}
				
			$model = $this->get_model();
			$model->id = $this->id;
			
			$model->delete();
			
			//$this->postback_render_success();
		}
		catch( Exception $e ) {
			throw $e;
		}	
	}
	
	public function get_image_manager( $options = null ) {
		
		try {
			if ( !$this->_Image_manager ) {
				
				$class = $this->get_image_manager_class();
				
				if ( LL::Include_class($class) ) {
				
					$class_name = LL::class_name_from_location_reference($class);
					$this->_Image_manager = new $class_name;
				}
				else {
					LL::Require_class('Image/PhotoManager');
					
					$this->_Image_manager = new PhotoManager();
					$this->_Initialize_image_manager( $this->_Image_manager, $options );
				}
				
				$this->setup_image_manager( $this->_Image_manager, $options );

				$photo_subdir = strtolower(pluralize(studly_caps_to_underscore($this->get_controller_name())));
				
				if ( !$this->_Image_manager->photo_base_path ) {
					$public_photo_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $photo_subdir;				

					if ( is_dir($public_photo_path) ) {
						$this->_Image_manager->photo_base_path = $public_photo_path;
					}
					else {

						$this->_Image_manager->photo_base_path = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $photo_subdir;
					}
				}

				if ( !$this->_Image_manager->photo_base_link ) {
					$this->_Image_manager->photo_base_link = constant('SITE_BASE_URI') . '/photos/' . $photo_subdir;
				}

			}
			
			$this->_Image_manager->require_photo_upload = $this->require_image_upload;  
		
			
			return $this->_Image_manager;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	protected function _Initialize_image_manager( $image_manager, $options = null ) {
		
		$image_manager->set_db_interface($this->model->get_db_interface());
		$image_manager->set_uploaded_file_chmod( 0755 );
		
		$image_manager->require_ref_id = true; 
		$image_manager->enable_photo_db = true; 
		$image_manager->auto_overwrite_when_full = true; 
		$image_manager->max_album_photos = 1; 
		$image_manager->merge_referenced_photo_table = true;
		$image_manager->max_batch_upload = 1; 
		
		$dir_range_cfg_var =  __CLASS__ . '.enable_dir_ranges';
		 
		if ( Config::Is_set($dir_range_cfg_var) ) {
			$image_manager->enable_dir_ranges = Config::Get( $dir_range_cfg_var );
		}
		else {
			$image_manager->enable_dir_ranges = false;
		}
		
		$image_manager->db_photo_table = $this->model->get_table_name();
		$image_manager->db_photo_id_field = $this->model->get_id_column(); 

		if ( !is_array($this->image_filename_field) ) {
			$image_manager->db_filename_field = $this->model->db_field_name($this->image_filename_field);
			$image_manager->file_input_name = $this->image_filename_field;
		}

		$image_manager->add_allowed_image_type( IMAGETYPE_JPEG ); 
		$image_manager->add_allowed_image_type( IMAGETYPE_GIF ); 
		$image_manager->add_allowed_image_type( IMAGETYPE_PNG ); 	
					
	}


	public function setup_image_manager( $image_manager, $options = null ) {

		return $image_manager;		
	}

	public function get_image_manager_class() {
		
		if ( !$this->_Image_manager_class ) {
			$this->_Image_manager_class = $this->get_controller_name() . self::SUFFIX_IMAGE_MANAGER_CLASS_NAME;
		} 

		return $this->_Image_manager_class;
		
	}

}
?>