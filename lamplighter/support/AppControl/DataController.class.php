<?php

LL::require_class('AppControl/ApplicationController');
LL::Require_interface('AppControl/DataControllerInterface');

class DataController extends ApplicationController implements DataControllerInterface {

		const KEY_GET_MODEL = 'model';
		const KEY_ITERATOR_NAME = 'iterator_name';
		
		public $paginate = null;
		public $model_name;
		public $model_library;
		public $model_auto_identify = true;
		public $form_methods = array('add', 'edit' );
		public $form_name;
				
		public $list_options = array();
		public $view_options = array();
		public $edit_options = array();
		public $add_options  = array();
		
		public $search_fields = array();
		public $search_settings = array();
		
		public $model_identify_from_params = true;
		
		public $static_cache_flush = 
			array( 
					'add' => array( 'method' => array('show_list', 'view'), 'id' => 'id*' ), 
					'edit' => array( 'method' => array('show_list', 'view'), 'id' => 'id*' ),
					'delete' => array( 'method' => array('show_list', 'view'), 'id' => 'id*' ),
					'sortable_update' => array( 'method' => 'show_list', 'id' => 'id*' )

			);

    	//protected $_Model_obj;
    	protected $_Loaded_models = array();
    	protected $_Loaded_forms = array();
		protected $_Model_library;
		protected $_Search_obj;
		protected $_Search_query_obj;
		protected $_Absolute_result_count = null;

		protected $_Form_handling_actions = array('add', 'edit');

		public function __get( $what ) {
			
			if ( $what == self::KEY_GET_MODEL ) {
				return $this->get_model();
			}
			
			try { 
				if (  $model = $this->loaded_model_by_table_name($what) ) {
					return $model;
				}
				else if (  $model = $this->loaded_model_by_table_name(pluralize($what)) ) {
					return $model;
				}
				
				/*
				else if ( $what == depluralize($this->get_model()->table_name) ) {
					return $this->get_model();
				}
				*/
							
			}
			catch( ModelNotFoundException $e ) {
				// ignore this exception type here	
			}	
			
					
			return parent::__get($what);
			
		}

		public function __set($key, $val) {
			
			if ( $key == self::KEY_GET_MODEL ) {
				$this->set_model($val);
			}
			else {
				return parent::__set($key, $val);
			}
		}
		
		public function get_model_library() { 

			return ( $this->model_library ) ? $this->model_library : $this->_Model_library;
		}


		public function get_model_reference_path() { 

			$reference = null;

			if ( $library = $this->get_model_library() ) {
				$reference .= $library . constant('DIRECTORY_SEPARATOR');
			}
			
			$reference .= $this->get_model_name();
			
			return $reference;
		}

		public function get_model_obj( $options = array() ) {

			return $this->get_model( $options );
		}


		public function set_model_name( $name ) {
			$this->model_name = $name;
		}

		public function set_model( DataModel $model ) {
			
			return $this->add_model( $model );
		
		}
		
		public function add_model( DataModel $model ) {
			
			$class_name = get_class($model);
			
			$this->_Loaded_models[$class_name] = $model;
			//$this->_Model_obj = $model;
			
		}

		public function set_model_library( $library ) {
		
			$this->_Model_library = $library;
		}

		public function get_model_name() {
		
			if ( $this->model_name ) {
				return LL::Class_name_from_location_reference($this->model_name);
			}
			else {
				return $this->get_controller_name();			
			}
		
		}

		public function loaded_model_by_table_name( $table ) {
			
			try {
				foreach( $this->_Loaded_models as $model ) {
					
					if ( $model->table_name == $table ) {
						return $model;
					}
				}
				
				return null;	
			}
			catch( Exception $e ) {
				throw $e;
			}
			
		}

		public function get_model( $options = array() ) {

			try {
				
				$ret = null;

				if ( !isset($options['class_name']) || !$options['class_name'] ) {
				
					//
					// Use default model
					//
				
					$class_name 	= $this->get_model_name();
					$class_library = $this->get_model_library();
					
				}
				else {
					
					$class_name = $options['class_name'];
					
					$class_library = LL::Class_library_from_location_reference($class_name);
					$class_name = LL::Class_name_from_location_reference($class_name);
					
					
				}
				
				if ( isset($this->_Loaded_models[$class_name]) && (!isset($options['refresh']) || !$options['refresh'])  ) {
					$ret = $this->_Loaded_models[$class_name];
				}
				else {
								
					$class_path = ( $class_library ) ? "{$class_library}/{$class_name}" : $class_name;
					
					if ( LL::Include_model($class_path) ) {

						$ret = new $class_name;
						$ret->set_calling_controller($this);
						$this->_Loaded_models[$class_name] = $ret;
					}
					else {
						
						throw new ModelNotFoundException( $class_name );
					}
				}

				if ( $ret ) {
					if ( $this->model_auto_identify ) {
						if ( !$ret->was_changed() && !$ret->id ) {
							if ( $id = $this->get_param($this->_Key_id) ) {
								$ret->set_id($id);
							}
							else {
								
								if ( $this->model_identify_from_params ) {
									if ( is_array($params = $this->get_params()) ) {
										foreach( $params as $key => $val ) {
											
											if ( $ret->is_unique_key($key) ) {
												$ret->$key = $val;
											}
											else {
												
												$model_key = $ret->column_key_by_name($key);
												if ( $ret->is_unique_key($model_key) ) {
													$ret->$model_key = $val;
												}
											}
											
										}
									}
								}						
								
							}
						}
					}
				}
				
				return $ret;
			}
			catch( Exception $e ) {
				throw $e;
			}

	}
	
	public function get_form( $options = array() ) { 

		try { 

			if ( !isset($options['class_name']) || !$options['class_name']) {
				try {
					$class_name = $this->get_model_name();
				}
				catch( ModelNotFoundException $mnf ) {
					$class_name = get_class($this);
				}
			}
			else {
				$class_name = $options['class_name'];
			}
			
			if ( !$this->_Loaded_forms[$class_name] ) {

				try { 				
					$model = $this->get_model_obj($options);
					$form = $model->get_form();
				}
				catch( ModelNotFoundException $mnf ) {
					LL::require_class('Form/InputFormValidator');
					$form = new InputFormValidator();
				}
				
				if ( !$form->has_completed_setup(self::FORM_SETUP_KEY_INITIALIZED) ) {
					$this->form_initialize($form);
				}
			
				$form->set_validation_filename( $this->generate_form_javascript_filename() );
				$this->_Loaded_forms[$class_name] = $form;
			}
				
		
			return $this->_Loaded_forms[$class_name];
			
		}
		catch( Exception $e ) {
			throw $e;
		}

	}



	public function get_form_value( $field_name, $options = null ) {
		
		try {
			
			if ( $this->is_postback() ) {
				$form = $this->get_form();
			
				//$form_dataset = $form->get_dataset();
				//if ( isset($form_dataset[$field_key]) ) {
				if ( $this->form->get($field_name) ) {
					
					if ( !array_val_is_nonzero($options, 'allow_html_tags') ) {
						return $form->parse_form_text($form->get_unparsed($field_name));
					}
					else {
						return $form->get_unparsed($field_name);
					}
				}
				/*
				else {
					$full_field_name = $model->form_field_name_by_field_key($field_key);
					
					if ( !array_val_is_nonzero($options, 'allow_html_tags') ) {
						return $form->parse_form_text($form->get_unparsed($full_field_name));
					}
					else {
						return $form->get_unparsed($full_field_name);
					}
					
				}
				*/
					
			}
			else {
				
				if ( $this->param_is_set($field_name) ) {
					$form_value = $this->get_param($field_name);
					if ( array_val_is_nonzero($options, 'for_repopulate') && !array_val_is_nonzero($options, 'allow_html_tags')) {
						$form_value = htmlspecialchars($form_value);
					}
					
					return $form_value;
				}
				
			}	
			
			if ( $model = $this->get_model_obj() ) {
				return $model->get_form_value( $model->field_key_from_form_input_name($field_name), $options );
			}
			
			return null;
			
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function before_add() {
		
	}

	//
	// Used to merge explicit $options and controller options for a method
	//
	public function get_method_options( $prefix, $options = array() ) {
	
		$option_member = $prefix . '_options';
	
	  	if ( is_array($this->$option_member) && $this->$option_member ) {
       	
       		if ( is_array($options) ) {
           		$options = array_merge( $this->$option_member, $options );
           	}
	        else {
    	    	$options = $this->$option_member;
        	}
        }
        else {
        	if ( !is_array($options) ) {
        		$options = array();
        	}
        }
        
        return $options;
	}
	
	

    public function add( $options = array() ) {

			try {
               
               $options = $this->get_method_options( 'add', $options );

				//$this->before_add();
				//$this->call_mixin_method('before_add');

				$is_postback = ( isset($options['is_postback']) ) ? $options['is_postback'] : $this->is_postback();
				
										
                if ( $is_postback ) {
						
						$model = $this->get_model();
                        $model->set_form( $this->get_form() );
                        
                        $new_id = $model->add_from_form();

						$this->render_on_success = $this->get_flag_render_on_success($options);
						
						//if ( !is_array($options) || !array_key_exists('skip_after_add', $options) || !$options['skip_after_add']) {
						//	$this->call_mixin_method('after_add');
						//	$this->after_add();
						//}
						
						return $new_id;
						
                }
                else {
                		//$this->apply_form();
                        if ( !isset($options['render']) || $options['render'] == true ) {
                        	$this->render();
                        }
                }
			}
			catch( UserDataException $e ) {
				
				$this->method_failed(__FUNCTION__, true); 
				$render_on_fail = $this->get_flag_render_on_fail($options);
				
				$is_ajax = ( isset($options['is_ajax']) ) ? $options['is_ajax'] : $this->is_ajax();
				
				if ( $render_on_fail && !$is_ajax ) {
				
					if ( $redirect = $this->get_redirect_fail(__FUNCTION__) ) {
						$this->redirect($redirect);
					}
					else {
	
						$message = null;

						if ( $message = $this->get_message_success('add') ) {
							$message = LL::Translate($message) . Config::Get('output.newline');
						}
				
						$message .= $e->getMessage();
				
						$this->set_message( $message );
    	        	    //$this->apply_form();
        	        	if ( !isset($options['render']) || $options['render'] == true ) {
                        	$this->render();
        	        	}

					}
				}
				else {
					throw $e;
				}
			}
			catch( Exception $e ) {
				$this->method_failed(__FUNCTION__, true); 
				throw $e;
			}
        }

		public function after_add() {
			
	    	try {
				if (  $this->is_postback() ) {
								
					$render_options['form_repopulate'] = false;

					if ( $this->method_failed('add') ) { 
						
						$render_options['message'] = $this->get_message_fail('edit');
						
						if ( $redirect = $this->get_redirect_fail('edit') ) {
							$this->set_message( $render_options['message'] );
							$this->redirect($redirect);
						}
						else if ( $this->render_on_fail ) {
							$this->render_fail('add', $render_options);
						}
					}
					else {
						
						$render_options['message'] = $this->get_message_success('edit');
						
						if ( $redirect = $this->get_redirect_success('edit') ) {
							$this->set_message( $render_options['message'] );
							$this->redirect($redirect);
						}
						else if ( $this->render_on_success ) {
							$this->render_success('add', $render_options);
						}
					}
				}
	    	}
	    	catch( Exception $e ) {
	    		throw $e;
	    	}
		
		}

		public function before_before_edit() {
			
			if ( !$this->is_postback() ) {
			
				LL::Require_class('Form/FormDataProcessor');
                        
                $template = $this->get_template();
                $form     = $this->get_form();
				$model	  = $this->get_model();

				$processor = new FormDataProcessor();
				$processor->set_model ( $model );
				$processor->set_form( $form );
						
				$form = $processor->process_outgoing();
                        
                //$model->populate_form($template, $options);
                $model->populate_template($template, $this->edit_options);
			}

			
		}


		public function repopulate_form( $options = array() ) {
			
			try {

				FUSE::Require_class('Form/FormDataProcessor');
                        
	            $template = ( isset($options['template']) && $options['template'] ) ? $options['template'] : $this->get_template();
				$form = ( isset($options['form']) && $options['form'] ) ? $options['form'] : $this->get_form();
				$model	  = $this->get_model();

				$hash_key = $this->model->get_form_input_key();
				$orig_dataset = $form->get_dataset();
				


				//
				// FormDataProcessor updates the model record
				//
				$processor = new FormDataProcessor();
				$processor->set_model ( $model );
				$processor->set_form( $form );
						
				$form = $processor->process_outgoing();
				$new_dataset = $form->get_dataset();
                        
                //$model->populate_form($template, $options);
                $model->populate_template($template, $this->edit_options);
				
				//
				// Check for any form values for this hash key 
				// that may not actually be part of the db table
				//
				if ( isset($orig_dataset[$hash_key]) ) {
					foreach( $orig_dataset[$hash_key] as $key => $val ) {
						if ( !isset($new_dataset[$key]) ) {
							$val = ( get_magic_quotes_gpc() ) ? $val : htmlspecialchars($val);
							//
							// should be changed to set_param once controllers
							// can properly apply array params when passed a string
							//
							$this->template->params[$hash_key][$key] = $val;
							
						}
					}
				}
				
			}
			catch( Exception $e ) {
				throw $e;
				
			}
			
		}


		public function before_edit() {
			
		}

        function edit( $options = array() ) {

			try {
                
				$options = $this->get_method_options( 'edit', $options );
                
                //$this->before_edit();
				//$this->call_mixin_method('before_edit');

                $model = $this->get_model();
                $model->set_form( $this->get_form() );
				$model->set_id( $this->get_param('id') );
             
             	$is_postback = ( isset($options['is_postback']) ) ? $options['is_postback'] : $this->is_postback();
				
                if ( $is_postback ) {
                       
                       
                       	$model->update_from_form();
						$this->repopulate_form();
						
						$this->render_on_success = $this->get_flag_render_on_success($options);
						
						//$this->call_mixin_method('after_edit');
						//$this->after_edit();
						
                }
                else {
                        /*
                        LL::Require_class('Form/FormDataProcessor');
                        
                        $template = $this->get_template();
                        $form     = $this->get_form();

						$processor = new FormDataProcessor();
						$processor->set_model ( $model );
						$processor->set_form( $form );
						
						$form = $processor->process_outgoing();
                        
                        $model->populate_template($template, $options);
                		*/
                
                		if ( !isset($options['render']) || $options['render'] == true ) {
                        	$this->render();
                		}
						
                }

			}
			catch( UserDataException $e ) {
				
				$this->method_failed(__FUNCTION__, true); 
				$render_on_fail = $this->get_flag_render_on_fail($options);
				$is_ajax = ( isset($options['is_ajax']) ) ? $options['is_ajax'] : $this->is_ajax();
				
				if ( $render_on_fail && !$is_ajax ) {
					
					$message = null;

					if ( $message = $this->get_message_success('edit') ) {
						$message = LL::Translate($message) . Config::Get('output.newline');
					}
				
					$message .= $e->getMessage();
					$this->set_message( $message );
            	
            		//$this->apply_form();
                	
                	if ( !isset($options['render']) || $options['render'] == true ) {
	                	$this->render();
                	}
                	exit;
				}
				else {
					throw $e;
				}
			}
			catch( Exception $e ) {
				$this->method_failed(__FUNCTION__, true); 
				throw $e;
			}

        }
    
    public function after_edit() {
    	
    	try {
			if (  $this->is_postback() ) {
				
				$render_options['form_repopulate'] = true;
		
				if ( $this->method_failed('edit') ) { 
					
					$render_options['message'] = $this->get_message_fail('edit');
					
					if ( $redirect = $this->get_redirect_fail('edit') ) {
						$this->set_message( $render_options['message'] );
						$this->redirect($redirect);
					}
					else if ( $this->render_on_fail ) {
						$this->render_fail('edit', $render_options);
					}
				}
				else {
					$render_options['message'] = $this->get_message_success('edit');
					
					if ( $redirect = $this->get_redirect_success('edit') ) {
						$this->set_message( $render_options['message'] );
						$this->redirect($redirect);
					}
					else if ( $this->render_on_success ) {
						$this->render_success('edit', $render_options);
					}
				}
			}
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    }
    
    public function delete( $options = null ) {
    	
    	try {
				
                $model = $this->get_model();
				//$model->set_id( $this->get_param('id') );
             
            	$model->delete();
				//$this->after_delete();
				
    	}
		catch( Exception $e ) {
			
			if ( $redirect = $this->get_redirect_fail('delete') ) {
				$this->redirect($redirect);
			}
			else {
				$message = null;

				if ( $this->message_delete_error ) {
					$message = LL::Translate($this->message_delete_error) . Config::Get('output.newline');
				}
				
				$message .= $e->getMessage();
				
				throw new Exception( $message );
			}
			
		}
			
    	
    }
    
    public function after_delete() {
    	
			if ( $this->render_on_success ) {
					
					/*	
					$render_options = array();
					$render_options['message'] 		   = $this->message_delete_success;
					$render_options['form_repopulate'] = true;
					
					$this->postback_render_success('delete', $render_options);
					*/
					
					if ( $message = $this->get_message_success('delete') ) {
						$this->set_message($message);
					}
					$controller_link = strtolower($this->get_controller_name());
					
					$this->redirect( "{$controller_link}/list");
			}
    	
    	
    }
	
	public function apply_list( $options = array() ) {
	
		try { 
			
			LL::Require_class('PDO/PDOParent');
			
			$options = $this->get_method_options( 'list', $options );
			
			if ( isset($options['to_controller']) && $options['to_controller'] ) {
				$to_controller = $options['to_controller'];
			}
			else {
				$to_controller = $this;
			}
		
			if ( $this->paginate && (!isset($options['skip_pagination']) || !$options['skip_pagination']) ) {
			
				if ( $max_pages = $this->get_pagination_option('max_pages') ) {
					if ( $this->page > $max_pages ) { 
						$this->page = $max_pages;
					}
				}
				
				if ( $items_per_page = $this->get_items_per_page() ) {
                	$start_limit    = ( $this->page <= 1 ) ? 0 : ($this->page - 1) * $items_per_page;
                	$end_limit = $items_per_page;
                }

				if ( isset($options['query_obj']) && $options['query_obj'] ) {
                	$custom_query_obj = $options['query_obj'];
                	if ( !$custom_query_obj->has_limit() ) {
                		$custom_query_obj->set_limit_start( $start_limit );
                		$custom_query_obj->set_limit_end( $end_limit );
                	}
				}
				else {
					if ( !isset($options['limit']) ) {
                		$options['limit'] = "{$start_limit},{$end_limit}";
                	}
				}
				
			}

			if ( !$this->page ) {
				$this->page = 1;
			}
			
			if ( isset($options['db_result']) && $options['db_result'] ) {
				LL::Require_class('ORM/DataModelIterator');
				
				$iterator = $this->model->get_fresh_iterator();
				$iterator->set_db_resultset( $options['db_result'] );
				$last_query_result = $options['db_result'];
				
				$result_count = $iterator->count;
				
				if ( $this->_Absolute_result_count === null ) {
					$this->set_absolute_result_count( $this->model->db->get_absolute_result_count($last_query_result) );
				}
				
			}
			else if ( isset($options['result_array']) && $options['result_array'] ) {
				//
				// Here, the iterator is just an associative array 
				// that should be looped through with <{LOOP}>
				//
				$iterator = $options['result_array'];
				if ( $this->_Absolute_result_count === null ) {
					$this->set_absolute_result_count( count($iterator) );
				}				
				
				$result_count = count($iterator);				 
			}
			else {
				$iterator = $this->model->fetch_all( $options );
				$last_query_result = $this->model->get_last_query_result();
				
				if ( $this->_Absolute_result_count === null ) {
					$this->set_absolute_result_count( $this->model->db->get_absolute_result_count($last_query_result) );
				}
				
				$result_count = $iterator->count;
			}
			
			//$last_query_obj = $this->model->get_last_query_obj();
			
			//if ( $this->_Absolute_result_count === null ) {
			//	$this->_Absolute_result_count = $this->model->db->get_absolute_result_count($last_query_result);
			//}
			
			//if ( $this->paginate ) {
			//	$this->set_pagination_query_obj(clone $last_query_obj);
			//}

			if ( array_val_is_nonzero($options, self::KEY_ITERATOR_NAME) ) {
				$iterator_name = $options[self::KEY_ITERATOR_NAME];				
			}
			else {
				$iterator_name = $this->model->get_table_name();
			} 

			
			$count_var = depluralize($iterator_name) . '_count';
			$absolute_count_var = $count_var . '_absolute';
			$to_controller->$absolute_count_var = $this->_Absolute_result_count; 

			$to_controller->$count_var = $result_count;
			$to_controller->$iterator_name = $iterator;
			
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
    
    public function set_absolute_result_count( $count ) {
    	
    	$this->_Absolute_result_count = $count;
    	
    }
    
    public function get_pagination_limit_clause( $options = array() ) {

		try {
			return $this->get_pagination_limits( $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
    }

    public function get_pagination_limits( $options = array() ) {
    	
    	try {
    		
    		$page = isset($options['page']) ? $options['page'] : $this->page;
    		$items_per_page = isset($options['items_per_page']) ? $options['item_per_page'] : $this->get_pagination_option('items_per_page');
    		$ret = array();
    		
    		if ( $items_per_page ) {
                $ret['start']    = ( $page <= 1 ) ? 0 : ($page - 1) * $items_per_page;
                $ret['end'] = $items_per_page;
            	
            }
            
            return $ret;
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    }
    
	public function show_list( $options = null ) {
		
		try {
			$this->apply_list($options);
			return $this->render( $options );
		}
		catch( Exception $e ) {
			throw $e;
		}	
	}
    
    /*
	function apply_controller_data_to_template( $template = null, $options = null ) {

		try {
			
			if ( !$template ) $template=$this->get_template();			

			
			if ( !$this->is_postback() && $this->action_requires_form_handling() ) {
				$model = $this->get_model();
			  	$model->populate_form($template);
			}

			$template = parent::apply_controller_data_to_template( $template, $options );
			
			return $template;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	*/
	
	public function apply_form( $options = array() ) {
		
		if ( !$this->is_postback()  ) {
			$model = $this->get_model();
		  	$model->populate_form($this->template);
		}
		
		parent::apply_form( $options );
	}
	
	public function action_requires_form_handling() {
		
		try {
			if ( in_array($this->get_requested_action(), $this->_Form_handling_actions ) ) {
				
				return true;
			}
			
			return 0;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function get_pagination_obj( $options = array() ) {
		
		try {
			
			//$model = $this->get_model();
			//$query_obj = $model->query_obj_from_option_hash($options);
			
			if ( !isset($options['total_item_count']) ) {
				$total_item_count = $this->get_pagination_count($options);
			}
			else {
				$total_item_count = $options['total_item_count'];
			}
			
			$pager = parent::get_pagination_obj( $total_item_count, $options );
			
			if ( array_val_is_nonzero($options, self::KEY_PAGINATION_NAME) ) {
				$pager->name = $options[self::KEY_PAGINATION_NAME];				
			}
			else {
				$pager->name = $this->model->get_name();
			} 
			
			return $pager;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function get_pagination_count( $options = null ) {

		try {
			
			//
			// Check to see if an explicit query object was set.
			// This should not be set by Lamplighter, only explicitly
			// by a custom function
			//
			if ( isset($options['query_obj']) && $options['query_obj']) {
				return $this->model->count_all( $options );
			}
			else {
				if ( $this->_Absolute_result_count === null ) {
					trigger_error( 'Pagination count called before (successful) query was run. Check your query.', E_USER_WARNING);
					return 0;
				}
				else {
					return $this->_Absolute_result_count;
				}
			}			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function set_pagination_count( $count ) {
		
		$this->_Absolute_result_count = $count;
		
	}

 	public function view( $options = array() ) {
 		
 		try {
 			
           	$options = $this->get_method_options( 'view', $options );
			$model	  = $this->get_model();
 			$render_options = $options;
 			
 			if ( $model->id ) {

	 			$template = $this->get_template();
 				
 				if ( $options ) {
 					$options['force_new'] = true;
 				}
 				
 				if ( !isset($options['global_template_params']) ) {
 					$options['global_template_params'] = true;
 				}
 				
 				$model->populate_template($template, $options);
 			
 			}
 			
 			return $this->render( $render_options );
 			
 		}
 		catch( Exception $e ) {
			throw $e;
		}
 		
 	}
 	
 	/**
 	 * 
 	 * It took a while to settle on a name for this option, hence
 	 * all these checks. Canonical name moving forward is 'render_on_success'
 	 * and 'render_on_fail'
 	 */
 	public function get_flag_render_on_success( $options = null ) {
 		
 		if ( isset($options['render_on_success']) ) {
			return $options['render_on_success'];
 		}
 		if ( isset($options['skip_render_success']) ) {
			return ( $options['skip_render_success'] ) == true ? false : true;
 		}
		else if ( $this->skip_render_success !== null ) {
			return ( $this->skip_render_success == true ) ? false : true;
		}
		else if ( $this->skip_postback_render_success !== null ) {
			return ( $this->skip_postback_render_success == true ) ? false : true;
		}
		else {
			return $this->render_on_success;
		}
		
 		
 	}
 	
 	public function get_flag_render_on_fail( $options = null ) {
 		
 		if ( isset($options['render_on_fail']) ) {
			return $options['render_on_fail'];
 		}
 		if ( isset($options['skip_render_fail']) ) {
			return ( $options['skip_render_fail'] ) == true ? false : true;
 		}
		else if ( $this->skip_render_fail !== null ) {
			return ( $this->skip_render_fail == true ) ? false : true;
		}
		else if ( $this->skip_postback_render_fail !== null ) {
			return ( $this->skip_postback_render_fail == true ) ? false : true;
		}
		else {
			return $this->render_on_fail;
		}
		
 		
 	}

  public function sortable_update( $options = array() ) {
   		
   		try {
	   		/*
	   		if ( Config::Get('sortable.type') == 'scriptaculous' ) {
	   			$this->sortable_update_scriptaculous( $options );
	   		}
	   		else {
	   			
	   		}
	   		*/
	   		
	   		$data = array();
	   		
	   		if ( isset($options['order_field']) ) {
	   			$order_field = $options['order_field'];
	   		}
	   		else {
	   			$order_field = $this->model->db_column_name_order;
	   		}
	   		
	   		$sort_data = $this->get_param($this->model->table_name);
	   		
	   		if ( is_array($sort_data) ) {
	   			$data = $sort_data;
	   		}
	   		else {
	   			$parsed_array = array();
	   			$data_string = $this->get_param($this->model->table_name);
	   	
				parse_str($data_string, $parsed_array);
	
		   		if ( isset($parsed_array[$this->model->table_name]) ) {
	   				$data = $parsed_array[$this->model->table_name];
	   			}
	   		}
	   	
		   	$this->model->db->connect_w();
	   	
	   		for ($i = 0; $i < count($data); $i++ ) {
	
					$id = $data[$i];
					$order = $i+1;
					
					if ( is_numeric($id) ) {
	   					$query = "UPDATE {$this->model->table_name} SET {$order_field} = {$order} WHERE {$this->model->db_column_name_id}={$id}";
	   					
	   					$this->model->db->query($query);
					}
	   		}
   		}
   		catch( Exception $e ) {
   			throw $e;
   		}
   	
   }

	public function get_search_query_obj( $options = array() ) {

		if ( !$this->_Search_query_obj || (isset($options['force_new']) && $options['force_new']) ) {

			$search_obj = $this->get_search_obj();

			if ( isset($options['query_obj']) ) {
				$search_obj->set_query_obj($options['query_obj']);
			}
					
			if ( !($this->_Search_query_obj = $search_obj->generate_query_obj()) ) {
				throw new SearchGenerateException( LL::Get_error_messages() );
			}
		}
		
		return $this->_Search_query_obj;	
		
	}

	public function apply_search( $options = array() ) {
		
		$this->search_active = false;
		$search_obj = $this->get_search_obj();
		
	
		if ( $this->start_search ) {
			$search_obj->start_search_session();
				
        }
        else {
        	if ( ($search_id = from_post_or_get('search_id')) ) {
            	$search_obj->set_search_id( $search_id );
            	$search_obj->repopulate_template( $this->template );
            }
        }
			
			
		if ( $search_obj->search_id ) {
		
				$this->search_is_active = true;
				
				$qs = 'search_id=' . $search_obj->search_id;
				
				if ( $this->paginate ) {
					if ( isset($this->paginate['query_string']) && $this->paginate_query_string) {
						$qs = '&' . $qs;					
					}
				
					$this->paginate['query_string'] = $qs;
				} 

				try { 
					$query_obj = $this->get_search_query_obj();
				
					$fetch_options = $this->list_options;
					$fetch_options['query_obj'] = $query_obj;
				
					$this->apply_list( $fetch_options );
				
				}
				catch( SearchGenerateException $sge ) {
					$this->message = $sge->getMessage();
				}
				
		}
				
		$this->from_search = true;
		
		
	}

	public function search( $options = array() ) {
		
		$this->apply_search( $options );
		return $this->render( $options );
		
	}

	public function get_search_obj( $options = array() ) {

		if ( !$this->_Search_obj || (isset($options['force_new']) || $options['force_new']) ) {
			
			LL::Require_class('SQL/SQLSearch');

			$this->_Search_obj = new SQLSearch();
			$this->_Search_obj->set_session_prefix( depluralize($this->table_name) );
			$this->_Search_obj->set_form_submit_method( 'get' );
			$this->_Search_obj->table( $this->model->table_name );
			$this->_Search_obj->set_db_interface($this->model->get_db_interface());

			$this->_Search_initialize($this->_Search_obj);
			$this->search_setup( $this->_Search_obj );
		}

		return $this->_Search_obj;

	}

	protected function _Search_initialize( $search_obj ) {
		
		if ( $this->search_fields ) {
			
			foreach( $this->search_fields as $field_name => $field_setup ) {
				
				if ( !isset($field_setup['filter_type']) || !$field_setup['filter_type']) {
					$field_setup['filter_type'] = 'like';
				}
				
				$this->_Search_obj->set_field_filter_type( $field_name, $field_setup['filter_type']);

				if ( isset($field_setup['db_field']) && $field_setup['db_field'] ) {
					$this->_Search_obj->rewrite( $field_name, $field_setup['db_field'] );
				}				
				
			}
			
		}
		
		if ( $this->search_settings ) {
			
			if ( isset($this->search_settings['join_if']) ) {
				foreach( $this->search_settings['join_if'] as $field => $join ) {
					$this->_Search_obj->join_if_field_present( $field, $join );
				}
			}

			if ( isset($this->search_settings['select_if']) ) {
				foreach( $this->search_settings['select_if'] as $field => $join ) {
					$this->_Search_obj->select_if_field_present( $field, $join );
				}
			}
			
			
			if ( isset($this->search_settings['form_submit_method']) && $this->search_settings['form_submit_method'] ) {
				$this->_Search_obj->set_form_submit_method($this->search_settings['form_submit_method']);				
			}
		}
		
		
	}

	public function search_setup( $search_obj ) {
 	
	}
}

class SearchGenerateException extends Exception {
		
}

class ModelNotFoundException extends Exception {
	
	public function __construct( $msg ) {
		
		return parent::__construct( 'Could not find Model: ' . $msg);
		
	}
}

/* Fuse compatibility */
class FuseDataController extends DataController{
	
}


?>