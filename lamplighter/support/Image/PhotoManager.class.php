<?php

//	
// ref_id - when we have many photos for one element 
//          (e.g. many photos for one user profile), 
//			the ref_id is the database's ID for that one element
//			(e.g. the user's profile_id) 
//
// image_id - this can be one of three things depending on configuration:
//            1. the database's ID for the row containing data pertaining
//                 to a particular image 
//            2. a randomly generated unique name for the image
//			  3. the image's original filename, if original filenames are 
//               kept when uploading
// 
// album_id - this is going to be EITHER the ref_id or the image_id 
//            depending on the configuration. The "album_id" is just
//			  a way of referring which number to "file" the image under.
//			  		e.g. if we're using a ref_id and prepending it to our image 
//						 name like 85-943.jpg, our "album_id" is 85, because
//					     that's where the image gets filed (e.g. photos/0-500/)
//					
//						conversely, if we use the image ID by itself with no ref id,
//					    the "album_id" will be 43, as the image gets filed under photos/500-999/
//						
//				 		 
	
LL::Require_class('Image/ImageManip');

class PhotoManager extends ImageManip {
	
	const PHOTO_UPLOAD_RETURN_FILENAMES_ONLY = 1;
	const PHOTO_UPLOAD_RETURN_RELATIVE_LINK_PATH = 2;
	const PHOTO_UPLOAD_RETURN_ABSOLUTE_LINK_PATH = 3;
	const PHOTO_UPLOAD_RETURN_INFO_ARRAY = 4;
	
	const KEY_PHOTO_INDEX = 'photo_index';

	public $photo_base_path;
	public $photo_base_link;


	//---------------------
	// DB / SQL variables
	//---------------------
	public $enable_photo_db        = true;
	public $db_enable_transactions = true;	
	public $merge_referenced_photo_table = false;

	public $db_photo_table = 'photos';
	public $db_filename_field = 'photo_filename';
	public $db_photo_id_field = 'photo_id';
	public $db_photo_name_field  = null;
	public $db_ref_id_field	= null; // This could be a UID or a bandID or whatever. 
	public $db_timestamp_field = null;

	//-----------------
	// Class Variables
	//----------------
	public $active_ref_id;
	public $prepend_ref_id  = true;
	public $require_ref_id  = false;
	public $require_photo_upload   = false;
	public $auto_overwrite_when_full = false;
	
	public $enable_random_photo_filename = null; //alias to enable_random_photo_id
	public $enable_random_photo_id = false;
	public $enable_dir_ranges = true;
	public $allowed_img_types = array();
	public $thumbnail_sizes = array();
	public $photo_dir_umask = 0755;
	public $max_album_photos = 6;
	public $max_batch_upload = 1;
	public $max_upload_size  = 100; //KB
	public $max_final_size   = 50;  //KB
	public $dir_range_increment = 500;
	public $thumb_quality = 100;

	public $max_sane_width  = 3000; // max pixel width to actually try to process
	public $max_sane_height = 3000; // max pixel height to actually try to process

	public $auto_resize_image = true; //deprecated usage
	public $auto_resize_photo = true;
	public $auto_create_album_dir = true;
	
	public $resized_photo_quality = 100;
	public $fixed_photo_width;
	public $fixed_photo_height;
	public $photo_shrink_only = false;

	public $auto_create_thumbnails = true;
	public $thumbnail_dirname	    = 'thumb';
	public $fail_on_thumbnail_create = true;
	public $fail_on_watermark_error  = true;

	public $require_image_name    = false;
	
	public $random_id_prefix_len = 3;
	public $watermark_default_trans = 50;
	public $watermark_thumbnails = false;
	public $sanitize_filenames;
	public $upload_tmp_dir;
	public $dest_filename_prefix;
	public $dest_filename_suffix;
	public $separate_dirs_for_prefix_chars = false;

	//---------------------
	// Form Input variables
	//---------------------
	protected $_DB_input_fields;
	
	public $form;
	public $file_input_name    = 'image_file';
	public $image_name_input	   = 'image_name';
	public $image_name_defaults_to_filename = true;

	protected $_Image_file_chmod; 
	protected $_First_uploaded_filename;
	protected $_Uploaded_files = array();
	protected $_Watermark_global;
	protected $_Upload_overwrite = false;

	protected $_DB;
	protected $_Watermarks = array();
	protected $_Verbose = false;	//don't turn me on unless debugging.

	public function __construct() {

		
	}

	public function random_photo_id_enabled() {
		
		if ( $this->enable_random_photo_filename ) {
			return true;
		}
		
		if ( $this->enable_random_photo_id ) {
			return true;
		}
		
		return false;
	}

	public function initialize_members() {
		
		//
		// Certain variables (e.g. merge_referenced_photo_table)
		// require that other variables have a specific value. We take care of that here.
		//
		
		if ( $this->merge_referenced_photo_table ) {
			$this->max_album_photos = 1;
			$this->auto_overwrite_when_full = true;
			$this->prepend_ref_id = false;
			$this->require_ref_id = true;
		}
		
		
	}

	function get_form_value( $key ) {
		
		if ( is_object($this->form) ) {
			return $this->form->get($key);
		}
		else {
			return get_post_var($key);
		}
		
	}

	function add_db_field( $db_field, $options = null ) {
	
		$form_field = ( isset($options['form_field']) ) ? $options['form_field'] : $db_field;
		$quote 		= ( isset($options['quote']) ) ? $options['quote'] : null;
		$parse 		= ( isset($options['parse']) ) ? $options['parse'] : null;
	
		$options['db_field'] = $db_field;
	
		$this->_DB_input_fields[$form_field] = $options;
	}

	public function add_allowed_image_type( $img_type ) {

		try { 
			if ( !($mime_type = image_type_to_mime_type($img_type)) ) {
				throw new Exception("invalid_img_type %{$img_type}%" );
			}
			else {
				$this->allowed_img_types[] = $img_type;
			}

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	
	}

	public function get_allowed_mime_types() {
	
		try { 
			$allowed_mime_types = array();
	
			if ( count($this->allowed_img_types) > 0 ) {
				foreach( $this->allowed_img_types as $cur_img_type ) {
	
					//
					// IE reports image/jpeg as image/pjpeg, so add that as well
					// if we're allowing JPEGs.
					//
					if ( $cur_img_type == IMAGETYPE_JPEG ) {
						$allowed_mime_types[] = 'image/pjpeg';
					}
	
					$allowed_mime_types[] = image_type_to_mime_type($cur_img_type);
				}
			}
	
			return $allowed_mime_types;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function verify_file( $file_info, $options = array() ) {
		
		try { 
			
			$try_album_dir_create = $this->auto_create_album_dir;
			
			foreach( array('size', 'type', 'name', 'id') as $key ) {
				if ( !array_key_exists($key, $file_info) ) {
					throw new Exception( __CLASS__ . '-no_file_' . $key . '_specified' );
				}
			}
			
			if ( $file_info['size'] > ($this->max_upload_size * 1000) ) {
				throw new UserDataException( __CLASS__ . "-file_too_large %{$this->max_upload_size}%" );
			}
	
			$allowed_mime_types = $this->get_allowed_mime_types();

			//print_r( $file_info );
			
			if ( !$file_info['type'] || !is_array($allowed_mime_types) || ( (count($allowed_mime_types) > 0) && !in_array($file_info['type'], $allowed_mime_types)) ) {
				$valid_extension_list = $this->valid_extension_list();
				throw new UserDataException( __CLASS__ . "-invalid_upload_type %{$valid_extension_list}%" );
			}
	
			$posted_file_info = pathinfo($file_info['name']);
			$posted_file_ext  = $posted_file_info['extension']; 
	
			if ( !($posted_file_img_type = $this->mime_type_to_image_type($file_info['type'])) ) {
				throw new Exception( __CLASS__ . "-no_image_type_for_mime_type {$file_info['type']}" );
			}
			
			
			if ( !$corrected_file_ext = $this->image_extension_by_type($posted_file_img_type, false) ) {
				$valid_extension_list = $this->valid_extension_list();
				throw new UserDataException( __CLASS__ . "-invalid_upload_extension %{$valid_extension_list}%" );
			}
			
			if ( $this->enable_photo_db || $this->random_photo_id_enabled() ) {
	
				if ( !$this->is_valid_image_id($file_info['id']) ) {
					throw new Exception( __CLASS__ . "-invalid_image_id %{$file_info['id']}%" );
				}
	
				if ( $this->active_ref_id && $this->prepend_ref_id ) {
					$album_id	  	= $this->active_ref_id;
					$dst_filename	= "{$album_id}-{$file_info['id']}.{$corrected_file_ext}";
				}
				else {
					$album_id	  = $file_info['id'];
					$dst_filename = "{$file_info['id']}.{$corrected_file_ext}";
				}
	
				if ( !($album_dir = $this->album_dir_by_album_id($album_id)) ) {
					throw new Exception ( __CLASS__ . "-couldnt_get_album_dir %{$album_id}%" );
				}
				
			}
			else {
				
				$try_album_dir_create = false;
				$album_dir	  = $this->photo_base_path;
				$dst_filename = $file_info['name'];
				
				if ( $this->sanitize_filenames ) {
					if ( is_callable($this->sanitize_filenames) ) {
						$dst_filename = call_user_func($this->sanitize_filenames, $dst_filename);
					}
					else {
						$dst_filename = $this->sanitize_filename($dst_filename);	
					}
				}
				
			}
	
			if ( $this->_Verbose ) {
				echo "<br />dst filename is: {$dst_filename}<br />";
			}

			if ( $this->dest_filename_prefix ) {
				$dst_filename = $this->dest_filename_prefix . $dst_filename;
			}

			if ( $this->dest_filename_suffix ) {
				FUSE::Require_class('File/FilePath');

				$dst_filename = FilePath::Strip_extension($dst_filename);
				$dst_filename = $dst_filename . $this->dest_filename_suffix;
				$dst_filename .= '.' . $corrected_file_ext;
			}
	
			$ret['dest_filename'] = $dst_filename;
			$ret['album_id'] = $album_id;
			$ret['album_dir'] = $album_dir;
			$ret['album_path'] = $album_dir;
			$ret['album_dir_create'] = $try_album_dir_create;
			
			return $ret;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	public function album_dir_check_and_create( $upload_info, $options = array() ) {

		try { 
			
			//----------------------------------------------------------------------
			// Make sure the directory for this photo range (i.e. 500-1000) exists.
			// If not, try to create it.
			//----------------------------------------------------------------------
			if ( !is_dir($upload_info['album_dir']) ) {
				if ( $upload_info['album_dir_create'] ) {
					if ( !$this->create_image_dir($upload_info['album_id']) ) {
						throw new Exception( __CLASS__ . "-couldnt_create_dir %{$album_id}" );
					}
				}
				else {
					throw new InvalidPathException( __CLASS__ . "-photo_directory_doesnt_exist %{$upload_info['album_dir']}%" );
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function upload_photo( $image_id, $options = array() ) {

		try { 
			
			LL::Require_class('File/FileUpload');
			
			if ( $this->active_ref_id || $this->require_ref_id ) {
				if ( !is_numeric($this->active_ref_id) || !$this->active_ref_id ) {
					throw new InvalidParameterException('active_ref_id');
				}
			}
	
			if ( !$this->photo_base_path ) {
				throw new MissingParameterException('photo_base_path');
			}
			
			if ( !is_writable($this->photo_base_path) ) {
				throw new FileInaccessibleException( "Cannot write to {$this->photo_base_path}" );
			}
			
			$file_input_name = ( isset($options['input_name']) ) ? $options['input_name'] : $this->file_input_name;
	
			if ( isset($_FILES[$file_input_name]['error']) && $_FILES[$file_input_name]['error'] ) {
				throw new UserDataException( FileUpload::Upload_error_num_to_string($_FILES[$file_input_name]['error']) );
			}
			
			if ( !$_FILES[$file_input_name]['name'] ) {
				throw new MissingParameterException("\$_FILES[$file_input_name]['name']");
			}
	
			$file_info = array();
			$file_info = $_FILES[$file_input_name];
			$file_info['id'] = $image_id;
	
			$allowed_mime_types = $this->get_allowed_mime_types();
			$upload_info = $this->verify_file($file_info);
			
			$this->album_dir_check_and_create($upload_info);
			
			$upload = new FileUpload;
			$upload->destination_dir = $upload_info['album_dir'];
			$upload->destination_filename = $upload_info['dest_filename'];
			$upload->file_input_name = $file_input_name;
			$upload->overwrite_existing_file = $this->_Upload_overwrite;
			$upload->max_size = $this->max_upload_size;
			
			
			foreach( $allowed_mime_types as $type ) {
				$upload->allow_mime_type($type);
			}
			
			$upload->process();
			$this->apply_file_chmod( $upload->get_destination_path() );

			return $upload->get_destination_path();
	
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function apply_file_chmod( $filepath ) {
		
		try { 
			if ( $file_chmod = $this->get_uploaded_file_chmod() ) {
					if ( !chmod($filepath, $file_chmod) ) {
						throw new Exception ( "couldnt_chmod_file %{$uploaded_file}%");
					}
				}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function sanitize_filename( $filename ) {
		
		return preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
		
	}

	public function update_image_filename( $image_id, $filename ) {

		try { 
			$db = $this->get_db_interface();
			$query =  $db->new_query_obj();
	
			if ( !$this->is_valid_image_id($image_id) ) {
				throw new InvalidParameterException( 'image_id' );
			}
	
			$filename = $db->parse_if_unsafe($filename);
	
			$query->add_update_data( $this->db_filename_field, $filename );
			
			$query->auto_update($this->db_photo_table, "WHERE {$this->db_photo_id_field}={$image_id}");
		}
		catch( Exception $e ) {
			throw $e;
		}			
	}

	public function update_image_row( $ref_id = null, $image_field_data = null ) {

		try { 
			
			LL::Require_class('PDO/PDOStatementHelper');
	
			$db = $this->get_db_interface()->connect_w();
			$query = $db->new_query_obj();
	
			if ( !is_numeric($ref_id) || !$ref_id ) {
				throw new InvalidParameterException('ref_id');
			}
	
			if ( !($ref_id_field = $this->get_ref_id_field()) ) {
				throw new MissingParameterException('ref_id_field');
			}
				
			
			if ( $this->require_image_name ) {
	
				if ( !$image_field_data[$this->db_photo_name_field] && !$image_field_data[$this->image_name_input] ) {
					throw new MissingParameterException('image_name' );
				}
				else {
					if ( !$this->db_photo_name_field ) {
						throw new MissingParameterException('db_photo_name_field' );
					}
				}
			}
			
			if ( $this->db_photo_name_field && isset($image_field_data[$this->image_name_input]) ) {
				$query->add_update_data( $this->db_photo_name_field, $image_field_data[$this->image_name_input] );
				unset( $image_field_data[$this->image_name_input] );
	
			}
			
			if ( is_array($this->_DB_input_fields) ) {
				foreach( $this->_DB_input_fields as $form_field => $field_info ) {
					$query->add_update_data( $field_info['db_field'], $this->get_form_value($form_field), $field_info['quote'], $field_info['parse'] );
					
				}
				
			}
	
			if ( $this->db_timestamp_field ) {
				$query->add_update_data( $this->db_timestamp_field, 'NOW()', PDOStatementHelper::BIND_TYPE_FUNCTION );
			}
	
			if ( $query->has_update_data() ) {
				$query->auto_update($this->db_photo_table, "WHERE {$ref_id_field}={$ref_id}");
			}
	
			//$db->parse_auto_insert_data = $orig_parse_val;
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function insert_new_image( $ref_id = null, $image_field_data = null, $options = null ) {

		try { 
		
			$db = $this->get_db_interface()->connect_w();
			$query = $db->new_query_obj();
			
			if ( $ref_id || $this->require_ref_id ) {
				if ( !is_numeric($ref_id) || !$ref_id ) {
					throw new InvalidParameterException('ref_id');
				}
	
				if ( !($ref_id_field = $this->get_ref_id_field()) ) {
					throw new InvalidParameterException('ref_id_field');
				}
				
			}
			
	
			if ( $this->require_image_name ) {
	
				if ( !$image_field_data[$this->db_photo_name_field] && !$image_field_data[$this->image_name_input] ) {
					throw new MissingParameterException('image_name');
				}
				else {
					if ( !$this->db_photo_name_field ) {
						throw new MissingParameterException('db_photo_name_field');
					}
				}
			}
	
	
			if ( $this->db_photo_name_field && $image_field_data[$this->image_name_input] ) {
				$query->add_insert_data( $this->db_photo_name_field, $image_field_data[$this->image_name_input] );
				unset( $image_field_data[$this->image_name_input] );
	
			}
	
			if ( isset($options[self::KEY_PHOTO_INDEX]) ) {
				$photo_index = $options[self::KEY_PHOTO_INDEX];
			}
			else {
				$photo_index = null;
			}
			
			
			if ( is_array($this->_DB_input_fields) ) {
				foreach( $this->_DB_input_fields as $form_field => $field_info ) {
					
					$insert_value = null;
					$form_value   = $this->get_form_value($form_field);
					
					if ( is_array($form_value) ) {
					
						if ( is_numeric($photo_index) ) {
							$insert_value = $form_value[$photo_index - 1];
						}
					}
					else {
						if ( is_numeric($photo_index) ) {
							$form_field = $form_field . $photo_index;
							$form_value   = $this->get_form_value($form_field);
						}
						
						$insert_value = $form_value;
					}
					
					if ( $insert_value ) {
						$query->add_insert_data( $field_info['db_field'], $insert_value, $field_info['quote'], $field_info['parse'] );
					}
					
				}
				
			}
	
			
			if ( $this->db_timestamp_field ) {
				$query->add_insert_data( $this->db_timestamp_field, 'NOW()', PDOStatementHelper::BIND_TYPE_FUNCTION );
			}
			
			if ( $ref_id ) {
				$query->add_insert_data( $ref_id_field, $ref_id );
			}
			
			$result = $query->auto_insert($this->db_photo_table);

			if ( $db_image_id = $db->last_insert_id() ) {
				return $db_image_id;
			}
	
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function process_new_photo( $file_input_name = null ) {
		
		try {
			return $this->process_new_photos( $file_input_name );
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function check_photo_count() {

		try { 
				if ( $this->active_ref_id ) {
					if ( (!$this->merge_referenced_photo_table) ) {
	
						if ( false === ($photo_count = $this->count_photos_by_ref_id($this->active_ref_id)) ) {
							throw new Exception( __CLASS__ . '-couldnt_count_photos', "\$ref_id: {$ref_id}" );
						}
		
						if ( $this->max_album_photos && ($photo_count >= $this->max_album_photos) ) {
	
							if ( $this->auto_overwrite_when_full ) {
	
								if ( !$this->delete_oldest_photo_by_ref_id($this->active_ref_id) ) {
									throw new Exception ( __CLASS__ . "-couldnt_delete_oldest_photo %{$ref_id}%" );
								}
							}
							else {
								throw new UserDataException( __CLASS__ . '-too_many_photos' );
							}
						}
	
					}
					else {
						$this->_Upload_overwrite = true;
					}
				}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function process_url_list( $urls, $options = array() ) {

		try { 
	
			$txn_started = false;
			$this->initialize_members();
			$db = $this->get_db_interface();
			
			if ( $this->merge_referenced_photo_table && !$this->enable_photo_db ) {
				throw new Exception( __CLASS__ . '-dont_set_merge_referenced_photo_table_without_db');
			}
	
			if ( $this->require_ref_id ) {
				if ( !$this->active_ref_id ) {
					throw new Exception( __CLASS__ . '-missing_ref_id' );
				}
			}
	
			if ( !$db->in_transaction() ) {
				$db->start_transaction();
			}
			
			foreach ( $urls as $image_url ) {
	
				$this->check_photo_count();
	
				$uploaded_filepath = $this->_Process_url($image_url);
	
				$index = count($this->_Uploaded_files);
				$filename = basename($uploaded_filepath);
	
				$this->_Uploaded_files[$index]['filename'] = $filename; 

				if ( $this->enable_photo_db ) { 
					$this->_Uploaded_files[$index]['id'] = $db->last_insert_id( $this->db_photo_id_field, $this->db_photo_table );
				}
				
				
			}

			if ( $txn_started ) {
				$db->commit();
			}
	
	
			return true;
		}
		catch( Exception $e ) {
			if ( $txn_started && $this->enable_photo_db && $db ) {
				$db->rollback();
			}
			
			throw $e;
		}

	}

	protected function _Process_url( $url, $options = array() ) {

		try { 
			
			$image_info = array();
			$image_name = null;
	
			//
			// Determine our image name
			//
			if ( isset($options['image_name']) ) {
				$image_name = $options['image_name'];
			}
	
			if ( !$image_name ) {
	
				if ( $this->image_name_defaults_to_filename ) {
					if ( !($image_name = basename($url)) ) {
						throw new Exception ( __CLASS__ . '-no_image_name' );
					}
				}
				else {
					if ( $this->require_image_name ) {
						throw new Exception ( __CLASS__ . '-no_image_name' );
					}
				}
	
			}

			$image_field_data['name'] = $image_name;
			
			//
			// Get our upload references
			//
			$ref_data = $this->_Get_photo_ref_data( $image_field_data, $options );
			
			//
			// Pull the image down
			//			
			$ch = curl_init( $url );

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER , 0);
	
			$res = curl_exec($ch);
		
			if ( $res === false ) {
				throw new Exception( curl_error($ch) ); 
			}
	
			curl_close($ch);

			if ( $this->upload_tmp_dir ) {
				$tmp_dir = $this->upload_tmp_dir;
			}		
			else if ( Config::Get('upload_tmp_dir') ) {
				$tmp_dir = Config::Get('upload_tmp_dir');
			}
			else {
				$tmp_dir = ini_get('upload_tmp_dir');
			}
			//
			// Write the file temporarily 
			//
			$tmp_filepath = $tmp_dir . DIRECTORY_SEPARATOR . uniqid(rand());
			if ( !($fp = fopen($tmp_filepath, 'w+b')) ) {
				throw new Exception( __CLASS__ . "-couldnt_open_temp_file %{$tmp_filepath}" );
			}			
			if ( !fwrite($fp, $res) ) {
				throw new Exception( __CLASS__ . "-couldnt_write_temp_file %{$tmp_filepath}" );
			}

			fclose($fp);

			//
			// Use getimagesize to get file type
			//
			$image_info = getimagesize($url);
			
			//
			// Setup our $file_info array
			// this should look like a $_FILES array
			//
			$file_info['name'] = basename($url);
			
			if ( ($qs_pos = strpos($file_info['name'], '?')) !== false ) {
				$file_info['name'] = substr( $file_info['name'], 0, $qs_pos );
			}
			
			$file_info['size'] = filesize($tmp_filepath);
			$file_info['type'] = $image_info['mime'];
			$file_info['id'] = $ref_data['upload_image_id'];
			
			$upload_info = $this->verify_file( $file_info, $options );
			
			$this->album_dir_check_and_create($upload_info);
			
			$dest_filepath = $upload_info['album_path'] . DIRECTORY_SEPARATOR . $upload_info['dest_filename'];

			//
			// Write the final file
			//
			if ( !($fp = fopen($dest_filepath, 'w+b')) ) {
				throw new Exception( __CLASS__ . "-couldnt_open_dest_file %{$dest_filepath}" );
			}			
			if ( !fwrite($fp, $res) ) {
				throw new Exception( __CLASS__ . "-couldnt_write_dest_file %{$dest_filepath}" );
			}

			fclose($fp);
	
			$this->apply_file_chmod( $dest_filepath );
			$this->process_uploaded_file( $dest_filepath, $ref_data, $options );
			
	
			unlink( $tmp_filepath );
	
			return $dest_filepath;
		}
		catch( Exception $e ) {
		 	@unlink($tmp_filepath);
			throw $e;
		}
		
		
		
	}

	public function process_new_photos( $ref_id = null, $file_input_name = null, $input_form = null ) {

		try { 
	
			$this->initialize_members();
	
			$file_input_name = ( $file_input_name ) ? $file_input_name : $this->file_input_name;
			$txn_started     = false;
			
			if ( $this->merge_referenced_photo_table && !$this->enable_photo_db ) {
				throw new InvalidParameterException( __CLASS__ . '-dont_set_merge_referenced_photo_table_without_db');
			}
			
			if ( !$file_input_name ) {
				throw new InvalidParameterException( __CLASS__ . '-missing_file_input_name' );
			}
	
			if ( !$ref_id ) {
				$ref_id = $this->active_ref_id;
			}
			else {
				$this->active_ref_id = $ref_id;
			}
			
			if ( $ref_id || $this->require_ref_id ) {
				if ( !is_numeric($ref_id) || !$ref_id ) {
					throw new InvalidParameterException( __CLASS__ . "-invalid_reference_id %{$ref_id}%");
				}
		
				$this->set_active_ref_id( $ref_id );
			}
	
			if ( $this->enable_photo_db ) {
	
				if ( !($db = $this->get_db_interface()) ) {
					throw new MissingParameterException( 'no db interface found. Make sure to set_db_interface() to a database object');
				}
	
				$db = $db->connect_w();
	
				if ( !$db->in_transaction() ) {
					$db->start_transaction();
					$txn_started = true;
				}
			}
	
			for ( $j = 1; $j <= $this->max_batch_upload; $j++ ) {
			
				$this->_Upload_overwrite = false;
				$cur_file_input_name     = ( $this->max_batch_upload > 1 ) ? "{$file_input_name}{$j}" : $file_input_name;
	
				if ( !isset($_FILES[$cur_file_input_name]['name']) || !$_FILES[$cur_file_input_name]['name'] ) {
					if ( $this->max_batch_upload == 1 ) {
						$cur_file_input_name = "{$file_input_name}1";
					}
					
					if ( !isset($_FILES[$cur_file_input_name]['name']) || !$_FILES[$cur_file_input_name]['name'] ) {
						if ( $j == 1 ) {
							$cur_file_input_name = "{$file_input_name}";		
						}
					}
				}
		
				if ( !isset($_FILES[$cur_file_input_name]['name']) || !$_FILES[$cur_file_input_name]['name'] ) {
		
				
					if ( $this->max_batch_upload <= 1 ) {
						if ( $this->require_photo_upload ) {
	
							if ( $this->enable_photo_db ) {
								if ( $txn_started) $db->rollback();
							}

							throw new NotFoundException( __CLASS__ . '-no_posted_file' );

						}
						else { 
							return true;
						}
					}
					else {
						continue;
					}		
				}
			
				if ( $ref_id ) {
					if ( (!$this->merge_referenced_photo_table) ) {
	
						if ( false === ($photo_count = $this->count_photos_by_ref_id($ref_id)) ) {
							if ( $txn_started) $db->rollback();
							throw new Exception( __CLASS__ . "-couldnt_count_photos %{$ref_id}%" );
						}
		
						if ( $this->max_album_photos && ($photo_count >= $this->max_album_photos) ) {
	
							if ( $this->auto_overwrite_when_full ) {
	
								if ( !$this->delete_oldest_photo_by_ref_id($ref_id) ) {
									if ( $txn_started ) $db->rollback();
									throw new Exception( __CLASS__ . '-couldnt_delete_oldest_photo'  );
								}
							}
							else {
								throw new UserDataException( __CLASS__ . '-too_many_photos' );
							}
						}
	
					}
					else {
						$this->_Upload_overwrite = true;
					}
				}
	
				//
				// Try to determine input name
				//
				if ( is_object($input_form) ) {
					$image_field_data = $input_form->get_dataset();
				}
	
				//$cur_file_input_name = ( $this->max_batch_upload > 1 ) ? $this->file_input_name . $j : $this->file_input_name;
				$cur_image_name_input = ( $this->max_batch_upload > 1 ) ? $this->image_name_input . $j : $this->image_name_input;
	
				if ( is_object($input_form) ) {
					$image_name = $input_form->get( $cur_image_name_input );
				}
				else {
				
					$image_name = isset($_POST[$cur_image_name_input]) ? $_POST[$cur_image_name_input] : null;
				}
	
				$options['input_form'] = $input_form;
				$options['image_name'] = $image_name;
				$options[self::KEY_PHOTO_INDEX] = $j;
				$options['input_name'] = $cur_file_input_name;
					
				$image_info = $_FILES[$cur_file_input_name];
				
				if ( !($uploaded_filepath = $this->_process_new_photo($image_info, $options)) ) {
					if ( $txn_started) $db->rollback();
					throw new Exception( LL::Get_error_messages() );
				}
	
	
				if ( !$this->_First_uploaded_filename ) {
					$this->_First_uploaded_filename = basename($uploaded_filepath);
				}
	
				$filename = basename($uploaded_filepath);
	
				$index = count($this->_Uploaded_files);
	
				$this->_Uploaded_files[$index]['filename'] = $filename; 
	
				if ( $this->enable_photo_db ) { 
					$this->_Uploaded_files[$index]['id'] = $db->last_insert_id( $this->db_photo_id_field, $this->db_photo_table );
				}
	
	
				if ( $txn_started ) {
					$db->commit();
				}
				
				
			}
	
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	protected function _Get_photo_ref_data( $image_field_data, $options = array() ) {
		
		try {
	
			$ref_data = array();
			$ref_id = $this->active_ref_id;
	
			if ( $this->enable_photo_db ) {
	
				if ( $this->merge_referenced_photo_table ) {
	
					if ( !$this->update_image_row($ref_id, $image_field_data) ) {
						throw new Exception( __CLASS__ . '-couldnt_insert_image' );
					}
					
					$ref_data['db_image_id'] = $ref_id;
					
				}
				else {
					
					if ( !($ref_data['db_image_id'] = $this->insert_new_image($ref_id, $image_field_data, $options)) ) {
						throw new Exception( __CLASS__ . '-couldnt_insert_image' );
					}
					
				}
	
				if ( $this->random_photo_id_enabled() ) {
					$ref_data['upload_image_id'] = $this->get_random_photo_id();
				}
				else {
					$ref_data['upload_image_id'] = $ref_data['db_image_id'];
				}
	
				$ref_data['upload_ref_id']   = $ref_id;
	
			}
			else if ( $this->random_photo_id_enabled() ) {
	
				$ref_data['upload_image_id'] = $this->get_random_photo_id();
				$ref_data['upload_ref_id']   = $ref_id;
				
			}
			else {
				
				$ref_data['upload_image_id'] = $image_field_data['name'];
				
				if ( $this->sanitize_filenames ) {
					if ( is_callable($this->sanitize_filenames) ) {
						$ref_data['upload_image_id'] = call_user_func($this->sanitize_filenames, $ref_data['upload_image_id']);
					}
					else {
						$ref_data['upload_image_id'] = $this->sanitize_filename($ref_data['upload_image_id']);	
					}
				}
				
				$ref_data['upload_ref_id'] = null;
			}
	
			return $ref_data;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	protected function _process_new_photo( $image_info, $options = array() ) {

		try { 
			
			$image_field_data = array();
			$image_name = null;
			$ref_id = $this->active_ref_id;	
	
			if ( $this->require_ref_id ) {
				if ( !$ref_id ) {
					throw new InvalidParameterException('ref_id');
				}
			}
	
			if ( isset($options['image_name']) ) {
				$image_name = $options['image_name'];
			}
	
			if ( !$image_name ) {
	
				if ( $this->image_name_defaults_to_filename ) {
					if ( !($image_name = basename($image_info['name'])) ) {
						throw new InvalidParameterException('image_info[\'name\']');
					}
				}
				else {
					if ( $this->require_image_name ) {
						throw new MissingParameterException('image_info[\'name\']');
					}
				}
	
			}
			
			$image_field_data[$this->image_name_input] = $image_name;
	
			$ref_data = $this->_Get_photo_ref_data( $image_field_data, $options );
			
			$uploaded_filepath = $this->upload_photo($ref_data['upload_image_id'], $options);
			
			$this->process_uploaded_file( $uploaded_filepath, $ref_data, $options );
			
			return $uploaded_filepath;
		}
		catch( Exception $e ) {
			
			try {
				//
				// Cleanup half-inserted image
				//
				if ( isset($ref_data) && isset($ref_data['db_image_id']) && $ref_data['db_image_id'] && $this->enable_photo_db ) {
					$db = $this->get_db_interface()->connect_w();
					$db->query( "DELETE FROM {$this->db_photo_table} WHERE {$this->db_photo_id_field}={$ref_data['db_image_id']}" );
				}
				
				throw $e;
			}
			catch( Exception $e ) {
				throw $e;
			}			
		
			
		}

	}

	public function process_uploaded_file( $uploaded_filepath, $ref_data, $options = array() ) {
		
		try { 

			 $uploaded_image_info = $this->get_image_info($uploaded_filepath);
			
			if ( $this->max_sane_width || $this->max_sane_height ) {
	
				if ( $this->max_sane_width && ($uploaded_image_info['width'] > $this->max_sane_width) ) {
					throw new UserDataException( __CLASS__ . "-image_too_wide %{$this->max_sane_width}%" );
					// DELETE IMAGE HERE TODO
				}
	
				if ( $this->max_sane_height && ($uploaded_image_info['height'] > $this->max_sane_height) ) {
					throw new UserDataException( __CLASS__ . "-image_too_high %{$this->max_sane_width}%" );
					// DELETE IMAGE HERE TODO
					return false;
				}
	
			}
	
			if ( $this->auto_resize_photo && $this->auto_resize_image ) {
	
				if ( $this->fixed_photo_width || $this->fixed_photo_height ) {
					$this->auto_resize_image($uploaded_filepath);
				}
	
			}
	
			//
			// If the watermark applies also to thumbnails, watermark the image before creating them.
			//
			if ( $this->watermark_thumbnails ) {
				$this->apply_watermark_to_upload($uploaded_filepath, $options);
			}
	
			if ( $this->auto_create_thumbnails ) {
	
				if ( count($this->thumbnail_sizes) > 0 ) {			
					$this->generate_thumbnails_for_image($ref_data['upload_image_id'], $ref_data['upload_ref_id'], $uploaded_filepath);
				}
	
			}
	
			if ( !$this->watermark_thumbnails ) {
				$this->apply_watermark_to_upload($uploaded_filepath, $options);
			}
	
			if ( $this->enable_photo_db ) {
				$this->update_image_filename($ref_data['db_image_id'], basename($uploaded_filepath));
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function apply_watermark_to_upload( $uploaded_filepath, $options = array() ) {

		try { 
			if ( isset($options['input_name']) ) {
				$input_key = $options['input_name'];
			}
	
			if ( $this->_Watermark_global['img'] || ($input_key && $this->_Watermarks[$input_key]) ) {
	
				$wm_hash = ( $this->_Watermarks[$input_key] ) ? $this->_Watermarks[$input_key] : $this->_Watermark_global;
	
				$wm_image = $wm_hash['img'];
				$wm_pos   = $wm_hash['pos'];
				$wm_trans = $wm_hash['trans'];
	
				$this->apply_watermark_to_image($uploaded_filepath, $uploaded_filepath, $wm_image, $wm_pos, $wm_trans);
	
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function auto_resize_image( $src_filepath ) {

		try { 
			return $this->reproportion_image($src_filepath, $src_filepath, $this->fixed_photo_width, $this->fixed_photo_height, $this->photo_shrink_only, $this->resized_photo_quality);
		}
		catch( Exception $e ) {
			throw $e;
		}
		

	}

	public function generate_thumbnails_for_image( $image_id, $ref_id = null, $src_path ) {

		try { 
			LL::Require_class('File/FilePath');
			LL::Require_class('File/FileOperation');
	
			if ( count($this->thumbnail_sizes) <= 0 ) {
				return true;
			}
	
			if ( !$this->is_valid_image_id($image_id) ) {
				throw new InvalidParameterException('image_id');
			}
			
			if ( $ref_id || $this->require_ref_id ) {
				if ( !is_numeric($ref_id) || !$ref_id ) {
					throw new MissingParameterException('image_id');
				}
			}
	
			if ( !$src_path ) {
				throw new MissingParameterException('src_path');
			}
	
			$image_info = $this->get_image_info($src_path);
		
			if ( !($image_ext = $this->image_extension_by_type($image_info['type'], false)) ) {
				throw new InvalidImageException( "no_image_extension %{$src_path}%" );
			}
		
			if ( !($file_basename = $this->file_basename_by_image_id($image_id, $ref_id)) ) {
				throw new Exception ('no_file_basename' );
			}
	
			$thumb_filename = "{$file_basename}.{$image_ext}";
	
			foreach( $this->thumbnail_sizes as $thumb_key => $thumb_info ) {
	
				if ( !$thumb_key ) {
					throw new InvalidParameterException('thumb_key');
				}
	
				$thumb_dir =$this->thumb_dir_by_image_id_ref_id($image_id, $ref_id, $thumb_key);
			
				if ( !is_dir($thumb_dir) ) {
					
					//if ( ini_get('safe_mode') != 0 ) {
	                //	$mkdir_res = mkdir($thumb_dir, $this->photo_dir_umask);
	                //}
	                //else {
	                //	$mkdir_res = mkpath($thumb_dir, $this->photo_dir_umask);
	                //}
	
	                if ( !FileOperation::Create_path($thumb_dir, $this->photo_dir_umask, array('base_path' => $this->photo_base_path)) ) { 
						throw new WriteException ("mkdir_failed %{$thumb_dir}%" );
					}
				}
	
				$thumb_filepath = FilePath::Append_slash($thumb_dir) . $thumb_filename;

				if ( $this->dest_filename_prefix ) {
					$thumb_filepath = $this->dest_filename_prefix . $thumb_filepath;
				}
	
				if ( $this->dest_filename_suffix ) {
					FUSE::Require_class('File/FilePath');
	
					$thumb_filepath = FilePath::Strip_extension($thumb_filepath);
					$thumb_filepath = $thumb_filepath . $this->dest_filename_suffix;
					$thumb_filepath .= '.' . $image_ext;
				}
	
				if ( !$thumb_info['width'] && !$thumb_info['height'] ) {
					throw new MissingParameterException('thumb_info');
				}
				else if ( $thumb_info['width'] && !$thumb_info['height'] ) {
					$this->reproportion_by_width($src_path, $thumb_filepath, $thumb_info['width'], false, $this->thumb_quality, $thumb_info);
				}
				else if ( $thumb_info['height'] && !$thumb_info['width'] ) {
					$this->reproportion_by_height($src_path, $thumb_filepath, $thumb_info['height'], false, $this->thumb_quality, $thumb_info);
				}
				else {
					//
					// both width and height were specified.
					//	
					
					$this->resize_image($src_path, $thumb_filepath, $thumb_info['width'], $thumb_info['height'], $this->thumb_quality, $thumb_info);
				}
	
				if ( $thumb_filepath ) {
					if ( $file_chmod = $this->get_image_file_chmod() ) {
						if ( !@chmod($thumb_filepath, $file_chmod) ) {
							throw new WriteException( "couldnt_chmod_file %{$thumb_filepath}%");
						}
					}
					
				}
			}
	
	
			return true;		
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}


	function file_basename_by_album_id( $album_id, $ref_id = null ) {

		return $this->file_basename_by_image_id( $album_id, $ref_id );

	}

	function file_basename_by_image_id( $image_id, $ref_id = null ) {

		try {
			if ( $ref_id || $this->require_ref_id ) {
				if ( !is_numeric($ref_id) || !$ref_id ) {
					throw new InvalidParameterException('ref_id');
				}
			}
	
			if ( $this->enable_photo_db ) {
	
				if ( $ref_id && $this->prepend_ref_id ) {
					$filename	  = "{$ref_id}-{$image_id}";
				}
				else {
					$filename	  = $image_id;
				}
			}
			else {
	
				$parts = pathinfo($image_id);
				
				$filename = $parts['filename'];
	
			}
			
			return $filename;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}


	public function thumb_dir_by_image_id_ref_id( $image_id, $ref_id = null, $thumb_key ) {

		try { 
			$album_id = $this->album_id_by_image_id_ref_id($image_id, $ref_id);
			return $this->thumb_dir_by_album_id( $album_id, $thumb_key );
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function thumb_dir_by_album_id( $album_id, $thumb_key ) {

		try { 
			LL::Require_class('File/FilePath');
	
			if ( !$thumb_key  ) {
				throw new MissingParameterException('thumb_key');
			}
	
			if ( !$album_id || !$this->is_valid_album_id($album_id) ) {
				throw new InvalidParameterException('album_id');
			}				
	
			$album_dir = $this->album_dir_by_album_id($album_id);
	
			$thumb_subdir = $this->thumbnail_dirname . '_' . $thumb_key;
	
			$thumb_dir = FilePath::Append_slash($album_dir) . $thumb_subdir;
	
			return $thumb_dir;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function add_thumbnail_size( $thumb_key, $thumb_width = null, $thumb_height = null, $options = array() ) { 

		try { 		
			if ( !$thumb_key  ) {
				throw new MissingParameterException( 'thumb_key' );
			}
	
			if ( !$thumb_width && !$thumb_height ) {
				throw new MissingParameterException( 'thumb_width or thumb_height' );
			}
	
			$options['width'] = $thumb_width;
			$options['height'] = $thumb_height;
	
			$this->thumbnail_sizes[$thumb_key] = $options;
		
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function dir_range_by_album_id ( $album_id ) {

		try { 
			
			if ( $this->random_photo_id_enabled() ) {
				return $this->get_dir_range( $this->album_range_index_by_album_id($album_id) );
			}
			else {
				return $this->get_dir_range( $album_id );
			}
		}
		catch( Exception $e ) {
			throw $e;
		}


	}

	public function get_ref_id_field() {
		
		try { 
			if ( $this->merge_referenced_photo_table ) {
				return $this->db_photo_id_field;
			}
			else {
				return $this->db_ref_id_field;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_dir_range( $which_number, $options = array()) {
	       
	    try {
	    	LL::Require_class('File/FilePath');
	    	
	    	if ( !isset($options['increment']) || !$options['increment'] ) {
	    		$options['increment'] = $this->dir_range_increment;
	    	}
	    	
	    	if ( $this->random_photo_id_enabled() && $this->separate_dirs_for_prefix_chars ) {
	    		
	    		$dir_name = '';
	    		
	    		for( $j = 0; $j < $this->random_id_prefix_len; $j++ ) {
	    		
	    			$dir_name .= substr($which_number, $j, 1) . DIRECTORY_SEPARATOR;
	    		
	    		}
	    		
	    		return rtrim($dir_name, DIRECTORY_SEPARATOR);	
	    	}
	    	else {
	    		return FilePath::Range_dir_name( $which_number, $options );
	    	}
	    } 
		catch( Exception $e ) {
			throw $e;
		}	       
	}

	public function create_image_dir( $album_id, $additional_path = '' ) {

		try { 
			LL::Require_class('File/FilePath');
			LL::Require_class('File/FileOperation');
	
			if ( !$album_id || !$this->is_valid_album_id($album_id) ) {
				throw new InvalidParameterException('album_id');
			}
	
			if ( $additional_path ) {
				$additional_path = FilePath::Append_slash($additional_path);
			}
	
			if ( $this->enable_dir_ranges ) {
	
				$dir_range = $this->dir_range_by_album_id($album_id);
				$destination_dir = FilePath::Append_slash($this->photo_base_path) . $additional_path . $dir_range;
			}
			else {
				$destination_dir = FilePath::Append_slash($this->photo_base_path) . $additional_path;
			}
	
			
			if ( !is_dir($destination_dir) ) {
				
				//if ( ini_get('safe_mode') != 0 ) {
	            // 	$mkdir_res = mkdir($destination_dir, $this->photo_dir_umask);
	            //}
	            //else {
	            //   	$mkdir_res = mkpath($destination_dir, $this->photo_dir_umask);
	            //}
	
	           FileOperation::Create_path($destination_dir, $this->photo_dir_umask, array('base_path' => $this->photo_base_path) );
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function album_id_by_image_id_ref_id( $image_id, $ref_id = null ) {

		try {

			if ( !$this->is_valid_image_id($image_id) ) {
				throw new InvalidParameterException('image_id');
			}
	
			if ( $ref_id || $this->require_ref_id ) {
				if ( !is_numeric($ref_id) || !$ref_id ) {
					throw new InvalidParameterException('ref_id');
				}
			}
	
			if ( $this->enable_photo_db ) {
	
				if ( $ref_id && $this->prepend_ref_id ) {
					$album_id	  = $ref_id;
				}
				else {
					$album_id	  = $image_id;
				}
			}
			else if ( $this->random_photo_id_enabled() ) {
				$album_id = $image_id;
			}
			else {
	
				$album_id = $image_id;
			
			}
	
			return $album_id;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function album_dir_by_album_id( $album_id, $get_link = 0) {

		try {
			LL::Require_class('File/FilePath');
	
			if ( !$album_id || !$this->is_valid_album_id($album_id) ) {
				throw new InvalidParameterException( 'album_id' );
			}				
	
			$album_dir = false;
			$base_dir  = ( $get_link ) ? $this->photo_base_link : $this->photo_base_path;
		
			if ( !$base_dir ) {
				throw new MissingParameterException('base_dir');
			}
			else {
				if ( $this->enable_dir_ranges ) {
		
					$dir_range = $this->dir_range_by_album_id($album_id);
					$album_dir = FilePath::Append_slash($base_dir) . $dir_range;
				}
				else {
					$album_dir = $base_dir;
				}
			}
	
			return $album_dir;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function count_photos_by_ref_id( $ref_id ) {

		try { 
			
			$num_pics = false;		
	
			if ( !is_numeric($ref_id) || !$ref_id ) {
				throw new InvalidParameterException('ref_id');
			}
			
			if ( $this->enable_photo_db ) {
	
				if ( !($ref_id_field = $this->get_ref_id_field()) ) {
					throw new MissingParameterException('ref_id_field');
				}
	
				$db = $this->get_db_interface();
				
				$sql_query = "SELECT count(*) AS count FROM {$this->db_photo_table} WHERE {$ref_id_field}={$ref_id}";
				
				$result = $db->query($sql_query);
				
				
				$num_pics = $db->fetch_col('count', 0, $result );
				
			}
			else {
	
				$album_id  = $ref_id;
				$album_dir = $this->album_dir_by_album_id( $album_id );
	
				if ( false === ($glob_arr = glob("{album_dir}/{$album_id}-[0-9]*")) ) {
					throw new ReadException( __CLASS__ . "-couldnt_glob_pics %{album_dir}/{$album_id}-[0-9]*%" );
				}
	
				$num_pics = ( is_array($glob_arr) ) ? count($glob_arr) : 0;
			}
	
			return $num_pics;
		}
		catch( Exception $e ) {
			throw $e;
		}	 
	
	}


	public function get_album_linkdir( $album_id ) {

		try { 
			return $this->get_album_link($album_id);
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_album_link( $album_id ) {
		try { 
			return $this->album_dir_by_album_id( $album_id, 1 );
		}
		catch( Exception $e ) {
			throw $e;
		}			
	}


	public function valid_extension_list() {
	
		try { 
			$extension_list = null;
	
			if ( count($this->allowed_img_types) > 0 ) {
				foreach( $this->allowed_img_types as $cur_type ) {
					$extension_list .= $this->image_extension_by_type($cur_type, false) . ', ';
				}
	
				$extension_list = substr( $extension_list, 0, -2 ); //Strip trailing comma space
			}
	
	
			return $extension_list;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function first_uploaded_filename() {

		return $this->_First_uploaded_filename;

	}

	public function first_uploaded_path_info() {
		
		try { 
			return $this->photo_path_info_by_filename($this->first_uploaded_filename());
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function get_uploaded_files( $return_type = null ) {

		try { 
			$uploaded_arr = array();

			foreach( $this->_Uploaded_files as $index => $info ) {

				$info_arr = $this->photo_path_info_by_filename($info['filename']);
	
				foreach( $info_arr as $key => $val ) {
					if ( !isset($this->_Uploaded_files[$index][$key]) ) {
						$this->_Uploaded_files[$index][$key] = $val;
					}					
				}

				return $this->_Uploaded_files;
			}

		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function photo_path_info_by_filename( $filename ) {

		try { 
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}
		
			$info_arr = array();
				
			$filename = basename($filename);
	
			clearstatcache(); //Otherwise filesize will be cached from before resize
	
			$info_arr['filename']      = $filename;
			$info_arr['absolute_link'] = $this->absolute_image_link_by_filename($filename);
			$info_arr['relative_link'] = $this->relative_link_path_by_filename($filename);
			$info_arr['dir_range']	   = ( $this->enable_dir_ranges ) ? $this->image_dir_range_by_filename($filename) : null;
			$info_arr['absolute_path'] = $this->absolute_image_path_by_filename($filename);
			$info_arr['filesize']	   = @filesize($info_arr['absolute_path']);
			$info_arr['filesize_kb']   = round(($info_arr['filesize'] / 1024), 2);
	
			return $info_arr;	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function get_uploaded_filenames() {

		return $this->_Uploaded_filenames;

	}

	public function absolute_image_path_by_filename( $filename ) {

		try { 
	
			LL::Require_class('File/FilePath');
	
			if ( !$this->photo_base_path ) {
				throw new MissingParameterException('photo_base_path');
			}
	
			$filename = basename($filename);
	
			if ( $this->enable_dir_ranges ) {
				return FilePath::Append_slash($this->photo_base_path) . $this->relative_range_path_by_filename($filename, false);
			}
			else {
				return FilePath::Append_slash($this->photo_base_path) . $filename;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function absolute_image_link_by_filename( $filename ) {

		try { 
			LL::Require_class('File/FilePath');
	
			if ( !$this->photo_base_path ) {
				throw new MissingParameterException('photo_base_path');
			}
	
			$filename = basename($filename);
	
			if ( $this->enable_dir_ranges ) {
				return FilePath::Append_slash($this->photo_base_link) . $this->relative_range_path_by_filename($filename, false);
			}
			else {
				return FilePath::Append_slash($this->photo_base_link) . $filename;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function relative_link_path_by_filename( $filename ) {

		if ( $this->enable_dir_ranges ) {
			return str_replace( DIRECTORY_SEPARATOR, '/', $this->relative_range_path_by_filename( $filename, true ));
		}
		else {
			return '/' . $filename;
		}
		
	}
	

	public function relative_range_path_by_filename( $filename, $prepend_slash = true ) {

		try { 
			LL::Require_class('File/FilePath');
			
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}
	
			if ( $range = $this->image_dir_range_by_filename($filename) ) {
				if ( $prepend_slash ) {
					$range = FilePath::Prepend_slash($range);
				}
			
				return FilePath::Append_slash($range) . basename($filename);
			}		
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function absolute_range_path_by_filename( $filename ) {

		try {
			
			LL::Require_class('File/FilePath');

			if ( !$this->photo_base_path ) {
				throw new MissingParameterException('photo_base_path');
			}
	
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}

			$relative_range_path = $this->relative_range_path_by_filename($filename, false);
		
			return FilePath::Append_slash($this->photo_base_path) . $relative_range_path;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function image_dir_range_by_filename( $filename ) {

		try {
			
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}
	
			$filename = basename($filename); //Strip out any path information
	
			if ( strpos($filename, '.') ) {
				$filename = substr( $filename, 0, strrpos($filename, '.') ); //Strip file extension.
			}
			
			list($album_id, $ref_id) = $this->album_id_ref_id_by_filename($filename);
	
			if ( $this->is_valid_album_id($album_id) ) {
	
				if ( $this->random_photo_id_enabled() ) {
	
					//
					// In this case, the album id will be a hex string with a number at the beginning.
					// Strip out that number to get our range.
				
					$find_range_from = substr( $album_id, 0, $this->random_id_prefix_len );
				}
				else {
					$find_range_from = $album_id;
				}
	
				if ( $range = $this->get_dir_range($find_range_from) ) {
					return $range;
				}
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function album_id_ref_id_by_filename( $filename ) {

		try { 
			$ref_id   = null;
			$album_id = null;
			
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}
			
			$filename = basename($filename); //Strip out any path information
	
			if ( strpos($filename, '.') ) {
				$filename = substr( $filename, 0, strrpos($filename, '.') ); //Strip file extension.
			}
	
	
			if ( strpos($filename, '-') ) { //OK if zero returns false here, which will happen if the first character is -
				
				$ref_id   = substr($filename, 0, strrpos($filename,'-') );
				$album_id = substr( strrchr($filename, '-'), 1 );
				
			}
			else {
				$album_id = $filename;
			}
	
	
			return array($album_id, $ref_id);

		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function set_global_watermark( $watermark_image, $watermark_pos = null, $watermark_trans = null ) {

		try { 
			if ( !$watermark_pos ){
				$watermark_pos = constant('WATERMARK_POS_LOWER_RIGHT');
			}
	
			if ( !$watermark_trans ) {
				$watermark_trans = $this->watermark_default_trans;
			}
	
			$this->_Watermark_global['pos']   = $watermark_pos;
			$this->_Watermark_global['img']   = $watermark_image;
			$this->_Watermark_global['trans'] = $watermark_trans;
	
			return true;
		}		
		catch( Exception $e ) {
			throw $e;
		}		
	}

	public function set_watermark( $input_key , $watermark_image = null, $watermark_pos = null, $watermark_trans = null ) {

		try { 

			if ( !$input_key ) {
				throw new MissingParameterException('input_key');
			}
	
	
			if ( !$watermark_image ) {
				$watermark_image = $this->get_global_watermark_image();
			}
	
			if ( !$watermark_pos ){
				if ( !($watermark_pos = $this->get_global_watermark_pos()) ) {
					$watermark_pos = self::WATERMARK_POS_LOWER_RIGHT;
				}
			}
	
			if ( !$watermark_trans ) {
				$watermark_trans = $this->watermark_default_trans;
			}
	
			$this->_Watermarks[$input_key] = array( 'img' => $watermark_image, 'pos' => $watermark_pos, 'trans' => $watermark_trans );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_global_watermark_image() {

		if ( $this->_Watermark_global['img'] ) {
			return $this->_Watermark_global['img'];
		}

		return false;

	}

	public function get_global_watermark_pos() {

		if ( $this->_Watermark_global['pos'] ) {
			return $this->_Watermark_global['pos'];
		}

		return false;

	}

	public function is_valid_image_id ( $image_id ) {

		if ( $this->enable_photo_db || $this->random_photo_id_enabled() ) {
			if ( !$image_id || preg_match('/[^A-Za-z0-9]/', $image_id) ) {
				return false;
			}
		}

		return true;

	}

	public function is_valid_album_id( $album_id ) {

		return $this->is_valid_image_id( $album_id );

	}

	public function get_random_photo_id() {
	
		//
		// we always want random IDs to start  
		// with random_id_prefix_len numbers, 
		// so that range directories are still applicable
		//
	
		$rand_max = str_pad( '1', $this->random_id_prefix_len + 1, '0', STR_PAD_RIGHT );
		$rand_max = intval($rand_max);
		$rand_max = $rand_max - 1;

		$extra_unique = uniqid();

		$id = uniqid( str_pad(rand(1, $rand_max), $this->random_id_prefix_len, '0', STR_PAD_LEFT) , false);
		$id .= $extra_unique;
		
		return $id;
	}

	//
	// This function is used to determine what part of an image id to use to determine its "range" 
	// For random IDs, this will be the first random_id_prefix_len numbers of the image_id, 
	// 		   since only those numbers are guaranteed to be numeric. 
	// Otherwise, the range is simply the imade id itself.
	//
	public function album_range_index_by_image_id( $image_id ) {

		try { 
			if ( !$this->is_valid_image_id($image_id) ) {
				throw new InvalidParameterException('image_id');
			}
	
			if ( $this->random_photo_id_enabled() ) {
	
				$range_index = substr( $image_id, 0, $this->random_id_prefix_len );
	
			}
			else {
				$range_index = $image_id;
			}
	
			return $range_index;			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function album_range_index_by_album_id( $album_id ) {

		return $this->album_range_index_by_image_id( $album_id );

	}


	public function delete_image_file_by_filename( $filename, $include_thumbs = false ) {

		try { 
			
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}

			$absolute_path = $this->absolute_image_path_by_filename($filename);
	
			if ( file_exists($absolute_path) ) {
			
				if ( !unlink($absolute_path) ) {
					throw new WriteException( __CLASS__ . '-couldnt_delete_image' );
				}
			}

			if ( $include_thumbs ) {
				$this->delete_thumbs_by_filename($filename);
			}
		
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function delete_thumbs_by_filename( $filename ) {

		try { 
			LL::Require_class('File/FilePath');
			
			if ( !$filename ) {
				throw new MissingParameterException('filename');
			}
	
			if ( count($this->thumbnail_sizes) > 0 ) {
	
				if ( !$this->photo_base_path ) {
					throw new MissingParameterException('photo_base_path');
				}
	
				if ( $this->enable_dir_ranges ) {
					$range_dir = $this->image_dir_range_by_filename($filename);
					$thumb_parent_dir = FilePath::Append_slash($this->photo_base_path) . $range_dir;
				}
				else {
	
					$thumb_parent_dir = $this->photo_base_path;
				}

				foreach( $this->thumbnail_sizes as $thumb_key => $thumb_info ) {
		
					if ( !$thumb_key  ) {
						continue;
					}
	
					if ( $cur_thumb_dirname = $this->thumb_dirname_by_key($thumb_key) ) {
						$cur_thumb_path = FilePath::Append_slash($thumb_parent_dir) . FilePath::Append_slash($cur_thumb_dirname) . $filename;
	
						if ( file_exists($cur_thumb_path) ) {
							if ( !unlink($cur_thumb_path) ) {
								throw new WriteException ( __CLASS__ . '-couldnt_delete_thumb' );
							}
						}
	
					}
				}
	

			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}			

	}
	function thumb_dirname_by_key ( $thumb_key ) {

		return $this->thumbnail_dirname . '_' . $thumb_key;

	}

	public function delete_photo_by_db_image_id( $image_id ) { 

		try { 
			$db = $this->get_db_interface()->connect_w();
			$txn_started = false;
			$image_filename = null;
	
			if ( !$image_id || !is_numeric($image_id) ) {
				throw new InvalidParameterException('image_id');
			}
	
			$photo_info = $this->db_photo_info_by_image_id($image_id);
	
			
			
			if ( !isset($photo_info[$this->db_photo_id_field]) || !$photo_info[$this->db_photo_id_field] ) {
				throw new MissingParameterException('db_photo_id_field');
			}
	
			if ( isset($photo_info[$this->db_filename_field]) && $photo_info[$this->db_filename_field] ) {
				$image_filename = $photo_info[$this->db_filename_field];
			}
	
			if ( !$db->in_transaction() ) {
				$db->start_transaction();
				$txn_started = true;
			}
	
			$this->remove_image_from_db_by_id($image_id);
	
			if ( $image_filename ) {
				$this->delete_image_file_by_filename($image_filename, true);
			}
	
			if ( $txn_started ) {
				$db->commit();
			}
	
			return true;
		}
		catch( Exception $e ) {
			
			if ( $txn_started ) {
				$db->rollback();
			}
			
			throw $e;
			
			
		}
	}

	public function remove_image_from_db_by_id( $image_id ) {

		try { 
		
			$db = $this->get_db_interface()->connect_w();

			if ( !$image_id || !is_numeric($image_id) ) {
				throw new InvalidParameterException('image_id');
			}		

			$sql_query = "DELETE FROM {$this->db_photo_table} WHERE {$this->db_photo_id_field} = {$image_id}";

			$result = $db->query($sql_query);
			return true;
		}
		catch( Exeption $e ) {
			throw $e;
		}

	}

	public function db_oldest_photo_id_by_ref_id( $ref_id ) {

		try { 
			
			$db = $this->get_db_interface();
			$query_obj = $db->new_query_obj();

			if ( !is_numeric($ref_id) || !$ref_id ) {
				throw new InvalidParameterException('ref_id');
			}		
		
			$query_obj->select_first( "{$this->db_photo_table}.*" );
			$query_obj->from( $this->db_photo_table );
	
			if ( $this->db_timestamp_field ) {
				$query_obj->order_by( "{$this->db_timestamp_field} ASC ");
			}
			else {
				$query_obj->order_by( "{$this->db_photo_id_field} ASC ");
			}

			$ref_id_field = $this->get_ref_id_field();

			$query_obj->limit(1);
			$query_obj->where( "{$ref_id_field} = {$ref_id}" );

			$sql_query = $query_obj->generate_sql_query();
		

			$result = $db->query($sql_query);
			if ( $id = $result->fetch_col($this->db_photo_id_field, 0, $result) ) {
				return $id;
			}
		
			return null;	
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	public function delete_oldest_photo_by_ref_id( $ref_id ) {

		try { 
			$db = $this->get_db_interface();
	
			if ( !is_numeric($ref_id) || !$ref_id ) {
				throw new InvalidParameterException('ref_id');
			}		
	
			$photo_count = $this->count_photos_by_ref_id($ref_id);
	
			if ( $photo_count > 0 ) {
				$image_id = $this->db_oldest_photo_id_by_ref_id($ref_id);
				$this->delete_photo_by_db_image_id($image_id);
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
			
	}

	public function set_active_ref_id( $ref_id ) {

		$this->active_ref_id = $ref_id;
		
	}

	public function db_photo_info_by_image_id( $image_id, $query_obj = null ) {

		try { 
			
			$db = $this->get_db_interface();
	
			if ( !$image_id || !is_numeric($image_id) ) {
				throw new InvalidParameterException('image_id');
			}		
	
			if ( !is_object($query_obj) ) {
				$query_obj = $db->new_query_obj();
			}
	
			$query_obj->select_first( "{$this->db_photo_table}.*" );
			$query_obj->from( $this->db_photo_table );
			$query_obj->where( "{$this->db_photo_id_field} = {$image_id}" );
	
			$sql_query = $query_obj->generate_sql_query();
			
			$result = $db->query($sql_query);

			if ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
				return $row;
			}
	
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function delete_photos_by_ref_id( $ref_id ) {

		try { 
			
			$db = $this->get_db_interface()->connect_w();		
			$txn_started = false;
	
			if ( !is_numeric($ref_id) || !$ref_id ) {
				throw new InvalidParameterException('ref_id');
			}		
	
			if ( !$db->in_transaction() ) {
				$db->start_transaction();
				$txn_started = true;
			}
	
			$photo_res = $this->fetch_db_photos_by_ref_id($ref_id);

			while ( $row = $photo_res->fetch(PDO::FETCH_ASSOC) ) {
				$this->delete_photo_by_db_image_id($row[$this->db_photo_id_field]);
			}
	
			if ( $txn_started ) {
				$db->commit();
			}
	
			return true;
	
		}
		catch( Exception $e ) {
			if ( $txn_started ) {
				$db->rollback();
			}
		
			throw $e;
		}
		
	}

	public function fetch_db_photos_by_ref_id( $ref_id, $options = null ) {

		try { 
			
			$db = $this->get_db_interface();
			
			if ( isset($options['query_obj']) ) {
				$query_obj = $options['query_obj'];
				$query_obj = $query_obj->clone_query_obj();
			}
			else {
				$query_obj = $db->new_query_obj();
			}
	
			if ( !is_numeric($ref_id) || !$ref_id ) {
				throw new InvalidParameterException('ref_id');
			}		
	
			$ref_id_field = $this->get_ref_id_field();
	
			$query_obj->select( "{$this->db_photo_table}.*" );		
			$query_obj->from( $this->db_photo_table );		
			$query_obj->where( "{$this->db_photo_table}.{$ref_id_field} = $ref_id" );
	
			$sql_query = $query_obj->generate_sql_query();
	
			$result = $db->query($sql_query);
	
			return $result;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_uploaded_file_chmod( $mode ) {
		
		$this->_Image_file_chmod = $mode;
	}

	public function get_uploaded_file_chmod() {

		return $this->_Image_file_chmod;

	}

	public function set_image_file_chmod($mode) {

		$this->_Image_file_chmod = $mode;

	}

	public function get_image_file_chmod() {

		return $this->_Image_file_chmod;

	}
	
	public function get_db_interface() {
		
		return $this->_DB;
	}
	
	public function set_db_interface($db) {
		
		$this->_DB = $db;
	
	}

} //end class

