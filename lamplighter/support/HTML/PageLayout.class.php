<?php


class PageLayout {
	
	const KEY_TARGET_TEMPLATE = 'target_template';
	
	static $Template_filename_suffix_head = '-head';
	static $Param_name_head_elements = 'HTML_HEAD_ELEMENTS';
	
	static $Key_header = 'header';
	static $Key_footer = 'footer';
	static $Layout_template_dir_name = 'Layout';

	var $header_template_file_default;
	var $footer_template_file_default;

	var $header_callback_pre  = null;
	var $header_callback_post = null;

	var $footer_callback_pre  = null;
	var $footer_callback_post = null;

	var $_Header_template_obj;
	var $_Header_template_param_manager;
	var $_Header_template_params = null;
	var $_Header_templates;	

	var $_Footer_template_obj;
	var $_Footer_template_param_manager;
	var $_Footer_template_params = null;
	var $_Footer_templates;	

	var $_Active_header_key      = null;
	var $_Active_footer_key	     = null;
	var $_Allow_duplicate_header = false;
	var $_Allow_duplicate_footer = false;
	var $_Was_header_printed     = false;
	var $_Was_footer_printed     = false;
	
	var $_Warn_on_nonexistent_header_key = false;
	var $_Warn_on_nonexistent_footer_key = false;

	protected $_Template_object;

	var $_Param_name_body_onload = 'body_onload';


	public function __construct() {

		$this->_Determine_default_header_filename();
		$this->_Determine_default_footer_filename();

	}

	function set_header_callback_pre( $callback ) {

		$this->header_callback_pre = $callback;

	}

	function get_header_callback_pre() {

		return $this->header_callback_pre;

	}

	function set_header_callback_post( $callback ) {

		$this->header_callback_post = $callback;

	}

	function get_header_callback_post() {

		return $this->header_callback_post;

	}

	function set_footer_callback_pre( $callback ) {

		$this->footer_callback_pre = $callback;

	}

	function set_footer_callback_post( $callback ) {

		$this->footer_callback_post = $callback;

	}

	function get_footer_callback_pre() {

		return $this->footer_callback_pre;

	}

	function get_footer_callback_post() {

		return $this->footer_callback_post;

	}

	function &get_header_template( $options = null ) {

		if ( !$this->_Header_template_obj ) {

			$template_filename = $this->get_header_template_filename();

			if ( !$template_filename ) {
				trigger_error( 'No header template specified', E_USER_WARNING );
				return false;
			}		
			else {
			
				LL::Require_class('HTML/MarkupTemplate');
			
				$this->_Header_template_obj = new MarkupTemplate($template_filename);
				$this->_Header_template_obj = $this->apply_header_params_to_template($this->_Header_template_obj, $options);

				if ( $this->_Header_template_param_manager ) {
					$this->_Header_template_param_manager->apply_params_to_template($this->_Header_template_obj);
				}

			}
		}

		return $this->_Header_template_obj;

			
	}

	function get_header_template_filename() {

		$header_key = $this->get_active_header_key();

		if ( $header_key ) {
			if ( !($template_filename = $this->header_template_file_by_key($header_key)) ) {
				trigger_error( "Invalid header key: {$header_key}", E_USER_WARNING );
			}
		}
		else {
			$template_filename = $this->header_template_file_default;
		}

		return $template_filename;
	}

	function print_html_header() {

		return $this->print_layout_header();
	}

	function get_footer_output( $options = array() ) {
		
		if ( !($template = $this->get_footer_template()) ) {
			trigger_error( 'Error getting footer template obj', E_USER_WARNING );
		}
		else {
			$this->apply_footer_params_to_template($template, $options);
			$template->parse();
			
			return $template->get_output();
				
		}
		
	}

	function get_header_output( $options = array() ) {
		
		if ( !($template = $this->get_header_template($options)) ) {
			trigger_error( 'Error getting header template obj', E_USER_WARNING );
		}
		else {

			$this->apply_header_params_to_template($this->_Header_template_obj, $options);
			$template->parse();

			return $template->get_output();
				
		}
		
	}

	function print_layout_header( $options = array() ) {
		
		LL::require_class('HTML/MarkupTemplate');
		
		if ( MarkupTemplate::template_file_is_readable($this->get_header_template_filename()) && (!$this->_Was_header_printed) || $this->_Allow_duplicate_header ) {
		
			$pre_callback  = $this->get_header_callback_pre();
			$post_callback = $this->get_header_callback_post();

			if ( $pre_callback ) {
				if ( !is_callable($pre_callback) ) {
					trigger_error( "Invalid header pre callback:" . print_r($pre_callback, true), E_USER_WARNING );
				}
				else {
					call_user_func($pre_callback);
				}
			}


			if ( !($template = $this->get_header_template($options)) ) {
				trigger_error( 'Error getting header template obj', E_USER_WARNING );
			}
			else {

				$this->apply_header_params_to_template($this->_Header_template_obj, $options);
				$template->print_html();

				$this->mark_header_as_printed(true);
			}

			if ( $post_callback ) {
				if ( !is_callable($post_callback) ) {
					trigger_error( "Invalid header post callback:" . print_r($post_callback, true), E_USER_WARNING );
				}
				else {
					call_user_func($post_callback);
				}
			}


		}

	}

	function get_header_template_params() {

		return $this->_Header_template_params;

	}

	function header_template_file_by_key( $key ) {

		if ( isset($this->_Header_templates[$key]) ) {
			return $this->_Header_templates[$key];
		}
		else {
			$file_ref = self::$Layout_template_dir_name;
			$file_ref .= DIRECTORY_SEPARATOR . $key;
			$file_ref .= DIRECTORY_SEPARATOR . $key . '-' . self::$Key_header;
		
			return $file_ref;
		}
		

	}

	function mark_header_as_printed( $val ) {

		if ( $val ) {
			$this->_Was_header_printed = true;
		}
		else {
			$this->_Was_header_printed = false;
		}
	}
	

	function was_header_printed() {

		if ( $this->_Was_header_printed ) {
			return true;
		}

		return 0;

	}


	function &get_footer_template() {

		if ( !$this->_Footer_template_obj ) {

			if ( $template_filename = $this->get_footer_template_filename() ) {
	
				LL::require_class('HTML/MarkupTemplate');

				$this->_Footer_template_obj = new MarkupTemplate($template_filename);
				$this->_Footer_template_obj = $this->apply_footer_params_to_template($this->_Footer_template_obj);

				if ( $this->_Footer_template_param_manager ) {
					$this->_Footer_template_param_manager->apply_params_to_template($this->_Footer_template_obj);
				}

			}
		}

		return $this->_Footer_template_obj;

			
	}

	protected function _Determine_default_header_filename() {
		
		if ( !$this->header_template_file_default ) {
				
			LL::require_class('HTML/MarkupTemplate');
			
			if ( $template_base = MarkupTemplate::get_base_path() ) {
					
				if ( file_exists($template_base . DIRECTORY_SEPARATOR . 'header.' . MarkupTemplate::get_template_file_extension()) ) {
					$this->header_template_file_default = 'header';						
				}
			}
				
			if ( !$this->header_template_file_default ) {
				$this->header_template_file_default = $this->header_template_file_by_key('default');
			}
				
		}
			
		
	}

	protected function _Determine_default_footer_filename() {

		if ( !$this->footer_template_file_default ) {
				
			LL::require_class('HTML/MarkupTemplate');
			
			if ( $template_base = MarkupTemplate::get_base_path() ) {
					
				if ( file_exists($template_base . DIRECTORY_SEPARATOR . 'footer.' . MarkupTemplate::get_template_file_extension()) ) {
					$this->footer_template_file_default = 'footer';						
				}
			}
				
			if ( !$this->footer_template_file_default ) {
				$this->footer_template_file_default = $this->footer_template_file_by_key('default');
			}
				
		}
		
	}


	function get_footer_template_filename() {

		$footer_key = $this->get_active_footer_key();

		if ( $footer_key ) {
			if ( !($template_filename = $this->footer_template_file_by_key($footer_key)) ) {
				trigger_error( "Invalid footer key: {$footer_key}", E_USER_WARNING );
			}
		}
		else {
			$template_filename = $this->footer_template_file_default;
		}

		return $template_filename;
	}

	function print_html_footer() {

		return $this->print_layout_footer();
	}


	function print_layout_footer( $options = array() ) {
		
		LL::require_class('HTML/MarkupTemplate');
		
		if ( MarkupTemplate::template_file_is_readable($this->get_footer_template_filename()) && (!$this->_Was_footer_printed) || $this->_Allow_duplicate_footer ) {

			$pre_callback  = $this->get_footer_callback_pre();
			$post_callback = $this->get_footer_callback_post();

			if ( $pre_callback ) {
				if ( !is_callable($pre_callback) ) {
					trigger_error( "Invalid footer pre callback:" . print_r($pre_callback, true), E_USER_WARNING );
				}
				else {
					call_user_func($pre_callback);
				}
			}


			if ( !($template = $this->get_footer_template()) ) {
				trigger_error( 'Error getting footer template obj', E_USER_WARNING );
			}
			else {
				$this->apply_footer_params_to_template($template, $options);
				$template->print_output();
				$this->mark_footer_as_printed(true);
				
			}

			if ( $post_callback ) {
				if ( !is_callable($post_callback) ) {
					trigger_error( "Invalid footer post callback:" . print_r($post_callback, true), E_USER_WARNING );
				}
				else {
					call_user_func($post_callback);
				}
			}

		}

	}

	function get_footer_template_params() {

		return $this->_Footer_template_params;

	}

	function footer_template_file_by_key( $key ) {

		if ( isset($this->_Footer_templates[$key]) ) {
			return $this->_Footer_templates[$key];
		}
		else {
			
			
			$file_ref = self::$Layout_template_dir_name;
			$file_ref .= DIRECTORY_SEPARATOR . $key;
			$file_ref .= DIRECTORY_SEPARATOR . $key . '-' . self::$Key_footer;
		
			return $file_ref;
		}
		
	}

	function mark_footer_as_printed( $val ) {

		if ( $val ) {
			$this->_Was_footer_printed = true;
		}
		else {
			$this->_Was_footer_printed = false;
		}
	}
	

	function was_footer_printed() {

		if ( $this->_Was_footer_printed ) {
			return true;
		}

		return 0;

	}

	function get_header_template_param( $key ) {

		if ( isset($this->_Header_template_params[$key]) ) {
			return $this->_Header_template_params[$key];
		}

		return null;

	}

	function add_header_template_param( $key, $val ) {

		$this->_Header_template_params[$key] = $val;
	}

	function set_header_template_param( $key, $val ) {

		$this->_Header_template_params[$key] = $val;
	}

	function append_header_template_param( $key, $val ) {

		$cur_val = '';

		if ( isset($this->_Header_template_params[$key]) ) {
			$cur_val = $this->_Header_template_params[$key];
		}
		
		$this->set_header_template_param( $key, "{$cur_val}{$val}");
	}


	function set_active_header_key( $key ) {

		if ( !$this->header_key_exists($key) ) {
			if ( $this->_Warn_on_nonexistent_header_key ) {
				trigger_error( "Nonexistent header key set as active: {$key}", E_USER_WARNING );
			}
		}

		$this->_Active_header_key = $key;
	}

	function header_key_exists( $key ) {

		if ( isset($this->_Header_templates[$key]) ) {
			if ( $this->_Header_templates[$key] ) {
				return true;
			}
		}

		return 0;

	}

	function get_active_header_key() {

		return $this->_Active_header_key;
	}

	function set_header_template_file_default( $filename ) {

		$this->header_template_file_default = $filename;

	}

	function add_header_template( $header_key, $file ) {

		$this->_Header_templates[$header_key] = $file;

	}

        function get_footer_template_param( $key ) {

                if ( isset($this->_Footer_template_params[$key]) ) {
                        return $this->_Footer_template_params[$key];
                }

                return null;

        }

        function add_footer_template_param( $key, $val ) {

                $this->_Footer_template_params[$key] = $val;
        }

		function set_footer_template_param( $key, $val ) {

			$this->_Footer_template_params[$key] = $val;
		}

        function set_active_footer_key( $key ) {

		if ( !$this->footer_key_exists($key) ) {
			if ( $this->_Warn_on_nonexistent_footer_key ) {
				trigger_error( "Nonexistent footer key set as active: {$key}", E_USER_WARNING );
			}
		}

                $this->_Active_footer_key = $key;
        }

        function get_active_footer_key() {

                return $this->_Active_footer_key;
        }

        function set_footer_template_file_default( $filename ) {

                $this->footer_template_file_default = $filename;

        }

        function add_footer_template( $footer_key, $file ) {

                $this->_Footer_templates[$footer_key] = $file;

        }

	function footer_key_exists( $key ) {

		if ( isset($this->_Footer_templates[$key]) ) {
			if ( $this->_Footer_templates[$key] ) {
				return true;
			}
		}

		return 0;

	}

	function add_body_onload( $new_onload ) {

		$cur_onload = $this->get_header_template_param( $this->_Param_name_body_onload );

		return $this->set_header_template_param( $this->_Param_name_body_onload, "{$cur_onload}{$new_onload}" );

	}	

	function set_active_layout_key( $key ) {

		$this->set_active_header_key( $key );
		$this->set_active_footer_key( $key );
	}


	function add_header_template_params( $param_arr ) {

		if ( !is_array($param_arr) ) {
			trigger_error( __METHOD__ . ' must be called with an array', E_USER_WRNING );
		}
		else {
			$this->_Header_template_params = array_merge( $this->_Header_template_params, $param_arr );
		}

	}

	public function get_head_element_filepath( $template_file_ref ) {
		
		LL::require_class('MarkupTemplate');
		
		$file_path = MarkupTemplate::absolute_filepath_by_file_reference($template_file_ref);
		$file_path = MarkupTemplate::Strip_template_file_extension($file_path);
		$file_path .= self::$Template_filename_suffix_head . '.' . MarkupTemplate::get_template_file_extension();
		
		return $file_path;
		
	}

	protected function _Get_fresh_template_object( $file = null, $options = null ) {
		
		$options['reset'] = true;
		return $this->_Get_template_object( $file, $options );
		
	}

	protected function _Get_template_object( $file = null, $options = null ) {
		
		try {
			if ( !$this->_Template_object ) {
				LL::require_class('MarkupTemplate');
				$this->_Template_object = new MarkupTemplate();
			}
			
			if ( array_val_is_nonzero($options, 'reset') ) {
				$this->_Template_object->reset();
			}
			
			if ( $file ) {
				$this->_Template_object->set_file($file);
			}
			
			return $this->_Template_object;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function apply_header_params_to_template( &$template, $options = null ) {

		if ( is_array($this->_Header_template_params) && (count($this->_Header_template_params) > 0) ) {
			foreach( $this->_Header_template_params as $key => $val ) {
				$template->add_param( $key, $val );
			}
		}

		if ( isset($options[self::KEY_TARGET_TEMPLATE]) && ($target_template_file = $options[self::KEY_TARGET_TEMPLATE])) {
			
			if ( $head_element_filepath = self::Get_head_element_filepath($target_template_file) ) {
				
				if ( is_readable($head_element_filepath) ) {
					$head_template = $this->_Get_fresh_template_object();
					$head_template->set_file_by_absolute_path($head_element_filepath);
					//print_r( $template->params );
					$head_template->params = $template->params;
					$head_template->parse();
					$template->add_param( self::$Param_name_head_elements, $head_template->get_output() );
				}
			}
		}


		return $template;
	}

	function set_header_template_param_manager( &$manager ) {
		
		$this->_Header_template_param_manager = $manager;
	}

	function add_footer_template_params( $param_arr ) {

		if ( !is_array($param_arr) ) {
			trigger_error( __METHOD__ . ' must be called with an array', E_USER_WRNING );
		}
		else {
			$this->_Footer_template_params = array_merge( $this->_Footer_template_params, $param_arr );
		}

	}

	function apply_footer_params_to_template( &$template ) {

		if ( is_array($this->_Footer_template_params) && (count($this->_Footer_template_params) > 0) ) {
			foreach( $this->_Footer_template_params as $key => $val ) {
				$template->add_param( $key, $val );
			}
		}

		return $template;
	}

	function set_footer_template_param_manager( &$manager ) {
		
		$this->_Footer_template_param_manager = $manager;
	}

	public static function layout_template_base_path() {
		
		$base_path = null;
		
		if ( defined('TEMPLATE_BASE_PATH') ) {
			$base_path = constant('TEMPLATE_BASE_PATH') . DIRECTORY_SEPARATOR;
		}
		
		$base_path .= self::$Layout_template_dir_name;
		
		return $base_path; 
	}	
	
	public function header_allow_duplicate( $yesno ) {
		
		if ($yesno ) {
			$this->_Allow_duplicate_header = true;	
		}
		else {
			$this->_Allow_duplicate_header = false;
		}
	}

	public function footer_allow_duplicate( $yesno ) {
		
		if ($yesno ) {
			$this->_Allow_duplicate_footer = true;	
		}
		else {
			$this->_Allow_duplicate_footer = false;
		}

		
	}

}

?>
