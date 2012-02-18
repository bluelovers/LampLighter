<?php

define('TEMPLATE_PARAM_TYPE_SCALAR', 1 );
define('TEMPLATE_PARAM_TYPE_ARRAY', 2 );
define('TEMPLATE_PARAM_TYPE_ARRAY_REF', 3 );
define('TEMPLATE_PARAM_TYPE_LOOP', 4 );
define('TEMPLATE_PARAM_TYPE_DB_RESULT', 4 );


if ( defined('ERROR_LEVEL_WARN') ) {
        define('ERROR_LEVEL_TEMPLATE', constant('ERROR_LEVEL_WARN'));
}
else {
        define('ERROR_LEVEL_TEMPLATE', 0);
}

class MarkupTemplate {

	const VAR_REFERENCE_SUB_ITERATOR = '$_Sub_iterator'; 

	const SUFFIX_COUNT = '_count';
	const KEY_PARENT_LOOP 	   = 'parent_loop';
	const KEY_PARENT_LOOP_TYPE = 'parent_loop_type';
	const KEY_PARENT_LOOP_INFO = 'parent_loop_info';
	
	const KEY_LOOP_REFERENCE = 'reference';

	const LOOP_TAG_TYPE_ITERATOR = 'ITERATOR';
	const LOOP_TAG_TYPE_DB_LOOP  = 'DB_LOOP';

	const REGEXP_LOOP_TAG_TYPE = '((DB_)?LOOP|ITERATOR)';

	static $Template_file_extension_default = 'tmpl';
	static $Key_loop_index = 'index';
	static $Key_active_row = 'active_row';
	
	public $head_elements_auto = true;
	public $catch_exceptions   = true;
	public $template_file_was_outdated = null;
	public $data;

    var $file;
	var $absolute_filepath;
    var $_Output;
    var $params = array();

	//var $template_extension = 'tmpl';
	var $report_errors = 1;
	var $verbose = 0;
	var $quiet = 0;
	var $error;
	var $var_allowed_regexp =  'A-Za-z0-9_'; //characters to allow in variable/loop names
	var $class_name_allowed_char_class = 'A-Za-z0-9_';

	var $loop_bracket_open  = '{';
	var $loop_bracket_close = '}';
	var $loop_bracket_open_regexp;
	var $loop_bracket_close_regexp;

	var $global_param_array = 'html_template_globals';

	var $require_cache_constant = 1;
	var $template_cache_path;
	var $force_regenerate = 0;
	var $auto_regenerate_includes;
	var $reserved_words = array( 'and', 'or', 'if', 'else', 'null', 'true', 'false' ); 
	var $reserved_char_class = ',\*\+\-\(\)\=\!\<\>\.\/%&;\{\}|'; //Dont put $ or ' in me or parse will break
	var $reserved_char_class_eval = ',\*\+\-\(\)\=\!\<\>\.\/%&;\{\}|'; //Dont put $ or ' in me or parse will break

	var $tag_start = '<{';
	var $tag_end = '}>';
	//var $tag_regexp_start = '<\{';
	//var $tag_regexp_end = '\}>';
	var $valid_filename_regexp = 'A-Za-z0-9\.\/\-_\s\\\\';
	var $exit_on_error;
	var $iteration_indexes;

	var $static_cache = false;
	var $static_cache_prefix = '';
	var $static_cache_suffix = '';
	var $static_cache_id;

	var $static_cache_header = true;
	var $static_cache_footer = true;

	var $static_file_chmod = 0755;
	var $static_dir_chmod = 0755;

	var $errors;
	var $error_count;
	var $loops;
	var $loop_count;

	var $template_base_path;
	var $custom_options = 0;

	var $exit_hook_function = null;
	var $resource_maps = array();

	var $_Comparator_chars = array( '<', '>', '=', '%', '+', '-' );

	var $_Print_without_parse = 0;
	var $_Prefix_global =  '$_';	
	var $_Prefix_global_regexp;
	var $_Parse_called = false;

	var $_Global_params_added = 0;
	var $_Use_global_params   = 1;
	
	var $_Verbose = 0;

	var $_Class_debug	   = 0;
	var $_Object_references	   = array();

	var $_Key_var_prefix = 'var_prefix';
	var $_Key_iteration_name = 'iteration_name';
	var $_Key_loop_string = 'loop_string';
	var $_Key_loop_name = 'loop_name';
	var $_Key_loop_key = 'loop_key';
	var $_Key_loop_var = 'loop_var';
	var $_Key_loop_val = 'loop_val';
	var $_Key_loop_param = 'loop_param';
	var $_Key_loop_nested_references = 'nested_references';
	var $_Key_outer_iteration_name = 'outer_iteration_name';
	
	var $_Key_param_type = 'FT_param_type';
	var $_Key_param_val  = 'FT_param_val';
	var $_Key_param_name = 'FT_param_name';
	
	var $_Key_param_type_callback_reference = 'callback_ref';
	var $_Key_param_type_iterator = 'iterator';
	var $_Key_param_type_nested_loop_ref = 'nested_loop';
	var $_Key_param_type_db_resultset = 'db_result';
	var $_Key_param_type_array = 'array';
	var $_Key_param_type_direct_var = 'direct_var';
	
	protected $_Active_iterators = array();
	protected $_Active_models = array();
	protected $_Active_loops = array();
	protected $_Controller = null;
	protected $_Iterator_auto_count = true;
	
	protected $_DB_resultsets  = array();
	protected $_Iterator_loops = array();
	
	protected $_Loop_nest_index = 0;
	protected $_Loops = array();
	protected $_Loop_info_cache = array();
	protected $_Temp_files = array();
	protected $_Exhausted_db_loops = array(); 
	
	public function initialize_class() {

		$this->file    = null;
		$this->absolute_filepath = null;
		
		$this->params  = array();
		$this->_Output = null;
		//$this->_Active_iterator = null;
		$this->_Controller      = null;
		$this->_DB_resultsets 	= array();
		$this->_Parse_called 	= false;


		if ( !defined('TEMPLATE_MODULE_LOADED') ) {
			define ('TEMPLATE_MODULE_LOADED', 1);
		}

		$this->tag_start = ( defined('TEMPLATE_TAG_START') ) ? constant('TEMPLATE_TAG_START') : $this->tag_start;
		$this->tag_end = ( defined('TEMPLATE_TAG_END') ) ? constant('TEMPLATE_TAG_END') : $this->tag_end;

		$this->auto_regenerate_includes = ( defined('TEMPLATE_AUTO_REGENERATE_INCLUDES') ) ? constant('TEMPLATE_AUTO_REGENERATE_INCLUDES') : 0;
		$this->force_regenerate = ( defined('TEMPLATE_FORCE_REGENERATE') ) ? constant('TEMPLATE_FORCE_REGENERATE') : $this->force_regenerate;
	
		$this->exit_hook_function = ( defined('TEMPLATE_EXIT_HOOK_FUNCTION') ) ? constant('TEMPLATE_EXIT_HOOK_FUNCTION') : $this->exit_hook_function;

		$this->tag_regexp_start = ( defined('TEMPLATE_TAG_REGEXP_START') ) ? constant('TEMPLATE_TAG_REGEXP_START') : preg_quote($this->tag_start, '/');
		$this->tag_regexp_end = ( defined('TEMPLATE_TAG_REGEXP_END') ) ? constant('TEMPLATE_TAG_REGEXP_END') : preg_quote($this->tag_end, '/');

		$this->template_cache_path = ( defined('TEMPLATE_CACHE_PATH') ) ? constant('TEMPLATE_CACHE_PATH') : NULL;
		

		$this->template_extension = self::get_template_file_extension();
		$this->template_base_path = self::get_base_path(); 


		$this->loop_bracket_open_regexp  = preg_quote($this->loop_bracket_open, '/');
		$this->loop_bracket_close_regexp = preg_quote($this->loop_bracket_close, '/');

		//
		// Note: Using _Prefix_global instead of _Prefix_global_regexp 
		// in preg() functoins is deprecated, but it's still used a few times, 
		// so we still parse _Prefix_global for regexp compatibility
		//
		$this->_Prefix_global	     = $this->parse_regexp_string($this->_Prefix_global);
		$this->_Prefix_global_regexp = $this->parse_regexp_string($this->_Prefix_global);
		
		$this->exit_on_error = ( defined('TEMPLATE_EXIT_ON_ERROR') ) ? constant('TEMPLATE_EXIT_ON_ERROR') : 0;

		$this->static_dir_chmod = ( defined('TEMPLATE_STATIC_DIR_CHMOD') ) ? constant('TEMPLATE_STATIC_DIR_CHMOD') : $this->static_dir_chmod;
		$this->static_file_chmod = ( defined('TEMPLATE_STATIC_FILE_CHMOD') ) ? constant('TEMPLATE_STATIC_FILE_CHMOD') : $this->static_file_chmod;

		$this->loop_count = 0;

		if ( $this->_Use_global_params ) {
			$this->set_global_vars();
		}				

	

		
	}
	
	public function __construct( $template_file = '', $ignore_directory_constant = 0, $ignore_extension_constant = 0 ) {

		$this->initialize_class();

		if ( $template_file ) {

			$this->set_file( $template_file, $ignore_directory_constant, $ignore_extension_constant );
		}	

	}

	public function __destruct() {
		
		$this->clean_temp_files();
		
	}

	public function __get( $key ) {
		
		return $this->get_param_val($key);
		
	}
	
	public function __set( $key, $val ) {
		
		$this->add_param( $key, $val );
		
	}
	
	static function get_base_path() {
		
		return constant('TEMPLATE_BASE_PATH');
	}

	static function get_template_file_extension() {
		
		if ( defined('TEMPLATE_FILE_EXTENSION') ) {
			return constant('TEMPLATE_FILE_EXTENSION');
		} 
		else {
			return self::$Template_file_extension_default;
		}
		
		
	}

	function set_tag_start( $chars ) {

		$this->tag_start = $chars;
		$this->tag_regexp_start = preg_quote($chars, '/');

	}

	function set_tag_end( $chars ) {

		$this->tag_end = $chars;
		$this->tag_regexp_end = preg_quote($chars, '/');

	}
	
	function set_controller( $controller ) {
		
		$this->_Controller = $controller;
	} 

	function get_controller() {
	
		return $this->_Controller;
		
	}

	public function set_global_vars() {

		try { 

			LL::require_class('HTML/TemplateGlobals');
		
			if ( is_array(TemplateGlobals::$Params) ) {
				foreach( TemplateGlobals::$Params as $key => $val ) {
					$this->add_param( $key, $val );
				}
			}

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	//------------------------------------------------------------------
	// read_template_file()
	//
	// Attempts to open and read the contents of $this->file for parsing
	//
	// returns: false on error, file contents otherwise
	//------------------------------------------------------------------
	function read_template_file() {

		$my_location = $_SERVER['PHP_SELF'] . ' - ' . __FUNCTION__;

		if ( !$this->file ) {
			
			$this->template_error ( "Could not Parse file: No file set.", 'parse() called without this->file set', $my_location );
			return false;
		}
	
		if ( !is_file($this->file) ) {
			$this->template_error( "Could not Parse file: $this->file.", 'not a regular file.', $my_location );
			return false;
		}

		if ( filesize($this->file) > 0 ) {	

	    	if ( !$fp = fopen($this->file, "r") ) {
				$this->template_error( "Could not open file: $this->file.", 'error on fopen().', $my_location );
				return false;
			}

	        $file_data = fread( $fp, filesize($this->file) );
			fclose( $fp );
		}
		else {
			$file_data = '';
		}

		return $file_data;

	}

	function get_tmp_filepath() {

		return $this->get_cache_filepath() . uniqid() . '.tmp';


	}

	//--------------------------------------------------------------
	// passthru()
	//
	// This function can be called instead of parse if the template
	// has no variables, if statements, etc, and is just plain HTML.
	//--------------------------------------------------------------
	function passthru() {
		
		return $this->parse( 0, 1 );

	}

	//--------------------------------------------------------------
	// parse()
	//
	// This is the main template function that actually compiles the
	// HTML template into a PHP file and saves it.
	//
	// returns: true on success, false otherwise.
	//---------------------------------------------------------------
	function parse( $do_print = 0, $passthru = 0 ) {

		try { 
			if ( $this->_Class_debug ) {
				echo 'parse called';
			}

			$my_location = __METHOD__;
	
			$regenerate = false;
			$tmp_cache  = false;
			$this->_Parse_called = true;

			if ( !$this->data ) {
		        
		        if ( !is_readable($this->file) ) {
					throw new Exception ( "MarkupTemplate-cant_read_file {$this->file}" ); 
        		}

				$cache_file = $this->get_cache_filepath();

				if ( $this->template_file_outdated() || $this->force_regenerate ) {

					$regenerate = true;
					$tmp_cache = false;
					$this->template_file_was_outdated = true;

					if ( !($this->data = $this->read_template_file()) ) {
						return false;
					}
				}
			}
			else {
				//
				// Data is being passed explicitly, 
				// not read from a file
				// 
				$regenerate = true;
				$tmp_cache = true;
			}
			
			if ( $regenerate ) {
				
				if ( !$passthru ) {
					
					if ( !($this->_Output = $this->compile($this->data)) ) {
						return false;
					} 

					$this->_Output .= '<?php return true; ?>';

					//$this->_Output = $this->compile_includes( $parsed_file );
					if ( $this->catch_exceptions ) {
			
						$this->_Output = '<?php try { ?>' . $this->_Output; 

						$this->_Output .= '<?php ' .  
					  					'		}' . 
					  					'		catch (Exception $e ) {' . 
					  					'              trigger_error( $e->getMessage(), E_USER_ERROR );' .
                      					'		}?>'; 
					}
					
				}
				else {
					$this->_Output = $this->data;
				}

				$cache_options['temp_file_only'] = $tmp_cache;

				
				if ( !($cache_file = $this->write_cache_file($this->_Output, $cache_options)) ) {
					if ( !defined('TEMPLATE_INTERNAL_ERROR') ) {
						define ('TEMPLATE_INTERNAL_ERROR', 1 );
					}
					$this->template_error ( "Couldn't open cache file $cache_file for writing. Check file permissions and try again.", $cache_file, $my_location );
					return false;
				}

			}

			if ( !$do_print ) ob_start();
		
			if ( !include($cache_file) ) {
				if ( !defined('TEMPLATE_INTERNAL_ERROR') ) {
					define ('TEMPLATE_INTERNAL_ERROR', 1 );
				}
				$this->template_error( "Error including $cache_file in template", '', $my_location );
				return false;
			}

			if ( !$do_print ) $this->_Output = ob_get_clean();

			return true;
		}
		catch( Exception $e ) {
			
			trigger_error( "Template Error: " . $e->getMessage(), E_USER_ERROR );
			exit;
		}
	}	


	//-----------------------------------------------------------------
	// extract_loop()
	//
	// extracts relevant data for the loop specified by loop name, so that	
	// it can be parsed separately.
	//
	// Parameters: data to be parsed, name of loop to extract
	// Returns: array containing: 
	//		0 the loop's opening tag
	//		1 the data between tags
	//		2 the loop's closing tag
	//		3 the location of the opening tag in $data, 
	//		  to be passed to substr_replace()
	//-------------------------------------------------------------------
	function extract_loop ( $data, $loop_name ) {

		$my_location = $_SERVER['PHP_SELF'] . ' - extract_loop(): ';
	
		if ( $this->_Class_debug ) echo "<BR/>Extract loop got: $loop_name<BR/>";

		if ( !$this->data_has_loop($data) ) {
			
			return false;
		}
		else {
			
			$loop_name_regexp = preg_quote( $loop_name, '/' );
			
			//--------------------------------------------------------------
			// If the loop name was explicitly specified in the closing tag, 
			// preg_match() can extract the loop.
			//--------------------------------------------------------------
			if ( preg_match( "/({$this->tag_regexp_start}\s*(ITERATOR|(DB_)?LOOP)\s+{$loop_name_regexp}\s*{$this->tag_regexp_end})(.*)({$this->tag_regexp_start}\s*\/(ITERATOR|(DB_)?LOOP)\s+{$loop_name_regexp}\s*{$this->tag_regexp_end})/si", $data, $reg_matches, PREG_OFFSET_CAPTURE) ) {

				if ( $this->_Class_debug ) {
					echo "LOOP NAME SPECIFIED<br />";
					echo "[1][0] - {$reg_matches[1][0]}<br />";
					echo "[2][0] - {$reg_matches[2][0]}<br />";
					echo "[3][0] - {$reg_matches[3][0]}<br />";
					echo "[4][0] - {$reg_matches[4][0]}<br />";
					echo "[5][0] - {$reg_matches[5][0]}<br />";
					echo "[1][1] - {$reg_matches[1][1]}<br />";
					echo '<BR><BR>';
				}

				return array( $reg_matches[1][0], $reg_matches[4][0], $reg_matches[5][0], $reg_matches[1][1] );
			}
			else {

				if ( $this->_Class_debug ) {
					echo "\n\n<BR/>--------------DATA TO EXTRACT LOOP $loop_name--------<BR/>\n\n$data\n\n-----------------------END DATA FOR $loop_name--------\n\n<BR/>";
				}

				$lines = explode( "\n", $data );
				$nest_count = 0;
				$skip_ends  = 0;
	
				$loop_block = '';
				foreach ( $lines as $cur_line ) {

					$line_parsed = 0;

					//---------------------------------------------------------------------
					// check current line to see if the loop we're looking for starts here
					//---------------------------------------------------------------------
					if ( preg_match("/{$this->tag_regexp_start}\s*(ITERATOR|(DB_)?LOOP)\s+$loop_name_regexp\s*{$this->tag_regexp_end}/si", $cur_line, $reg_matches, PREG_OFFSET_CAPTURE) ) {

						$tag_regexp = preg_quote($reg_matches[0][0], '/');
						if ( !preg_match("/{$tag_regexp}/si", $data, $tag_match, PREG_OFFSET_CAPTURE) ) {
							$this->template_error("Error finding location of opening tag for loop $loop_name", '', $my_location . __LINE__ );
							return false;
						}

						$opening_tag 	      = $reg_matches[0][0];
						$opening_tag_location = $tag_match[0][1];
	
						list ( $pre_tag, $loop_block ) = explode( "$opening_tag", $cur_line ); 

						$nest_count++;
						$skip_ends   = 0;
						$loop_block .= "\n";
						$line_parsed = 1;

						if ( $this->_Class_debug ) echo "<BR/>\n---\n<BR/>Extract loop: Found opening tag.<BR/>pre tag: $pre_tag<BR/>opening tag: $opening_tag<BR/>NEST COUNT TOP: $nest_count<BR/>--------<BR/>";

					}


					//-----------------------------------------
					// Found another loop tag within this one.
					//-----------------------------------------
					else if ( preg_match("/{$this->tag_regexp_start}\s*(ITERATOR|(DB_)?LOOP).*{$this->tag_regexp_end}/siU", $cur_line, $reg_matches) ) {
	
						if ( $nest_count ) {
							$nest_count ++; 
						}
						else {
							$skip_ends++;
						}

						$loop_block .= "$cur_line\n";
	
						$line_parsed = 1;
					}

					//-----------------------------------------
					// Found a closing loop tag
					//-----------------------------------------
					if ( preg_match("/({$this->tag_regexp_start}\s*\/(ITERATOR|(DB_)?LOOP)\s*([$this->var_allowed_regexp]+)?\s*{$this->tag_regexp_end})/si", $cur_line, $reg_matches, PREG_OFFSET_CAPTURE) ) {

						//----------------------------------------------------------------------
						// Found a closing loop tag. If it is for a nested loop, nest
						// count will be > 0 and we will continue searching for the applicable
						// closing tag. 
						//--------------------------------------------------------------------*/
			
						if ( $nest_count ) {
							$nest_count --;
						}

						if ( !$skip_ends ) {

							if ( $nest_count == 0 ) {

								$closing_tag 	      = $reg_matches[0][0];

								list ( $pre_tag, $post_tag ) = explode( $closing_tag, $cur_line );
								$loop_block .= $pre_tag;
			
								return array( $opening_tag, $loop_block, $closing_tag, $opening_tag_location );
							}
						}
						else {
							$skip_ends--;
						}

					}

					if ( !$line_parsed ) {
						$loop_block .= "$cur_line\n";
					}
		
				}	

				//------------------------------------------------------------------------------------------------
				// Finished searching through all lines - if nest count still wasn't 0, so a /LOOP tag is missing.
				//------------------------------------------------------------------------------------------------*/
				if ( $nest_count != 0 ) {
					$this->template_error ( "Parse Error: Couldn't find closing loop tag for $loop_name", '', $my_location . __LINE__  );
					return false;
				}

			}
		}

		return false;

	}


	function get_cache_filepath( $file = '' ) {

		if ( !$file ) {
			$filename = $this->file;
		}
		else {
			$filename = $file;
		}

		//
		// This could be a "temp only" file for when
		// data was passed explicitly instead of being read in
		//
		if ( !$filename ) {
			$filename = uniqid();			
		}

		if ( !isset($this->template_cache_path) || !$this->template_cache_path ) {
			trigger_error( 'No cache path set.', E_USER_ERROR );
			return false;
		}

		return $this->template_cache_path . DIRECTORY_SEPARATOR . basename($filename) . '.php';
	}

	//----------------------------------------------------------------------
	// template_file_outdated()
	//
	// returns true if the template file was modified more recently
	// than the compiled version was cached. Allows for automatic recompile
	// when the template file is changed.
	//-----------------------------------------------------------------------
	function template_file_outdated( $file = '' ) {
		
		return $this->cache_file_outdated( $file );
		
	}
	
	function cache_file_outdated( $file = '' ) {

		if ( !$file ) {
			$file = $this->get_absolute_filepath();
		}


		$cache_file = $this->get_cache_filepath( $file );

		$cache_mtime = ( file_exists($cache_file) ) ? filemtime( $cache_file ) : 0;

		if ( !$tmpl_mtime = filemtime($file) ) {
			return true;
		}

		if ( $tmpl_mtime > $cache_mtime ) {
			return true;
		}

		/*
		$script_mtime = @filemtime($_SERVER['SCRIPT_FILENAME']);
		$script_mtime = ( $script_mtime ) ? $script_mtime : 0;

		if ( $script_mtime > $cache_mtime ) {
			return true;
		}

		*/
	
		if ( $this->auto_regenerate_includes ) {
			if ( !$file_data = $this->read_template_file($this->file) ) {
				return true;
			}
			else {
				if ( $include_filenames = $this->get_includes($file_data) ) {
					foreach( $include_filenames as $cur_filename ) {
			
						$cur_filename = $this->generate_include_path($cur_filename);
						if ( $include_mtime = @filemtime($cur_filename) ) {
							if ( ($include_mtime > $cache_mtime) ) {
								return true;
							}
						}
						else {
							return false;
						}
					}
				}
			}
		}			

		return false;

	}

	function get_includes( $data ) {

		$include_files = array();

		//preg_match_all( "/{$this->tag_regexp_start}\s*include \'?([{$this->valid_filename_regexp}]+)\'?\s*{$this->tag_regexp_end}/i", $data, $include_matches );
		$include_matches = $this->get_include_matches($data);
		
		if ( count($include_matches[0]) ) {
			for ( $j = 0; $j < count($include_matches[0]); $j++ ) {
				$include_files[] = $include_matches[1][$j];
			}
		}

		return $include_files;


	}
	
	function get_include_matches( $data ) {

		$include_files = array();

		preg_match_all( "/{$this->tag_regexp_start}\s*include \'?([{$this->valid_filename_regexp}]+)\'?\s*{$this->tag_regexp_end}/i", $data, $include_matches );
		
		return $include_matches;


	}
	

	function write_cache_file( $data, $options = array() ) {

		$my_location = get_current_script_location( __FILE__, __FUNCTION__, __CLASS__ );
		static $temp_cleanup_set = 0;

		if ( $this->require_cache_constant ) {
			//---------------------------------------------------------------------
			// check for constant TEMPLATE_MODULE_LOADED before showing template file
			//---------------------------------------------------------------------

			$data = '<?php if ( !defined(\'TEMPLATE_MODULE_LOADED\') ) exit(\'Invalid template access.\'); ?>' . $data;
		}


		if ( !isset($this->template_cache_path) || !$this->template_cache_path ) {
			trigger_error( 'No cache path set.', E_USER_ERROR );
			return false;
		}

		$tmp_file   = $this->get_tmp_filepath();
		
		if ( !($tmp_fp = fopen($tmp_file, "w+")) ) {
			$this->template_error("Could not open temp file", "\$tmp_file: {$tmp_file}", $my_location . __LINE__ );
			return false;
		}

		if ( !fwrite($tmp_fp, $data) ) {
			fclose($tmp_fp);
			$this->template_error("Could not write to temp file", "\$tmp_file: {$tmp_file}", $my_location . __LINE__ );
			return false;
		}

		fclose($tmp_fp);

		if ( !isset($options['temp_file_only']) || !$options['temp_file_only']) {
			$cache_file = $this->get_cache_filepath();

			//
		    // Release any open file handles
		    //
		    if ( $fh = @fopen($cache_file, 'r') ) {
    			fclose($fh);
		    }
		
			if ( !@rename($tmp_file, $cache_file) ) {
		        // Delete the file if it already existed (this is needed on Windows,
		        // because it cannot overwrite files with rename() 
				@unlink( $cache_file );
				if ( !@rename($tmp_file, $cache_file) ) {
					$this->template_error( "Could not rename temp file", "\$tmp_file: {$tmp_file}, \$cache_path: {$cache_path}", $my_location . __LINE__  );
					return false;
				}
			}

			if ( file_exists($tmp_file) ) {
				@unlink($tmp_file);
			}
			return $cache_file;
		}
		else {
			$this->_Temp_files[] = $tmp_file;
			return $tmp_file;
		}

		
	}
	

	//---------------------------------------------------------------------
	// compile()
	//
	// This function calls all of the parsing functions in a specific order
	// so that they will work right. Called by parse(). 
	//
	// Parameters: unparsed template
	// Returns   : compiled template, ready to be written to file.
	//---------------------------------------------------------------------
    function compile( $input, $options = array() ) {

		try {
			$output = $input;
			$loop_name = null;
	
	
			if ( is_scalar($options) ) {
				//
				// Deprecated call where compile gets loop name as 2nd param
				// 
				$loop_name = $options;
				$options = array();
				$options[$this->_Key_loop_name] = $loop_name;
			}
			else {
				if ( isset($options[$this->_Key_loop_name]) ) {
					$loop_name = $options[$this->_Key_loop_name];
				}
			}
	
			$output = $this->replace_includes( $output, $options );
			$output = $this->replace_comments( $output );	

			$output = $this->parse_control_flow( $output );
			$output = $this->parse_while_loops( $output, array($this->_Key_loop_name => $loop_name) );
			$output = $this->parse_foreach_loops( $output, array($this->_Key_loop_name => $loop_name) );
	
			$output = $this->parse_loops( $output, $options );
			$output = $this->parse_object_references( $output, array($this->_Key_loop_name => $loop_name) );
			$output = $this->parse_vars( $output, $options );	
			$output = $this->parse_params( $output, array($this->_Key_loop_name => $loop_name) );
			$output = $this->parse_ifs( $output, array($this->_Key_loop_name => $loop_name) );
			$output = $this->parse_functions( $output, array($this->_Key_loop_name => $loop_name) ); 

			//$output = $this->compile_includes( $output, $options );
			
	
	       return $output;
		}
		catch( Exception $e ) {
			throw $e;
		}
    }


	//------------------------------------------------------------------------
	// extract_loop_references()
	//
	// Searches through $data, retrieving the loop name from loop tags
	// 
	// Parameters: template data
	// Returns   : an array containing the names of all loops present in $data
	//------------------------------------------------------------------------
	function extract_loop_references( $data ) {

		$names = array();
		
		//preg_match_all("/{$this->tag_regexp_start}\s*(DB_)?LOOP\s*(([{$this->var_allowed_regexp}]+)({$this->loop_bracket_open_regexp}'[{$this->var_allowed_regexp}]+'{$this->loop_bracket_close_regexp})*)\s*{$this->tag_regexp_end}/si", $data, $loop_matches, PREG_OFFSET_CAPTURE);
		preg_match_all("/{$this->tag_regexp_start}\s*((DB_)?LOOP|ITERATOR)(.*){$this->tag_regexp_end}/siU", $data, $loop_matches, PREG_OFFSET_CAPTURE);

		if ( count($loop_matches[0]) ) {
			for ( $j = 0; $j < count($loop_matches[0]); $j++ ) {
				if ( $loop_name = $loop_matches[3][$j][0] ) {
					$names[] = trim($loop_name);
				}
			}
		}

		return $names;
	}

	protected function _Parse_Loops( &$data, $options = array() ) {

	
		$loop_info = array();
		$final_data = '';
		$cur_loop_data = '';
					
		$in_loop = false;

		if ( !isset($options['nest_count']) ) {
			$nest_count = 0;
			$options['nest_count'] = 0;
		}
		else {
			$nest_count = $options['nest_count'];	
		}

		if ( is_scalar($data) ) {

			if ( !$this->data_contains_loop_tag($data) ) {
				return $data;
			}

			$data 	 = explode("\n", $data);	
		}
		
		if ( isset($options['starting_line']) && $options['starting_line'] ) {
			array_unshift( $data, $options['starting_line'] );
		}
		
		while( null !== ($cur_line = array_shift($data)) ) {
			
			if ( !$in_loop ) {
				if ( preg_match("/{$this->tag_regexp_start}\s*((DB_)?LOOP|ITERATOR)\s+(.*){$this->tag_regexp_end}/siU", $cur_line, $loop_matches, PREG_OFFSET_CAPTURE) ) {
					
					$loop_info = array();
					$in_loop = true;
					$cur_loop_data = '';
					
					$loop_info = $this->_Loop_info_by_loop_string($loop_matches[0][0], $options); 

					//$loop_info['full_tag'] = $loop_matches[0][0];
					//$loop_info['tag_type'] = $loop_matches[1][0];
					
										
					//tmp
					//$debug_info = $loop_info;
					//$debug_info['loop_val'] = null;
					//$debug_info['loop_param'] = null;
					//echo '<br><Br>===>THREE<BR><BR> '; print_r( $this->params['three_artists'] );
					//echo '<BR><BR><=====<BR><BR>';
					//print_r( $debug_info ); echo '<br />'; echo '<br />';
					
					
					
				}
				else {
					$final_data .= $cur_line . "\n";
				}
			}
			else {
				if ( $this->data_contains_loop_tag($cur_line) ) {

					//
					// Start another loop
					//
					
					$sub_options['nest_count'] = $nest_count+1;
					$sub_options['starting_line'] = $cur_line;
					$sub_options[self::KEY_PARENT_LOOP] = $loop_info['full_tag'];
					
					$function_name = __FUNCTION__;
					$cur_loop_data .= $this->$function_name( $data, $sub_options );
					
					continue;							
				}
				else if ( preg_match("/({$this->tag_regexp_start}\s*\/(ITERATOR|(DB_)?LOOP)\s*([$this->var_allowed_regexp]+)?\s*{$this->tag_regexp_end})/si", $cur_line, $closing_matches, PREG_OFFSET_CAPTURE) ) {

						//
						// Found a closing loop tag. 
						//
						
						
						$start_tag_replacement = $this->loop_start_tag_replacement( $loop_info['full_tag'], $options ); 
						$end_tag_replacement   = $this->loop_end_tag_replacement( $loop_info['full_tag'], $options );
	
						
						$sub_options = array(
										$this->_Key_loop_name => $loop_info['full_tag'], 
										'nest_count' => $nest_count
										
										);
						
						$cur_loop_data = $this->parse_vars( $cur_loop_data, $sub_options );
						$cur_loop_data = $this->parse_params($cur_loop_data, $sub_options );
						$cur_loop_data = $this->parse_ifs( $cur_loop_data, $sub_options );
						$cur_loop_data = $this->parse_functions($cur_loop_data, $sub_options );
						//$cur_loop_data = $this->compile_includes($cur_loop_data, $sub_options );
						
						
						$complete_loop_data = $start_tag_replacement . $cur_loop_data;
						$complete_loop_data .= $end_tag_replacement;
						
						
						//echo 'START: ' . $loop_info['full_tag'] . '----<br><br>';
						//echo nl2br(htmlspecialchars($complete_loop_data));
						//echo 'END: ' . $closing_matches[0][0] . '----<br><br>';
						
						if ( isset($options['nest_count']) && $options['nest_count'] > 0 ) {
							return $complete_loop_data;							
						}
						else {
							$final_data .= $complete_loop_data;
							$loop_info = array();
							$cur_loop_data = '';
							$in_loop = false;
						}
						
				}
				else {
					$cur_loop_data .= $cur_line ."\n";
				}
				
				
			}
		}
		
		//echo 's----<br><br>';
		//echo nl2br(htmlspecialchars($final_data));
		//echo 'e----<br><br>';
		
		
		return $final_data;


	}

		
	protected function _Loop_function_reference_start_tag_replacement( $loop_string, $options = null ) {
		
		try {

			$replacement = null;
			$loop_info   = $this->_Loop_info_by_loop_string($loop_string, $options);
			$loop_string = $loop_info[$this->_Key_loop_string];
			
			if ( isset($options[self::KEY_PARENT_LOOP]) ) {
				$parent_loop_string = $options[self::KEY_PARENT_LOOP];
				$parent_loop_info   = $this->_Loop_info_by_loop_string($parent_loop_string);  
			}
			else {
				$parent_loop_string = null;
				$parent_loop_info   = null;
			}
			
			/*
			//preg_match( "/([{$this->var_allowed_regexp}]+)\s*\((.*)\)\s*;?/siU", $loop_string, $loop_matches );
			preg_match( "/(([$this->var_allowed_regexp]+)(-\>|\:\:|.))?([{$this->var_allowed_regexp}]+)\s*\((.*)\)\s*;?/si", $loop_string, $loop_matches );				
			
			if ( count($loop_matches) > 0 ) {
					
				print_r( $loop_matches );
					
				$full_match = $loop_matches[0];
			
				 
				$class_ref      = $loop_matches[2];
				$class_operator = $loop_matches[3];
				$func_name    	= $loop_matches[4];
				$func_params  	= $loop_matches[5];
				$param_arr    	= explode(',', $func_params);
				$param_string 	= '';
				
				echo $func_params;
				
				
				foreach( $param_arr as $param ) {
					
					$param = trim($param);
					
					if ( $this->is_potential_param_reference($param) ) {
						$param = $this->parse_param_reference($param, $options);
					}
					else {
						
						$parsed = $this->parse_eval_string($param, $options );
						$param = $parsed['val'];
					}
						
					$param_string .= $param . ',';
				}

				$param_string = substr($param_string, 0, -1); //strip trailing comma

			}
			*/

			$parsed_reference = $this->parse_function_call($loop_string);
			
			if ( !$parsed_reference['val'] ) {
				$this->template_error( "Could not parse function call: " . $loop_string );
			}
			
			
			if ( $loop_info[self::KEY_PARENT_LOOP_TYPE] == $this->_Key_param_type_iterator ) {
				
				$iterator_arr_name = '$this->_Active_iterators';
				$model_arr_name    = '$this->_Active_model';

				$replacement = '<?php ';

				if( is_array($parsed_reference['load_statements']) && $parsed_reference['load_statements'] ) { 
					$replacement .= implode("\n", $parsed_reference['load_statements']) . "\n";
				}
					
				$replacement .= self::VAR_REFERENCE_SUB_ITERATOR . ' = $this->get_active_iteration_object()->' . $parsed_reference['val'] . ";\n";
				$replacement .= 'if ( is_object(' . self::VAR_REFERENCE_SUB_ITERATOR . ') ) {' . "\n";
   	            $replacement .= 'array_unshift($this->_Active_iterators, ' . self::VAR_REFERENCE_SUB_ITERATOR . ');' . "\n";
				$replacement .= "\$this->get_active_iterator()->reset();" . "\n";

			    //$replacement .= "while( (\$this->get_active_iterator()->count > 0) && \$this->get_active_iterator()->next() ) {" ;
				$replacement .= "while( \$this->get_active_iterator()->next() ) {" ;
				$replacement .= ' ?>';
			}
			else {
				trigger_error ("Unsupported type for nested loop var: {$loop_info[self::KEY_PARENT_LOOP_TYPE]}", E_USER_ERROR );
				exit;
			}
			
			return $replacement;
			
		}
		catch ( Exception $e ) {
			throw $e;
		}
		
	}

	public function parse_loops( &$data, $options = array() ) {

		if ( Config::Get('template.legacy_loop_parsing') ) {
			return $this->parse_loops_legacy( $data );
		}
		else {
			return $this->_Parse_loops( $data, $options );
		}
	}	
	

	function parse_loops_legacy( &$data, $recursive_call_from = '' ) {

		$my_location = $_SERVER['PHP_SELF'] . ' - parse_loops()';

		if ( $loop_refs = $this->extract_loop_references($data) ) {

			foreach( $loop_refs as $full_loop_ref ) {

				//echo $recursive_call_from . ':' . $full_loop_ref . '<br /><br />';

				if ( list ($opening_tag, $loop_data, $closing_tag, $opening_tag_location) = $this->extract_loop($data, $full_loop_ref) ) {

					list( $loop_name, $reference_vars ) = $this->split_nested_loop_reference( $full_loop_ref );
					
					//echo "FOUND LOOP IN |{$opening_tag}| for LOOP NAME |{$loop_name}|<br />";
					$this->add_iteration_index($loop_name);

					if ( $this->loop_opener_has_db_prefix($opening_tag) ) {
						if ( $this->_Class_debug ) { 
							echo "FOUND DB LOOP IN |{$opening_tag}| for LOOP NAME |{$loop_name}|<br />";
						}
						$this->mark_as_db_loop($loop_name);
					}
					else if ( $this->loop_opener_is_for_iterator($opening_tag) ) {
						$this->mark_as_iterator($loop_name);
					}

					$tag_options[$this->_Key_loop_nested_references] = $reference_vars;

					if ( $recursive_call_from && !$this->has_global_prefix($loop_name) ) {
						$tag_options[self::KEY_PARENT_LOOP] = $recursive_call_from;
					}

					$loop_length = strlen($opening_tag) + strlen($loop_data) + strlen($closing_tag);
					$start_tag_replacement = $this->loop_start_tag_replacement( $full_loop_ref, $tag_options ); 
					$end_tag_replacement   = $this->loop_end_tag_replacement( $full_loop_ref, $tag_options );
	
					if ( $this->_Class_debug ) echo "\n<BR/>-------<BR/>opening tag: $opening_tag location: $opening_tag_location <BR/>closing tag: $closing_tag<BR/>loop length:$loop_length<BR/>\n------------<BR/>\n";	

					if ( $this->data_has_loop($loop_data) ) {
						$this->_Loop_nest_index++;
						$function_name = __FUNCTION__;
						$loop_data = $this->$function_name($loop_data, $loop_name);
						$this->_Loop_nest_index--;
					}

					$loop_data = $this->parse_vars( $loop_data, array($this->_Key_loop_name => $full_loop_ref) );
					$loop_data = $this->parse_params($loop_data, array($this->_Key_loop_name => $full_loop_ref) );
					$loop_data = $this->parse_ifs( $loop_data, array($this->_Key_loop_name => $full_loop_ref) );

					//added in vers 1.3.27
					//parse_vars used to happen here - 2007-03-14 JK
					$loop_data = $this->parse_functions($loop_data, array($this->_Key_loop_name => $full_loop_ref));

					$loop_data = $this->compile_includes($loop_data, array($this->_Key_loop_name => $full_loop_ref));
					$loop_data = $start_tag_replacement . $loop_data;
					$loop_data = $loop_data . $end_tag_replacement;

					$data = substr_replace( $data, $loop_data, $opening_tag_location, $loop_length );

					if ( $recursive_call_from == $loop_name ) {
						$this->subtract_iteration_index($loop_name);
					}

				}
			}
		}
		
		return $data;

	}

	function &parse_control_flow( &$data, $options = null ) {

		$exit_replacement = null;

		if ( $this->exit_hook_function ) {
			$exit_replacement = '<?php ' . $this->exit_hook_function . '(); ?>' . "\n";
		}
		$exit_replacement .= '<?php return true; exit; ?>' . "\n";
		$data = preg_replace("/{$this->tag_regexp_start}\s*EXIT\s*{$this->tag_regexp_end}/si", $exit_replacement, $data ); 

		return $data;
		
	}
	
	function &parse_while_loops( &$data, $options = null ) {
		
		try {

			$preg_result = preg_match_all("/{$this->tag_regexp_start}\s*WHILE\s*(.*){$this->tag_regexp_end}/siU", $data, $matches ); 
			
			for ( $i = 0; $i < $preg_result; $i++ ) {

				$entire_match = $matches[0][$i];
				$eval_string  = $matches[1][$i];
			
				$parsed = $this->parse_eval_string($eval_string, $options );
				$parsed_eval_string = $parsed['val'];
	
				$replacement = '<?php while( ' . $parsed_eval_string . ' ) { ?>'; 
				
				$data = str_replace( $entire_match, $replacement, $data );
				
	
			}
			
			$end_replacement = '<?php } ?>';
			$data = preg_replace("/{$this->tag_regexp_start}\s*\/WHILE\s*{$this->tag_regexp_end}/i", $end_replacement, $data ); 
				
			return $data;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function &parse_foreach_loops( &$data, $options = null ) {
		
		try {
			
			$preg_result = preg_match_all("/{$this->tag_regexp_start}\s*FOREACH\s*(({$this->_Prefix_global_regexp}|\\$)?[$this->var_allowed_regexp]+)\s*AS\s*\\$([$this->var_allowed_regexp]+)(\s*\=\>\s*\\$([$this->var_allowed_regexp]+))?{$this->tag_regexp_end}/siU", $data, $matches ); 
			
			for ( $i = 0; $i < $preg_result; $i++ ) {

				$entire_match = $matches[0][$i];
				$param_ref    = $matches[1][$i];
				$loop_key     = $matches[3][$i];
				$loop_val     = $matches[5][$i];
				
				$parsed = $this->parse_eval_string($param_ref, $options);
				$parsed_eval_string = $parsed['val'];
	
				$replacement = '<?php if ( !is_scalar(' . $parsed_eval_string . ') && (count(' . $parsed_eval_string . ')>0) ) { foreach( ' . $parsed_eval_string . ' as $' . $loop_key;

				if ( $loop_val ) {
					$replacement .= ' => $' .  $loop_val;
				}

				$replacement .= ' ) { ?>'; 
				
				$data = str_replace( $entire_match, $replacement, $data );
				
	
			}

			$end_replacement = '<?php } } ?>';
			$data = preg_replace("/{$this->tag_regexp_start}\s*\/FOREACH\s*{$this->tag_regexp_end}/i", $end_replacement, $data ); 

				
			return $data;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function is_method_call( $data, $options = null ) {
		
		if ( preg_match( "/([{$this->class_name_allowed_char_class}\/]+)(\.|\->|::)([{$this->var_allowed_regexp}]+)/U", $data ) ) {
			return true;
		} 

		return 0;		
		
	}

	//
	// Used when class mehods are called within a function call
	// e.g. print( CartHelper::Get_total() )
	// Currently only works with static methods
	//
	public function get_function_class_load_statement( $function_call, $options = array() ) {
		
		$load_statement = '';

		if ( $method_info = $this->extract_class_method_call($function_call) ) {

			//$force_new   = ( $method_info['class_operator'] == '->' ) ? 1 : 0;
			if ( $method_info['class_reference'] != 'this' ) {
				$load_statement = '$this->load_class(\'' . $method_info['class_reference'] . "');\n";
			}

		}

		return $load_statement;
		

	}

	function &parse_functions( &$data, $options = null ) {

		//preg_match_all( "/{$this->tag_regexp_start}\s*(([{$this->class_name_allowed_char_class}\/]+)(\.|\->|::))?([{$this->var_allowed_regexp}]+)\s*\((.*)\)\s*;?\s*{$this->tag_regexp_end}/iU", $data, $func_matches );
		preg_match_all( "/{$this->tag_regexp_start}\s*((([{$this->class_name_allowed_char_class}\/]+)(\.|\->|::))?([{$this->var_allowed_regexp}]+)\s*\((.*)\)\s*;?)\s*{$this->tag_regexp_end}/siU", $data, $func_matches );

		if ( count($func_matches[0]) ) {
			for ( $j = 0; $j < count($func_matches[0]); $j++ ) {
		
				$replacement = '';
				$function_call = $func_matches[1][$j];				

				$frepl = $this->parse_function_call($function_call, $options);
				
				if( is_array($frepl['load_statements']) && $frepl['load_statements'] ) { 
					$replacement .= implode("\n", $frepl['load_statements']);
				}
				
				$replacement .= $frepl['val'] . ';';
				
				$data = str_replace($func_matches[0][$j], '<?php ' . $replacement . ' ?>', $data);
				
			}
		}

		return $data;
		

	}

	public function extract_class_method_call( $call ) {
		
		preg_match( "/^([{$this->class_name_allowed_char_class}\/]+)(->|::|\.)([{$this->var_allowed_regexp}]+)/si", $call, $matches );
		$ret = array();
		
		if ( $matches ) {
			$ret['class_reference'] = $matches[1];
			$ret['class_library'] = LL::Class_library_from_location_reference($matches[1]);
			$ret['class_name'] = LL::Class_name_from_location_reference($matches[1]);
			$ret['class_operator'] = $matches[2];
			$ret['method_name'] = $matches[3];
		}

		return $ret;
	}

	public function parse_function_call ( &$data, $options = array() ) {
		
		$ret = array();
		$replacement = '';
		$ret['load_statements'] = array();
		static $obj_var_index = 0;
		
		preg_match_all( "/((([{$this->class_name_allowed_char_class}\/]+)(\.|\->|::))?([{$this->var_allowed_regexp}]+))\s*\((.*)\)\s*;?\s*/si", $data, $func_matches );

		
		if ( count($func_matches[0]) ) {
			for ( $j = count($func_matches[0]); $j > 0; $j-- ) {
				
				$index = $j-1;
				
				$full_match = $func_matches[0][$index];

				$full_callback = $func_matches[1][$index];
				$class_ref  = $func_matches[3][$index];
				$class_name = LL::class_name_from_location_reference($class_ref);
				$class_operator = $func_matches[4][$index];
				$func_name    = $func_matches[5][$index];
				$func_params  = $func_matches[6][$index];
				
				$param_arr    = explode(',', $func_params);
				$param_string = '';
				
				$tokenized = $this->tokenize_function_call( $full_match );
				$replacement = '';
				
				foreach( $tokenized as $cur_param ) {
					
					$replacement_added = false;
					
					if ( $cur_param['parse'] ) {
						
						$parsed = $this->parse_eval_string( $cur_param['string'], $options );
						$replacement .= $parsed['val'];
						$replacement_added = true;
					}
					else {
						
						if ( $cur_param['type'] == 'function' ) {

							if ( $load_statement = $this->get_function_class_load_statement( $cur_param['string'] ) ) {
								$ret['load_statements'][] = $load_statement; 
							}
							
							if ( $method_info = $this->extract_class_method_call($cur_param['string']) ) {
								if ( $method_info['class_operator'] == '->' || $method_info['class_operator'] == '.' ) {
									
									$force_new = ( $method_info['class_operator'] == '->' ) ? 1 : 0;
									
									if ( $method_info['class_reference'] == 'this' ) {
										$obj_var_name = '$this';
										
									}
									else {
										$obj_var_name = '$_class_ref' . $obj_var_index;
										$obj_var_index++;
										$ret['load_statements'][] = $obj_var_name . ' = $this->instantiate_class_by_name(\'' . $method_info['class_name'] . "', {$force_new});\n";
									}
									
									
									$replacement .= $obj_var_name . '->' . $method_info['method_name'];
									$replacement_added = true;			
								}
								else {
									//$replacement .= substr( strrchr($cur_param['string'], '/'), 1 );
									
									$replacement .= $method_info['class_name'] . $method_info['class_operator'] . $method_info['method_name'];
									$replacement_added = true;
								}
							}
						
						}

					}

					if ( !$replacement_added ) {
						$replacement .= $cur_param['string'];
					}					
				}
			
				//$param_arr = $this->extract_function_params_from_string( $func_params, $index );
			}
		}		

		$ret['val'] = $replacement;		
		return $ret;
	}
	
	function tokenize_function_call( $call ) {
		
		$tokens = array();
		$working_string = '';
		$in_literal = false;
		$literal_quote = null;
		
		for ( $j =0 ; $j < strlen($call); $j++ ) {
			
			$prev_char = ( $j > 0 ) ? substr($call, $j - 1, 1) : null;
			$cur_char = substr($call, $j, 1);
			$next_char =  substr($call, $j +1, 1);
			
			if ( $cur_char == '\'' || $cur_char == '"' ) {
				if ( !$in_literal ) {
					$in_literal = true;
					$literal_quote = $cur_char;
					
				}
				else {
					if ( $prev_char != '\\' && ($cur_char == $literal_quote) ) {
						$in_literal = false;
					}
			
				}
	
			}
			
			if ( !$in_literal ) {
				
				if ( $cur_char == '(' ) {
					
					$tokens[] = array( 'string' => $working_string, 'parse' => false, 'type' => 'function' );
					$tokens[] = array( 'string' => $cur_char, 'parse' => false, 'type' => 'char' );
					
					$working_string = '';
				}
				else if ( $cur_char == ',') {
					
					$tokens[] = array( 'string' => $working_string, 'parse' => true, 'type' => 'param' );
					$tokens[] = array( 'string' => $cur_char, 'parse' => false, 'type' => 'char' );
					
					$working_string = '';
				}
				else if ( $cur_char == ')') {
					$tokens[] = array( 'string' => $working_string, 'parse' => true, 'type' => 'param' );
					$tokens[] = array( 'string' => $cur_char, 'parse' => false, 'type' => 'char' );
	
					$working_string = '';
				}			
				else {
					$working_string .= $cur_char;
				}
			}
			else {
				$working_string .= $cur_char;
			}
		}
		
		return $tokens;
	}
	
	function is_potential_param_reference( $val, $options = null ) {
		
		if ( preg_match("/^({$this->_Prefix_global_regexp}|[A-Za-z_])[{$this->var_allowed_regexp}]+$/", $val) ) {
			return true;
		}
		
		return 0;
	} 

	function load_class( $class_ref, $force_new = false ) {

		$class_name = LL::class_name_from_location_reference($class_ref);

		/*
		if ( !$force_new ) {
			
			if ( isset($this->_Object_references[$class_name]) && $this->_Object_references[$class_name] ) {
				return $this->_Object_references[$class_name];
			}
		}
		*/
		
		if ( !class_exists($class_name, false) ) {
			LL::require_class($class_ref);
		}

		if ( is_callable(array($class_name, 'Set_calling_template')) ) {
			call_user_func(array($class_name, 'Set_calling_template'), $this );
		}

		//return $obj;
		return true;
	}

	function instantiate_class_by_name( $class_name, $force_new = false ) {
		
		if ( !$force_new && isset($this->_Object_references[$class_name]) && $this->_Object_references[$class_name] ) {
			return $this->_Object_references[$class_name];
		}
		
		$obj = new $class_name;

		
		if ( is_callable(array($obj, 'set_calling_template')) ) {
			$reflector = new ReflectionMethod($obj, 'set_calling_template');
			if ( !$reflector->isStatic() ) {
				//
				// If this method is static ,it will have already been called
				// when the class was loaded
				//
				call_user_func(array($obj, 'set_calling_template'), $this );
			}
		}
		
		$this->_Object_references[$class_name] = $obj;
		
		return $obj;
		
	}

	function split_nested_loop_reference( $loop_name ) {

		$var_reference = null;

		if ( !(false === strpos($loop_name, $this->loop_bracket_open)) ) {
			list ( $loop_var, $var_reference ) = explode( $this->loop_bracket_open, $loop_name, 2);
		}
		else {
			$loop_var = $loop_name;
		}
		
		if ( $var_reference )  {

			$var_reference = str_replace( $this->loop_bracket_open, '[', $var_reference );
			$var_reference = str_replace( $this->loop_bracket_close, ']', $var_reference );
			$var_reference = '[' . $var_reference;
		}

		return array ( $loop_var, $var_reference );

	}

	function get_iteration_name( $loop_name, $subtract_from_index = 0 ) {
	
		$index = $this->get_iteration_index($loop_name) - $subtract_from_index;
		$name  = $loop_name . '_' . $index;

		return $name;
	
	}	

	function add_iteration_index( $loop_name ) {

		list( $loop_name, $references ) = $this->split_nested_loop_reference($loop_name);

		if ( $loop_name ) {
			if ( !isset($this->iteration_indexes[$loop_name]) || !$this->iteration_indexes[$loop_name] ) {
				$this->iteration_indexes[$loop_name] = 1;
			}
			else {
				$this->iteration_indexes[$loop_name]++;
			}
		}

		return true;
		
	}

	function subtract_iteration_index($loop_name) {

		list( $loop_name, $references ) = $this->split_nested_loop_reference($loop_name);

		if ( $loop_name ) {
			if ( $this->iteration_indexes[$loop_name] ) {
				$this->iteration_indexes[$loop_name]--;
			}
		}
		
		return true;
	}		

	
	function get_iteration_index( $loop_name ) {

		list( $loop_name, $references ) = $this->split_nested_loop_reference($loop_name);
		$index = ( $this->iteration_indexes[$loop_name] ) ? $this->iteration_indexes[$loop_name] : 1;

		return $index;

	}
	
	//
	// Nest index will be used later to make this function support nested loops within loops
	//
	function loop_key_from_reference_var( $reference_var, $nest_index = 0 ) {

		$my_location = @getenv('SCRIPT_NAME') . ' - ' . __CLASS__ . '::' . __FUNCTION__ . ':';
		$loop_key = NULL;

		if ( strpos($reference_var, '[') == 0 ) {
			$loop_key = substr( $reference_var, 2 );
			$loop_key = substr( $loop_key, 0, strpos($loop_key, ']') -1 );
		}

		return $loop_key;
		

	}

	function loop_start_tag_replacement( $loop_string, $options = null ) {
		
		$my_location = $_SERVER['PHP_SELF'] . ' - replace_loop_tag()';
		$map_replace = null;
		$replacement = '';

		$loop_info = $this->_Loop_info_by_loop_string($loop_string, $options);

		$loop_key 		= $loop_info[$this->_Key_loop_key];
		$loop_var 		= $loop_info[$this->_Key_loop_var];
		$iteration_name = $loop_info[$this->_Key_iteration_name];
		$param_val	    =& $loop_info[$this->_Key_param_val];
		$param_type	    = $loop_info[$this->_Key_param_type];
		
		if ( (!$param_type || $param_type == $this->_Key_param_type_nested_loop_ref || $param_type == $this->_Key_param_type_array) && !$this->loop_is_db_result($loop_key) && !is_object($this->params[$loop_key]) && !is_resource($this->params[$loop_key]) ) {	
			//
			// This is a regular array
			//

			$replacement = '<?php if ( isset(' . $loop_var . ') && count(' . $loop_var . ') > 0 ) { foreach( ' . $loop_var . ' as $' . $iteration_name . ' ) { ?>';
		}
		else if ( $param_type == $this->_Key_param_type_direct_var ) {
			$replacement = '<?php if ( is_array(' . $loop_var . ') ) { foreach( ' . $loop_var . ' as ' . $iteration_name . ' ) { ?>';
		}
		else {
		
			if ( $param_type == $this->_Key_param_type_iterator ) {
				
				//
				// This is an iterator
				//
				$iterator_arr_name = '$this->_Active_iterators';
				$model_arr_name    = '$this->_Active_model';
				$iterator_ref	   = $loop_var;
				
				//$replacement .= '<?php if ( isset(' . $iterator_ref . ') && is_object(' . $iterator_ref . ') ) {' . "\n";
				$replacement .= '<?php if ( is_object(' . $iterator_ref . ') ) {' . "\n";
                $replacement .= "array_unshift({$iterator_arr_name}, " . $iterator_ref . ');' . "\n";
				$replacement .= "\$this->get_active_iterator()->reset();" . "\n";

				
                //$replacement .= "while( (\$this->get_active_iterator()->count > 0) && \$this->get_active_iterator()->next() ) {" ;
				$replacement .= "while( \$this->get_active_iterator()->next() ) {" ;
				
				//iterator map callbacks not yet implemented
				//$replacement .= $this->resource_map_replacement_code($loop_string);
				
				$replacement .= '?>';
				
			}
			else if ( $param_type == $this->_Key_param_type_callback_reference ) {
				
				//
				// _Loop_nest_index can be removed once new loop parsing is in place 
				// 2008-10-29 JK
				
				//echo $loop_string . '<br />';
				//print_r( $loop_info );
				//echo '<br /><br />';
				
				if ( (isset($options['nest_count']) && $options['nest_count'] > 0) || $this->_Loop_nest_index > 0 ) {
					$replacement = $this->_Loop_function_reference_start_tag_replacement($loop_string, $options);
				}
				
			}
			else if ( $param_type == $this->_Key_param_type_db_resultset || $this->loop_is_db_result($loop_key) ) {
				
				//
				// This is a database result
				// TODO: once all loop params used the new "info hash as value" method,
				// the only if clause needed above is the $param_type check.
				//
				
				$replacement = '<?php $_TMPL_db = $this->get_controller()->get_db_interface(); ?>';
				$db_var_name = '$_TMPL_db';

                $replacement .= '<?php if ( isset(' . $loop_var . ') && (is_resource(' . $loop_var . ') || is_object(' . $loop_var . ')) ) { ' . "\n";
				
				//
				// num_rows() and data_seek() are expensive in PDO because queries are unbuffered, 
				// so only use them if this is our second time through this loop.
				//
				$replacement .= 'if ( in_array(\'' . $loop_key . '\', $this->_Exhausted_db_loops) ) {';
				$replacement .= " if ( {$db_var_name}->num_rows(" . $loop_var . ') > 0 ) { ' . "\n";
				$replacement .= "{$db_var_name}->data_seek(" . $loop_var . ',0);' . "\n";
				$replacement .= "\t" . '}' . "\n";
				$replacement .= '}' . "\n";
				
				$replacement .= 'while( $' . $iteration_name . "= {$db_var_name}->fetch_unparsed_assoc(" . $loop_var . ') ) {';
				$replacement .= '?>';
				$replacement .= $this->_Resource_map_replacement_code($loop_string);
				

			}
			else {
				trigger_error( 'Unknown loop type for ' . $loop_string, E_USER_ERROR );
			}
		}			

		return $replacement;

	}

	protected function _Get_active_loop_string() {
		
		try {
			return $this->_Active_loops[0][$this->_Key_loop_string];
		}
		catch( Exception $e ) {
			throw $e;
		}				
		
	}

	function get_active_iterator() {
		
		try {
			return $this->_Active_iterators[0];
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function get_active_model() {
		
		try {
			return $this->get_active_iteration_object();	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
		
	public function get_active_iteration_object() {
		
		
		try {
			if ( $itr = $this->get_active_iterator() ) {
				return $itr->get_active_object();
			}
			
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	protected function _Loop_param_type_by_loop_string( $loop_string, $options = null ) {
		
		$loop_info = $this->_Loop_info_by_loop_string($loop_string, $options );
		
		if ( isset($loop_info[$this->_Key_param_type]) ) {
			return $loop_info[$this->_Key_param_type];
		}
		
		return null;
		
	}
	
	public function loop_param_type_by_tag_type( $tag_type ) {
		
		switch( strtoupper($tag_type) ) {
			case self::LOOP_TAG_TYPE_DB_LOOP:
				return $this->_Key_param_type_db_resultset;
				break;
			case self::LOOP_TAG_TYPE_ITERATOR:
				return $this->_Key_param_type_iterator;
				break;
		}
		
		//
		// returning null means param type
		// cannot be determined by loop type tag
		//	
		
		return null;
	}
	
	public function loop_string_parts_by_tag( $loop_string ) {
		
		//$loop_string = trim($loop_string);
		//$loop_string = trim($this->strip_template_tags($loop_string));
		
		$ret = array();
		$ret['full_tag'] = null;
		
		$orig_loop_string = trim($loop_string);
		$loop_string = trim($loop_string);
		
		$loop_string = $this->strip_template_tags($loop_string);
		
		//
		// If strip_template_tags actually did strip tags,
		// then we were passed a full tag
		//
		if ( $orig_loop_string != $loop_string ) {
			$ret['full_tag'] = $orig_loop_string;	
		}

		if ( preg_match('/^' . self::REGEXP_LOOP_TAG_TYPE . '\s+(.*)/', $loop_string, $matches) ) {
			
			$ret['tag_type'] = $matches[1];
			$ret['reference'] = $matches[3];
			
		}
		else {
			$ret['tag_type'] = null;
			$ret['reference'] = $loop_string;	
		}
		
		return $ret;
		
	}
	
	public function loop_string_has_tag_type( $loop_string ) {
		
		$loop_string = trim($loop_string);
		if ( preg_match('/^' . self::REGEXP_LOOP_TAG_TYPE . '/i', $loop_string) ) {
			return true;
		}
		
		return false;
	}
	
	public function iteration_name_by_loop_name( $loop_name, $options ) {
		
		if ( isset($options['nest_count']) ) {
			$nest_count = $options['nest_count'];
		}
		else {
			$nest_count = 0;
		}
		
		return $loop_name . '_' . $nest_count;
		
	}
	
	//private
	function _Loop_info_by_loop_string( $loop_string, $options = null ) {
		
		//
		// if ( !$options ), because we 
		// don't use the loop cache if different options were set
		//
		//if ( !$options && (!isset($options['ignore_cache']) || $options['ignore_cache'] == false) && isset($this->_Loop_info_cache[$loop_string]) ) {
		//	return $this->_Loop_info_cache[$loop_string];
		//}
		
		$loop_info = array();
		$param_info_arr = false;
		$is_direct_var = false;
		
		//
		// This function supports a full opening loop tag, 
		// or just the loop reference (e.g MY_LOOP_NAME or get_some_iterator() ), 
		// so we need to parse the type tag from the actual reference
		//
		$tag_parts = $this->loop_string_parts_by_tag($loop_string);
		$loop_string = trim($tag_parts['reference']);
		$loop_info['full_tag'] = $tag_parts['full_tag'];
		$loop_info['tag_type'] = $tag_parts['tag_type'];
		
		$loop_info[self::KEY_LOOP_REFERENCE] = $tag_parts['reference'];
		$loop_info[$this->_Key_loop_string]  = $loop_string;
		list ( $loop_name, $reference_vars ) = $this->split_nested_loop_reference($loop_string);

		$loop_name = $this->strip_global_prefix($loop_name);
	
		$loop_info[$this->_Key_loop_name]		  = $loop_name;

		//
		// What to call the variable for each iteration of the loop?
		// e.g. foreach( $myarr as $iteration_name_here )
		//
		// most likely, once new parse loop is in place, 
		// we can assume nest count is always present 
		//
		if ( !isset($options['nest_count']) ) {
			$options['nest_count'] = 0 ;
		}

		$loop_info[$this->_Key_iteration_name] = $this->iteration_name_by_loop_name($loop_name, $options);
		
		
		$loop_info[$this->_Key_loop_nested_references] = $reference_vars;
		$loop_info[$this->_Key_loop_key] = $loop_name;
		
		//
		// See if we can auto determine the parameter type
		// 
		if ( !isset($loop_info[$this->_Key_param_type]) ) {
			
			if ( $tag_parts['tag_type'] && ($type = $this->loop_param_type_by_tag_type($tag_parts['tag_type'])) ) {
				$loop_info[$this->_Key_param_type] = $type; 				
			}

		}
		
		
		if ( substr($loop_name, 0, 1) == '$' ) {
			$is_direct_var = true;
			$loop_info[$this->_Key_loop_param] = null;
			
			if ( !isset($loop_info[$this->_Key_param_type]) ) {
				$loop_info[$this->_Key_param_type] = $this->_Key_param_type_direct_var;
			}
			
		}
		else {		
			$loop_info[$this->_Key_loop_param] =& $this->params[$loop_info[$this->_Key_loop_key]];
		}

		//
		// If we have a parent loop, add it to our loop info
		//
		if ( array_val_is_nonzero($options, self::KEY_PARENT_LOOP) ) {
			$loop_info[self::KEY_PARENT_LOOP]      = $options[self::KEY_PARENT_LOOP];			
			
			if ( !isset($options[self::KEY_PARENT_LOOP_TYPE]) ) {
				$loop_info[self::KEY_PARENT_LOOP_TYPE] = $this->_Loop_param_type_by_loop_string($options[self::KEY_PARENT_LOOP]);
			}
			else {
				$loop_info[self::KEY_PARENT_LOOP_TYPE] = $options[self::KEY_PARENT_LOOP_TYPE];
			}
		}
		else {
			$loop_info[self::KEY_PARENT_LOOP] 	   = null;
			$loop_info[self::KEY_PARENT_LOOP_TYPE] = null;
		}

		//
		// Some legacy functions return a scalar value for the parameter
		// but the preferred way is to use an array with multiple types of info
		//		
		if ( is_array($loop_info[$this->_Key_loop_param]) && isset($loop_info[$this->_Key_loop_param][$this->_Key_param_type]) ) {
			$param_info_arr = true;
			$loop_info[$this->_Key_loop_val] = $loop_info[$this->_Key_loop_param][$this->_Key_param_val];
		}
		else { 
			$param_info_arr = false;
			$loop_info[$this->_Key_loop_val] = $loop_info[$this->_Key_loop_param];
		}
							

		if ( isset($loop_info[$this->_Key_loop_param]) && is_array($loop_info[$this->_Key_loop_param]) && isset($loop_info[$this->_Key_loop_param][$this->_Key_param_type]) ) {
				$loop_info[$this->_Key_param_type] = $loop_info[$this->_Key_loop_param][$this->_Key_param_type];
		}

		//
		// Check to see if this loop is referencing an outer loop
		//
		if ( $reference_vars ) {
			
			$loop_info[$this->_Key_param_type] = $this->_Key_param_type_nested_loop_ref;
			
			if ( isset($options['nest_count']) ) {
				 $sub_options = $options;
				 $sub_options['nest_count']--;
				 $loop_info[$this->_Key_outer_iteration_name] = $this->iteration_name_by_loop_name($loop_name, $sub_options);
			}
			else {
				$sub_options = $options;
				$sub_options['nest_count'] = 0;
				$loop_info[$this->_Key_outer_iteration_name]  = $this->iteration_name_by_loop_name($loop_name, $sub_options);
			}
			
			
			$loop_info[$this->_Key_loop_key] = $this->loop_key_from_reference_var($reference_vars);
			$loop_info[$this->_Key_loop_var] = '$' . $loop_info[$this->_Key_outer_iteration_name] . $reference_vars;
		}
		else if ( $this->string_contains_callback_reference($loop_string) ) {
			
			//echo "FOund callback reference: {$loop_string}<br />";
			
			$loop_info[$this->_Key_param_type] = $this->_Key_param_type_callback_reference;
			
			
		}
		else if ( $is_direct_var ) {
			
			//
			// this loop references a direct variable like $myvar
			//
			
			$loop_info[$this->_Key_loop_var] = $loop_name;
						
		}
		else {
			
			if ( !isset($loop_info[$this->_Key_param_type]) ) {
				
				$loop_info[$this->_Key_param_type] = $this->_Key_param_type_array;
			}

			//$loop_info[$this->_Key_loop_var] = '$this->get_loop_val(\'' . $loop_name . '\')';

			if ( !$loop_info[self::KEY_PARENT_LOOP] ) {			
				if ( $param_info_arr ) {				
					$loop_info[$this->_Key_loop_var] = '$this->params[\'' . $loop_name . '\'][\'' . $this->_Key_param_val . '\']';
				}
				else {
					$loop_info[$this->_Key_loop_var] = '$this->params[\'' . $loop_name . '\']';
				}
			}
			else {
				if ( $loop_info[self::KEY_PARENT_LOOP_TYPE] == $this->_Key_param_type_iterator ) {
					
					$loop_info[$this->_Key_loop_var] = '$this->get_active_iteration_object()' . '->' . $loop_name; 
				}
			}
			
		}
		
		if ( isset($loop_info[$this->_Key_param_type]) ) {
			if ( $loop_info[$this->_Key_param_type] == $this->_Key_param_type_iterator || $loop_info[$this->_Key_param_type] == $this->_Key_param_type_callback_reference ) {
				$loop_info[$this->_Key_iteration_name] = '$this->get_active_iterator()';
			}
		}
		
		
		$this->_Loop_info_cache[$loop_string] = $loop_info;
		
		return $loop_info;
		
		
	}

	public function string_contains_callback_reference( $str ) {

		if ( preg_match( "/(([{$this->class_name_allowed_char_class}\/]+)(\.|\->|::))?([{$this->var_allowed_regexp}]+)\s*\((.*)\)\s*;?/siU", $str) ) {
			return true;
		}
		
		return 0;
		
	}


	//private
	function _Resource_map_replacement_code( $loop_string ) {
		
		$replacement = null;
		$loop_info = $this->_Loop_info_by_loop_string($loop_string);

		$loop_key 		= $loop_info[$this->_Key_loop_key];
		$loop_var 		= $loop_info[$this->_Key_loop_var];
		
		if ( $this->has_resource_map($loop_key) ) {
			if ( count($this->resource_maps[$loop_key]) > 0 ) {

				foreach ( $this->resource_maps[$loop_key] as $cur_map ) {

					$param_string   = '';
					$map_callback   = $cur_map['callback'];
					$params         = $cur_map['params'];
					$include_result = $cur_map['include_result'];
					$iteration_name = $loop_info[$this->_Key_iteration_name];
						
					$callback_repl  = "'{$map_callback}'";

					if ( is_array($map_callback) ) {
						// 
						// The callback is an object and function name
						//
						if ( substr($map_callback[0], 0, 1) == '$' ) {
							//
							// Callback was passed an instantiated object, 
							// pass the object variable 
							//
							$callback_repl = "array({$map_callback[0]},'{$map_callback[1]}')";
						}
						else {
							//
							// Callback was passed a class name, 
							// use a string literal for map_callback[0]
							//
							$callback_repl = "array('{$map_callback[0]}','{$map_callback[1]}')";
						}
					}

					if ( $params && is_scalar($params) ) {
						$params = array($params);
					}
					else {
                       	$params = array();
                    }

					if ( $include_result ) {
						array_unshift( $params, $loop_var );
					}
					else {
						array_unshift( $params, '$n = null' );
					}

					if ( is_array($params) && (count($params) > 0) ) {
                    	foreach( $params as $cur_param ) {
                        	$param_string .= ',' . $cur_param;
                        }

                    }

					$replacement .= '<?php $this->apply_template_loop_map(' . $callback_repl . ', $' . $iteration_name . $param_string . '); ?>';
				}
		
			}	
		}
		
		return $replacement;
		
	}

	function loop_end_tag_replacement( $loop_string = null, $reference_loop_name = '' ) {

		$repl = '<?php }';
		
		if ( $loop_string ) {

			$loop_info = $this->_Loop_info_by_loop_string($loop_string);
			$loop_type = $this->_Loop_param_type_by_loop_string($loop_string);
			
			if ( $loop_type == $this->_Key_param_type_iterator || $loop_type == $this->_Key_param_type_callback_reference ) {
				$repl .= ' array_shift($this->_Active_iterators); ';				
			}
			
			if ( $loop_type == $this->_Key_param_type_db_resultset  ) {
				$repl .= ' $this->_Exhausted_db_loops[]=\'' . $loop_info[$this->_Key_loop_key] . '\';' . "\n";				
			}
		}
		
		$repl .= 'if ( isset(' . self::VAR_REFERENCE_SUB_ITERATOR . ') ) unset(' . self::VAR_REFERENCE_SUB_ITERATOR . ');' . "\n";
		
		$repl .= ' }  ?>';

		return $repl;
	}

	function template_error( $message, $internal_message = '', $location = '') {
	
       throw new Exception ( $message ); 
		
	}
	
	function _report_error( $message, $internal_message = '', $location = '', $return_only = 0) {

		$message = "<b>Error in Template class:</b> $message";
		$message.= ( $internal_message ) ? "\n<BR/>" . $internal_message : '';
		$message.= ( $location ) ? " in $location" . '<BR/>' : '';

		if ( !$return_only ) {
			echo $message;
		}

		return $message;

	}

	// deprecated
	function get_error_message() {

		/*
		$message = '';

		if ( count($this->errors) ) {
			foreach ( $this->errors as $cur_error ) {
				$message .= $this->_report_error( $cur_error['message'], $cur_error['internal_message'], $cur_error['location'], $cur_error['error_level'], 1 );
			}
		}
	
		return $message;
		*/
		return true;
	}

	function get_error_messages() {
		return $this->get_error_message();
	}

	function get_errors() {

		return $this->get_error_message();

	}



	function data_has_loop( $data, $loop_name = '') {
	
		return $this->data_contains_loop_tag( $data, $loop_name );
	
	}
	 
	public function data_contains_loop_tag( $data, $loop_name = '') {
		
		if ( $loop_name ) {
			$loop_name_regexp = preg_quote($loop_name, '/');
			if ( preg_match("/{$this->tag_regexp_start}\s*(ITERATOR|(DB_)?LOOP)\s*{$loop_name_regexp}\s*{$this->tag_regexp_end}/Ui", $data) ) {
				return true;
			}
		}
		else {
			if ( preg_match("/{$this->tag_regexp_start}\s*(ITERATOR|(DB_)?LOOP).*{$this->tag_regexp_end}/Ui", $data) ) {
				return true;
			}
		}

		return false;
	}

	function parse_params( &$data, $options = array() ) {

		$reference_vars = null;
		

		//preg_match_all( "/{$this->tag_regexp_start}\s*(({$this->_Prefix_global}|\\\$)?([{$this->var_allowed_regexp}\[\]']+))\s*{$this->tag_regexp_end}/", $data, $param_matches );
		preg_match_all( "/{$this->tag_regexp_start}\s*(({$this->_Prefix_global}|\\\$)?([{$this->var_allowed_regexp}\[\]']+))\s*{$this->tag_regexp_end}/", $data, $param_matches );


		//if ( isset($options[$this->_Key_loop_name]) ) {
		//	$loop_name = $options[$this->_Key_loop_name];
		//}

		//list ( $loop_name, $reference_vars ) = $this->split_nested_loop_reference( $loop_name );

		//$parse_options = $options;
		//$parse_options[$this->_Key_loop_name]  = $loop_name;

		for ( $j=0; $j < count($param_matches[0]); $j++ ) {

			$cur_tag	 = $param_matches[0][$j];
			$full_param_ref	 = $param_matches[1][$j];
			$param_var	 = null;
			$hash_ref_string = '';
			
			if ( !$this->is_reserved_word($full_param_ref) ) {
				$param_var = $this->parse_param_reference($full_param_ref, $options);
				
				//if ( $loop_name ) {
				//	$loop_info = $this->_Loop_info_by_loop_string($loop_name);
				//}
				
				$data = str_replace( $cur_tag, '<?php echo ' . $param_var . ';?>', $data);
			}

		}

		return $data;

	}

	function parse_object_references( &$data, $options = array() ) {

		//preg_match_all( "/{$this->tag_regexp_start}\s*(({$this->_Prefix_global}|\\\$)?([{$this->var_allowed_regexp}\[\]']+))\s*{$this->tag_regexp_end}/", $data, $param_matches );
		preg_match_all( "/{$this->tag_regexp_start}\s*(({$this->_Prefix_global}|\\\$)?([{$this->var_allowed_regexp}]+))(-\>|\:\:)([{$this->var_allowed_regexp}]+)\s*{$this->tag_regexp_end}/", $data, $param_matches );

		//if ( isset($options[$this->_Key_loop_name]) ) {
		//	$loop_name = $options[$this->_Key_loop_name];
		//}

		for ( $j=0; $j < count($param_matches[0]); $j++ ) {

			$cur_tag	 = $param_matches[0][$j];
			$object_name = $param_matches[1][$j];
			$object_property = $param_matches[5][$j];
			$operator    = $param_matches[4][$j];
			$param_var	 = null;
			
			$param_var = $this->parse_param_reference($object_name, $options);
			$data = str_replace( $cur_tag, '<?php print(' . $param_var . $operator . $object_property . ');?>', $data);
			

		}

		return $data;
		
	}

	function parse_param_reference( $ref_string, $options = array() ) {

		$loop_name	 	 = ( isset($options[$this->_Key_loop_name]) ) ? $options[$this->_Key_loop_name] : null;
		$parsed_ref	 	 = $ref_string;
		$hash_ref_string = '';
		$hash_parts 	 = array();
		$in_loop	 	 = false;

		if ( !$this->is_reserved_word($ref_string) && !$this->is_template_var($ref_string) ) {

			if ( preg_match("/\['?[{$this->var_allowed_regexp}]+'?\]/", $ref_string) ) {
                
            	//
                // This is an array reference like $params['my_array']['key']
                //
                 
                $hash_parts = explode( '[', $ref_string, 2 );
                $cur_param_key = $this->strip_global_prefix($hash_parts[0]);
                                                
				$hash_refs = $hash_parts[1];
				$hash_refs = rtrim( $hash_refs, ']' );
				$hash_ref_arr = explode( '][', $hash_refs );

				if ( is_array($hash_ref_arr) && (count($hash_ref_arr) > 0) ) {
					foreach( $hash_ref_arr as $cur_ref ) {
						$cur_ref = trim($cur_ref, '\'');
						$hash_ref_string .= '[\'' . $cur_ref . '\']';
					}
				}

				$var_ref = '$this->params';
				$parsed_ref = $var_ref . '[\'' . $cur_param_key . '\']' . $hash_ref_string;

            }
            /*
            else if ( preg_match('/(-\>|\:\:)/', $ref_string) ) { 
            	echo "{$ref_string} is object reference<br />";
			}
			*/
			else {
				
				if ( !$this->has_global_prefix($ref_string) ) {
					
					if ( $loop_name ) {

						$in_loop = true;
						
						//$parsed_ref = '$this->get_loop_val(\'' . $ref_string . '\')';

						$loop_info = $this->_Loop_info_by_loop_string($loop_name, $options);
						
						//print_r( $loop_info ); echo '<br><br>';
						
						$loop_name = $loop_info[$this->_Key_loop_name];
						$reference_vars = $loop_info[$this->_Key_loop_nested_references];
						$loop_type = $loop_info[$this->_Key_param_type];
						
						//list( $loop_name, $reference_vars ) = $this->split_nested_loop_reference($loop_name);
						
						$loop_name = $this->strip_global_prefix($loop_name);
						$iteration_name = $loop_info[$this->_Key_iteration_name];
						
						//$loop_type = $this->_Loop_param_type_by_loop_string($loop_name);
						
						if ( $loop_type == $this->_Key_param_type_iterator || $loop_type == $this->_Key_param_type_callback_reference ) {
						
							//param is being referenced via an iterator object
							//$parsed_ref = '$' . $iteration_name . '->' . $ref_string;
							
							if ( $ref_string == '_active_iteration_object' ) {
								$parsed_ref = "\$this->get_active_iteration_object()";
							}
							else if ( $ref_string == '_active_iterator' ) {
								$parsed_ref = "\$this->get_active_iterator()";
							}
							else {
								$parsed_ref = "\$this->get_active_iteration_object()->" . $ref_string;
							}
						}
						else if ( $loop_type == $this->_Key_param_type_direct_var ) {
							$parsed_ref = $iteration_name . '[\'' . $ref_string . '\']';
						}
						else {
							//$parsed_ref = '$' . $iteration_name . '[\'' . $ref_string . '\']';
							$parsed_ref = '$this->get_iteration_val($' . $iteration_name . ', \'' . str_replace('\'', '\\\'', $ref_string) . '\')';
						}
						
					}
				}

				if ( $this->has_global_prefix($ref_string) ) {
					$ref_string = $this->strip_global_prefix($ref_string);
				}

				if ( !$in_loop ) {
					$parsed_ref = '$this->get_param_val(\'' . $ref_string . '\')';
				}
			}
		}

		return $parsed_ref;

	}

	public function get_iteration_val( $var, $ref ) {
		
		if ( is_object($var) ) {
			return $var->$ref;
		}
		else {
			return $var[$ref];
		}
	}
	
	function parse_var_action( $action_string, $options = array() ) {

		return $this->parse_eval_string( $action_string, $options );

	}

	function parse_eval_string( $eval_string, $options = array(), $disable_isset_check = false ) {

		$in_literal = 0;
		$in_var			    = 0;
		$rebuilt_statement = '';
		$condition_index = 0;
		$function_string	= '';
		$prev_match = null;
		$load_statements = array();
		$function_split = array();

		$disable_isset_check = true; //always, since introduction of get_param_val()

		//if ( $loop_name && $this->param_is_marked_as_iterator($loop_name) ) {
		//	$disable_isset_check = true;
		//}

		if ( $this->_Class_debug ) {
			echo "Parsing eval string: {$eval_string}<br />\n";
		}

		if ( $condition_array = preg_split("/([\s{$this->reserved_char_class}])/", $eval_string, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) ) {	

			$in_function		= 0;
			$function_nest		= 0;
		
			foreach( $condition_array AS $cur_condition ) {

				$add_working_string = true;
				$next_match	    	= null;
				$rebuilt_set	    = false;
				$new_literal	    = 0;
				$new_var			= false;
				$condition_count    = count($condition_array);
				$condition_string   = $cur_condition[0];
				$condition_location = $cur_condition[1];
				$working_string		= '';

				$match_index = $condition_index + 1;
						
				while ( (!$next_match || $this->is_whitespace($next_match)) && $match_index < count($condition_array) ) {
					$next_match = ( isset($condition_array[$match_index]) ) ? $condition_array[$match_index][0] : null;
					$match_index ++;
				}
				
				//echo "cur condition: $condition_string<BR />";
				//echo "loc: {$condition_location}<br />";
				//echo '<br /><br />';
				//echo "condition array: " . str_replace("\n", '<br>', print_r($condition_array, 1) ) . '<br />';
				//echo "condition count: {$condition_count}<br />";		


				//else {
					if ( !$in_var AND !$in_literal AND !$this->is_whitespace($condition_string) AND !$this->is_reserved_word($condition_string) AND !$this->is_reserved_char_for_eval($condition_string) AND !is_numeric($condition_string) ) {
	
						//echo "$condition_string is ok<BR />";
	
						if ( preg_match('/^[\'\"]/', $condition_string, $lit_matches) ) {
							//echo "starting literal<BR />";
							//$rebuilt_statement .= $condition_string;
							$working_string .= $condition_string;
							$in_literal = 1;
							$literal_quote = $lit_matches[0];
		
							if ( !preg_match("/^\s*(?<!\\\){$literal_quote}([^\s]*)(?<!\\\){$literal_quote}\s*\$/", $condition_string) ) {
								// no sense in setting this as the start of a new literal
								// if the literal has no spaces, since parsing will finish
								// on the first iteration.
								$new_literal = 1;
							}
						}
						else if ( !$in_function && substr($condition_string, 0, 1) == '$' && !$this->has_global_prefix($condition_string) ) {
							
							$in_var = true;	
							$new_var = true;
							$working_string .= $condition_string;
						}
						else if ( 
								 ($this->is_valid_function_name($condition_string) && $next_match == '(')
								 ||
								 ($this->is_method_call($condition_string))) {
							
								//
								// This is a function/method call, don't parse it
								// 	
								
								//$load_statements[] = $this->get_function_class_load_statement( $condition_string );
								
								//$rebuilt_statement .= $condition_string;
								$in_function = true;
								$function_nest++;
								
								
						}
						else {
		
	                        //$options = array();
	                        //$options[$this->_Key_loop_name]  = $loop_name;
	
		
							//
							// Check to see if this condition is just "var"
							//
							
							$param_var = $this->parse_param_reference( $condition_string, $options );
	
							//echo "param var: {$param_var}<br />";
	
							if ( $this->_Class_debug ) {
								echo "next is: {$next_match}<br />";
								echo "{$next_match} is comparator : " . $this->is_comparator($next_match) . "<br />";
							}
	
							if ( preg_match("/^\s*({$this->_Prefix_global}|\\\$)?[{$this->var_allowed_regexp}\-\>]+\s*$/", $condition_string) ) {
													
								if ( !$disable_isset_check ) {
									//if ( !$next_match || (!$this->is_comparator($next_match) && $next_match != '!') ) {
									if ( !$next_match || (!$this->is_reserved_char($next_match)) ) {
	
										if ( $this->_Class_debug ) {
											echo "prev match: {$prev_match}<br />\n";
										}
	
										if ( !$prev_match || !$this->is_reserved_char($prev_match) ) {
										//if ( !$prev_match || !$this->is_comparator($prev_match) ) {
											//
											// This condition is just testing if ( $var ), 
											// make it isset($var) && $var
											//
	
											if ( $this->_Class_debug ) {
												echo "FOUND VAR ONLY: {$condition_string}<br />";
											}
			
											$working_string = "( isset($param_var) && $param_var )";
											
										}
									}
								}
							}
	
							if ( !$working_string ) {
								$working_string .= $param_var;
							}
	
						}
									
					}
					else {

						if ( !$in_function && !$in_var && !$in_literal ) {
							if ( $condition_string == '>' && $prev_match == '-' ) {
								
								$in_var = true;	
								$new_var = true;
								
								//echo 'object ref on ' . $condition_string . '<br />'; 
								
							}
						}

						$working_string .= $condition_string;
					}						
	
					//----------------------------------------------------------
					// Note: this is outside of the previous else
					// because otherwise literals without spaces will get stuck
					// $in_literal until the end of the if statement
					//----------------------------------------------------------
					if ( $in_literal ) {
	
						$prev_match = null;
	
						if ( !$new_literal ) {
							//if ( preg_match('/[\'\"]$/', $condition_string) ) {
							if ( preg_match("/(?<!\\\)$literal_quote\$/", $condition_string) ) {
								$in_literal = 0;
							}
						}
					}
					else if ( $in_var ) { 
						if ( !$new_var && preg_match('/[^A-Za-z0-9_\-\>\[\]]/', $condition_string) ) {
							$in_var = 0;
						}
					}
					
					if ( !$in_var && $in_function ) {

						//if ( $condition_string == '(') {
						//	$function_nest++;
						//}
						$add_working_string = false;
						
						if ( $condition_string == ')' ) {
							
							$function_nest--;
							$function_string .= ')';
						
							if ( $function_nest == 0 ) { 
							
								//$func_options[$this->_Key_loop_name]  = $loop_name;
						
								
								$function_data = $this->parse_function_call($function_string, $options); //$this->parse_functions($function_string, $func_options);
							
								$load_statements = array_merge( $load_statements, $function_data['load_statements']);
								$rebuilt_statement .= $function_data['val'];
							 
								$in_function = 0;
								$function_string = '';
								$in_function = 0;
								
							}
	

						}
						else {
							$function_string .= $condition_string;
						}
						
					}
					else {
						if ( !$this->is_whitespace($condition_string) ) {
							$prev_match = $condition_string;
						}
					}
	
					if ( $add_working_string ) {
						
						if ( $special_replacement = $this->special_var_name_replacement($working_string) ) {
							$rebuilt_statement .= $special_replacement;
						}
						else {
							$rebuilt_statement .= $working_string;
						}
					}
					
					$condition_index++;
	
				}
			//}
		}		

		$ret = array();
		$ret['load_statements'] = $load_statements;
		$ret['val'] = $rebuilt_statement;
		
		
		return $ret;
		//return $rebuilt_statement
	}

	function parse_vars( &$data, $options = array() ) {

		preg_match_all( "/{$this->tag_regexp_start}\s*VAR ([$this->var_allowed_regexp]+)\s*([^\]]*){$this->tag_regexp_end}/i", $data, $reg_matches );

		if ( $reg_matches[0] ) {

			for ( $j = 0; $j < count($reg_matches[0]); $j++ ) {
	
				$act_on_var = $reg_matches[2][$j];
				$var_name = $reg_matches[1][$j];
				$var_tag = $reg_matches[0][$j];
				
				if ( $act_on_var ) {
					
					$parse_ret  = $this->parse_var_action( $act_on_var, $options );
					$act_on_var = $parse_ret['val'];

					$data = str_replace( $var_tag, "<?php \${$var_name}{$act_on_var}; //hi ?>", $data );
				}
				else {
					$data = str_replace( $var_tag, "<?php if ( isset(\${$var_name}) ) echo \${$var_name}; ?>", $data );
				}
				
			}
		}

		//-----------------------------------------------------------
		// $some_var within the template also counts as template var
		//-----------------------------------------------------------
		//preg_match_all( "/(\$[{$this->var_allowed_regexp}])(.*){$this->tag_regexp_end}/", $data, $reg_matches );
		//preg_match_all( "/(\$[{$this->var_allowed_regexp}])/", $data, $reg_matches );
		
		//$preg_result = preg_match_all( "/{$this->tag_regexp_start}\s*(.*)(\\$[{$this->var_allowed_regexp}]+)(.*)\s*{$this->tag_regexp_end}/", $data, $var_matches, PREG_OFFSET_CAPTURE );
		$preg_result = preg_match_all( "/{$this->tag_regexp_start}\s*(\\$[{$this->var_allowed_regexp}\[\]\'\"\-\>]+)(.*)\s*{$this->tag_regexp_end}/U", $data, $var_matches, PREG_OFFSET_CAPTURE );

		if ( $preg_result ) {

			
			FUSE::Require_class('Util/StringUtils');

			for ( $j = 0; $j < $preg_result; $j++ ) {

				$entire_match = $var_matches[0][$j][0];
				$match_string = $this->strip_template_tags($entire_match);
				
				$assign_match = StringUtils::Strip_literals($match_string);
			
				if ( ($eq_loc = strpos($assign_match, '=')) !== false ) {
					
					$varname = '';
					$literal_starter = null;
					$eq_index = 0;
					
					//
					// Looking for the first equals sign
					// that is not inside a literal
					//
					while ( $eq_index < (strlen($match_string) -1) ) {
						$cur_char = substr($match_string, $eq_index, 1);
						
						if ( $cur_char == '=' ) {
							if ( $literal_starter == null ) {
								break;
							}							
						}
						else {
							if ( $cur_char == '\'' || $cur_char == '\"') {
								if ( $literal_starter ) {
									if ( $cur_char == $literal_starter ) {
										$literal_starter = null;
									}
								}
								else {
									$literal_starter = $cur_char;									
								}
							}
						}
						
						$eq_index++;
					}
					
					
					//$real_eq_loc = strpos($match_string, '=');
					
					$varname = substr($match_string,0, $eq_index);
					
					$action  = substr($match_string, $eq_index + 1 );
					
					$parse_ret = $this->parse_eval_string($action, $options);
					
					$replacement = '<?php ' . $varname . '=' . $parse_ret['val'] . '; ?>';
				}
				else {
					
					if ( $this->has_global_prefix($match_string) ) {
						continue;
					}

					if ( $special_replacement = $this->special_var_name_replacement($match_string) ) {
						$replacement = '<?php echo ' . $special_replacement . '; ?>';
					}
					else {
						$replacement = '<?php echo ' . $this->strip_template_tags($entire_match) . '; ?>';	
					}
					
				}

				$data = str_replace( $entire_match, $replacement, $data );

			}	
		}

		return $data;

	}

	public function special_var_name_replacement( $var ) {
		
		if ( $var == '$active_iteration_object' ) {
			return '$this->get_active_iteration_object()';
		}
		if ( $var == '$active_iterator' ) {
			return '$this->get_active_iterator()';
		}
		
		return null;
		
	}
	
	function is_comparator( $value ) {

		if ( in_array($value, $this->_Comparator_chars) ) {
			return true;
		}
	

		return false;
	}

	function is_whitespace( $value ) {

		if ( !preg_match('/\S/', $value) ) {
			return true;
		}
		else {
			return false;
		}

	}

	function is_valid_variable_name( $name ) {

		if ( preg_match("/[^{$this->var_allowed_regexp}]/", $name) ) {
			return false;
		}

		return true;

	}

	function is_valid_function_name( $name ) {

		if ( preg_match("/[^A-Za-z0-9\_]/", $name) ) {
			return false;
		}

		return true;

	}

	function strip_template_tags( $data ) {

		$data = preg_replace( "/^{$this->tag_regexp_start}/", '', $data );
		$data = preg_replace( "/{$this->tag_regexp_end}$/", '', $data );
	
		$data = preg_replace( '/^\s*/', '', $data );
		$data = preg_replace( '/\s*$/', '', $data );

		return $data;
	}

	function parse_ifs( &$data, $options = array() ) {

		$loop_name = isset($options[$this->_Key_loop_name]) ? $options[$this->_Key_loop_name] : null;

		preg_match_all("/{$this->tag_regexp_start}\s*IF\s*(.+){$this->tag_regexp_end}/Uis", $data, $reg_matches, PREG_OFFSET_CAPTURE);

		if ( isset($reg_matches[1][0]) && (count($reg_matches[1][0]) > 0) ) {

			for ( $j=0; $j < count($reg_matches[1]); $j++ ) {

				$if_tag	      = $reg_matches[0][$j][0];
				$if_statement = $reg_matches[1][$j][0];
				$if_location  = $reg_matches[0][$j][1];
				$in_literal   = 0;

				$parsed = $this->parse_eval_string( $if_statement, $options );
				
				$rebuilt_statement = '<?php '; 
				
				if ( isset($parsed['load_statements']) && $parsed['load_statements'] ) {

					$load_statement = implode("\n", $parsed['load_statements']);
					$rebuilt_statement .= $load_statement . "\n";
				}
				
				$rebuilt_statement .= 'if ( ' . $parsed['val'] . ') { ?>';
				
				$data = str_replace( $if_tag, $rebuilt_statement, $data );

			}


		}

  		$endif_replacement = '<?php } ?>';

		$data = preg_replace( "/{$this->tag_regexp_start}\s*ELSE\s*{$this->tag_regexp_end}/U", "<?php } else { ?>", $data );
		$data = preg_replace( "/{$this->tag_regexp_start}\s*\/IF\s*{$this->tag_regexp_end}/U", $endif_replacement, $data );
  
		return $data;


	}


	function is_template_var( $value ) {

		if ( !$this->has_global_prefix($value) ) {
			$value = preg_replace('/^\s*/', '', $value);

			if ( substr($value, 0, 1) == '$' ) {
				return true;
			}
		}

		return 0;

	}

	function is_template_var_prefix( $value ) {
	
		if ( $value == '$' ) {
			return true;
		}

		return false;


	}

	public function replace_comments( &$input, $options = array() ) {

		try {
			
			return preg_replace( "/{$this->tag_regexp_start}\s*\/\/.*\s*{$this->tag_regexp_end}/sU", '<?php ?>', $input );
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function replace_includes($input, $options = array() ) {
		
		try {

			$output = $input;
			
			$include_matches = $this->get_include_matches( $input );
	
			for ( $j = 0; $j < count($include_matches[0]); $j++ ) {
				$include_tag = $include_matches[0][$j];
				$include_file = $include_matches[1][$j];
	
	
				$include_file = $this->generate_include_path( $include_file, $options );	
				$include_filename = basename( $include_file );
	
				if ( file_exists($include_file) AND is_file($include_file) ) {
	
			    	if ( filesize($include_file) > 0 ) {					
	
						if ( !$fp = fopen($include_file, "r") ) {
	            			throw new ReadException($include_file);
	            		}
	
						$file_data = fread( $fp, filesize($include_file) );
	    		        fclose( $fp );
	    		        
	    		        $nested_matches = $this->get_include_matches($file_data);
	    		        
	    		        if ( count($nested_matches[0]) > 0 ) {
							$sub_options = $options;
	    		        	$sub_options['parent_file'] = $include_file;
	    		        	$include_contents = $this->replace_includes($file_data, $sub_options);
												
						}
						else {
							$include_contents = $file_data;
						}
			      	}
				  	else {
						$include_contents = null;
				  	}
	
				  	$output = str_replace( $include_tag, $include_contents, $output );
				}
	
			}
	
	
			return $output;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	function compile_includes( $input, $options = array() ) {

		try {
			
			$output = $input;
			$loop_name = null;
			
			if ( isset($options[$this->_Key_loop_name]) ) {
				$loop_name = $options[$this->_Key_loop_name];
				
				if ( !isset($options['nest_count']) ) {
					$options['nest_count'] = 1;
				}
				else {
					$options['nest_count'] = $options['nest_count'] + 1;
				}
				
				$options[self::KEY_PARENT_LOOP] = $options[$this->_Key_loop_name];
			}
	
			//preg_match_all( "/{$this->tag_regexp_start}\s*include\s*\'([{$this->valid_filename_regexp}]+)\'\s*{$this->tag_regexp_end}/i", $input, $include_matches );
			$include_matches = $this->get_include_matches( $input );
	
			for ( $j = 0; $j < count($include_matches[0]); $j++ ) {
				$include_tag = $include_matches[0][$j];
				$include_file = $include_matches[1][$j];
	
	
				$include_file = $this->generate_include_path( $include_file, $options );	
				$include_filename = basename( $include_file );
	
				if ( file_exists($include_file) AND is_file($include_file) ) {
	
			       if ( filesize($include_file) > 0 ) {					
	
						if ( !$fp = fopen($include_file, "r") ) {
	            			$this->template_error("Could not Parse file $include_file", 'couldn\'t open $include_file', 'compile_includes' );
	            		}
	
						$file_data = fread( $fp, filesize($include_file) );
	    		        fclose( $fp );
	    		        
	    		        $options['parent_file'] = $include_file;
						
						$include_contents = $this->compile( $file_data, $options );
						$options['parent_file'] = null;
					}
					else {
						$include_contents = null;
					}
	
					$output = str_replace( $include_tag, $include_contents, $output );
	
				
	
				}
	
			}
	
	
			return $output;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	//--------------------------------------------------------------------------
	// generate_include_path()
	//
	// Parse the include path so that paths relative to the tmpl dir will work
	//--------------------------------------------------------------------------
	function generate_include_path( $include_file, $options = array() ) {

		if ( isset($options['parent_file']) && $options['parent_file'] ) {
			$parent_file = $options['parent_file'];
		}
		else {
			$parent_file = $this->get_absolute_filepath();
		} 
		

		$first_char = substr($include_file, 0, 1);
		
		if ( $first_char == '/' || $first_char == '\\' ) {
			
			$include_path = self::Get_base_path();
			$ds = null; 			
		}
		else {
			$include_path = dirname( realpath($parent_file) );
			$ds = DIRECTORY_SEPARATOR;
		}

		$include_file = $include_path . $ds . $include_file;
		
		return $include_file;
	}

        function print_html() {

		return $this->print_output();
	}

	public function render() {
		
		try {
			return $this->print_output();
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function print_output() {
		try { 
			if ( !$this->_Parse_called ) {
				if ( !$this->_Print_without_parse ) {
					if ( !$this->parse() ) {
						return false;
					}
				}
			}
		
			echo $this->_Output;

			return true;
		}
		catch ( Exception $e ) {
			throw $e;
		}
    }
        
	function add_by_reference( $name, &$value ) {

                $this->params[$name] = $value;

		return true;
	}

	function add_loop( $name, $value ) {

		$this->params[$name] = $value;
	}

	function add_param( $name, $value ) {
        
	        $this->params[$name] = $value;

			return true;
        }       	

	function get_param( $name ) {

		if ( isset($this->params[$name]) ) {
			return $this->params[$name];
		}

		return null;

	}

	public function get_param_val( $name ) {

		if ( isset($this->params[$name]) ) {

			
			if ( is_array($this->params[$name]) && array_key_exists($this->_Key_param_val, $this->params[$name]) ) {
				return $this->params[$name][$this->_Key_param_val];
			}
			else {
				return $this->params[$name];
			}
		}
		else {

			LL::Require_class('Util/ArrayString');

			if ( ArrayString::String_contains_array_key_reference($name) ) {
				
				$second_key = ArrayString::Extract_array_name_from_string($name);
				$key_string = ArrayString::Extract_array_keys_from_string($name);
				$key_string = "[{$second_key}]" . $key_string;
			
				return ArrayString::Get_array_value_by_keys($this->params, $key_string);
			}
			
			
		}
		return null;
		
		
	}

	public function get_loop_val( $name ) {
		
		if ( $loop_info = $this->get_active_loop_info() ) {
			$loop_type = $loop_info[$this->_Key_param_type];
			
			if ( $loop_type == $this->_Key_param_type_iterator ) {
				if ( $model = $this->get_active_model()  ) {
					return $model->$name;
				}
			}
			
		}
		
		return null;
		
	}

	public function get_active_loop_info() {
		
		if ( isset($this->_Active_loops[0]) ) {
			return $this->_Active_loops[0];
		} 
		
		return null;
		
	}

	function get_params() {

		return $this->params;

	}

	function copy_params_from_template( &$template ) {

		$params = $template->get_params();

		if ( count($params) > 0 ) {
			foreach( $params as $key => $val ) {
				$this->add_param( $key, $val );
			}
		}

		return true;
	}

	function count_params() {

		return count($this->params);

	}

        function add_array( $array ) {
            
            return $this->add_assoc_arr( $array );
        }
                 
        public function add_assoc_arr( $array ) {
        	
	        if ( is_array($array) ) {
    	           $this->params = array_merge( $this->params, $array );
			}
                                        
        }
                                                         
        function add_array_by_ref( &$array ) {
        	
        	return $this->add_assoc_arr_by_ref( $array );
        }

	public function add_assoc_arr_by_ref( &$array ) {

		if ( is_array($array) ) {
			$this->params = array_merge( $this->params, $array );
		}

    }

	function get_output() {

		return $this->_Output;

	}

	function set_template_base_path( $path ) {

		$this->template_base_path = $path;

	}

	function set_file_by_absolute_path( $file_path ) {
		
		$this->file = $file_path;
		$this->absolute_filepath = $file_path;
		
	}

	function set_file( $filename, $ignore_directory_constant = 0, $ignore_extension_constant = 0 ) {

		$my_location = $_SERVER['PHP_SELF'] . ' - ' . __CLASS__ . '::' . __FUNCTION__ . ':';

		if ( is_array($ignore_directory_constant) ) {
			//
			// We were passed an options array
			//
			$options = $ignore_directory_constant;
			
			$ignore_extension_constant = 0;
			$ignore_directory_constant = false;
			
			if ( array_val_is_nonzero($options, 'absolute_path') ) {
				$ignore_directory_constant = true;
			}
			
			if ( isset($options['append_extension']) && $options['append_extension'] == 0 ) {
				$ignore_extension_constant = true;
			}
		}

		if ( file_exists($filename) || $ignore_directory_constant || !$this->template_base_path ) {
			$this->set_file_by_absolute_path($filename);
		}
		else {

			$filename = $this->parse_safe_filename($filename);
			//$directory = preg_quote($this->template_directory, '/');

			//$filename = preg_replace("/^{$directory}/i", '', $filename);
			$this->file = $this->template_base_path . DIRECTORY_SEPARATOR . $filename;

			if ( !$ignore_extension_constant ) {
	
				$extension = self::get_template_file_extension();
				$extension_len = strlen($extension);

				if ( substr($filename, 0- $extension_len - 1) != ".{$extension}" ) {
					$this->file .= '.' . $extension;
				}
			}

		}



		$this->absolute_filepath = $this->file;

		return $this->file;

	}

	function set_template_file_by_full_path( $file_path ) {

		return $this->set_file( $file_path, true, true );

	}

	function parse_safe_filename( $filename ) {
		
		$regexp_dir_slash = preg_quote( DIRECTORY_SEPARATOR, '#' );


		//Don't allow something like ../../../
		$filename = str_replace('..', '', $filename);

		//Make sure filename doesn't start with a slash.
		$filename = preg_replace("#^{$regexp_dir_slash}*#", '', $filename);
		
		return $filename;

	}
	
	function escape_regexp( $value, $delimiter = '/' ) {

		$value = preg_quote( $value, $delimiter );
		return $value;

	}

	function parse_regexp_string( $value ) {

		$value = preg_quote( $value );
		//$value = str_replace('$', '\$', $value);
		//$value = str_replace('[', '\[', $value);
		//$value = str_replace(']', '\]', $value);

		return $value;


	}

	function unparse_regexp_string( $value ) {

		$value = str_replace('\$', '$', $value);
		$value = str_replace('\[', '[', $value);
		$value = str_replace('\]', ']', $value);

		return $value;


	}

	function is_global_prefix( $value ) {

		$global_prefix = $this->unparse_regexp_string($this->_Prefix_global);
		if ( $global_prefix == $value ) {
			return true;
		}

		return false;
	}
	
	function has_global_prefix( $value ) {

		$global_prefix = $this->unparse_regexp_string($this->_Prefix_global);

		return $this->is_global_prefix(substr($value, 0, strlen($global_prefix)));

	}


	function strip_global_prefix($value) {
	
		$global_prefix = $this->unparse_regexp_string($this->_Prefix_global);
		$substr_start  = 0;
		$substr_len    = strlen($global_prefix);
		$negation      = '';

		$value = preg_replace('/^\s*/', '', $value);
		if ( preg_match('/^!/', $value) ) {
			$negation = '!';
			$value = preg_replace('/^!/', '', $value);
		} 

		if ( substr($value, $substr_start, $substr_len ) == $global_prefix ) {
			$value = substr( $value, $substr_len );
		}
		$value = $negation . $value;

		return $value;
		

	}

	function is_literal( $which_val ) {
	
		$which_val = preg_replace('/^\s*/', '', $which_val);
		$which_val = preg_replace('/\s*$/', '', $which_val);

		if ( preg_match('/^([\'"])/', $which_val, $matches) ) {
			if ( preg_match("/{$matches[1]}\$/", $which_val) ) {
				return true;
			}
		}

		return false;

	}

	function is_reserved_word( $which_word ) {

		$count = 0;

		foreach ( $this->reserved_words as $word ) {

			$this->reserved_words[$count] = strtolower($word);
			$count++;
		}


		if ( in_array(strtolower($which_word), $this->reserved_words) ) {
			return true;
		}
	
		return false;

	}

	function is_reserved_char( $char ) {

		if ( preg_match("/[{$this->reserved_char_class}]/", $char) ) {
			return true;
		}

		return false;

	}

	function is_reserved_char_for_eval( $char ) {

		if ( preg_match("/[{$this->reserved_char_class_eval}]/", $char) ) {
			return true;
		}

		return false;

	}

        function has_value ($var) {
                                        
                if ("$var" != "") {
                        return true;
                }
                else {   
                        return false;
                }
        }

	function write_static_cache_file() {

		require_library('files');

		$header_data = '';
		$footer_data = '';

		$my_location = $_SERVER['PHP_SELF'] . ' - MarkupTemplate::' . __FUNCTION__ . ':';

		if ( $this->static_cache_header ) {
			if ( function_exists('print_header') ) {
				ob_start();
				print_header(1, 0);
				$header_data = ob_get_clean();
			}
		}

		if ( $this->static_cache_footer ) {
			if ( function_exists('print_footer') ) {
				$footer_data = print_footer(0, 1);
			}
		}

		if ( !($write_path = get_static_cache_path( $this->static_cache_suffix, $this->static_cache_prefix, $this->static_cache_id, $_SERVER['SCRIPT_FILENAME'])) ) {
			$this->template_error( 'MarkupTemplate-couldnt_get_static_path', '', $my_location . __LINE__ );
			return false;
		}

		if ( !$this->_Output ) {

			$cache_file = $this->get_cache_filepath();
			
			ob_start();
		
			if ( !include($cache_file) ) {
				$this->template_error( "Error including $cache_file in template", '', $my_location );
				return false;
			}

			$this->_Output = ob_get_clean();

		}

		//echo "Header: " . "<CODE>$header_data</CODE>" . '<BR>';

		if ( !flock_write_path($write_path, $header_data . $this->_Output . $footer_data, $this->static_file_chmod, $this->static_dir_chmod) ) {
			$this->template_error( 'MarkupTemplate-couldnt_safe_write_static_file', '', $my_location . __LINE__ );
			return false;
		}
		else {
			return true;
		}
		
		
	}

        function add_resource_result_map( $param_name, $callback, $params = null ) {

                return $this->add_resource_map( $param_name, $callback, $params, true );
        }

        function add_resource_map( $param_name, $callback, $params = null, $include_result = false ) {

                $my_location = $_SERVER['PHP_SELF'] . ' - ' . __CLASS__ . '::' . __FUNCTION__. ':';

                if ( !$callback ) {
                        $this->template_error('general-missing_parameter $callback', '', $my_location . __LINE__ );
                        return false;
                }

                $this->resource_maps[$param_name][] = array( 'callback' => $callback, 'params' => $params, 'include_result'=> $include_result );

                return true;
        }


	function has_resource_map( $param_name ) {

		if ( isset($this->resource_maps[$param_name]) && $this->resource_maps[$param_name] ) {
			return true;
		}

		return false;

	}

        function add_db_resultset( $result_name, &$result ) {

                return $this->add_db_result( $result_name, $result );
        }

        function add_db_result( $result_name, &$result ) {

                $this->_DB_resultsets[] = $result_name;
                $this->add_by_reference( $result_name, $result );
        }

        function loop_is_db_result( $loop_name ) {

                if ( in_array($loop_name, $this->_DB_resultsets) ) {
                        return true;
                }

                return false;

        }

		public function loop_marked_as_iterator( $loop_name ) {

			if ( $loop_name && in_array($loop_name, $this->_Iterator_loops) ) {
            	return true;
            }

            return 0;
			
		}

		function param_is_marked_as_iterator( $param_name ) {
			
			if ( isset($this->params[$param_name]) && is_array($this->params[$param_name]) ) {
				
				if ( isset($this->params[$param_name][$this->_Key_param_type]) ) {
					if ( $this->params[$param_name][$this->_Key_param_type] == $this->_Key_param_type_iterator ) {
						return true;						
					}
				}
			}
			
			return 0;
		}

	function mark_as_db_loop( $loop_name ) {
    
    	$this->_DB_resultsets[] = $loop_name;
	}

	function loop_opener_has_db_prefix( $opening_tag ) {
	
		if ( preg_match("/^\s*{$this->tag_regexp_start}\s*DB_LOOP\s+/i", $opening_tag) ) {
			return true;
		}

		return false;

	}

	public function loop_opener_is_for_iterator( $opening_tag ) {
	
		if ( preg_match("/^\s*{$this->tag_regexp_start}\s*ITERATOR\s+/i", $opening_tag) ) {
			return true;
		}

		return false;

	}

	public function mark_as_iterator( $loop_name ) {
		$this->_Iterator_loops[] = $loop_name;
	}

	function set_template_extension( $ext ) {

		$this->template_extension = $ext;

	}

	function set_template_cache_path( $path ) {

		$this->template_cache_path = $path;

	}

        function unset_param( $param_name ) {

		if ( $param_name ) {
	                if ( isset($this->params[$param_name]) ) {
        	                unset($this->params[$param_name]);
                	}
		}

        }

        function get_template_filepath() {

                return $this->file;

        }

	function get_absolute_filepath() {

		return $this->absolute_filepath;

	}
	


	static function template_file_is_readable($file) {
		
		$file_path = self::absolute_filepath_by_file_reference($file);
		
		return is_readable($file_path);
		
	}
	
	static function absolute_filepath_by_file_reference( $file ) {

		$file_path = '';
		$base_path = self::get_base_path();
		$extension = self::get_template_file_extension();
		
		if ( substr($file, 0, strlen($base_path)) != $base_path ) {
			$file_path = $base_path . DIRECTORY_SEPARATOR;
		}
		
		$file_path .= $file;
		
		if ( substr($file, 0 - strlen($extension)) != $extension ) {
			
			$file_path .= '.' . $extension;
		}

		return $file_path;
		
	}

	static function Strip_template_file_extension( $filename ) {
		
		$extension = self::get_template_file_extension();
		
		$extension_len = strlen($extension);
		
		//
		//Add one to extension length for dot
		//
		if ( substr($filename, 0 - ($extension_len+1)) == ".{$extension}" ) {
			$filename = substr($filename, 0, 0-($extension_len+1) );
		}
		
		return $filename;		
		
	}


	function apply_template_loop_map( $callback, &$iteration_row, &$result ) {
	
		$map_result = null;
		
		if ( is_scalar($callback) ) {
			$call_string = $callback;
		}
		else if ( is_array($callback) ) {
			if ( count($callback) != 2 ) {
				trigger_error( "Invalid template map callback" . print_r($callback, 1), E_USER_WARNING );
			}
			else {
				if ( is_object($callback[0]) ) {
					$call_string = 	"\${$callback[0]}->{$callback[1]}";
				}
				else {
					$call_string = "{$callback[0]}::{$callback[1]}";
				}
			}
		}

               	if ( $result ) {
                       eval( "\$map_result =& {$call_string}(\$iteration_row, \$result);" );
               	}
               	else {
                       eval( "\$map_result =& {$call_string}(\$iteration_row);" );
               	}

		return $map_result;

	}

	public function reset() {
		
		$this->initialize_class();
		
	}

	public function clean_temp_files() {
		
		if ( is_array($this->_Temp_files) ) {
			foreach( $this->_Temp_files as $cur_file ) {
				$path = $this->template_cache_path . DIRECTORY_SEPARATOR . basename($cur_file);
				unlink( $path );
			}
		}
		
	}

}

class TemplateLoop {
	
	public $nest_index;
	public $type;
	public $parent;
	
	
	
}

//
// Standalone Version compat

if ( !function_exists('get_current_script_location') ) {
function get_current_script_location( $file, $function = NULL, $class_name = NULL ) {

        $location = @getenv('SCRIPT_NAME') . ' (' . $file . ') ';

        if ( is_object($class_name) ) {
                $class_name = get_class($class_name);
        }

        if ( $class_name ) {
                $location .= $class_name;
        }

        if ( $function ) {
                $location .= '::' . $function . ':';
        }

        return $location;

}
}

?>
