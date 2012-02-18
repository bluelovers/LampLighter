<?php

/*----------------------------------------------------------------------------
| Copyright (C) 2004 Jim Keller (jim@phpfuse.net)
| PHP FUSE (http://www.phpfuse.net)
|
| This program is free software; you can redistribute it and/or
| modify it under the terms of the GNU General Public License
| as published by the Free Software Foundation; either version 2
| of the License, or (at your option) any later version.
|
| This program is distributed in the hope that it will be useful,
| but WITHOUT ANY WARRANTY; without even the implied warranty of
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
| GNU General Public License for more details.
|
| You should have received a copy of the GNU General Public License
| along with this program; if not, write to the Free Software
| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
+----------------------------------------------------------------------------*/


if ( !defined('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') ) {

define ('FUSE_UPLOAD_ORGANIZER_CLASS_NAME', 'FuseUploadOrganizer' );

//
// Possible parameters for get_uploaded_files
//
define ('UPLOAD_ORGANIZER_RETURN_FILENAMES_ONLY', 1);
define ('UPLOAD_ORGANIZER_RETURN_RELATIVE_LINK_PATH', 2);
define ('UPLOAD_ORGANIZER_RETURN_RELATIVE_LINKPATH', 2);
define ('UPLOAD_ORGANIZER_RETURN_ABSOLUTE_LINK_PATH', 3);
define ('UPLOAD_ORGANIZER_RETURN_ABSOLUTE_LINKPATH', 3);
define ('UPLOAD_ORGANIZER_RETURN_INFO_ARRAY', 4);

class FuseUploadOrganizer {

	var $upload_rootdir;
	var $upload_linkdir;

	var $post_field_prefix = NULL;

	var $file_extensions_case_sensitive = false;
	var $mime_types_case_sensitive      = false;
	var $allow_missing_file_extension   = false;
	var $file_extensions_to_lowercase   = true;
	var $store_original_file_name	    = true;

	//---------------------
	// DB / SQL variables
	//---------------------
	var $db_field_prefix = NULL;

	var $enable_file_db      = true;
	var $db_use_transactions = true;	

	var $db_ref_table = NULL;
	var $db_ref_id_field = NULL;

	var $db_table_files = 'files';
	var $db_field_file_id = 'file_id';
	var $db_field_file_datetime = 'file_datetime';
	var $db_field_file_name = 'file_name';
	var $db_field_original_file_name = 'original_file_name';

	//-----------------
	// Class Variables
	//----------------
	var $active_ref_id;
	var $prepend_ref_id  = true;
	var $require_ref_id  = false;
	var $require_file_upload   = false;
	var $auto_overwrite_when_full = false;

	var $enable_random_file_id = false;
	var $enable_upload_slots = false;
	var $enable_dir_ranges = true;
	var $upload_dir_umask = 0755;
	var $max_files_per_ref_id = 6;
	var $max_batch_upload     = 1;
	var $max_upload_size      = 100; //KB
	var $dir_range_increment  = 500;

	var $force_open_slot  = 0;

	var $file_name_key	   = 'filename';

	var $random_id_prefix_len = 3;

	//
	// Private
	//
	var $_field_delimiter = '_';
	var $_Block_dangerous_file_extensions = true;
	var $_First_uploaded_filename;
	var $_Uploaded_filenames;
	var $_Uploaded_file_chmod;
	var $_Verbose = false;	//don't turn me on unless debugging.

	//
	// Protected
	//
	var $_Key_post_input_name  = '_post_input_name';
	var $_Key_db_field_name	   = '_db_field';
	var $_Key_field_value	   = '_value';
	var $_Key_field_parse	   = '_parse_val';
	var $_Key_field_quote	   = '_quote_val';


	var $_Additional_data_fields;
	var $_Allowed_file_extensions;
	var $_Allowed_mime_types;

	var $_Disallowed_file_extensions;

	//---------------------
	// Form Input variables
	//---------------------
	var $file_input_key    = 'upload_file';


	function FuseUploadOrganizer() {

		$this->upload_rootdir 	= ( defined('FUSE_UPLOAD_ORGANIZER_ROOTDIR') ) ? constant('FUSE_UPLOAD_ORGANIZER_ROOTDIR') : NULL;
		$this->upload_linkdir 	= ( defined('FUSE_UPLOAD_ORGANIZER_LINKDIR') ) ? constant('FUSE_UPLOAD_ORGANIZER_LINKDIR') : NULL;

		$this->max_files_per_ref_id	= ( defined('FUSE_UPLOAD_MAX_FILES_PER_REF_ID') ) ? constant('FUSE_UPLOAD_MAX_FILES_PER_REF_ID') : $this->max_files_per_ref_id;
		$this->max_batch_upload		= ( defined('FUSE_UPLOAD_MAX_BATCH') ) ? constant('FUSE_UPLOAD_MAX_BATCH') : $this->max_batch_upload;

		$this->db_table_files	= ( defined('FUSE_UPLOAD_DB_TABLE_FILES') ) ? constant('FUSE_UPLOAD_DB_TABLE_FILES') : $this->db_table_files;
		$this->db_field_file_id = ( defined('FUSE_UPLOAD_DB_FIELD_FILE_ID') ) ? constant('FUSE_UPLOAD_DB_FIELD_FILE_ID') : $this->db_field_file_id;
		$this->db_field_file_datetime = ( defined('FUSE_UPLOAD_DB_FIELD_FILE_DATETIME') ) ? constant('FUSE_UPLOAD_DB_FIELD_FILE_DATETIME') : $this->db_field_file_datetime;
		$this->db_field_file_name = ( defined('FUSE_UPLOAD_DB_FIELD_FILE_NAME') ) ? constant('FUSE_UPLOAD_DB_FIELD_FILE_NAME') : $this->db_field_file_name;

		$this->require_ref_id		= ( defined('FUSE_UPLOAD_REQUIRE_REF_ID') ) ? constant('FUSE_UPLOAD_REQUIRE_REF_ID') : $this->require_ref_id;
		$this->prepend_ref_id		= ( defined('FUSE_UPLOAD_PREPEND_REF_ID') ) ? constant('FUSE_UPLOAD_PREPEND_REF_ID') : $this->prepend_ref_id;		
		$this->enable_file_db		= ( defined('FUSE_UPLOAD_ENABLE_FILE_DB') ) ? constant('FUSE_UPLOAD_ENABLE_FILE_DB') : $this->enable_file_db;
		$this->file_input_key		= ( defined('FUSE_UPLOAD_FILE_INPUT_KEY') ) ? constant('FUSE_UPLOAD_FILE_INPUT_KEY') : $this->file_input_key;

		$this->_Uploaded_file_chmod	= ( defined('FUSE_UPLOAD_FILE_CHMOD') ) ? constant('FUSE_UPLOAD_FILE_CHMOD') : $this->_Uploaded_file_chmod;

		$this->_Additional_data_fields  = array();
		$this->_Allowed_file_extensions = array();
		$this->_Allowed_mime_types 	= array();
		$this->_Disallowed_file_extensions = array();
	
		if ( defined('FUSE_UPLOAD_DISALLOWED_FILE_EXTENSIONS') ) {
			$bad_extensions = constant('FUSE_UPLOAD_DISALLOWED_FILE_EXTENSIONS');

			if ( $bad_extensions ) {
				$this->_Disallowed_file_extensions = explode_strip_whitespace(',', $bad_extensions );
			}
		}

		if ( $this->_Block_dangerous_file_extensions ) {

			$this->disallow_file_extension( 'com' );
			$this->disallow_file_extension( 'exe' );
			$this->disallow_file_extension( 'dll' );
			$this->disallow_file_extension( 'php' );
			$this->disallow_file_extension( 'phtml' );
			$this->disallow_file_extension( 'php4' );
			$this->disallow_file_extension( 'php5' );
			$this->disallow_file_extension( 'pl' );
			$this->disallow_file_extension( 'cgi' );
		}

	}

	function set_db_field_prefix( $prefix ) {
		
		$this->db_field_prefix = $prefix;
	}

	function set_post_field_prefix( $prefix ) {

		$this->post_field_prefix = $prefix;
	}

	function add_allowed_file_extension( $file_extension ) {
		
		return $this->allow_file_extension( $file_extension );
	}

	function allow_file_extension( $file_extension ) {

		$this->_Allowed_file_extensions[] = $file_extension;

	}

	function disallow_file_extension( $file_extension ) {

		$this->_Disallowed_file_extensions[] = $file_extension;

	}

	function add_allowed_mime_type( $mime_type ) {

		$this->_Allowed_mime_types[] = $mime_type;

	}

	function get_allowed_file_extensions() {

		if ( $this->file_extensions_case_sensitive ) {
			return $this->_Allowed_file_extensions;
		}
		else {
			return array_map('strtolower', $this->_Allowed_file_extensions);
		}
			
	}

	function get_disallowed_file_extensions() {

		return $this->_Disallowed_file_extensions;

	}

	function get_allowed_mime_types() {

		if ( $this->mime_types_case_sensitive ) {
			return $this->_Allowed_mime_types;
		}
		else {
			return array_map('strtolower', $this->_Allowed_mime_types);
		}
			
	}

	function is_valid_file_id ( $file_id ) {

		if ( !$file_id || preg_match('/[^A-Za-z0-9]/', $file_id) ) {
			return false;
		}

		return true;

	}

	function valid_extension_list() {
	
		$extension_list = null;

		if ( count($this->_Allowed_file_extensions) > 0 ) {
			foreach( $this->_Allowed_file_extensions as $cur_ext ) {
				$extension_list .= $cur_ext . ', ';
			}

			$extension_list = substr( $extension_list, 0, -2 ); //Strip trailing comma space
		}


		return $extension_list;

	}

	function applied_id_by_file_id_ref_id( $file_id, $ref_id = null ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$applied_id  = NULL;

		if ( !$this->is_valid_file_id($file_id) ) {
			LL::raise_fuse_error( 'FuseUploadOrganizer-invalid_file_id', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $ref_id || $this->require_ref_id ) {
			if ( !is_numeric($ref_id) || !$ref_id ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		if ( $this->enable_file_db ) {

			if ( $ref_id && $this->prepend_ref_id ) {
				$applied_id	  = $ref_id;
			}
			else {
				$applied_id	  = $file_id;
			}
		}
		else if ( $this->enable_random_file_id ) {
			$applied_id = $file_id;
		}
		else {
			$applied_id = $file_id;
		
		}

		return $applied_id;
	}

	function dir_range_by_ref_id( $ref_id ) {

		return $this->get_dir_range( $ref_id );

	}

	function dir_range_by_file_id( $file_id ) {

		return $this->get_dir_range( $file_id );

	}

	function dir_range_by_applied_id ( $applied_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( $this->enable_random_file_id ) {
			return $this->get_dir_range( $this->applied_range_index_by_applied_id($applied_id) );
		}
		else {
			return $this->get_dir_range( $applied_id );
		}

		return false;

	}

	//
	// This function is used to determine what part of a file id to use to determine its "range" 
	// For random IDs, this will be the first random_id_prefix_len numbers of the file_id, 
	// 		   since only those numbers are guaranteed to be numeric. 
	// Otherwise, the range is simply the file id itself.
	//
	function applied_range_index_by_file_id( $file_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$this->is_valid_file_id($file_id) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-invalid_file_id', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $this->enable_random_file_id ) {
			$range_index = substr( $file_id, 0, $this->random_id_prefix_len );

		}
		else {
			$range_index = $file_id;
		}

		return $range_index;			

	}

	function album_range_index_by_applied_id( $applied_id ) {

		return $this->album_range_index_by_file_id( $applied_id );

	}


	function file_dir_by_applied_id( $applied_id, $get_link = 0) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		require_library('files');

		if ( !$applied_id || !$this->is_valid_applied_id($applied_id) ) {
			LL::raise_fuse_error( 'FuseUploadOrganizer-invalid_applied_id', "\$applied_id: {$applied_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}				

		$file_dir = false;
		$base_dir  = ( $get_link ) ? $this->upload_linkdir : $this->upload_rootdir;
	
		if ( !$base_dir ) {
			LL::raise_fuse_error( 'FuseUploadOrganizer-missing_basedir', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
		else {
			if ( $this->enable_dir_ranges ) {
	
				if ( !$dir_range = $this->dir_range_by_applied_id($applied_id) ) {
					LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_range_dir', "\$applied_id: {$applied_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
					return false;
				}
				else {
					$file_dir = add_trailing_path_slash($base_dir) . $dir_range;
				}
			}
			else {
				$file_dir = $base_dir;
			}
		}

		return $file_dir;
	}

	function is_valid_applied_id( $applied_id ) {

		return $this->is_valid_file_id( $applied_id );

	}

	function create_file_dir_by_applied_id( $applied_id, $additional_path = '' ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		require_library('files');

		if ( !$applied_id || !$this->is_valid_applied_id($applied_id) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-invalid_applied_id', "\$applied_id: {$applied_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $additional_path ) {
			$additional_path = add_trailing_path_slash($additional_path);
		}

		if ( $this->enable_dir_ranges ) {

			if ( !($dir_range = $this->dir_range_by_applied_id($applied_id)) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_dir_range', "\$applied_id: {$applied_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}

			$destination_dir = add_trailing_path_slash($this->upload_rootdir) . $additional_path . $dir_range;
		}
		else {
			$destination_dir = add_trailing_path_slash($this->upload_rootdir) . $additional_path;
		}

		$destination_dir = sanitize_filepath($destination_dir);

		if ( !is_dir($destination_dir) ) {
			if ( !mkpath($destination_dir, $this->upload_dir_umask) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-mkdir_failed', "\$destination_dir: {$destination_dir}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		return true;
	}



	function count_files_by_ref_id( $ref_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$num_files = 0;		

		if ( !is_numeric($ref_id) || !$ref_id ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $this->enable_file_db ) {

			if ( !$this->db_ref_id_field ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_ref_id_field_set', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}

			$db =& global_db_object();
			
			$sql_query = "SELECT count({$this->db_field_file_id}) AS count FROM {$this->db_table_files} WHERE {$this->db_ref_id_field}={$ref_id}";

			if ( !($result = $db->query($sql_query)) ) {
				LL::sql_query_error( $sql_query, $my_location . __LINE__ );
				return false;
			}
			else {
				if ( $db->num_rows($result) > 0 ) {
					$num_files = $db->fetch_col('count', 0, $result );
				}
				else {
					$num_files = 0;
				}
			}
		
		}
		else {

			$applied_id  = $ref_id;
			$file_dir    = $this->file_dir_by_applied_id( $applied_id );

			if ( false === ($glob_arr = glob("{$file_dir}/{$applied_id}-[0-9]*")) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_glob_files', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}

			$num_files = ( is_array($glob_arr) ) ? count($glob_arr) : 0;
		}

		return $num_files;
		 

	}

	function db_oldest_file_id_by_ref_id( $ref_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();
		$query_obj =& new FuseSQLQuery();

		if ( !is_numeric($ref_id) || !$ref_id ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}		
		
		$query_obj->select_first( "{$this->db_table_files}.*" );
		$query_obj->from( $this->db_table_files );
	
		if ( $this->sql_timestamp_field ) {
			$query_obj->order_by( "{$this->db_field_file_datetime} ASC ");
		}
		else {
			$query_obj->order_by( "{$this->db_field_file_id} ASC ");
		}

		$query_obj->limit(1);
		$query_obj->where( "{$this->db_ref_id_field} = {$ref_id}" );

		$sql_query = $query_obj->generate_sql_query();
		

		if ( !($result = $db->query($sql_query)) ) {
			LL::sql_query_error( $sql_query, $my_location . __LINE__ );
			return false;
		}
		else { 
			if ( $db->num_rows($result) <= 0 ) {
				return 0;
			}
			else {
				if ( $id = $db->fetch_col($this->db_field_file_id, 0, $result) ) {
					return $id;
				}
			}
		}

		return false;
		
	}

	function db_file_info_by_file_id( $file_id, $query_obj = null ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();

		if ( !$file_id || !is_numeric($file_id) ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}		

		if ( !is_object($query_obj) ) {
			$query_obj =& new FuseSQLQuery();
		}

		$query_obj->select_first( "{$this->db_table_files}.*" );
		$query_obj->from( $this->db_table_files );
		$query_obj->where( "{$this->db_field_file_id} = {$file_id}" );

		$sql_query = $query_obj->generate_sql_query();
		
		if ( !($result = $db->query($sql_query)) ) {
			LL::sql_query_error( $sql_query, $my_location . __LINE__ );
			return false;
		}
		else { 
			if ( $db->num_rows($result) <= 0 ) {
				return false;
			}
			else {
				if ( $row = $db->fetch_unparsed_arr($result) ) {
					return $row;
				}
			}
		}

		return false;

	}

	function remove_file_from_db_by_id( $file_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();

		if ( !$file_id || !$this->is_valid_file_id($file_id) ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}		

		if ( !($dbh_w = $db->connect_w()) ) {
			LL::db_connect_error( $my_location . __LINE__ );
			return false;
		}

		$sql_query = "DELETE FROM {$this->db_table_files} WHERE {$this->db_field_file_id} = {$file_id} LIMIT 1";

		if ( !($result = $db->query($sql_query)) ) {
			LL::sql_query_error( $sql_query, $my_location . __LINE__ );
			return false;
		}

		return true;

	}

	function applied_id_ref_id_by_file_name( $filename ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		$ref_id   = null;
		$applied_id = null;

		if ( !$filename ) {
			LL::raise_fuse_error( 'general-missing_parameter %filename%', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		$filename = basename($filename); //Strip out any path information

		if ( strpos($filename, '.') ) {
			$filename = substr( $filename, 0, strrpos($filename, '.') ); //Strip file extension.
		}


		if ( strpos($filename, '-') ) { //OK if zero returns false here, which will happen if the first character is -
			
			$ref_id   = substr($filename, 0, strrpos($filename,'-') );
			$applied_id = substr( strrchr($filename, '-'), 1 );
			
		}
		else {
			$applied_id = $filename;
		}


		return array($applied_id, $ref_id);

		
	}

	function file_dir_range_by_file_name( $filename ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$filename ) {
			LL::raise_fuse_error( 'general-missing_parameter %filename%', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		$filename = basename($filename); //Strip out any path information

		if ( strpos($filename, '.') ) {
			$filename = substr( $filename, 0, strrpos($filename, '.') ); //Strip file extension.
		}
		
		if ( !(list($applied_id, $ref_id) = $this->applied_id_ref_id_by_file_name($filename)) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_extract_applied_id', "\$filename: {$filename}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $this->is_valid_applied_id($applied_id) ) {

			if ( $this->enable_random_file_id ) {

				//
				// In this case, the applied id will be a hex string with a number at the beginning.
				// Strip out that number to get our range.
			
				$find_range_from = substr( $applied_id, 0, $this->random_id_prefix_len );
			}
			else {
				$find_range_from = $applied_id;
			}

			if ( $range = $this->get_dir_range($find_range_from) ) {
				return $range;
			}
		}

		return false;

	}


	function relative_range_path_by_file_name( $filename, $prepend_slash = true ) {

		require_library('files');
	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$filename ) {
			LL::raise_fuse_error( 'general-missing_parameter %filename%', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $range = $this->file_dir_range_by_file_name($filename) ) {
			if ( $prepend_slash ) {
				$range = prepend_leading_path_slash($range);
			}
		
			return add_trailing_path_slash($range) . basename($filename);
		}		

		return false;

	}


	function absolute_file_path_by_file_name( $filename ) {

		require_library('files');

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$this->upload_rootdir ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_upload_rootdir', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		$filename = basename($filename);

		if ( $this->enable_dir_ranges ) {
			return add_trailing_path_slash($this->upload_rootdir) . $this->relative_range_path_by_file_name($filename, false);
		}
		else {
			return add_trailing_path_slash($this->upload_rootdir) . $filename;
		}

		return false;

	}

	function delete_file_by_file_name( $filename ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();

		if ( !$filename ) {
			LL::raise_fuse_error( 'general-missing_parameter %filename%', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !($absolute_path = $this->absolute_file_path_by_file_name($filename)) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_get_absolute_path', "\$filename: {$filename}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
	
		$absolute_path = sanitize_filepath($absolute_path);

		if ( file_exists($absolute_path) ) {
			
			if ( !@unlink($absolute_path) ) {
				LL::raise_fuse_error(  constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_delete_file', "\$absolute_path: {$absolute_path}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}
		
		return true;
		
	}

	function delete_file_by_db_file_id( $file_id ) { 

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();
		$txn_started = false;

		if ( !$file_id || !$this->is_valid_file_id($file_id) ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !($file_info = $this->db_file_info_by_file_id($file_id)) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_file_info', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !isset($file_info[$this->db_field_file_name]) || !$file_info[$this->db_field_file_name] ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_filename', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;		
		}

		if ( !isset($file_info[$this->db_field_file_id]) || !$file_info[$this->db_field_file_id] ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_file_id', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;		
		}

		$file_name = $file_info[$this->db_field_file_name];
		$file_id   = $file_info[$this->db_field_file_id];

		if ( !$db->in_transaction() ) {
			if ( !$db->start_transaction() ) {
				return false;
			}

			$txn_started = true;
		}

		if ( !$this->remove_file_from_db_by_id($file_id) ) {
			if ( $txn_started ) {
				$db->rollback();
			}
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_remove_db_file', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !$this->delete_file_by_file_name($file_name, true) ) {
			if ( $txn_started ) {
				$db->rollback();
			}

			LL::raise_fuse_error(  constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . 'couldnt_remove_file', "\$file_name: {$file_name}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $txn_started ) {
			$db->commit();
		}

		return true;

	}


	function delete_oldest_file_by_ref_id( $ref_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();

		if ( !is_numeric($ref_id) || !$ref_id ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}		

		if ( false === ($file_count = $this->count_files_by_ref_id($ref_id)) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_count_files', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $file_count > 0 ) {
			if ( !($file_id = $this->db_oldest_file_id_by_ref_id($ref_id)) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_file_info', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}

			if ( !$this->delete_file_by_db_file_id($file_id) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_delete_file', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		return true;

			
	}

	function insert_new_file( $ref_id = null, $file_field_data = NULL ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();
		$query =& new FuseSQLQuery();

		$orig_parse_val = $db->parse_auto_insert_data;
		
		$db->parse_auto_insert_data = true;

		if ( !($dbh = $db->connect_w()) ) {
			LL::db_connect_error( $my_location . __LINE__ );
			$db->parse_auto_insert_data = $orig_parse_val;
			return false;
		}		

		if ( $ref_id || $this->require_ref_id ) {
			if ( !is_numeric($ref_id) || !$ref_id ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				$db->parse_auto_insert_data = $orig_parse_val;
				return false;
			}

			if ( !$this->db_ref_id_field ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_ref_id_field_set', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				$db->parse_auto_insert_data = $orig_parse_val;
				return false;
			}
			
		}
		
		$query->add_insert_data( $this->db_field_file_datetime, 'NOW()', constant('FUSE_DB_DONT_QUOTE_FIELD') );

		if ( $ref_id ) {
			$query->add_insert_data( $this->db_ref_id_field, $ref_id, constant('FUSE_DB_DONT_QUOTE_FIELD') );
		}

		if ( is_array($file_field_data) && (count($file_field_data) > 0) ) {
			
			foreach ( $file_field_data as $post_key => $field_options ) {

				$insert_field_name = ( $field_options[$this->_Key_db_field_name] ) ? $field_options[$this->_Key_db_field_name] : $post_key;
				$query->add_insert_data( $insert_field_name, $field_options[$this->_Key_field_value], $field_options[$this->_Key_field_quote], $field_options[$this->_Key_field_parse] );
			}

		}
			
		

		if ( !($result = $query->auto_insert($this->db_table_files)) ) {
			LL::sql_query_error( $query->auto_insert_query, $my_location . __LINE__ );
			$db->parse_auto_insert_data = $orig_parse_val;
			return false;
		}
		else {
			if ( $db_file_id = $db->last_insert_id() ) {
				$db->parse_auto_insert_data = $orig_parse_val;
				return $db_file_id;
			}
		}

		$db->parse_auto_insert_data = $orig_parse_val;

		return false;

	}

	function get_random_file_id() {
	
		$rand_max = str_pad( '1', $this->random_id_prefix_len + 1, '0', STR_PAD_RIGHT );
		$rand_max = intval($rand_max);
		$rand_max = $rand_max - 1;

		return uniqid( str_pad(rand(1, $rand_max), $this->random_id_prefix_len, '0', STR_PAD_LEFT) , false) . time();

	}

	function _upload_file( $file_id, $ref_id = NULL, $file_input_key = NULL ) {

		require_library('files');		
	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( $ref_id || $this->require_ref_id ) {
			if ( !is_numeric($ref_id) || !$ref_id ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}
				
		if ( !$this->is_valid_file_id($file_id) ) {
			LL::raise_fuse_error( 'FuseUploadOrganizer-invalid_file_id', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !$this->upload_rootdir ) {
			LL::raise_fuse_error( 'FuseUploadOrganizer-no_rootdir_set', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
		
		$file_input_key = ( $file_input_key ) ? $file_input_key : $this->file_input_key;
		
		if ( !$_FILES[$file_input_key]['name'] ) {
			LL::raise_fuse_error( 'FuseUploadOrganizer-no_posted_file', "\$file_input_key: {$file_input_key}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( $_FILES[$file_input_key]['size'] > ($this->max_upload_size * 1000) ) {
			LL::raise_user_error( "FuseUploadOrganizer-file_too_large %{$this->max_upload_size}%" );
			return false;
		}

		//
		// Check for valid mime type, if any were specified
		//
		$mime_type_compare  = ( $this->mime_types_case_sensitive ) ? $_FILES[$file_input_key]['type'] : strtolower($_FILES[$file_input_key]['type']);
		$allowed_mime_types = $this->get_allowed_mime_types();

		if ( !$mime_type_compare || !is_array($allowed_mime_types) ) {
			$valid_extension_list = $this->valid_extension_list();
			LL::raise_user_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . "-invalid_upload_type %{$valid_extension_list}%" );
			return false;
		}
		else {
			if ( count($allowed_mime_types) > 0 ) {
				if ( !in_array($mime_type_compare, $allowed_mime_types) ) {
					$valid_extension_list = $this->valid_extension_list();
					LL::raise_user_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . "-invalid_upload_type %{$valid_extension_list}%" );
					return false;
				}
			}
		}

		//
		// Check for valid file extenstion, if any were specified
		//
		$posted_filename_info = pathinfo($_FILES[$file_input_key]['name']);
		$posted_file_ext      = $posted_filename_info['extension']; 

		$file_ext_compare  = $posted_file_ext;
		$allowed_file_exts = $this->get_allowed_file_extensions();

		if ( !$this->file_extensions_case_sensitive ) {
			$file_ext_compare  = strtolower($file_ext_compare);
			$allowed_file_exts = array_map('strtolower', $allowed_file_exts );
		}		

		if ( !$file_ext_compare && !$this->allow_missing_file_extension ) {
			$valid_extension_list = $this->valid_extension_list();
			LL::raise_user_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . "-invalid_upload_ext %{$valid_extension_list}%" );
			return false;
		}

		if ( !is_array($allowed_file_exts) || ((count($allowed_file_exts) > 0) && !in_array($file_ext_compare, $allowed_file_exts)) ) {
			$valid_extension_list = $this->valid_extension_list();
			LL::raise_user_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . "-invalid_upload_ext %{$valid_extension_list}%" );
			return false;
		}

		//
		// Always use lowercase to compare DISallowed file extensions
		//
		$file_ext_compare  = strtolower($posted_file_ext);
		$disallowed_file_exts = $this->get_disallowed_file_extensions();
		$disallowed_file_exts = array_map( 'strtolower', $disallowed_file_exts );

		if ( !is_array($disallowed_file_exts) || ((count($disallowed_file_exts) > 0) && in_array($file_ext_compare, $disallowed_file_exts)) ) {
			$valid_extension_list = $this->valid_extension_list();
			LL::raise_user_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . "-invalid_upload_ext %{$valid_extension_list}%" );
			return false;
		}

		if ( !$this->file_extensions_case_sensitive && $this->file_extensions_to_lowercase ) {
			$corrected_file_ext = strtolower($posted_file_ext);
		}
		else {
			$corrected_file_ext = $posted_file_ext;
		}

		if ( $this->enable_file_db || $this->enable_random_file_id ) {

			if ( $ref_id && $this->prepend_ref_id ) {
				$applied_id	  = $ref_id;
				$dst_filename	  = "{$applied_id}-{$file_id}.{$corrected_file_ext}";
			}
			else {
				$applied_id	  = $file_id;
				$dst_filename	  = "{$file_id}.{$corrected_file_ext}";
			}
		}
		else {
			$dst_filename = "{$file_id}.{$corrected_file_ext}";

		}

		if ( $this->_Verbose ) {
			echo "<br />dst filename is: {$dst_filename}<br />";
		}

		if ( !($upload_dir = $this->file_dir_by_applied_id($applied_id)) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_get_upload_dir', "\$applied_id: {$applied_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		//----------------------------------------------------------------------
		// Make sure the directory for this file range (i.e. 500-1000) exists.
		// If not, try to create it.
		//----------------------------------------------------------------------
		if ( !is_dir($upload_dir) ) {
			if ( !$this->create_file_dir_by_applied_id($applied_id) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_create_dir', "\$applied_id: {$applied_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		if ( $uploaded_file = upload_file($upload_dir, $dst_filename, $file_input_key, $this->file_overwrite, $this->max_upload_size, $allowed_file_exts, $allowed_mime_types) ) {

			//
			// file was uploaded successfully.
			//
                        if ( $file_chmod = $this->get_uploaded_file_chmod() ) {
                                if ( !@chmod($uploaded_file, $file_chmod) ) {
                                        LL::raise_fuse_error('FuseUploadOrganizer-couldnt_chmod_file', "\$uploaded_file:{$uploaded_file}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
                                        return false;
                                }
                        }


			return $uploaded_file;

		}

		return false;		
	}


	function update_db_file_name( $file_id, $filename ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();
		$query =& new FuseSQLQuery();

		if ( !$this->is_valid_file_id($file_id) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-invalid_file_id', "\$file_id: {$file_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		$filename = $db->parse_if_unsafe($filename);

		$query->add_update_data( $this->db_field_file_name, $filename );
		
		if ( !($query->auto_update($this->db_table_files, "WHERE {$this->db_field_file_id}={$file_id}")) ) {
			LL::sql_query_error( $query->auto_update_query, $my_location . __LINE__ );
			return false;
		}

		return true;
			
	}

	//
	// _process_new_file should never be called from outside of this class.
	//
	//
	
	function _process_new_file( $file_index, $ref_id = null, $posted_field_data ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$file_field_data = array();
		$posted_file_name = NULL;
		$file_name 	  = null;

		if ( !$file_index || !is_numeric($file_index) ) {
			if ( $this->max_batch_uploads > 1 ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$file_index: {$file_index}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
			else {
				$file_index = NULL;
			}
		}

		if ( $ref_id || $this->require_ref_id ) {
			if ( !is_numeric($ref_id) || !$ref_id ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		$post_key_suffix    = ( $this->max_batch_upload > 1 ) ? $file_index : NULL;
		$cur_file_input_key = ( $this->max_batch_upload > 1 ) ? $this->file_input_key . $file_index : $this->file_input_key;
		$posted_file_name = isset($_FILES[$cur_file_input_key]['name']) ? basename($_FILES[$cur_file_input_key]['name']) : NULL;


		if ( $this->enable_file_db ) {

			if ( is_array($this->_Additional_data_fields) && (count($this->_Additional_data_fields) > 0) ) {

				foreach( $this->_Additional_data_fields as $post_key => $cur_field_options ) {

					$cur_field_options[$this->_Key_field_value] = $posted_field_data["{$post_key}{$post_key_suffix}"];
					$file_field_data[$post_key] = $cur_field_options;

				}

			}

			if ( $this->store_original_file_name ) {
				if ( !$this->db_field_original_file_name ) {
					LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-original_file_name_field_not_set', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
					return false;
				}	

				if ( !$posted_file_name ) {
					LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_posted_file_name', "\$file_index: {$file_index} \$cur_file_input_key {$cur_file_input_key}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
					return false;
				}
				else {
					$file_field_data[$this->db_field_original_file_name][$this->_Key_field_value]   = $posted_file_name;
					$file_field_data[$this->db_field_original_file_name][$this->_Key_db_field_name] = $this->db_field_original_file_name;
				}
			}


			if ( !($db_file_id = $this->insert_new_file($ref_id, $file_field_data)) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_insert_file', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}

			$upload_file_id = $db_file_id;
			$upload_ref_id   = $ref_id;


		}
		else if ( $this->enable_random_file_id ) {

			$upload_file_id = $this->get_random_file_id();
			$upload_ref_id   = $ref_id;
			
		}
		else { 
			//
			// Using original {$ref_id}-{$index} style uploads, 
			// So ref id becomes the file ID, and there is no ref id.
			//

			$upload_file_id = $ref_id;
			$upload_ref_id   = null;				
		}

		if ( !($uploaded_filepath = $this->_upload_file($upload_file_id, $upload_ref_id, $cur_file_input_key)) ) {
			raise_user_error ( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-upload_error' );
			return false;
		}


		if ( $this->enable_file_db ) {

			if ( !$this->update_db_file_name($db_file_id, basename($uploaded_filepath)) ) {
				LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_update_file_name', "\$uploaded_filepath: {$uploaded_filepath}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		return $uploaded_filepath;

	}


	function process_uploads( $ref_id = null, $input_form = null ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();
		$txn_started = false;

		if ( !$this->file_input_key || !is_valid_key($this->file_input_key) ) {
			LL::raise_fuse_error( 'general-invalid_key', "\$this->file_input_key: {$this->file_input_key}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !$ref_id ) {
			$ref_id = $this->active_ref_id;
		}

		if ( $ref_id || $this->require_ref_id ) {
			if ( !is_numeric($ref_id) || !$ref_id ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
	
			$this->set_active_ref_id( $ref_id );
		}

		if ( $this->enable_file_db ) {

			if ( $this->enable_db_transactions ) {
				if ( !$db->start_transaction() ) {
					return false;
				}
				$txn_started = true;
			}
		}

		
		for ( $j = 1; $j <= $this->max_batch_upload; $j++ ) {

			$cur_file_input_key    = ( $this->max_batch_upload > 1 ) ? "{$this->file_input_key}{$j}" : $this->file_input_key;

			if ( !isset($_FILES[$cur_file_input_key]['name']) || !$_FILES[$cur_file_input_key]['name'] ) {
				if ( $this->max_batch_upload <= 1 ) {
					if ( $this->require_file_upload ) {
						LL::raise_fuse_error(  constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_posted_file', "\$cur_file_input_key: {$cur_file_input_key}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );

						if ( $txn_started ) {
							$db->rollback();
						}
						return false;
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

				if ( false === ($file_count = $this->count_files_by_ref_id($ref_id)) ) {
					LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_count_files', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
					return false;
				}
	
				if ( $this->max_files_per_ref_id && ($file_count >= $this->max_files_per_ref_id) ) {

					if ( $this->auto_overwrite_when_full ) {

						if ( !$this->delete_oldest_file_by_ref_id($ref_id) ) {
							LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-couldnt_delete_oldest_file', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
							return false;
						}
					}
					else {
						LL::raise_user_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-too_many_files' );
						return false;
					}
				}

			}

			$posted_field_data = ( $input_form ) ?  $input_form->get_dataset() : $_POST;

			if ( !($uploaded_filepath = $this->_process_new_file($j, $ref_id, $posted_field_data)) ) {
				if ( $txn_started ) {
					$db->rollback();
				}
				return false;
			}

			if ( !$this->_First_uploaded_filename ) {
				$this->_First_uploaded_filename = basename($uploaded_filepath);
			}

			$this->_Uploaded_filenames[] = basename($uploaded_filepath);

			if ( $txn_started ) {
				$db->commit();
			}

		}


		return true;


	}


	function process_upload () {

		return $this->process_uploads();

	}

	function process_upload_form( &$form, $ref_id = null ) {

		return $this->process_uploads( $ref_id, $form );

	}


	function file_basename_by_applied_id( $applied_id, $ref_id = null ) {

		return $this->file_basename_by_file_id( $applied_id, $ref_id );

	}

	function file_basename_by_file_id( $file_id, $ref_id = null ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( $ref_id || $this->require_ref_id ) {
			if ( !is_numeric($ref_id) || !$ref_id ) {
				LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}
		}

		if ( $this->enable_file_db ) {

			if ( $ref_id && $this->prepend_ref_id ) {
				$filename	  = "{$ref_id}-{$file_id}";
			}
			else {
				$filename	  = $file_id;
			}
		}
		else if ( $this->enable_random_file_id ) {

			$filename = $file_id;
		}
		else {

			//--------------------------------------------------------------------------------------------
			// If we're using the old style upload that didn't reference a DB, 
			// The "file_id" will actually be what's now called the ref_id - a user's uid, band ID, etc, 
			// and the filename will become {file_id}-{next_file_index}.jpg (or whatever extension)
			//---------------------------------------------------------------------------------------------

			$filename = $file_id;

		}

		return $filename;


	}


	function get_dir_range( $which_number, $increment = null) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
	        
		if ( !$which_number || !is_numeric($which_number) ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$which_number: {$which_number}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		$increment = ( !$increment ) ? $this->dir_range_increment : 0;


		if ( $increment ) {

	        	$floored = floor( $which_number/$increment );
        	        $min_range = ($floored * $increment ) ;
        		$dir_name = "{$min_range}-" . (($min_range + $increment) -1 );
		        return $dir_name;

		}
		else {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_dir_range_increment', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
        
        	return false;
	}       


	function upload_linkdir_by_applied_id( $applied_id ) {

		return $this->get_album_link($applied_id);
	}

	function upload_link_by_applied_id( $applied_id ) {

		return $this->file_dir_by_applied_id( $applied_id, 1 );
	}


	function first_uploaded_file_name() {

		return $this->_First_uploaded_filename;

	}

	function first_uploaded_path_info() {

		return $this->file_path_info_by_file_name($this->first_uploaded_file_name());

	}

	function get_uploaded_files( $return_type = null ) {

		if ( !$return_type ) {
			$return_type = constant('UPLOAD_ORGANIZER_RETURN_FILENAMES_ONLY');
		}

		if ( $return_type == constant('UPLOAD_ORGANIZER_RETURN_RELATIVE_LINK_PATH') ) {
			return array_map( array($this, 'relative_range_path_by_file_name'), $this->_Uploaded_filenames );
		}
		else if ( $return_type == constant('UPLOAD_ORGANIZER_RETURN_ABSOLUTE_LINK_PATH') ) {
			return array_map( array($this, 'absolute_file_link_by_file_name'), $this->_Uploaded_filenames );
		}
		else if ( $return_type == constant('UPLOAD_ORGANIZER_RETURN_INFO_ARRAY') ) {

			$uploaded_arr = array();

			foreach( $this->_Uploaded_filenames as $uploaded_filename ) {

				if ( !($info_arr = $this->file_path_info_by_file_name($uploaded_filename)) ) {
					LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_file_path_info', "\$uploaded_filename: {$uploaded_filename}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
					return false;
				}
	
				foreach( $info_arr as $key => $val ) {
					$uploaded_arr[$uploaded_filename][$key] = $val;
				}

			}

			return $uploaded_arr;

		}

		return $this->_Uploaded_filenames;

	}

	function file_path_info_by_file_name( $filename ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$filename ) {
			LL::raise_fuse_error( 'general-missing_parameter %filename%', null, $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
	
		$info_arr = array();
			
		$filename = basename($filename);

		$info_arr['filename']      = $filename;
		$info_arr['absolute_link'] = $this->absolute_file_link_by_file_name($filename);
		$info_arr['relative_link'] = $this->relative_link_path_by_file_name($filename);
		$info_arr['dir_range']	   = ( $this->enable_dir_ranges ) ? $this->file_dir_range_by_file_name($filename) : null;
		$info_arr['absolute_path'] = $this->absolute_file_path_by_file_name($filename);
		$info_arr['filesize']	   = @filesize($info_arr['absolute_path']);
		$info_arr['filesize_kb']   = round(($info_arr['filesize'] / 1024), 2);

		return $info_arr;	

	}

	function get_uploaded_filenames() {

		return $this->_Uploaded_filenames;

	}


	function absolute_file_link_by_file_name( $filename ) {

		require_library('files');

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$this->upload_linkdir ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_upload_linkdir', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		$filename = basename($filename);

		if ( $this->enable_dir_ranges ) {
			return add_trailing_path_slash($this->upload_linkdir) . $this->relative_range_path_by_file_name($filename, false);
		}
		else {
			return add_trailing_path_slash($this->upload_linkdir) . $filename;
		}

		return false;

	}

	function relative_link_path_by_file_name( $filename ) {

		if ( $this->enable_dir_ranges ) {
			return $this->relative_range_path_by_file_name( $filename, true );
		}
		else {
			return '/' . $filename;
		}
		
		return false;
	}
	

	function absolute_range_path_by_file_name( $filename ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		require_library('files');

		if ( !$this->upload_rootdir ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_rootdir_set', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
	
		if ( !$filename ) {
			LL::raise_fuse_error( 'general-missing_parameter %filename%', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !($relative_range_path = $this->relative_range_path_by_file_name($filename, false)) ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_relative_range_path', "\$filename: {$filename}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
		
		return add_trailing_path_slash($this->upload_rootdir) . $relative_range_path;

	}




	function &apply_range_dir_to_query_obj( $query_obj = null, $select_as = 'file_range_dir' ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';

		if ( !$this->upload_linkdir ) {
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_upload_linkdir', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}

		if ( !is_object($query_obj) ) {
			$query_obj =& new FuseSQLQuery();
		}		

		$id_field = ( $this->prepend_ref_id ) ? $this->db_ref_id_field : $this->db_field_file_id;
		$id_field = "{$this->db_table_files}.{$id_field}";

		if ( $this->enable_dir_ranges ) {
			if ( !$this->dir_range_increment ) {
				raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_dir_range_increment', '', $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
				return false;
			}

			$query_obj->select( "
				CONCAT( 
						CAST(
							(
							FLOOR({$id_field}/{$this->dir_range_increment}) * {$this->dir_range_increment}
							) AS CHAR
						), 
				
						'-', 
						CAST( 
							(
							((FLOOR({$id_field}/{$this->dir_range_increment}) * {$this->dir_range_increment}) 
								+ {$this->dir_range_increment}) -1 
							) AS CHAR
						)
				)
				AS {$select_as}" );
 		}
		else {
			$query_obj->select( "'{$this->upload_linkdir}' AS {$select_as}" );

		}

		return $query_obj;


	}


	function set_active_ref_id( $ref_id ) {

		$this->active_ref_id = $ref_id;
		
	}


	function delete_files_by_ref_id( $ref_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();		
		$txn_started = false;

		if ( !is_numeric($ref_id) || !$ref_id ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}		

		if ( !$db->in_transaction() ) {
			if ( !$db->start_transaction() ) {
				return false;
			}
			$txn_started = true;
		}

		if ( !($file_res = $this->fetch_db_files_by_ref_id($ref_id)) ) {
			if ( $txn_started ) {
				$db->rollback();
			}
			LL::raise_fuse_error( constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '-no_file_info', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}
		else {
			if ( $db->num_rows($file_res) > 0 ) {
				while ( $row = $db->fetch_unparsed_arr($file_res) ) {
					if ( !$this->delete_file_by_db_file_id($row[$this->db_field_file_id]) ) {
						if ( $txn_started ) {
							$db->rollback();
						}

						return false;
					}
				}
			}

			if ( $txn_started ) {
				$db->commit();
			}

			return true;

		}

		return false;
	}

	function &fetch_db_files_by_ref_id( $ref_id ) {

	 	$my_location = @getenv('SCRIPT_NAME') . ' - ' . constant('FUSE_UPLOAD_ORGANIZER_CLASS_NAME') . '::' . __FUNCTION__ . ':';
		$db =& global_db_object();
		$query_obj =& new FuseSQLQuery();

		if ( !is_numeric($ref_id) || !$ref_id ) {
			LL::raise_fuse_error( 'general-non_numeric_value', "\$ref_id: {$ref_id}", $my_location . __LINE__, constant('ERROR_LEVEL_INTERNAL') );
			return false;
		}		

		$query_obj->select( "{$this->db_table_files}.*" );		
		$query_obj->from( $this->db_table_files );		
		$query_obj->where( "{$this->db_table_files}.{$this->db_ref_id_field} = $ref_id" );

		if ( !$query_obj = $this->apply_range_dir_to_query_obj($query_obj) ) {
			return false;
		}

		$sql_query = $query_obj->generate_sql_query();

		if ( !($result = $db->query($sql_query)) ) {
			sql_query_error( $sql_query, $my_location . __LINE__ );
			return false;
		}

		return $result;

	}

	function add_input_field( $post_key, $db_field = NULL, $quote = NULL, $parse = NULL ) {

		if ( !$db_field ) {
			$db_field = $post_key;
		}		

		$this->_Additional_data_fields[$post_key][$this->_Key_post_input_name] = $post_key;
		$this->_Additional_data_fields[$post_key][$this->_Key_db_field_name]   = $db_field;
		$this->_Additional_data_fields[$post_key][$this->_Key_field_quote]     = $quote;
		$this->_Additional_data_fields[$post_key][$this->_Key_field_parse]     = $parse;

	}

	function set_file_table( $table ) {
		$this->db_table_files = $table;
	}

	function set_file_id_field ( $field ) {
		$this->db_field_file_id = $field;
	}

	function set_ref_table ( $table ) {
		$this->db_ref_table = $table;
	}

	function set_ref_id_field ( $field ) {
		$this->db_ref_id_field = $field;
	}


	function set_file_datetime_field( $field ) {
		$this->db_field_file_datetime = $field;
	}

	function set_file_name_field( $field ) {
		$this->db_field_file_name = $field;
	}

	function set_original_file_name_field( $field ) {
		$this->db_field_original_file_name = $field;
	}

	function set_upload_linkdir( $dir ) {
		$this->upload_linkdir = $dir;
	}
	
	function set_upload_link_dir( $dir ) {
		return $this->set_upload_linkdir( $dir );
	}

	function set_upload_rootdir( $dir ) {
		$this->upload_rootdir = $dir;
	}
	
	function set_upload_root_dir( $dir ) {
		return $this->set_upload_rootdir( $dir );
	}

        function set_uploaded_file_chmod( $mode ) {

                $this->_Uploaded_file_chmod = $mode;
        }

        function get_uploaded_file_chmod() {

                return $this->_Uploaded_file_chmod;

        }

	function set_allow_missing_file_extension( $val ) {

		if ( $val ) {
			$this->allow_missing_file_extension = true;
		}
		else {
			$this->allow_missing_file_extension = false;
		}
	}

	function set_max_upload_size_kb( $size ) {

		$this->max_upload_size = $size;

	}

} //end class

}
