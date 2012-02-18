<?php

if ( !class_exists('ApplicationController', false) ) {

define ('APPLICATION_CONTROLLER_CLASS_LOADED', 1 );

LL::Require_interface('AppControl/AppControllerInterface');

class ApplicationController implements AppControllerInterface {

	const CLASS_NAME_SUFFIX = 'Controller';
	const PREFIX_FORM_NAME = 'form_';
	const KEY_FORM_VALUE_FOR_REPOPULATE = 'for_repopulate';
	const KEY_TEMPLATE_PARAM_DONT_OVERWRITE = 'param_dont_overwrite';
	const KEY_PREFIX_PARAM_NAME	= 'param_prefix';
	const KEY_NEWLINE_CHAR = 'newline_char';
	const KEY_LAYOUT_OBJ_GET = 'layout_obj';
	const KEY_TEMPLATE_OBJ_GET = 'template';
	const KEY_ACTIVE_USER_OBJ_GET = 'active_user';
	const KEY_FORM_OBJ_GET = 'form';
	const KEY_TEMPLATE_OBJ		  = 'template';
	const KEY_MIXIN_OBJ	= 'mixin';
	const KEY_PARAMS_GET = 'params';

	const FORM_SETUP_KEY_INITIALIZED = 'initialized';
	const FORM_SETUP_KEY_EXTENDED    = 'extended';
	
	const KEY_PAGE	  = 'page';
	const KEY_MESSAGE = 'message';
	const KEY_PAGINATION_NAME = 'pager_name';

	static $Render_methods = array('render', 'render_form');
	static $Prefix_allow_method = 'allow_';
	static $Key_controller_from = 'from_controller';
	static $Key_controller_to   = 'to_controller';

	public $message_add_success    = 'general-changes_saved';
	public $message_update_success = 'general-changes_saved';
	public $message_delete_success = 'general-changes_saved';
		
	public $message_add_error      = 'general-add_error';		
	public $message_update_error   = 'general-update_error';
	public $message_delete_error   = 'general-update_error';
	
	public $message_clear_on_render = true;
	
	public $form_methods; //methods that need apply_form()
	public $method_failure = array(); //keeps track of methods that failed with error

	
	/* Deprecated */
	public $skip_postback_render_success = null;
	public $skip_postback_render_fail = null;
	public $skip_render_success = null;
	public $skip_render_fail = null;
	/* -------------  */
	
	public $render_on_success = true;
	public $render_on_fail = true;
		
	public $skip_pre_action = false;
	
	public $auth_skip_redirect = false;
	
	public $redirect_success;
	public $redirect_fail;
	public $form_repopulate = true;
	
	/* Deprecated */
	public $message_from_get  = false;
	public $message_from_post = false;
	public $message_from_session = true;
	/* --------- */
	
	public $allowed_external_params = array();
	public $disallowed_external_params = array();
	
	public $params_from_get  = true;
	public $params_from_post = true;
	public $params_from_cookie = true;
	public $params_from_session = true;
	public $params_explicit_only = false; //setting to true is the 
										  //same as setting all params_from_x to false
	
	public $param_order = 'GPC';
	public $header_auto_render = true;
	public $footer_auto_render = true; 
	public $form_tag_attributes;
	public $form_name;
	public $form_action = '?';
	public $form_messages; 
	public $form_auto_validation = true; //whether to run the validation function
	public $form_auto_validation_script = true; //whether to include the validation script


	public $form_class = 'Form/InputFormValidator';
	public $template_file;
	public $template_filename_changed = false;
	
	public $template_auto_apply_controller_data = true;
	public $header_auto_apply_controller_data = true;
	public $footer_auto_apply_controller_data = true;

	public $enable_default_permission_entries = true;
	public $requires_login = false;
	public $global_access  = true;

	public $camel_case_keep_number_positioning = false;
	public $warn_on_missing_action = true;

	public $param_name_user_message	   = 'message';
	public $param_name_active_user_obj = 'active_user';
	public $param_name_active_username = 'active_username';
	public $param_name_active_user_id  = 'active_user_id';

	public $static_cache_flush = array();
	public $static_cache_headers = array();
	public $layout;
	public $method_access;

	public $template_uses_model_name = false;
	
	public $views_subdir_name_lowercase = null; 
	public $views_filename_lowercase = null;
	public $views_subdir_name_pluralize = null;
	public $views_filename_pluralize = null;
	public $views_subdir_name = '';
	public $views_action_key_ucfirst = null;
	
	public $add_uses_edit_template = true;
	public $use_db_config = 'default';

	//
	// Leave these as non-static
	//
	public $method_name_add    = 'add';
	public $method_name_edit   = 'edit';
	public $method_name_delete = 'delete';

	protected $_Template_tag_start = '<{';
	protected $_Template_tag_end   = '}>';
	
	protected $_Requested_action;
	protected $_Requested_action_is_explicit = false;
	protected $_Params = array();
	protected $_Params_loaded = false;
	protected $_Param_obj;
	protected $_Components_obj;
	
	//protected $_Disallowed_external_params = array();
	
	//protected $_Template_filename;
	
	/* Deprecated as protected member: */
	protected $_Template_uses_model_name = null;
	/* *********************************** */
	
	protected $_Template_file_options = array();
	protected $_Template;
	
	protected $_Controller_class_name;
	
	//protected $_Layout_key;
	protected $_Form;
	protected $_Messages_success = array();
	protected $_Messages_fail    = array();
	protected $_Pagination_query_obj = null;
	protected $_Mixin_reflector;
	protected $_Mixin_obj;
	protected $_Layout_obj;
	protected $_Form_applied = false;
	
	//protected $_Message;
	
	public $message_auto_translate      = true;
	public $message_require_translation = true;



	protected $_Class_suffix_controller = 'Controller';
	protected $_Delimiter_controller_action = '-';
	protected $_Prefix_template_params;

	protected $_Key_form_script_tag = 'form_script_tag';
	protected $_Key_form_name = 'form_name';
	protected $_Key_form_tag = 'form_tag';
	protected $_Key_message = 'message'; //deprecated
	
	protected $_Key_id   = 'id';
	protected $_Key_action_add  = 'add';
	protected $_Key_action_edit = 'edit';

	protected $_Key_is_postback = 'is_postback';
	protected $_Key_is_getback = 'is_getback';

	protected $_Extension_javascript_file = 'js';

	protected $_Auth_validate;
	protected $_Method_access_objs;
	protected $_Method_access_was_read = false;

	protected $_Active_user_obj;
	protected $_Active_user_params_enabled = true;
	
	protected $_DB;

	public function __construct( $options = array() ) {

		if ( Config::Get('controller.param_name_user_message') ) {
			$this->param_name_user_message = Config::Get('controller.param_name_user_message');
		}

		$this->disallow_external_param($this->param_name_user_message);
			

		$this->_Prefix_template_params = ( defined('APPLICATION_CONTROLLER_PREFIX_TEMPLATE_PARAMS') ) ? constant('APPLICATION_CONTROLLER_PREFIX_TEMPLATE_PARAMS') : $this->_Prefix_template_params;
		$this->_Template_tag_start = ( defined('APPLICATION_CONTROLLER_TEMPLATE_TAG_START') ) ? constant('APPLICATION_CONTROLLER_TEMPLATE_TAG_START') : $this->_Template_tag_start;
		$this->_Template_tag_end   = ( defined('APPLICATION_CONTROLLER_TEMPLATE_TAG_END') ) ? constant('APPLICATION_CONTROLLER_TEMPLATE_TAG_END') : $this->_Template_tag_end;

		//
		// Backward compability for when messages were handled
		// via unique properties instead of the message arrays
		//
		$this->set_message_success( 'add', $this->message_add_success );
		$this->set_message_success( 'edit', $this->message_update_success );
		$this->set_message_success( 'delete', $this->message_delete_success );
		$this->set_message_fail( 'add', $this->message_add_error );
		$this->set_message_fail( 'edit', $this->message_update_error );
		$this->set_message_fail( 'delete', $this->message_delete_error );


		//$this->get_params(); 
		
		//
		// Make sure this stays after get_params()
		//
		if ( $this->enable_default_permission_entries ) {
			$this->apply_default_permission_entries();
		}

		$this->apply_default_controller_data();
	}
	
	public function __get( $key) {
		
		if ( $key == self::KEY_LAYOUT_OBJ_GET ) {
			return $this->get_layout_object();
		}
		else if ( $key == self::KEY_TEMPLATE_OBJ_GET ) {
			return $this->get_template();
		}
		else if ( $key == self::KEY_ACTIVE_USER_OBJ_GET ) {
			return $this->get_active_user_obj();
		}
		else if ( $key == self::KEY_FORM_OBJ_GET ) {
			return $this->get_form();
		}
		else if ( $key == self::KEY_MIXIN_OBJ ) {
			return $this->get_mixin_obj();
		}
		else if ( $key == self::KEY_PARAMS_GET ) {
			if ( !$this->_Param_obj ) {
				$this->_Param_obj = new AppControllerParams($this);
			}
			
			return $this->_Param_obj;
		}
		else if ( $key == 'components' || $key == 'component') {
			
			if ( !$this->_Components_obj ) {
				$this->_Components_obj = new AppControllerComponents($this);
			}
			
			return $this->_Components_obj;
		}
		
		return $this->get_param($key);
		
	}
	
	public function __set( $key, $val) {

		$found_in_mixin = false;

		if ( $mixin = $this->get_mixin_obj() ) {
			$reflector = new ReflectionObject($mixin);
			if ( $reflector->hasProperty($key) ) {
				$prop_reflector = new ReflectionProperty($mixin, $key);
				if ( $prop_reflector->isPublic() ) {
					$mixin->$key = $val;
					$found_in_mixin = true;
				}
			}
		}

		if ( !$found_in_mixin ) {
			return $this->set_param($key, $val);
		}
		
	}

	public function __call( $method, $params = null ) {

		
		if ( $mixin = $this->get_mixin_obj() ) {
			
			if ( !$this->_Mixin_reflector ) {
				$this->_Mixin_reflector = new ReflectionObject($mixin);
			}
			
			if ( $this->_Mixin_reflector->hasMethod($method) ) {
				return call_user_func_array( array($mixin, $method), $params );
			}
		}
		
		if ( $method == '__controller_pre_action' || substr($method, 0, 6) == 'after_' || substr($method, 0, 7) == 'before_' ) {
			//it's ok if this method doesn't exist
			return null;
		}
		
		$trace = debug_backtrace();		
		$trace_string = str_replace("\n", LL::Get_message_newline(), print_r($trace[1], true ));

		trigger_error( "Non-existent method called: {$method}" . LL::Get_message_newline() . $trace_string, E_USER_ERROR );
		
	}

	public function before_action() {

	}

	public function after_action() {

	}


	public static function Append_class_name_suffix( $name ) {
		
		if ( substr($name, 0 - strlen(self::CLASS_NAME_SUFFIX)) != self::CLASS_NAME_SUFFIX ) {
			return $name . self::CLASS_NAME_SUFFIX;			
		}
		
		return $name;
		
	}

	public static function Strip_class_name_suffix( $name ) {
		
		if ( substr($name, 0 - strlen(self::CLASS_NAME_SUFFIX)) == self::CLASS_NAME_SUFFIX ) {
			return substr($name, 0, 0 - strlen(self::CLASS_NAME_SUFFIX)); 			
		}
		
		return $name;
		
	}

	public function method_failed( $method_name, $val = null ) {
		
		if ( $val === null ) {
			if ( isset($this->method_failure[$method_name]) ) {
				if ( $this->method_failure[$method_name] == true ) {
					return true;
				}
				else {
					return false;
				}
			} 
			else {
				return null;
			}
		}
		else {
			$this->method_failure[$method_name] = $val;
		}
		
		
	}

	function layout( $key ) {

		$this->set_layout_key( $key );

	}

	function layout_key_set_explicitly() {
		
		if ( $this->layout ) return true;
		
		return 0;
		
	}
 
 	
	function set_layout_key( $key ) {

		$layout = $this->get_layout_object();
		$layout->set_active_layout_key( $key );
		$this->layout = $key;

	}

	function get_layout_key() {

		return $this->layout;

	}

	function get_form() { 

		try { 
			
			if ( !$this->_Form ) {
				
				LL::require_class($this->form_class);
			
				$class_name = LL::Class_name_from_location_reference($this->form_class);
			
				//$form = new InputFormValidator($this->get_form_name());
				//$form->set_validation_filename( $this->generate_form_javascript_filename() );
				$form = new $class_name();
				
				if ( !$form->has_completed_setup(self::FORM_SETUP_KEY_INITIALIZED) ) {
					$this->form_initialize($form);
				}
		
				$this->_Form = $form;
			}
			
			return $this->_Form;
			
		}
		catch( Exception $e ) {
			throw $e;
		}

	}
	
	public function form_initialize( $form = null) {
		
		try {
			
			if ( !$form ) { 
				$form = $this->get_form();
			}
			
			$form->set_form_name( $this->get_form_name() );
			$form->set_validation_filename( $this->generate_form_javascript_filename(array('form' => $form) ) );

			if ( $this->form_requirements ) {
				
				$setup_options = array();
				 
				if ( method_exists($this, 'get_model') && $model = $this->get_model() ) {
					$setup_options['data_model'] = $model;	
				}
				
				$form->apply_setup_array( $this->form_requirements, $setup_options );
								
			}

			if ( method_exists($this, 'form_setup') ) {
				$this->form_setup( $form );
				$form->add_completed_setup( self::FORM_SETUP_KEY_EXTENDED );
			}
		
			$form->add_completed_setup( self::FORM_SETUP_KEY_INITIALIZED );
		
			return $form;
		}
		catch ( Exception $e ) {
			throw $e;
		}
	}
	
	public function form_validate() {
		
		try {
			$form = $this->get_form();
			
			if ( !$form->validate_input() ) {
				$this->form_messages =  $form->get_messages();
				$this->message_append( $this->form_messages );
				return 0;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function get_form_value_for_repopulate( $input_name, $options = null ) {
		
		$options[self::KEY_FORM_VALUE_FOR_REPOPULATE] = true;
		
		return $this->get_form_value( $input_name, $options );
		
	}
	
	public function get_form_value( $input_name, $options = null ) {
		
		try {
			
			if ( $this->is_postback() ) {
				$form = $this->get_form();
				
				if ( array_val_is_nonzero($options, self::KEY_FORM_VALUE_FOR_REPOPULATE) ) {
					
					if ( !array_val_is_nonzero($options, 'allow_html_tags') ) {
						return $form->parse_form_text($form->get_unparsed($input_name));
					}
					else {
						return $form->get_unparsed($input_name);
					}
				}
				else {
					
					return $form->get($input_name);
				}
			}
			else {
				if ( $this->param_is_set($input_name) ) {
					return $this->get_param($input_name);
				}
			}
			
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function get_form_name() {

		if ( $this->form_name ) {
			return $this->form_name;
		}
		else if ( isset($this->form_tag_attributes['name']) ) {
			return $this->form_tag_attributes['name'];
		}
		else {
			return self::PREFIX_FORM_NAME . $this->get_controller_name() . '_' . $this->get_requested_action();
		}
		
	}

	function generate_form_javascript_filename( $options = array() ) { 

		$filename = $this->get_controller_name() . '-' . $this->get_requested_action();
		
		if ( isset($options['form']) && $options['form'] ) {
			$filename .= '-' . $options['form']->get_name();
		}
		
		$filename .= '.' . $this->_Extension_javascript_file;
				
		return $filename;
	}
	

	function apply_form( $options = null ) {
		
		try { 
			if ( isset($options['form']) && $options['form'] ) {
				$form = $options['form'];
				$form_explicit = true;
			}
			else {
				$form = $this->get_form();
				$form_explicit = false;
			}

			if ( array_val_is_nonzero($options, self::KEY_TEMPLATE_OBJ) ) {
				$template =  $options[self::KEY_TEMPLATE_OBJ];
				$template_explicit = true;
			}
			else {
				$template = $this->get_template();
				$template_explicit = false;
			}
			
			if ( !$form ) {
				trigger_error( 'Could not get form object', E_USER_WARNING );
			}
			else {
				
				if ( (isset($options['form']) && $options['form']) || !$form->has_completed_setup(self::FORM_SETUP_KEY_INITIALIZED) ) {
					$this->form_initialize($form);
				}
	
				if ( isset($options['attributes']) ) {
					$form_tag_attributes = $options['attributes'];
				}
				else {
					$form_tag_attributes = $this->form_tag_attributes;
				}
	
				$template = ( array_val_is_nonzero($options, self::KEY_TEMPLATE_OBJ) ) ? $options[self::KEY_TEMPLATE_OBJ] : $this->get_template();
	
				if ( $template ) {
					
					$param_prefix = array_val_is_nonzero($options, self::KEY_PREFIX_PARAM_NAME) ? $options[self::KEY_PREFIX_PARAM_NAME] : null;
					
					$attrib_str  = '';
					$form_name = $form->get_name();
	
					if ( $this->form_auto_validation_script && $form->javascript_validation_enabled && $form->generate_validation_script() ) {
						$validation_link = $form->get_validation_script_linkpath();
						$template->add_param( $param_prefix . $this->_Key_form_script_tag, "<script type=\"text/javascript\" src=\"{$validation_link}\"></script>" );
					}
	
					$template->add_param( $param_prefix . $this->_Key_form_name, $form_name );
					
					if ( (isset($options['force']) && $options['force']) || $template_explicit || $form_explicit || !$this->_Form_applied ) {
	
						if ( !is_array($form_tag_attributes) ) {
							$extras = $form_tag_attributes;
							$form_tag_attributes = array();
							$form_tag_attributes['extras'] = $extras;
							unset($extras);
						}
	
						//if ( !isset($form_tag_attributes['name']) ) {
						//	$form_tag_attributes['name'] = $form_name;
						//}
					
						if ( !isset($form_tag_attributes['action']) ) {
							$form_tag_attributes['action'] = $this->form_action;
						}
	
						if ( !isset($form_tag_attributes['id']) ) {
							$form_tag_attributes['id'] = $form_name;
						}
	
	
						if ( !isset($form_tag_attributes['method']) ) {
							$form_tag_attributes['method'] = 'post';
						}
					
						if ( $this->form_auto_validation && $form->javascript_validation_enabled ) {
							$onsubmit = "try { if ( !validate_{$form_name}() ) return false; } catch( e ) {}";
						}
					
						
						if ( isset($form_tag_attributes['onsubmit']) ) {
							
							$form_tag_attributes['onsubmit'] = $onsubmit . $form_tag_attributes['onsubmit'];
							
						}
						else {
							$form_tag_attributes['onsubmit'] = $onsubmit;
						}
					
						foreach( $form_tag_attributes as $key =>$val ) {
						
							if ( $key == 'extras' ) {
								$attrib_str .= " $val ";
							}
							else {
								if ( "{$val}" != '' ) {
									$attrib_str .= "{$key}=\"" . str_replace('"', '\\"', $val) . "\" ";
								}
							}
						}
						
						$template->add_param( $param_prefix . $this->_Key_form_tag, "<form {$attrib_str}>" );
	
						if ( !$template_explicit && !$form_explicit ) {
							$this->_Form_applied = true;
						}
					}
					
					
					//if ( $this->is_postback() ) {
					//	$this->repopulate_form();
					//}
			
				}		
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	function repopulate_form() {

		try {		
			if ($template = $this->get_template() ) {
				$form = $this->get_form();
				$form->repopulate_template($template);
				
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function render_form( $options = array() ) {
		
		try {
			$options['render_form'] = true;
			return $this->render( $options );
		}
		catch( Exception $e) {
			throw $e;
		}
		
	}

	public function get_output( $options = array() ) {
		
		$this->prepare_render( $options );
		
		$output = null;
		$options['return_output'] = true;
		
		if ( $this->header_auto_render ) {
			$output .= $this->render_header( $options );
		}
		
		if ( $this->template ) {
			$this->template->parse();
			$output .= $this->template->get_output();
		} 

		if ( $this->footer_auto_render ) {
			$output .= $this->render_footer( $options );
		}
		
		return $output;
	}

	public function before_render( $options = array() ) {
		
	}

	public function prepare_render( $options = array() ) {
		
		$template_filename = $this->get_template_filename();
		
		if ( $this->template_auto_apply_controller_data ) {
		
			//
			// We use DONT_OVERWRITE here in case template params have been
			// altered purposely elsewhere in the controller
			//
			$template_options = array( self::KEY_TEMPLATE_PARAM_DONT_OVERWRITE => true );
		
			$this->apply_controller_data_to_template($this->template, $template_options);
		}
		
		if ( (is_array($options) && (array_key_exists('render_form', $options) && $options['render_form'])) || $this->form_methods && in_array($this->get_requested_method(), $this->form_methods) ) {
			$this->apply_form();			
		}

	}

	public function get_layout_object( $options = array() ) {
		
		try {
			
			if ( !$this->_Layout_obj || (isset($options['force_new']) && $options['force_new']) ) {
				LL::Require_class('HTML/PageLayout');
				$this->_Layout_obj = new PageLayout();
			}
			
			if ( $this->layout ) {
				$this->_Layout_obj->set_active_layout_key( $this->layout );
			}
			
			return $this->_Layout_obj; 
					
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function render( $options = array() ) {

		if ( $options && array_key_exists('return_output', $options) && $options['return_output'] ) {
			return $this->get_output();
		}
		
		//echo $this->get_output( $options );

		$this->prepare_render( $options );		

		$this->call_mixin_method( 'before_render' );
		$this->before_render( $options );

		if ( $this->header_auto_render ) {
			$this->render_header( $options );
		}
		
		if ( $this->template ) $this->template->print_output();

		if ( $this->footer_auto_render ) {
			$this->render_footer( $options );
		}
		
	}

	function render_header( $options = array() ) {
		
		try {
		
			$this->call_mixin_method( 'before_header' );
		
			$template_filename = $this->get_template_filename();
			
			if ( $this->header_auto_apply_controller_data ) {
			
				$header_template   = $this->get_header_template();
				$header_options = array( self::KEY_TEMPLATE_PARAM_DONT_OVERWRITE => true );
				$this->apply_controller_data_to_template($header_template, $header_options);
				
			}
			
			$layout   = $this->get_layout_object();
			
			if ( isset($options['return_output']) && $options['return_output'] ) {
				return $layout->get_header_output();
			}
			else {
				$layout->print_layout_header( array('target_template' => $template_filename) );
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function render_footer( $options = array() ) {
		
		try {
			
			$template_filename = $this->get_template_filename();
	
			if ( $this->footer_auto_apply_controller_data ) {
				$footer_template   = $this->get_footer_template();
				$footer_options = array( self::KEY_TEMPLATE_PARAM_DONT_OVERWRITE => true );
				$this->apply_controller_data_to_template($footer_template, $footer_options);
			}
	
			$layout   = $this->get_layout_object();

			if ( isset($options['return_output']) && $options['return_output'] ) {
				return $layout->get_footer_output();
			}
			else {
				$layout->print_layout_footer( array('target_template' => $template_filename) );
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function set_params( $params ) {

		$this->_Params = $params;
	}

	function merge_params( $params ) {

		if ( is_array($params) ) {
			$this->_Params = array_merge( $this->_Params, $params );
		}

	}

	function set_param( $name, $val ) {

		LL::require_class('Util/ArrayString');
			
		if ( ArrayString::String_contains_array_key_reference($name) ) {
			
			$arr_name   = ArrayString::Extract_array_name_from_string($name);
			$key_arr    = ArrayString::Extract_array_keys_from_string_as_array($name);
						
			
			ArrayString::Set_array_value_by_keys($this->_Params[$arr_name], $key_arr, $val);
			
			//list( $arr_name, $key_name ) = $this->split_array_reference($key);
			//$dataset[$arr_name][$key_name] = $value;
			
		}
		else {
			$this->_Params[$name] = $val;
		}

	}

	public function set_form( $form ) {
		
		$this->_Form = $form;
		
	}

	public function set_header_param( $key, $val ) {
		$this->get_layout_object()->set_header_template_param( $key, $val );
		
	}

	public function set_footer_param( $key, $val ) {
		
		$this->get_layout_object()->set_footer_template_param( $key, $val );
		
	}
	
	public function set_layout_param( $key, $val ) {
		
		$this->set_header_param( $key, $val );
		$this->set_footer_param( $key, $val );
		$this->template->set_param( $key, $val );
		
	}
	
	public function get_header_template() {
		
		$header = $this->get_layout_object()->get_header_template(array('target_template' => $this->get_template_filename()));
		
		if ( $this->header_auto_apply_controller_data ) {
			$this->apply_controller_data_to_template($header);
		}

		return $header;		
		
	}

	public function get_footer_template() {
		
		$footer = $this->get_layout_object()->get_footer_template(array('target_template' => $this->get_template_filename()));
		
		if ( $this->footer_auto_apply_controller_data ) {
			$this->apply_controller_data_to_template($footer);
		}
		
		return $footer;
		
	}

	public function add_body_onload( $val ) {
		
		$this->get_layout_object()->add_body_onload( $val );
		
	}


	function param_is_set($name) {
		
		LL::require_class('Util/ArrayString');
		
		$params = $this->get_params();
		
		if ( ArrayString::String_contains_array_key_reference($name) ) {
			
			$second_key = ArrayString::Extract_array_name_from_string($name);
			$key_string = ArrayString::Extract_array_keys_from_string($name);
			$key_string = "[{$second_key}]" . $key_string;
			
			return ArrayString::Array_value_isset_by_keys($params, $key_string);
		}
		else {
			if ( isset($params[$name]) ) {
				return true;
			}
		}
		
		return 0;
	}

	function get_param( $name, $options = array() ) {
		
		$param_val = null;
		
		$params = $this->get_params( $options );
		
		if ( isset($params[$name]) ) {
			$param_val = $this->_Params[$name];
		}
		else {
			
			LL::require_class('Util/ArrayString');
		
			if ( ArrayString::String_contains_array_key_reference($name) ) {
			
				$second_key = ArrayString::Extract_array_name_from_string($name);
				$key_string = ArrayString::Extract_array_keys_from_string($name);
				$key_string = "[{$second_key}]" . $key_string;
			
				$param_val = ArrayString::Get_array_value_by_keys($params, $key_string);
			}
		}
		
		return $param_val;

	}


	function get_params( $options = array() ) {

		try { 
			
			if ( !is_array($this->_Params) ) {
				$this->_Params = array();
			}
			
			if ( !$this->_Params_loaded || (isset($options['refresh']) && $options['refresh']) ) {
					
					$i = 0;
			
					$this->remove_disallowed_params();
					$added_params = array();
					$param_order = strrev($this->param_order);
				
					for ( $i = 0; $i < strlen($param_order); $i++ ) {
			
						$cur_char = substr($param_order, $i, 1);
			
						switch ( strtoupper($cur_char) ) {
				
							case 'G':
								if ( $this->params_from_get && !$this->params_explicit_only ) {
									//$this->_Params = array_merge( $this->_Params, $_GET );
									foreach( $_GET as $key => $val ) {
										
										if ( !Config::Get('controller.get_allow_underscore_params') ) {
											if ( substr($key, 0, 1) == '_' ) {
												continue;
											}
										}
										
										if ( $this->is_external_param_allowed($key) 
												&& !in_array($key, $added_params) ) {
											
												$this->_Params[$key] = $val;
												$added_params[] = $key;
										}
									}
								}
								break;
							case 'P':
								if ( $this->params_from_post && !$this->params_explicit_only  ) {
									foreach( $_POST as $key => $val ) {

										if ( !Config::Get('controller.post_allow_underscore_params') ) {
											if ( substr($key, 0, 1) == '_' ) {
												continue;
											}
										}
										
										if ( $this->is_external_param_allowed($key)
												&& !in_array($key, $added_params) ) {
											
												$this->_Params[$key] = $val;
												$added_params[] = $key;
										}
									}
								}
								break;
							case 'C':
								if ( $this->params_from_cookie && !$this->params_explicit_only  && is_array($_COOKIE) ) {
									foreach( $_COOKIE as $key => $val ) {
										
										if ( !Config::Get('controller.cookie_allow_underscore_params') ) {
											if ( substr($key, 0, 1) == '_' ) {
												continue;
											}
										}
										
										if ( $this->is_external_param_allowed($key)
												&& !in_array($key, $added_params) ) {
											
												$this->_Params[$key] = $val;
												$added_params[] = $key;
										}
									}
							}
								break;
							case 'S':
								if ( $this->params_from_session && !$this->params_explicit_only ) {
									if ( !isset($_SESSION) ) {
										session_start();
									}
									foreach( $_SESSION as $key => $val ) {
	
										if ( !Config::Get('controller.session_allow_underscore_params') ) {
											if ( substr($key, 0, 1) == '_' ) {
												continue;
											}
										}
											
										
										if ( $this->is_external_param_allowed($key)
													&& !in_array($key, $added_params) ) {
												
													$this->_Params[$key] = $val;
													$added_params[] = $key;
											}
									}
									break;
								}
						}
					}
	
				$this->_Params_loaded = true;
				
			}
	
			
			return $this->_Params;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function disallow_external_param( $key ) {
		
		try {
			$this->disallowed_external_params[] = $key;
			
			if ( isset($this->_Params[$key]) ) {
				unset($this->_Params[$key]);
			}
			
			$this->get_params(array('refresh' => true));
				
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function remove_disallowed_params( &$which_array = null ) {
		
		try {
			
			$found_disallowed = false;

			if ( $which_array === null ) {
				
				if ( is_array($_GET) ) $this->remove_disallowed_params($_GET);
				if ( is_array($_POST) ) $this->remove_disallowed_params($_POST);
				if ( is_array($_SESSION) ) $this->remove_disallowed_params($_SESSION);
				if ( is_array($_COOKIE) ) $this->remove_disallowed_params($_COOKIE);
				
			} 
			else {

				foreach( $which_array as $key => $val ) {
					if ( !$this->is_external_param_allowed($key) ) {
						unset($which_array[$key]);
						$found_disallowed = true;
					}
				}
				
			}

			return $found_disallowed;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function is_external_param_allowed( $key ) {
		
		try {
			
			if ( count($this->allowed_external_params) > 0 ) {
				if ( !in_array($key, $this->allowed_external_params) ) {
					return false;
				}
			}
			else {
				if ( count($this->disallowed_external_params) > 0 ) {
					if ( in_array($key, $this->disallowed_external_params) ) {
					
						return false;
					}
				}
			}
			
			return true;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function set_pagination_query_obj( $query_obj ) {
		
		$this->_Pagination_query_obj = $query_obj;
		
	}

	public function get_pagination_query_obj() {
		
		return $this->_Pagination_query_obj;
		
	}
	
	function get_template() {

		$new_template = false;
	
		if ( !$this->_Template ) {

			$new_template = true;
			LL::require_class('HTML/MarkupTemplate');

			$this->_Template = new MarkupTemplate();

			$this->_Template->set_controller( $this );
			if ( $this->template_auto_apply_controller_data ) {
				$this->apply_controller_data_to_template($this->_Template);
			}

			$this->_Template->set_tag_start( $this->_Template_tag_start );
			$this->_Template->set_tag_end( $this->_Template_tag_end );

		}			
			
		if ( $new_template || $this->template_filename_changed ) {

			if ( !($tmpl_filename  = $this->get_template_filename()) ) {
				trigger_error( 'No Action template found', E_USER_WARNING );
				return false;
			}
			
			$this->_Template->set_file( $tmpl_filename, $this->_Template_file_options );
			$this->template_file = $tmpl_filename;
		}

		return $this->_Template;

	}

	function apply_controller_data_to_template( &$template, $options = null ) {

		if ( is_array($controller_params = $this->get_params()) ) {
			$template_params = $template->get_params();
			
			foreach( $controller_params as $key => $val ) {
				if ( !array_val_is_nonzero($options, self::KEY_TEMPLATE_PARAM_DONT_OVERWRITE) || !isset($template_params[$key]) ) {
					$template->add_param( "{$this->_Prefix_template_params}{$key}", $val );
				}
			}
		}				

		$message = $this->get_message();
		
		if ( $message ) {
						
			if ( $this->message_auto_translate ) {
				$message = LL::Translate( $message, $this->message_require_translation);
			}
			
			$template->add_param( $this->param_name_user_message, $message );
			
			if ( $this->message_clear_on_render ) {
				LL::Clear_session_message();
			}
			
		}

		if ( $this->is_getback() ) {
			$template->add_param( $this->_Key_is_getback, 1 );
		}

		if ( $this->is_postback() ) {
			$template->add_param( $this->_Key_is_postback, 1 );
		}
		

		return $template;
	}

	function template_file_exists() {
		
		if ( $filename = $this->get_template_filename ) {
			LL::Require_class('HTML/MarkupTemplate');
			
			return MarkupTemplate::Template_file_is_readable($filename);
		}
		
		return false;
		
	}

	function get_template_filename() {

		$caller = null;

		if ( $this->template_file ) { //template file was set explicitly
			return $this->template_file;
		}
		else {


			if ( !$this->_Requested_method && !$this->_Requested_action_is_explicit ) {
			
				//
				// Here, we're trying to find out 
				// which method render() was called from, because 
				// we want to use that method's name in the template filename.
				// Loop through the backtrace to find the method called
				// before the first render method
				//
				$stack = debug_backtrace();
				$stack_index = 0;
				$found_render = false;
				if ( is_array($stack) ) {
			
					while ( isset($stack[$stack_index]) ) {
						$entry = $stack[$stack_index];
					
						if ( isset($entry['function']) && in_array($entry['function'], self::$Render_methods) ) {
						
							$found_render = true;
						
							if ( isset($stack[$stack_index+1]) ) {
								$caller = $stack[$stack_index+1]['function'];
							}
							
						}
						else {
							if ( $found_render ) {
								break;
							}
						}
						$stack_index++;

					}	
				}
			
				if ( $caller ) {
					$this->set_requested_action($caller, array('explicit' => false) );
				}
			}
			
			if ( !$this->_Requested_action ) {
				if ( $this->warn_on_missing_action ) {
					trigger_error( 'No requested controller action', E_USER_WARNING );
				}
				return null;
			}
		
			$template_filename = $this->template_filename_by_action($this->_Requested_action);
		}

		return $template_filename;
	}

 	public function get_template_subdir_name() {
 		
 		$use_mn = ( $this->_Template_uses_model_name !== null ) ? $this->_Template_uses_model_name : $this->template_uses_model_name;
 		
 		if ( $use_mn ) {
			if ( !($model = $this->get_model() )) {
				trigger_error( "Controller template is set to use model name, but no model is set.", E_USER_ERROR );
			}
			else {
				$template_subdir_name = $model->get_name();
			}
		}
		else {
			$template_name     = $this->get_controller_name();
			$template_subdir_name = $template_name;
		}
		
		if ( $this->views_subdir_name ) {
			$template_subdir_name = $this->views_subdir_name;
		}
		else {
		
			$lc = ( $this->views_subdir_name_lowercase !== null ) ? $this->views_subdir_name_lowercase : Config::Get('views.subdir_name_lowercase');
			$pl = ( $this->views_subdir_name_pluralize !== null ) ? $this->views_subdir_name_pluralize : Config::Get('views.subdir_name_pluralize');
			
			if ( $lc ) {
				$template_subdir_name = strtolower($template_subdir_name);
			}

			if ( $pl ) {
				$template_subdir_name .= 's';
			}

		}
	
		return $template_subdir_name;
 		
 		
 	}

	public function set_template_filename( $file, $options = null ) {
	
		return $this->set_template_file( $file, $options );
	}
	
	public function set_template_file( $file, $options = array() ) {
	
		if ( $this->template_file ) {
			$this->template_filename_changed = true;
		}
		
		$this->template_file = $file;
		$this->_Template_file_options = $options;
		$this->_Template = null;
	}

	public function get_requested_method() { 

		return $this->_Requested_method;
		
	}


	function set_requested_method( $method, $options = array() ) { 

		$this->_Requested_method = $method;
		
		$this->reset_template();
	}

	function set_requested_action( $action, $options = array() ) { 

		if ( isset($options['explicit']) ) {
			$this->_Requested_action_is_explicit = $options['explicit'];
		}
		else {
			$this->_Requested_action_is_explicit = true;
		}
		
		$this->_Requested_action = $action;
		$this->set_param('controller_action', $action);
		
		if ( $this->template_file ) {
			$this->template_filename_changed = true;
		}
		
		//if ( !isset($options['reset_template']) || $options['reset_template'] == true ) {
		//	$this->reset_template();
		//}
	}

	public function reset_template() {
		
		$this->template_file = null;
		$this->_Template_file_options = array();
		$this->_Template = null;
		
	}

	function get_requested_action() { 

		return $this->_Requested_action;

	}

	function template_filename_by_action( $action ) {

		$template_name = null;

		$use_mn = ( $this->_Template_uses_model_name !== null ) ? $this->_Template_uses_model_name : $this->template_uses_model_name;
 		
		if ( $use_mn  ) {
			if ( !($model = $this->get_model() )) {
				trigger_error( "Controller template is set to use model name, but no model is set.", E_USER_ERROR );
			}
			else {
				$template_name     = $model->get_name();
			}
		}

		if ( !$template_name ) {
			$template_name = $this->get_controller_name();
		}
		
		$template_dir_name = $this->get_template_subdir_name();
		
		$action 	= $this->format_action_key($action);
		$add_action = $this->format_action_key($this->_Key_action_add);
		
		if ( $this->add_uses_edit_template && ($action == $add_action) ) {
			$action = $this->format_action_key($this->_Key_action_edit);
		}
		
		$pl = ( $this->views_filename_pluralize !== null ) ? $this->views_filename_pluralize : Config::Get('views.filename_pluralize');
		$lc = ( $this->views_filename_lowercase !== null ) ? $this->views_filename_lowercase : Config::Get('views.filename_lowercase');
		
		if ( $pl ) {
			$template_name .= 's';
		}

		if ( $lc ) {
			$template_name = strtolower($template_name);			
		}		
		
		return "{$template_dir_name}/{$template_name}{$this->_Delimiter_controller_action}{$action}";
		

	}

	function format_action_key( $key ) {
		
		
		$ucfirst = false;
		
		if ( $this->views_action_key_ucfirst !== null ) {
			$ucfirst = $this->views_action_key_ucfirst;
		}
		else if ( Config::Is_set('views.action_key_ucfirst') ) {
			$ucfirst = Config::Get('views.action_key_ucfirst');
		}
		
		if ( $ucfirst ) {	
			$key = ucfirst(strtolower($key));
		}
		
		return $key;
		
		
	}
	
	function set_controller_class_name( $name ) {

		$this->_Controller_class_name = $name;

	}

	function get_controller_name( $which_class = null ) {

		return $this->get_name( $which_class );
	}

	function get_name( $which_class = null ) {

		if ( !$which_class ) {
			if ( !($which_class = $this->_Controller_class_name) ) {
				$which_class = $this;
			}
		}

		if ( is_object($which_class) ) {
			$class_name = get_class($which_class);
		}
		else {
			$class_name = $which_class;
		}

		$controller_name = substr( $class_name, 0, strrpos($class_name, $this->_Class_suffix_controller) );


		return $controller_name;

	}

	function get_template_param_name( $options = null ) {
		
		$use_mn = ( $this->_Template_uses_model_name !== null ) ? $this->_Template_uses_model_name : $this->template_uses_model_name;
 		
		if ( $use_mn  ) {
			if ( !($model = $this->get_model() )) {
				trigger_error( "Controller template is set to use model name, but no model is set.", E_USER_ERROR );
			}
			else {
				
				if ( !isset($options['keep_number_positioning']) ) {
					$options['keep_number_positioning'] = $model->camel_case_keep_number_positioning;
				}
				
				$param_name = $model->get_name();
			}
		}
		else {
			$param_name = $this->get_name();
		}
		
		return strtolower(camel_case_to_underscore($param_name, $options));
		
	}

	/*
	function pluralize_controller_template_directory( $val = null ) {

		if ( $val === null ) {
			return $this->_Template_subdir_name_pluralize;
		}
		else if ( $val ) {
			$this->_Template_subdir_name_pluralize = true;
		}
		else {
			$this->_Template_subdir_name_pluralize = false;
		}
	}


	function pluralize_controller_template_name( $val = null ) {

		if ( $val === null ) {
			return $this->_Template_filename_pluralize;
		}
		else if ( $val ) {
			$this->_Template_filename_pluralize = true;
		}
		else {
			$this->_Template_filename_pluralize = false;
		}
	}
	*/

	function set_controller_action_delimiter( $delim ) {

		$this->_Delimiter_controller_action = $delim;

	}

	function is_postback() { 

		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			return true;
		}

		return 0;

	}

	function is_getback() { 

		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			return true;
		}

		return 0;

	}
	
	public function is_ajax() { 

		if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ) {
			return true;
		}

		return 0;

	}
	

	
	public function set_message_success( $method, $message, $options = null ) {
		
		$this->_Messages_success[$method] = $message;
		
	}

	public function set_message_fail( $method, $message, $options = null ) {
		
		$this->_Messages_fail[$method] = $message;
		
	}

	public function get_message_success( $method ) {
		
		if ( isset($this->_Messages_success[$method]) ) {
			return $this->_Messages_success[$method];
		}
		
		return null;
		
	}

	public function get_message_fail( $method ) {
		
		if ( isset($this->_Messages_fail[$method]) ) {
			return $this->_Messages_fail[$method];
		}
		
		return null;
		
	}

	
	public function message_append( $message, $options = null ) {
		
		try {
			$newline = ( isset($options[self::KEY_NEWLINE_CHAR]) ) ? self::KEY_NEWLINE_CHAR : '<br />';
		
			if ( $this->message ) {
				$this->message .= $newline . $message;
			}
			else {
				$this->message = $message;	
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}


	protected function set_message( $message ) {

		LL::Set_session_message($message);
		
		/*
		$param_name = $this->param_name_user_message;

		$this->params->$param_name = $message;

		if ( $this->_Template ) {
			$this->_Template->add_param( $param_name, $message );
		}
		
		if ( $this->message_from_session ) {
			@session_start();
			$_SESSION['LL'][__CLASS__][$this->param_name_user_message] = $message;
		}
		*/

	}

	public function get_message() {

		return LL::Get_session_message();

		/*
		if ( $this->message_from_get ) {
			if ( isset($_GET[$this->param_name_message]) && $_GET[$this->param_name_message]) {
				return $_GET[$this->param_name_message];
			}
		}
		if ( $this->message_from_post ) {
			if ( isset($_POST[$this->param_name_message]) && $_POST[$this->param_name_message]) {
				return $_POST[$this->param_name_message];
			}
		}
		if ( $this->message_from_session ) {
			@session_start();
			$message = null;
			
			if ( isset($_SESSION['LL'][__CLASS__][$this->param_name_user_message]) && $_SESSION['LL'][__CLASS__][$this->param_name_user_message] ) {
				$message = $_SESSION['LL'][__CLASS__][$this->param_name_user_message];
				
			}
				
			return $message;
		}

		return null;
		*/
	}

    public function get_redirect_success( $key = null ) {
    
    	if ( $this->redirect_success ) {
    		if ( !is_array($this->redirect_success) ) {
    			return $this->redirect_success;
    		}
    		else {
    			if ( $key ) {
    				if ( isset($this->redirect_success[$key]) ) {
	    				return $this->redirect_success[$key];
    				}
    			}
    		}
    	}
    
    	return null;
    }

    public function set_redirect_success( $key = null, $redirect = null ) {
    
    	if ( $redirect === null ) {
    		$this->redirect_success = $key;
    	}
    	else {
    		$this->redirect_success[$key] = $redirect;
    	}

    }
    
    public function set_redirect_fail( $key = null, $redirect = null ) {
    
    	if ( $redirect === null ) {
    		$this->redirect_fail = $key;
    	}
    	else {
    		$this->redirect_fail[$key] = $redirect;
    	}

    }

    
    public function get_redirect_fail( $key = null ) {
    
    	if ( $this->redirect_fail ) {
    		if ( !is_array($this->redirect_fail) ) {
    			return $this->redirect_fail;
    		}
    		else {
    			if ( $key ) {
    				if ( isset($this->redirect_fail[$key]) ) {
	    				return $this->redirect_fail[$key];
    				}
    			}
    		}
    	}
    
    	return null;
    }
    
    
    public function redirect( $where_to ) {
    	
    	LL::Require_class('Util/Redirect');
    	Redirect::To( $where_to );
    	
    }

	protected function set_method_access( $method_name, $options = array() ) {
		
		try {
			
			$method = $this->new_method_object_by_name($method_name);
			
			if ( is_array($options) ) {
				foreach( $options as $key => $val ) {
					$method->$key = $val;
				}
			}
			
			
			$this->_Method_access_objs[$method_name] = $method;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_method_access_setup( $method_name ) {
	
		try {
			
			//
			// The following is in place to support
			// the fact that method access used to be applied 
			// through a call to set_method_access, 
			// whereas now it is done through the public method_access member
			//
			if ( !$this->_Method_access_was_read ) {
				$this->_Method_access_was_read = true;

				if ( $this->method_access ) {
					foreach( $this->method_access as $cur_method => $setup ) {
						$method_obj = $this->new_method_object_by_name($cur_method);
			
						if ( is_array($setup) ) {
							foreach( $setup as $key => $val ) {
								$method_obj->$key = $val;
							}
						}
						
						$this->_Method_access_objs[$cur_method] = $method_obj;
					}
				}
			}
			
			if ( isset($this->_Method_access_objs[$method_name]) ) {
				return $this->_Method_access_objs[$method_name];
			}
			
			return null;
			
		}
		catch( Exception $e ) {
			throw $e;
		}

		
	}

	public function validate_method_access( $method_name, $options = array() ) {
	
		try {
			
			if ( !Config::Get('auth.enabled') ) {
				return true;
			}
			else {
				
				if ( Config::Get('auth.validation_class') ) {
					$class = Config::Get('auth.validation_class');
				}
				else {
					$class = 'AppControl/ControllerAuth_Object';
				}
				
				$class_name = LL::Class_name_from_location_reference($class);
				
				LL::require_class($class);
								
				if ( isset($this->_Method_access_objs[$method_name]) ) {
					return call_user_func_array( array($class_name, 'Validate_method_access'), array($this->_Method_access_objs[$method_name], $options) );
					
				}
				else {
					if ( defined('CONTROLLER_AUTH_METHOD_REQUIRE_EXPLICIT_ENTRY') && constant('CONTROLLER_AUTH_METHOD_REQUIRE_EXPLICIT_ENTRY') ) {
						trigger_error( "Missing method entry for {$method_name}", E_USER_ERROR );
						exit(1);
					}
					else {
						return call_user_func_array( array($class_name, 'Validate_controller_access'), array($this, $options) );
					}
				}
					
				
			}
 
			//
			// Should never get here
			//
			trigger_error( "Invalid Permission", E_USER_ERROR );
			exit(1);
			
			
		}
		catch( UserLoginRequiredException $e ) {
			throw $e;
		}
		catch( UserNoPermissionException $e ) {
			throw $e;
		}
		catch( Exception $e ) {
			//echo LL::get_errors();
			
			trigger_error( "Unknown error in " . __METHOD__, E_USER_ERROR );
			exit;
		}
		
	}
	

	public function new_method_object_by_name( $method_name ) {
		
		try {
			LL::require_class('AppControl/ControllerMethod');

			$method = new ControllerMethod();
			$method->name = $method_name;
			$method->parent_controller = $this;
			$method->parent_controller_name = $this->get_name();
			
			return $method;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function apply_default_controller_data( $options = null ) {
		
		try {
			
			$this->apply_user_params_to_controller($options);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function apply_user_params_to_controller( $options = null ) {

		try {
			
			$user_obj_param = $this->param_name_active_user_obj;
			$username_param = $this->param_name_active_username;
			$uid_param = $this->param_name_active_user_id;
	
			$this->disallow_external_param($username_param);
			$this->disallow_external_param($uid_param);
			
			if ( Config::Get('auth.enabled') && $this->_Active_user_params_enabled ) {
				
				LL::Require_class('Auth/AuthSessionManager');
				
				$session = AuthSessionManager::Get_active_session();
					
				if ( $session->is_authenticated() ) {
				
					$user = $session->get_user_object();
				
					$this->$user_obj_param = $user;
					$this->$username_param = $user->name;
					$this->$uid_param = $user->id;

					$this->set_header_param( $username_param, $user->name );
					$this->set_header_param( $uid_param, $user->id );

					$this->set_footer_param( $username_param, $user->name );
					$this->set_footer_param( $uid_param, $user->id );
				}
				
			}
			else {
			
				$this->$user_obj_param = null;
				$this->$username_param = null;
				$this->$uid_param = null;
				
				$this->set_header_param( $this->$username_param, null );
				$this->set_header_param( $this->$uid_param, null );
				$this->set_footer_param( $this->$username_param, null );
				$this->set_footer_param( $this->$uid_param, null );
						
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}

		
	}

	public function apply_default_permission_entries( $options = null ) {
	
		try {
			
			$active_user_found = false;
			
			if ( Config::Get('auth.enabled') ) {
								
				LL::require_class('AppControl/ControllerAuth');

				$controller_from = isset($options[self::$Key_controller_from]) ? $options[self::$Key_controller_from] : $this;
				$controller_to   = isset($options[self::$Key_controller_to]) ? $options[self::$Key_controller_to] : $this;
			
				try { 
					$active_user = $this->get_active_user_obj();
					$active_user_found = true;
				}
				catch( NoActiveUserFoundException $e ) {
					$active_user_found = false;
				}

				$method_names = array( $controller_from->method_name_add, 
										$controller_from->method_name_edit, 
										$controller_from->method_name_delete );

				if ( !isset($options['keep_number_positioning']) ) {
					$case_options['keep_number_positioning'] = $controller_from->camel_case_keep_number_positioning;
				}
				else {
					$case_options['keep_number_positioning'] = $options['keep_number_positioning'];
				}
					
				$controller_base_param_name = strtolower(camel_case_to_underscore($controller_from->get_template_param_name(), $case_options));
				
				foreach( $method_names as $method_name ) {
					
					$method_param_name = self::$Prefix_allow_method . $controller_base_param_name . '_' . $method_name;
					$this->disallow_external_param($method_param_name);
					
					
					if ( $method = $controller_from->get_method_access_setup($method_name) ) {
												
						if ( $active_user_found && ControllerAuth::User_has_method_access($active_user, $method) ) {
							$controller_to->set_param( $method_param_name, true );
						}
						else {
							$controller_to->set_param( $method_param_name, false);
						}
				
					}
					else {
						
						if ( $active_user_found && ControllerAuth::User_has_controller_access($active_user, $controller_from, $options) ) {
							$controller_to->set_param( $method_param_name, true);
						}
						else {
							$controller_to->set_param( $method_param_name, false);
						}
					}
				}
				
				$controller_allow_param = self::$Prefix_allow_method . $controller_base_param_name . '_controller';
				$this->disallow_external_param($controller_allow_param);
				
				 if ( $active_user_found && ControllerAuth::User_has_controller_access($active_user, $controller_from, $options) ) {
				 	
				 	$controller_to->set_param( $controller_allow_param, true ); 
				 }
				 else {
				 	$controller_to->set_param( $controller_allow_param, false );
				 }
			}
		}
		catch( Exception $e ) {
			
			throw $e;
		}
		
	}	
	
	function get_db_interface() {
		
		try {
			if ( !$this->_DB ) {
				LL::Require_class('PDO/PDOFactory');
				$this->_DB = PDOFactory::Instantiate($this->use_db_config);
			}
			
			return $this->_DB;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	function get_pagination_obj( $num_items_total, $options = null ) {
		
		try {
			
			LL::require_class('HTML/Pagination');
			LL::require_class('SQL/SQLOptions');
			
			$pager = new Pagination();
			$pager->num_items_total = $num_items_total;
			$pager->items_per_page  = $this->get_items_per_page();
			$pager->current_page	= ( $this->page && is_numeric($this->page) ) ? $this->page : 0;
			$pager->page_offset		= ( $this->page_offset && is_numeric($this->page_offset) ) ? $this->page_offset : null;
			
			if ( is_array($this->paginate) ) {
				foreach( $this->paginate as $key => $val ) {
					$pager->$key = $val;
				}
			}
			
			return $pager;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function get_pagination_option( $key ) {

		if ( is_array($this->paginate) ) {
			if ( isset($this->paginate[$key]) ) {
				return $this->paginate[$key];
			}
		}
		
		return null;
	}
	
	public function get_items_per_page() {
		
		$items_per_page = 0;
		
		if ( $this->paginate ) {
			if ( is_array($this->paginate) ) { 
            	if ( isset($this->paginate['items_per_page']) && is_numeric($this->paginate['items_per_page']) ) {
	            	$items_per_page = $this->paginate['items_per_page'];
	            }
                else {
                	trigger_error( "Paginate requires that items_per_page be set and that it is numeric", E_USER_WARNING );
                }
            }
            else {
            	$items_per_page = $this->paginate;
            }
		}
		
		return $items_per_page;
		
	}
	
	public function header_allow_duplicate( $yesno ) {
		
		try {
			$layout = $this->get_layout_object();
			$layout->header_allow_duplicate($yesno);
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function footer_allow_duplicate( $yesno ) {
		
		try {
			$layout = $this->get_layout_object();
			$layout->footer_allow_duplicate($yesno);
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function postback_render_success( $action = null, $options = null ) {
		
		try {
			return $this->render_success( $action, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function postback_render_fail( $action = null, $options = null ) {
		
		try {
			return $this->render_fail( $action, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	
	public function render_success( $options = array(), $old_options = null ) {
 
 		try {
 			
 			if ( !is_array($options) ) {
 				
 				//deprecated call
				$action = $options;
 				$options = $old_options;
 				$options['action'] = $action;
 			}
 			
 			if ( !isset($options['action']) || !$options['action'] ) {
 				$action = $this->get_requested_action();
 			}
 			
 			if ( $redirect = $this->get_redirect_success($action) ) {
				$this->redirect($redirect);
			}
			else {
				
				
				/*
				if ( $action == 'delete' ) {
					
					$this->set_message( $this->message_delete_success );
					$this->set_requested_action('list');
					$this->show_list();
				}
				
				else {				
				*/
					if ( isset($options['form_repopulate']) ) {
						$this->form_repopulate = $options['form_repopulate'];
					}

					
					if ( isset($options['message']) ) {
						$this->set_message( $options['message'] );
					}
					else {
						if ( !$this->message ) {
							if ( isset($this->_Messages_success[$action]) ) {
								$this->set_message($this->_Messages_success[$action]);
							}
						}
					}
				
					//$this->apply_form();
					return $this->render( $options );
				//}
			}
 		}
 		catch( Exception $e ) {
			throw $e;
		}
	}  

	public function render_fail( $options = array(), $old_options = null ) {
 
 		try {
 			
 			if ( !is_array($options) ) {
 				
 				//deprecated call
 				$action = $options;
 				$options = $old_options;
 				$options['action'] = $action;
 				
 			}
 			
 			if ( !isset($options['action']) || !$options['action'] ) {
 				$action = $this->get_requested_action();
 			}
 			
 			if ( $redirect = $this->get_redirect_fail($action) ) {
				$this->redirect($redirect);
			}
			else {
				
				if ( isset($options['form_repopulate']) ) {
					$this->form_repopulate = $options['form_repopulate'];
				}
				if ( isset($options['message']) ) {
					$this->set_message( $options['message'] );
				}
				else {
					if ( !$this->message ) {
						if ( isset($this->_Messages_fail[$action]) ) {
							$this->set_message($this->_Messages_fail[$action]);
						}
					}
				}
							
				//$this->apply_form();
				return $this->render( $options );
			}
 		}
 		catch( Exception $e ) {
			throw $e;
		}
	}   	
 
	public function get_active_user_obj() {
	
		try {
			return $this->get_active_user();	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function get_active_user() {
		
		try { 
			if ( !$this->_Active_user_obj ) {
					
				LL::require_class('Auth/AuthSessionManager');
					
				$session = AuthSessionManager::Get_active_session();
				if ( $session->authenticate() ) {
					$this->_Active_user_obj = $session->get_user_object();
				}
				else {
					throw new NoActiveUserFoundException();
				}
	
			}			
				
			return $this->_Active_user_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}	
		
	}
	
	public function authentication_is_enabled() {
		
		try {

			if ( defined('CONTROLLER_AUTHENTICATION_ENABLED') ) {
				if ( constant('CONTROLLER_AUTHENTICATION_ENABLED') ) {
					return true;
				}
				else {
					return false;
				}					
			}
			else {
					
				LL::Require_file('Auth/Auth.conf.php');
				
				if ( Config::Get('auth.authentication_enabled') ) {
					return true;
				}
			}
			
			return false;
			
		}	
		catch( Exception $e ) {
			trigger_error( "Error determining if authentication is enabled: " . $e->getMessage(), E_USER_ERROR);
			exit;
		}
		
	}
	
	public function flush_static_cache( $options = array() ) {
		
		try {
			
			if ( defined('STATIC_CACHE_ENABLED') && constant('STATIC_CACHE_ENABLED') ) {			

				LL::Require_class('StaticCache/StaticFile');
					
				//$cache = new StaticFile();
				
				if ( $this->static_cache_flush ) {

					foreach( $this->static_cache_flush as $action_method => $flush_data ) {
						
						if ( isset($flush_data['method']) ) {
							
							if ( isset($flush_data['controller']) ) {
								$controller = $flush_data['controller'];
							}
							else {
								$controller = $this->get_name();
							}
						
							if ( $this->get_requested_method() == $action_method ) {

								
								if ( is_scalar($flush_data['method']) ) {
									$check_methods = array($flush_data['method']);
								}
								else {
									$check_methods = $flush_data['method'];
								}
							
								$static_id_field = isset($flush_data['id']) ? $flush_data['id'] : null;
								
								if ( is_scalar($static_id_field) ) {
									$static_id_field = array($static_id_field);
								}
								
								if ( is_array($static_id_field) ) {
									foreach( $static_id_field as $cur_field ) {
										
										$wildcard = '';
										
										if ( substr($cur_field, -1) == '*' ) {
											$wildcard = '*';
											$cur_field = substr($cur_field, 0, -1 ); 
										}
										
										$static_id_val[] = $this->$cur_field . $wildcard;
										
									}	
								}
								
								foreach( $check_methods as $cache_method ) {
								
									StaticFile::Flush_by_controller_method($controller, $cache_method, $static_id_val);
									//$cache->file_basename = StaticFile::Cache_file_basename_by_controller_method($controller, $cache_method, $static_id_val );
									//$cache->flush();
								}
							}
							
						}
					}
					
				}
				
				/* done by load_uri now 
				if ( $this->template->template_file_was_outdated || 
					 $this->get_header_template()->template_file_was_outdated || 
					 $this->get_footer_template()->template_file_was_outdated 
					) {
					
					StaticFile::Flush_by_controller_method($this->get_name(), $this->get_requested_method(), '*' );
				}
				*/
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function quit( $message = null, $options = array() ) {
		
		static $quitting = false;
		
		if ( !$quitting ) {
			$quitting = true;

			if ( !$message ) {
				$message = __METHOD__ . ' Called';
			}			
			
			$this->print_message( $message, $options );
			
		}
		
		exit;
		
	}

	public function print_message( $message, $options = array() ) {
		
		try { 
			LL::Require_class('MarkupTemplate');
			
			if ( isset($options['suppress_layout']) && $options['suppress_layout'] ) {
				$options['suppress_header'] = true;
				$options['suppress_footer'] = true;
			}
		
			if ( isset($options['suppress_header']) && $options['suppress_header'] == true ) {
				$this->header_auto_render = false;
			}

			if ( isset($options['suppress_footer']) && $options['suppress_footer'] == true ) {
				$this->footer_auto_render = false;
			}
		
			if ( !($template_file = Config::Get('output.print_message_template_file')) ) {
				$template_file = 'misc/display_message';
			}
			
			if ( !Config::Get('output.text_onlyt') && MarkupTemplate::Template_file_is_readable($template_file) ) {
				$this->set_template_file( $template_file );
				$this->message = $message;
				$this->render();
			}
			else {
				if ( $this->header_auto_render ) {
					$this->render_header();
				}
			
				echo $message;
			
				if ( $this->footer_auto_render ) {
					$this->render_footer();
				}
			
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function call_mixin_method( $method, $params = array(), $options = array() ) {

		try { 
			
			if ( $this->mixin_method_exists($method) ) {
				$mixin = $this->get_mixin_obj();
				return call_user_func(array($mixin, $method), $params);
			}
		}
		catch( Exception $e ) {
			throw $e;
		}		

	}

	public function mixin_method_exists( $method, $options = array() ) {

		try { 
			
			if ( $mixin = $this->get_mixin_obj() ) {
				if ( method_exists($mixin, $method) ) {
					return true;
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}		

	}
	
	public function get_mixin_obj() {
		
		
		if ( !$this->_Mixin_obj ) {
			if ( LL::Include_class('ControllerMixin') ) {
				
				$this->_Mixin_obj = new ControllerMixin();
				$this->_Mixin_obj->set_controller( $this );
				return $this->_Mixin_obj;
			}
			else {
				LL::Require_class('AppControl/ControllerMixinParent');
				return new ControllerMixinParent();
			}
		}
		
		return $this->_Mixin_obj;
	}
}

//
// This class is currently used exclusively 
// so that the __get() function in the app controller
// can support $this->params->something
//
class AppControllerParams {
	
	protected $_Controller;
	
	public function __construct( $controller ) {
		$this->_Controller = $controller;
	}
	
	public function __get( $key ) {
		return $this->_Controller->get_param($key);
	}

	public function __set( $key, $val ) {
		return $this->_Controller->set_param($key, $val);
	}
	
}

//
// This class is currently used exclusively 
// so that the __get() function in the app controller
// can support $this->components->something
//
class AppControllerComponents {
	
	protected $_Controller;
	protected $_Loaded_components = array();
	
	public function __construct( $controller ) {
		$this->_Controller = $controller;
	}
	
	public function __get( $key ) {
		
		try {
			
			$class_name = $this->format_name($key);
				
			if ( !isset($this->_Loaded_components[$class_name]) ) {
				$this->load_component($class_name);
				$obj = new $class_name;
				$obj->controller = $this->_Controller;
				$this->_Loaded_components[$class_name] = $obj;
			}
			
			return $this->_Loaded_components[$class_name];
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		return $this->_Controller->get_param($key);
	}

	public function reset( $name ) {
		
		$name = $this->format_name($name);
		
		if ( isset($this->_Loaded_components[$name]) ) {
			unset($this->_Loaded_components[$name]);
		}
		
	}

	public function format_name( $name ) {
		
		$name = underscore_to_studly_caps($name);
		$suffix = 'Component';
		
		if ( substr($name, 0 - strlen($suffix)) != LL::$Suffix_component_name ) {
			$name .= $suffix;
		}
				
		return ucfirst($name);
		
	}

	public function load_component( $name, $options = array() ) {
		
		try {
			
			$name = $this->format_name( $name );
			
			if ( !LL::Include_component($name) ) {
				throw new NotFoundException( "Couldn't load component: {$name}" );
			}
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
}

}

/* Fuse compatibility */
class FuseApplicationController extends ApplicationController{
	
}

?>
