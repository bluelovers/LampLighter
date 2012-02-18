<?php

// set_field_value() -> set_column_value()

if ( !class_exists('DataModel', false) ) {

class DataModel { 
	
	const CLASS_NAME_SUFFIX = 'Model';
	const KEY_APPLY_RELATIONSHIPS = 'apply_relationships';
	const KEY_REQUIRE_UNIQUE_IDENTIFIER = 'require_unique_identifier';
	const KEY_REQUIRE_UNIQUE_PARENT = 'require_unique_parent';
	const PREFIX_VAR_DB_COLUMN_NAME = '_DB_column_name_';
	const SUFFIX_QUERY_ID_FILTER   = '_id_filter';
	const SUFFIX_AUTHORITATIVE = '_authoritative';
	
	const KEY_QUERY_OBJ_FINAL = 'query_obj_final';	
	const KEY_IGNORE_PARENT_FILTER = 'ignore_parent_filter';
	const KEY_SKIP_RECORD_RESET = 'skip_record_reset';
	const KEY_USE_GLOBAL_TEMPLATE_PARAMS = 'global_template_params';
	const KEY_HASH_NAME = 'hash_name';
	const KEY_FOREIGN_TABLE_NAME = 'foreign_table_name';
	const KEY_FORM = 'form';
	const KEY_TABLE = 'table';
	const KEY_COLUMN = 'column';
	const KEY_VALUE = 'value';
	const KEY_REQUIRED = 'required';
	const KEY_REQUIRE_VALUE = 'require_value';
	const KEY_LINK_LOCAL_COLUMN = 'local_column';
	const KEY_LINK_FOREIGN_COLUMN   = 'foreign_column';
	const KEY_RECORD_MAP = 'record_map';
	const KEY_RECORD_DATA = 'record_data';
	const KEY_NO_RECORD_FETCH = 'no_record_fetch';
	const KEY_RELATIONSHIP_DATA = 'rel_data';
	const KEY_COLUMN_NAME_PREFIX = 'column_name_prefix';
	
	const KEY_LINK_TABLE_LOCAL_COLUMN = 'local_column_link';
	const KEY_LINK_TABLE_FOREIGN_COLUMN = 'foreign_column_link';
	
	const KEY_RELATIONSHIP_COLUMN_KEY = 'foreign_key';
	const KEY_ID_COLUMN = 'id_column';
	
	const KEY_LOCAL_KEY = 'local_key';
	
	const INCLUDE_TABLE_SEPARATOR = '.';
	
	//
	// Row/Record data
	//
	protected $_Require_numeric_id = true;
	protected $_Allow_explicit_id  = false;
	
	protected $_ID;
	protected $record_row;
	protected $_Record_result;
	protected $_Record_row_set_explicitly = false;
	protected $_Relationships = array();
	protected $_Table_name;
	protected $_Unique_keys = array();
	protected $_Unique_columns = array();
	protected $_Link_columns = array();
	protected $_Fetch_column_used;
	//protected $_Primary_key;
	protected $_Foreign_key;
	protected $_Form;
	protected $_Form_name;
	protected $_Form_input_key;

	protected $_Time_and_date_columns = array();
	
	protected $_Form_field_access = array();
	protected $_Form_field_explicit_permissions = array();
	//protected $_Field_details = array();
	protected $_Column_names   = array();
	protected $_Fetch_include_all_related_tables = false;
	
	protected $_Column_db_param_types = array();
	
	protected $_Prefix_db_field_name; // deprecated
	protected $_Order_by_default;
	
	private $_Iterator;
	
	//
	// Class data
	//
	public $field_name_prefix; //deprecated
	public $column_name_prefix;
	public $primary_key = 'id';
	public $prefix_db_column_name_get = 'column_name_';
	public $key_db_table_name_get    = 'table_name';
	public $enable_id_validity_checks = false; //doesn't work properly on upward relationships
	public $form_entry_index = null;
	public $camel_case_keep_number_positioning = true;
	public $use_db_config = 'default';
	public $selections = array();
	public $reset_on_unique_key = true;
	
	//
	// Used to keep track of 
	// table aliases for parents/children
	//
	public $_internal_identifier; //used to differentiate model instances
	public $child_table_key = null;
	public $active_table_key = null; //when aliased, this will be the alias name

	
	protected $_Count_query_select = array(); //additional selections for count_all()
	protected $_DB_object;
	protected $_Library;
	protected $_Record_required = false;
	protected $_Data_constraints = array();
	protected $_Related_classes_by_table = array();
	protected $_Model_objects   = array();
	protected $_Model_objects_by_table = array();
	protected $_Model_reference_aliases;
	protected $_Model_objects_by_unique_columns = array();
	//protected $_Reflector = array();
	protected $_Join_increments = array();
	protected $_Parent_model;
	protected $_Was_saved = false;
	protected $_Was_changed = false;
	protected $_Was_reset = false;
	protected $_Was_record_fetched = false;
	protected $_Column_key_map = array();
	protected $_Last_query;
	protected $_Last_query_obj;
	protected $_Last_query_result;
	protected $_Calling_controller;

	//
	// All keys should really be made static. TODO.
	//
	static $Key_class_name = 'class_name';
	static $Key_form	= 'form';
	static $Key_skip_character_parse = 'skip_character_parse';
	
	protected $_Key_id = 'id';

	protected $_Key_relationship_record_required = 'required';
	protected $_Key_relationship_type = 'rel_type';
	protected $_Key_relationship_table_from = 'table_from';
	protected $_Key_relationship_table_to = 'table_to';
	protected $_Key_relationship_model_library = 'in_library';
	protected $_Key_relationship_class_name = 'class_name';

	protected $_Key_relationship_type_one_to_many = '1toM';
	protected $_Key_relationship_type_one_to_one = '1to1';
	protected $_Key_relationship_type_many_to_one = 'Mto1';
	
	protected $_Key_relationship_direction = 'rel_direction';
	
	protected $_Key_relationship_direction_upward   = 'up';
	protected $_Key_relationship_direction_downward = 'down';
	protected $_Key_relationship_direction_lateral  = 'lateral';

	protected $_Key_all_relationships = 'all';
	protected $_Key_association_link_table = 'through';
	protected $_Key_table_alias = 'as';
	protected $_Key_include = 'include';
	protected $_Key_quote = 'quote';
	protected $_Key_parse = 'parse';
	protected $_Key_value = 'value';
	protected $_Key_table = 'table';
	protected $_Key_comparator = 'comparator';
	protected $_Key_query_obj = 'query_obj';
	protected $_Key_options = 'options';
	protected $_Key_require_id = 'require_id';
	protected $_Key_force_new = 'force_new';
	protected $_Key_allow = 'allow';
	protected $_Key_disallow = 'disallow';
	protected $_Key_return_type = 'return';
	protected $_Key_return_type_iterator = 'iterator';
	protected $_Key_return_type_resultset = 'resultset';
	protected $_Key_return_type_query_obj = 'query_obj';
	protected $_Key_return_type_array = 'array';
	protected $_Last_fetch_include = null;
	
	static $Key_format = 'format';
	static $Key_date_requires_double_digits = 'require_double_digits';
	
	protected $_Key_query_type_insert = 'insert';
	protected $_Key_query_type_update = 'update';

	protected $_Key_selections_base = '_SEL_BASE';
	protected $_Key_joins_base = '_JOINS_BASE';

 	protected $_Debug_object;

	//
	// Static members
	//
	//static $Debug_enabled = false;
	//static $Verbosity_level; 
	static $Loading_internal_model = false;
	static $Default_join_type = 'LEFT';

	function __construct() {

		$this->_internal_identifier = uniqid('model', true);

		if ( Config::Is_set('DataModel.class_suffix') ) {
			$this->_class_suffix = Config::Get('DataModel.class_suffix');
		}

		if ( defined('DATA_MODEL_DEFAULT_JOIN_TYPE') ) {
			self::$Default_join_type = constant('DATA_MODEL_DEFAULT_JOIN_TYPE');
		}

		//self::$Verbosity_level = Debug::VERBOSITY_LEVEL_BASIC;

		//
		// Deprecated Prefix constants
		// This is here for backward compatibility
		//
		if ( $this->field_name_prefix ) {
			$this->column_name_prefix = $this->field_name_prefix;
		}
		else if ( $this->_Prefix_db_field_name ) {
			$this->column_name_prefix = $this->_Prefix_db_field_name;
		}

		//
		// Add primary key as Unique_key
		//

		if ( $pk_column_name = $this->get_primary_key_column_name() ) {
			if ( is_array($pk_column_name) ) {
				foreach( $pk_column_name as $cur_col ) {
					$pk_keys[] = $this->column_key_by_name($cur_col);
				}
				$this->_Unique_keys[] = $pk_keys;
			}
			else {
				$this->_Unique_keys[] = $this->column_key_by_name($pk_column_name);
			}
			
			$this->_Unique_columns[] = $pk_column_name;
		}
		
		if ( method_exists($this, '_Init') ) {
			if ( !self::$Loading_internal_model ) {
				$this->_Init();
				$this->active_table_key = $this->get_table_name();
			}
		}

		try {
		
			if ( Config::Get('model.auto_field_length_restrictions') ) {
				$this->apply_field_length_restrictions_from_fields();
			}
			
			
		}
		catch(Exception $e) {
			trigger_error( 'Data initialization error for table: ' . $this->get_table_name() . Config::Get('output.newline') . $e->getMessage(), E_USER_ERROR);
			exit;
		}
		

	}

	function __destruct() {
	
	}

	function __set( $key, $val ) {

		try { 
	
			$reflector = new ReflectionObject($this);
	
			if ( $reflector->hasProperty($key) ) {
				$this->$key = $val;
			}
			else if ( isset($this->_Model_objects_by_table[$key]) ) {
				$this->_Model_objects_by_table[$key] = $val;
			}
			else {
	
				$options = array();
				$options['reset_on_unique_key'] = $this->reset_on_unique_key;
				$this->set_column_value( $key, $val, $options );
				
			}
			
			unset($reflector);
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_column_value( $key, $val, $options = array() ) {
		
		try {

			$col_name = $this->column_name($key);
			$pk_column_name = $this->get_id_column();
				
			if ( is_array($pk_column_name) ) {
				//
				// Multiple Primary Key
				//
				foreach( $pk_column_name as $cur_id_col ) {
					if ( $key == $cur_id_col ) {
						
						if ( $this->record_row && $this->was_record_fetched() ) {
							$this->reset_record_data();
							$this->reset_child_models();
						}
						
						break;
					}
				}	
			}
			
			if ( $col_name == $pk_column_name ) {

				$id_options = array();
				
				//
				// If we're not resetting on a unique key, 
				// tell set_id() to SKIP_RECORD_RESET
				//
				if ( isset($options['reset_on_unique_key']) ) {
					$id_options[self::KEY_SKIP_RECORD_RESET] = ( $options['reset_on_unique_key'] ) ? 0 : 1;
				}
			
				$this->set_id( $val, $id_options );
				//$this->was_changed( true );
			}
			else {
				
				if ( isset($this->_Link_columns[$col_name]) ) {
					
					//
					// We want to be able to set many-to-many 
					// linked id columns as if they are part of this model.
					// The _Link_columns array keeps track of the 
					// foreign keys required to transparently 
					// add data to link tables between two many-to-many 
					// related models.
					//
					
					$link_options = $this->_Link_columns[$col_name];
					$link_options[$this->_Key_value] = $val;
					$this->_Link_columns[$col_name] = $link_options;
					$this->was_changed(true); 
				}
				
				if ( $this->column_has_unique_key($col_name) ) {
					
					if ( !isset($options['reset_on_unique_key']) || $options['reset_on_unique_key'] == true ) {
						//
						// If a unique key is changed after the record result has been fetched, 
						// refresh our record row to reflect the change
						//
					
						if ( $this->record_row && $this->was_record_fetched() ) {
							$this->reset_record_data();
							$this->reset_child_models();
						}
					}					

				}
				
				if ( $col_name ) {
					
					$this->record_row[$col_name] = $val;
					$this->was_changed(true); //should id column count as was_changed?

					$this->bubble_column_value_upward($col_name, $val);
					
				}
				
			}
				
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	//
	// If a foreign key in a child was changed, we need to update the 
	// parent to reflect the change as well
	//
	public function bubble_column_value_upward( $column, $val ) {
		
		try {
			
			$column_name    = $this->column_name($column);
			$bubble_cols = array($column_name);
			
			Debug::Show( "Looking for {$column_name} to bubble in " . get_class($this), Debug::VERBOSITY_LEVEL_EXTENDED);
			
			if ( $this->column_has_unique_key($column_name) ) {
				$bubble_cols = array_unique(array_merge( $bubble_cols, $this->_Unique_columns ));
			}
			
								
			if ( $parent = $this->get_parent_model() ) {
				foreach( $bubble_cols as $cur_col ) {
					
					if ( $parent->table_has_column_name($cur_col) ) {
						
						Debug::Show( "BUBBLING Parent column {$cur_col}");
						$parent->record_row[$cur_col] = $this->get_column_value($cur_col);
						$parent->was_changed(true);
					}
				}


				
				$parent->bubble_column_value_upward( $column_name, $val );
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function __get( $what ) {
		
		try { 
			//	
			// don't use $this->debug here or you'll end up in an infinite loop
			//
			//$this->_Get_debug_object()->show_message( " __get() is trying to find: {$what} in " . get_class($this), Debug::VERBOSITY_LEVEL_INTERNAL );
		
			if ( $what == 'db' ) {
				return $this->get_db_object();
			}
			else if ( $what == $this->key_db_table_name_get ) {
				return $this->get_table_name();
			}
			else if ( substr($what, 0, strlen('db_field_name_')) == 'db_field_name_' ) {
				//
				// deprecated 'db_field_name' option - leave in for backward compatibility
				//
				$db_field_request = substr($what, strlen('db_field_name_'));
				return $this->column_name($db_field_request);			
			}
			else if ( substr($what, 0, strlen($this->prefix_db_column_name_get)) == $this->prefix_db_column_name_get ) {
				$db_column_request = substr($what, strlen($this->prefix_db_column_name_get));
				return $this->column_name($db_column_request);			
			}
			else if ( null !== ($col_value = $this->get_column_value($what)) ) {
				return $col_value;
			}
		
		
			if ( !$this->table_has_column_name($what) ) {
		
				Debug::Show( __METHOD__ . ": Checking for model: {$what}", Debug::VERBOSITY_LEVEL_EXTENDED );
					
				if ( $model = $this->get_child_model($what) ) {
					return $model;
				}
			}
		
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function get_child_model( $table ) {
		
		try {
	
			if ( $model = $this->_Model_object_by_table_reference($table) ) {
					
				$table_key = $this->table_name_by_table_reference($table);
					
				if ( !$model->get_parent_model() || ($model->_internal_identifier != $this->_internal_identifier) || $model->was_reset() ) {
					$model->set_parent_model($this, $table_key);
				}
				
				return $model;
			}
			
			return null;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function reset() {
		
		$this->reset_record_data();
		
	}
	
	public function reset_record_data() {
		
		try {

			//$this->id = null;
			$this->record_row = null;
			$this->_Record_result = null;
			$this->_Record_row_set_explicitly = false;
			$this->_Iterator = null;
			
			$this->was_saved(false);
			$this->was_changed(false);
			$this->was_record_fetched(false);
			$this->was_reset(true);
			
			
			//$this->_Model_objects = array();
			//$this->_Model_objects_by_table = array();
			//$this->_Model_objects_by_unique_columns = array();
		
			//$this->reset_related_models();
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function was_record_fetched( $truefalse = null ) {
		
		if ( $truefalse !== null ) {
			if ( $truefalse ) {
				$this->_Was_record_fetched = true;
			}
			else {
				$this->_Was_record_fetched = false;
			}
		}

		return $this->_Was_record_fetched;		
	}
	
	public function clear_parent() {
		
		$this->_Parent_model = null;
		
	}
	
	public function reset_related_models() {
		
		try {
			foreach( $this->_Model_objects as $class_name => $obj ) {
					
				if ( !$obj->was_reset() ) {
					$obj->reset_record_data();
				}
				
			}
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}

	public function reset_child_models() {
		
		try {
			foreach( $this->_Model_objects as $class_name => $obj ) {
					
				if ( !$obj->was_reset() ) {
					if ( get_class($obj->get_parent_model()) == $class_name ) {
						
						$obj->reset_record_data();
						$obj->reset_child_models();
					}
				}
				
			}
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}
	
/*
	protected function _Get_debug_object() {
		
		if ( !$this->_Debug_object ) {
			LL::require_class('Debug/Debug');
			
			$this->_Debug_object = new Debug;
			
			if ( !self::$Verbosity_level ) {
				self::$Verbosity_level = Debug::VERBOSITY_LEVEL_INTERNAL;
			}
			
			$this->_Debug_object->verbosity_level = self::$Verbosity_level;
			
		}
		
		$this->_Debug_object->enabled = self::$Debug_enabled;
			
		return $this->_Debug_object;
		
	}
*/
	
	public function model_object_by_table_reference( $reference, $require_id_column = false ) {

		return $this->_Model_object_by_table_reference( $reference, $require_id_column );
		
	}
	
	private final function _Model_object_by_table_reference( $reference, $require_id_column = false  ) {

		try { 
			
			$model      = null;
			
			Debug::Show( '<br />Related tables in ' . get_class($this) . '<br />' . print_r($this->_Related_classes_by_table, true), Debug::VERBOSITY_LEVEL_EXTENDED );
			Debug::Show( 'aliases in ' . get_class($this) . ':' . print_r( $this->_Model_reference_aliases, 1), Debug::VERBOSITY_LEVEL_INTERNAL );
			
			
			
			if ( isset($this->_Model_objects_by_table[$reference]) ) {
				$model = $this->_Model_objects_by_table[$reference];
			}
			else if ( isset($this->_Related_classes_by_table[$reference]) ) {
				$model = $this->_Model_object_by_relationship_data($this->_Related_classes_by_table[$reference]);
			}
			else {
	
				$reference_checks = $this->get_table_reference_array( $reference );
				
				foreach( $reference_checks as $cur_reference ) {
								
						Debug::Show( "Looking for: {$cur_reference}", Debug::VERBOSITY_LEVEL_INTERNAL );
					
						if ( isset($this->_Model_objects_by_table[$cur_reference]) ) {
							$model = $this->_Model_objects_by_table[$cur_reference];
							break;
						}
						else if ( isset($this->_Related_classes_by_table[$cur_reference]) ) {
							$rel_data = $this->_Related_classes_by_table[$cur_reference];
							$model = $this->_Model_object_by_relationship_data($rel_data);
							break;
							//$this->_Related_classes_by_table[$cur_reference]
						}
				}					
					
			}
			
			return $model;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	//
	// see get_reference_checks() comments for details on this method
	//
	public function table_name_by_table_reference( $reference ) {
		
		try {

			if ( isset($this->_Related_classes_by_table[$reference]) ) {
				return $reference;
			}

			$reference_checks = $this->get_table_reference_array( $reference );
			
			foreach( $reference_checks as $cur_reference ) {
						
				Debug::Show( "Looking for: {$cur_reference}", Debug::VERBOSITY_LEVEL_INTERNAL );
		
				if ( isset($this->_Model_objects_by_table[$cur_reference]) ) {
					return $cur_reference;
					break;
				}
				else if ( isset($this->_Related_classes_by_table[$cur_reference]) ) {
					return $cur_reference;
					break;
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	//
	// Tables might be referenced as 'user', 'users', 'profile_user', 
	// etc depending on the context. This is purely to make the programmer's
	// job a little easier by allowing the code to read a little bit more intuitively
	//
	public function get_table_reference_array( $reference ) {
		
		try {

			$reference_checks = array();
			$reference_prefixes = array();
			$references = array($reference);
								  
			if ( isset($this->_Prefix_model_references) ) {
					
				if ( is_scalar($this->_Prefix_model_references) ) {
					$reference_prefixes = array($this->_Prefix_model_references);
				}
				else {
					$reference_prefixes = $this->_Prefix_model_references;
				}		
			}
				
			$reference_checks[] = pluralize($reference);
			$reference_checks[] = $this->column_prefix_apply_to_key($reference);
			$reference_checks[] = $this->column_prefix_apply_to_key(pluralize($reference));
			
			$reference_checks[] = depluralize($reference);
			$reference_checks[] = $this->column_prefix_apply_to_key(depluralize($reference));
	
			foreach( $reference_prefixes as $cur_prefix ) {
				$prefixed_reference = $cur_prefix . $reference;
				$reference_checks[] = $prefixed_reference;
				$reference_checks[] = pluralize($prefixed_reference);
				$reference_checks[] = depluralize($prefixed_reference);
			}
			
			return $reference_checks;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function add_table_reference_alias( $alias, $table_name ) {
		
		$this->_Model_reference_aliases[$alias] = $table_name;
		
	}
	
	
	
	public function get_db_object() {
		
		return $this->get_db_interface();
			
	}
	
	public function get_db_interface() {
		
		try {
			if ( !$this->_DB_object ) {
				
				LL::Require_class('PDO/PDOFactory');
				$this->_DB_object = PDOFactory::Instantiate( $this->use_db_config );
			}
			return $this->_DB_object;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	function get_iterator( $options = null ) {
		
		try { 
			if ( !$this->_Iterator || (isset($options[$this->_Key_force_new]) && $options[$this->_Key_force_new]) ) {
				LL::require_class('ORM/DataModelIterator');
				
				$this->_Iterator = new DataModelIterator();
				$this->_Iterator->set_iterating_class_name( get_class($this) );
				//$this->_Iterator->set_model( $this );
			}
			
			return $this->_Iterator;
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}
	
	function get_fresh_iterator( $options = null ) {
		
		$options[$this->_Key_force_new] = true;
	
		return $this->get_iterator($options);
	}

	public function table_key_by_relationship_data( $rel_data ) {
		
		if ( isset($rel_data[$this->_Key_table_alias]) && $rel_data[$this->_Key_table_alias] ) {
			return $rel_data[$this->_Key_table_alias];			
		}
		else {
			return $rel_data[$this->_Key_relationship_table_to];
		}
		
	}

	//
	// rel_data is relationship data is the relationship data from the parent  
	// to this model e.g. if user->messages, this rel data would have a class name
	// of 'Messages'
	function set_parent_model( $parent, $via_table_key, $options = array() ) {
	
		try {
			
			$require_id_column = false;
			
			if ( !($parent_rel_data = $parent->relationship_data_by_table_name($via_table_key)) ) {
				trigger_error( 'Model referenced with no relationship applied: ' . get_class($parent), E_USER_ERROR );
			}

			//
			// The "child table key"
			// keeps track of which table alias is this parent's
			// immediate descendant. This is necessary for when we have 
			// multiple table aliases for the same model type
			// 
			$parent->child_table_key = $via_table_key;

			
			//
			// In case this model is related to multiple aliases 
			// for the same table, we need to tell the parent 
			// what its alias is as far as this child is concerned. 
			// e.g. if the child is a PrivateMessage class with alias rcvd_messages, 
			// the alias for the parent might be 'receiving_user', 
			// even though the parent model class is just User
			//

			$local_key = isset($parent_rel_data['foreign_key']) && $parent_rel_data['foreign_key'] ? $parent_rel_data['foreign_key'] : $this->foreign_key_column_name();
			$parent->active_table_key = $this->find_table_key_by_foreign_table_and_lk( $parent_rel_data[$this->_Key_relationship_table_from], $local_key);

			if ( !$parent->active_table_key ) {
				throw new NotFoundException( "No matching keys found for {$parent_rel_data[$this->_Key_relationship_table_from]} using {$local_key} in " . get_class($this));
			}
			
			if ( !is_array($options) ) {
				//
				// Deprecated call where $require_id_column was the second parameter
				//
				$require_id_column = $options;
				$options = array();
			}
			else {
				//
				// require_id_field is a deprecated name for this option
				//
				if ( isset($options['require_id_field']) ) {
					$require_id_column = $options['require_id_field'];
				}
				if ( isset($options['require_id_column']) ) {
					$require_id_column = $options['require_id_column'];
				}

			}
	
			$my_id_column 	 = $this->get_id_column();
			$my_class_name   = get_class($this);
			$my_rel_data	 = $this->relationship_data_by_table_name($parent->active_table_key);

			$parent_class_name = get_class($parent);
			$parent_rel_type = $parent_rel_data[$this->_Key_relationship_type];
			
			$parent_id_column = $parent->get_id_column();
			
			//
			// Check to see if, instead of defaulting to the primary key,
			// explicit keys were set for this relationship
			//
			if ( isset($parent_rel_data['foreign_key']) ) {
				$my_id_column = $parent_rel_data['foreign_key'];		
			}
	
			if ( isset($parent_rel_data['local_key']) ) {
				$parent_id_column = $parent_rel_data['local_key'];			
			}
			else {
				if ( isset($parent_rel_data['foreign_key']) ) {
					$parent_id_column = $parent_rel_data['foreign_key'];
				}
			}
	
			
			Debug::Show( __METHOD__ . ": I am a {$my_class_name} with id column: {$my_id_column}", Debug::VERBOSITY_LEVEL_EXTENDED );
			Debug::Show( __METHOD__ . ": My parent is a {$parent_class_name} with id column: {$parent_id_column}", Debug::VERBOSITY_LEVEL_EXTENDED );
			Debug::Show( __METHOD__ . ": Child/parent relationship has type {$parent_rel_data[$this->_Key_relationship_type]} and direction " . $parent_rel_data[$this->_Key_relationship_direction], Debug::VERBOSITY_LEVEL_EXTENDED );
			
			if ( $parent_rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_upward
				   && $my_rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_downward ) {
				
				// the parent belongs_to this
				// this is sort of "backwards" in that in this case, 
				// the "parent" (e.g. the previous object referenced by the -> operator) 
				// is actually the child table.
	
				if ( is_array($my_id_column) ) {
					foreach( $my_id_column as $cur_id_column ) {
											
					}
				}
				else {
					if ( $parent->$my_id_column ) {
						Debug::Show( __METHOD__ . ":1: parent ID ({$my_id_column}) is : " . $this->$parent_id_column, Debug::VERBOSITY_LEVEL_INTERNAL );	
		
						//$this->set_id( $parent->$my_id_column );
						$this->$my_id_column = $parent->$my_id_column;
						
					}
					else {
						if ( $require_id_column ) {
							trigger_error( "Laterally or upward related child model referenced without id column: {$my_id_column}", E_USER_ERROR );
						}
					}
				}
			}
			else if ( $parent_rel_data[$this->_Key_relationship_type] == $this->_Key_relationship_type_one_to_one
						|| $parent_rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_lateral ) { 
				
				//if ( $parent_rel_type == $this->_Key_relationship_one_to_one ) { //$parent_rel_type == $this->_Key_relationship_type_many_to_one ) {
					if ( $parent->$my_id_column ) {
						Debug::Show( __METHOD__ . ":2: parent ID ({$my_id_column}) is : " . $parent->$my_id_column, Debug::VERBOSITY_LEVEL_EXTENDED );	
		
						//$this->set_id( $parent->$my_id_column );
						$this->$my_id_column = $parent->$my_id_column;
					}
					else {
						if ( $require_id_column ) {
							trigger_error( "Laterally or upward related child model referenced without id column: {$my_id_column}", E_USER_ERROR );
						}
					}
				//}
				/*
				else {
						
					if ( $parent->$my_id_column ) {
						Debug::Show( __METHOD__ . ": parent ID ({$my_id_column}) is : " . $parent->$my_id_column, Debug::VERBOSITY_LEVEL_EXTENDED );	
						$this->set_id( $parent->$my_id_column );
					}
					else {
				*/
						$parent_id = $this->db->parse_if_unsafe($parent->id);
	
						$value_format = $this->column_value_get_query_format($parent_id);
	
						$single_res = $this->fetch_single( array( 
																	'where' => "where {$parent_id_column}={$value_format[$this->_Key_quote]}{$parent_id}{$value_format[$this->_Key_quote]}", 
																	'return' => $this->_Key_return_type_resultset)
														);
						
						if ( $single_res && $this->db->num_rows($single_res) > 0) {
							
								$row = $this->db->fetch_unparsed_assoc($single_res);	
								
								if ( isset($row[$parent_id_column]) ) {
									
									$this->id = $row[$my_id_column];
									$this->set_record_row($row);
								 
								}
								else {
									if ( $require_id_column ) {
										trigger_error( "Laterally related child model referenced without id column: {$my_id_column}", E_USER_ERROR );
									}
								}
						}
						else {
							if ( $require_id_column ) {
								trigger_error( "Laterally related child model referenced without id column: {$my_id_column}", E_USER_ERROR );
							}
						}
					//}	
				//}
				
			}
			else {
	
				//
				// This is a downward relationship, meaning that the parent class
				// has a one to many relationship to the current class. 
				// As such, the parent model doesn't have an id column for the child model.  
				// Rather, the child model has an id column for the parent model. 
				// Note that in a relationship like this, you cannot access 
				// single row data (e.g. $child->id), because more than one row 
				// is represented by the model.
				//
				
				if ( !($this->table_has_column_name($parent_id_column)) ) {
					if ( !isset($parent_rel_data[$this->_Key_association_link_table]) ) {
						trigger_error( "Could not link {$parent_class_name} to {$my_class_name}" . " via {$parent_id_column}", E_USER_ERROR );
					}
				}
				else { 
					if ( !$parent->get_id() ) {
						
						if ( $require_id_column ) {
							trigger_error( "Tried to link parent model {$parent_class_name} to {$my_class_name} without {$parent_id_column}", E_USER_ERROR );
						}
					}
					else {
						$this->$parent_id_column = $parent->get_id();
					}
				}
				
			}
			
			
			$this->_Parent_model = $parent;
			$this->was_changed(true);
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	//
	// In case this model is related to multiple aliases 
	// for the same table, we need to tell the parent 
	// what its alias is as far as this child is concerned. 
	// e.g. if the child is a PrivateMessage class with alias rcvd_messages, 
	// the alias for the parent might be 'receiving_user', 
	// even though the parent model class is just User
	// lk = local key
	// fk = foreign key
	public function find_table_key_by_related_table_and_key( $find_table, $find_key, $options = array() ) {

		try {
			
			
			$option_key = $options['search_option_key']; 
			
			foreach( $this->_Related_classes_by_table as $cur_table_key => $cur_rel_data ) {
				
				if ( $cur_rel_data[$this->_Key_relationship_table_to] == $find_table ) {
					if ( isset($cur_rel_data[$option_key]) && $cur_rel_data[$option_key] == $find_key ) {
						$tk = $this->table_key_by_relationship_data($cur_rel_data);
						return $tk;
						break;
					}
				}
			}
			
			//
			// Didn't find a match by using the explicit foreign key, 
			// so assume that there's only one relationship for this table 
			//
			return $find_table;
		}
		catch( Exception $e ) {
			throw $e;
		}


	}

	public function find_table_key_by_foreign_table_and_lk( $find_table, $find_key, $options = array() ) {
		
		try {
			
			$options['search_option_key'] = self::KEY_LOCAL_KEY;
			return $this->find_table_key_by_related_table_and_key($find_table, $find_key, $options);
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function find_table_key_by_foreign_table_and_fk( $find_table, $find_key, $options = array() ) {
		
		try {
			
			$options['search_option_key'] = 'foreign_key';
			return $this->find_table_key_by_related_table_and_key($find_table, $find_key, $options);
		}
		catch( Exception $e ) {
			throw $e;
		}

	}	
	
	function get_parent_model() {

		return $this->_Parent_model;

	}
	
	/*
	function get_reflector() {

		if ( !$this->_Reflector ) {
			$this->_Reflector = new ReflectionObject($this);
		}

		return $this->_Reflector;

	}
	*/

	function set_library( $library ) {

		$this->_Library = $library;

	}

	function get_library() {

		return $this->_Library;

	}

	function get_name() {
		
		return get_class($this);
		
	}

	function set_id( $id, $options = null ) {

		try {
			
			if ( $parent = $this->get_parent_model() ) {
				if ( $parent->is_uniquely_identified() ) {
					
					if ( !($parent_rel_data = $parent->relationship_data_by_table_name($parent->child_table_key)) ) {
						trigger_error( 'Model referenced with no relationship applied: ' . get_class($parent), E_USER_ERROR );
					}

					
					$my_class_name   = get_class($this);
					$parent_class_name = get_class($parent);
		
					Debug::Show( "Setting id for: {$my_class_name}. Parent is a: {$parent_class_name}", Debug::VERBOSITY_LEVEL_EXTENDED );
		
					if ( $parent_rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_lateral 
						|| $parent_rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_upward ) {
					
						
						if ( $this->enable_id_validity_checks ) {

							if ( $pk_column_name = $this->get_primary_key_column_name() ) {
							
								Debug::Show( "Checking ID validity in: {$my_class_name}. Parent is a: {$parent_class_name} with pk column {$pk_column_name} and pk of {$id}", Debug::VERBOSITY_LEVEL_INTERNAL );
							
								$iterator = $parent->fetch_all_by( $pk_column_name, $id );
								if ( $iterator->count <= 0 ) {
									throw new Exception(__CLASS__ . '-invalid_child_id', "\$id: {$id}");
								}
							}
						}
					
					}
					else {
				
						if ( $this->enable_id_validity_checks ) {
							if ( $parent_id = $parent->get_id() ) {
								if ( $pk_column_name = $this->get_primary_key_column_name() ) {
							
									$iterator  = $this->fetch_all_by( $pk_column_name, $id );
									if ( $iterator->count <= 0 ) {
										throw new Exception(__CLASS__ . '-invalid_child_id', "\$id: {$id}");
									}
								}
							}
						}
					}
				}
				
				//$this->bubble_column_value_upward( $this->column_name('id'), $id );
			}

			//
			// can't use __set() here
			// because functions called by __set() 
			// may also call this function
			//
		
			//$this->reset_record_data();
		
			if ( $this->record_row && $this->was_record_fetched() ) {
				if ( !array_val_is_nonzero($options, self::KEY_SKIP_RECORD_RESET) ) {
					$this->reset_record_data();
					$this->reset_child_models();
				}
			}
		
			$id_column = $this->get_id_column();
			$this->record_row[$id_column] = $id;

			//$this->was_changed(true);
			
			if ( $parent ) {
				$this->bubble_column_value_upward( $this->get_primary_key_column_name(), $id );
			}
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}

	function get_id( $options = array() ) {

		try { 
			//
			// can't use __get() here
			// because functions called by __get() 
			// may also call this function
			//
	
			$id_column = $this->get_id_column();
			$authoritative_alias = $this->get_authoritative_id_column_alias();
			
			if ( $authoritative_alias && isset($this->record_row[$authoritative_alias]) ) {
				return $this->record_row[$authoritative_alias];
			}
			else if ( $id_column && isset($this->record_row[$id_column]) ) {
				return	$this->record_row[$id_column];
			}
			else {
				if ( !isset($options[self::KEY_NO_RECORD_FETCH]) || $options[self::KEY_NO_RECORD_FETCH] == false ) {
					if (  $this->is_uniquely_identified(array('explicit_id_only' => true)) ) {
						
						$this->get_record( );
						if ( $id_column && isset($this->record_row[$id_column]) ) {
							return	$this->record_row[$id_column];
						}
					}
				}	
			}
			
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
		

	}

	function get_id_column() {
		
		return $this->get_primary_key_column_name();
	}
	
	function get_authoritative_id_column_alias( $options = array() ) {
		
		if ( isset($options[$this->_Key_table_alias]) && $options[$this->_Key_table_alias]) {
			$table = $options[$this->_Key_table_alias];
		}
		else {
			$table = $this->get_table_name();
		
			if ( $parent = $this->get_parent_model() ) {
				$table = $parent->child_table_key; 
			}
		}
		
		return strtolower(singularize($table)) . '_id' . self::SUFFIX_AUTHORITATIVE;
	}
	
	
	function get_primary_key_column_name() {
		
		try { 
			if ( $this->primary_key ) {
				if ( is_array($this->primary_key) ) {
					$ret = array();
					foreach( $this->primary_key as $cur_key ) {
						$ret[] = $this->column_name($cur_key);
					}
					
					return $ret;
				}
				else {
					$id_column_name = $this->column_name($this->primary_key);	
				}
				
			}
			else {	
				$id_column_name = $this->column_name('id');
			}
			
			if ( $this->table_has_column_name($id_column_name) ) {
				return $id_column_name; 
			}
	
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	
	/** 
	 * Deprecated alias to get_column_names()
	 */
	public function get_field_names() {
		try {
			return $this->get_column_names();
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function get_column_names() {

		try { 
			if ( !$this->_Column_names ) {
	
				$db = $this->get_db_object();
				$table_name = $this->get_table_name();
	
				$this->_Column_names = $db->get_column_names($table_name);
	
			}
	
			return $this->_Column_names;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	/**
	 *  Deprecated alias to table_has_column_name
	 */
	public function table_has_field_name( $field ) {
		return $this->table_has_column_name($field);
	}

	function table_has_column_name( $col ) {

		if ( $col_names = $this->get_column_names() ) {
			return in_array($col, $col_names);
		}
		
		return 0;

	}

	/**
	 *  Deprecated alias to table_has_column_key
	 * 
	 */
	function table_has_field_key( $field_key ) {
		
		return $this->table_has_column_key($field_key);
	}


	function table_has_column_key( $key ) {
		
		return $this->table_has_column_name($this->column_name_by_key($key));
	}

	function has_column( $col ) {
		
		return $this->table_has_column_name($this->column_name($col));
	}

	function refresh_record( $options = null ) {
		
		try { 
			$id = $this->id;
			
			$this->reset_record_data();
			$this->id = $id;
			
			$options[$this->_Key_force_new] = true;
			return $this->get_record($options);
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function get_record( $options = null ) {

		try { 
			
			$force_new = ( isset($options[$this->_Key_force_new]) && $options[$this->_Key_force_new] ) ? true : false;
	
			if ( $force_new || !$this->was_record_fetched() ) {
				
				if ( $result = $this->fetch_record_result($options) ) {
					
					$db_record = array();
					$db = $this->get_db_interface();
			
					if ( $db_record = $result->fetch(PDO::FETCH_ASSOC) ) {
				
						//$db_record = $db->fetch_unparsed_assoc($result);
							
						if ( ($this->was_changed() && !$this->was_saved()) && !$force_new && is_array($this->record_row) && (count($this->record_row) > 0) ) {
							$this->record_row = array_merge( $db_record, $this->record_row );
						}
						else {
							$this->record_row = $db_record;
						}
					}
						
					//
					// Since the record can be fetched using any unique key
					// specified in _Init() with has_unique_key, 
					// check to make sure that if we weren't given the primary key value,
					// it gets set once we have our row fetched.
					//
						
					Debug::Show( __METHOD__ ." column used to fetch row: {$this->_Fetch_column_used}", Debug::VERBOSITY_LEVEL_INTERNAL );
						
					if ( $this->_Fetch_column_used && ($this->_Fetch_column_used != $this->get_primary_key_column_name()) ) {
						$id_column = $this->get_id_column();
						if ( $id_column && isset($this->record_row[$id_column]) ) {
							$this->set_id( $this->record_row[$id_column], array(self::KEY_SKIP_RECORD_RESET => true) );
						}
					}
					
					$this->_Record_row_set_explicitly = false;
					//$this->apply_record_row( $this->_Record_row );
				
					$this->was_record_fetched(true);
				
				}
				else {
					
					if ( $this->was_changed() && !$this->was_saved() ) {
						return $this->record_row;
					}
					else {
						$this->_Record_row_set_explicitly = false;
						$this->record_row = array();
					}
				}

			}
			
			return $this->record_row;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_record_result( $result, $options = array() ) {
		
		$this->_Record_result = $result;
		
	}

	function get_record_without_related_tables( $options = null ) {

		try { 
			if ( !$this->record_row || !is_array($this->record_row) ) {
				
				$options[self::KEY_APPLY_RELATIONSHIPS] = false;
				return $this->get_record($options);
			}
			else {
			
				$table_cols = $this->get_column_names();
				$record = $this->record_row;
				
				foreach( $record as $key => $val ) {
					if ( !in_array($key, $table_cols) ) {
						unset($record[$key]);
					}
				}
			}
	
			return $record;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function &fetch_record_result_by( $col_key, $col_value, $options = null ) {
		try { 

	        $db = $this->get_db_interface();
	
			try {
				$result = $this->fetch_all_by($col_key, $col_value, $options);
			}
			catch (Exception $e) {
				throw $e;
			}
	
			if ( $db->num_rows($result) > 0 ) {
				throw new Exception( __CLASS__ . '-too_many_rows_for_single_record', "\$col_key: {$col_key} \$col_value:{$col_value}" );
			}
	
			return $result;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

    function &fetch_record_result( $options = null ) {

		try {

			Debug::Show( __FUNCTION__ . ' called in ' . get_class($this), Debug::VERBOSITY_LEVEL_EXTENDED );

			$db = $this->get_db_object();
			$options[$this->_Key_return_type] = $this->_Key_return_type_resultset;
			$force_new = false;

			$this->_Fetch_column_used = null;
			
			if ( !$this->_Record_result ) { 
				$force_new = true;
			}
			else {
				if (isset($options[$this->_Key_force_new]) && $options[$this->_Key_force_new]) {
					$force_new = true;				
				}			
				else {	
					if ( isset($options['include']) ) {
						if ( $this->_Last_fetch_include ) {
							if ( $this->_Last_fetch_include != $options['include'] ) {
								$force_new = true;
							}
						}
						
						$this->_Last_fetch_include = $options['include'];
					}
				}
			}	
			
					
			try {

			
			
				if ( $force_new  ) { //possible PDO TODO: or if PDO is enabled

					
					//
					// Note, since the primary key is now in the _Unique_keys array by default,  
					// the following if/then may be able to be removed, so that the 
					// search for a "fetch key" starts at foreach($this->_Unique_keys...)
					// Leaving it as is for now.
					//

					$options[self::KEY_REQUIRE_UNIQUE_IDENTIFIER] = true;

					// 
					// don't use get_id() here,
					// since it may try to call this method
					//

					$id_column_name = $this->get_id_column();

             		if ( isset($this->record_row[$id_column_name]) && $this->record_row[$id_column_name] ) { //}$id = $this->get_id() ) {
             			
             			$id = $this->record_row[$id_column_name];
             			
             			$this->_Record_result = $this->fetch_all_by( $this->get_primary_key_column_name(), $id, $options );
						$this->_Fetch_column_used = $this->get_primary_key_column_name();
					}
					else {
						
						//
						// The ID for this model wasn't set, 
						// but maybe another unique key was, and we can 
						// use that to fetch our row
						//
						if ( is_array($this->_Unique_columns) ) {
							foreach( $this->_Unique_columns as $column_name ) {
								
								//
								// Note: can't use __get() here
								// because of infinite loop, since __get()
								// will call this function
								//
								
								if ( isset($this->record_row[$column_name]) &&  $this->record_row[$column_name]) {
				
									$this->_Record_result = $this->fetch_all_by($column_name, $this->record_row[$column_name], $options );
		
									//if ( $db->num_rows($this->_Record_result) > 0 ) {
										$this->_Fetch_column_used = $column_name;
										break;	
									//}
								}
							}
						}
					}

             	}
				else {

					if ( $this->_Record_result && ($db->num_rows($this->_Record_result) > 0) ) {

						//
						// This is correct even for PDO - PDO will 
						// refetch in data_seek() if the result is 
						// iterated through twice
						//
						$db->data_seek( $this->_Record_result, 0 );
					}
				}
				
            	return $this->_Record_result;
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
    }
       
        
    

	function belongs_to( $object_name, $options = null ) {

		$options[$this->_Key_relationship_type] = $this->_Key_relationship_type_many_to_one;
		$options[$this->_Key_relationship_direction] = $this->_Key_relationship_direction_upward;
		
		$this->_Add_relationship($object_name, $options);

	}

	function has_one_from_one( $object_name, $options = null ) {

		$options[$this->_Key_relationship_type] = $this->_Key_relationship_type_one_to_one;
		$options[$this->_Key_relationship_direction] = $this->_Key_relationship_direction_lateral;
		
		$this->_Add_relationship($object_name, $options);

	}

	function has_one( $object_name, $options = null ) {

		//$options[$this->_Key_relationship_type] = $this->_Key_relationship_type_many_to_one;
		$options[$this->_Key_relationship_type] = $this->_Key_relationship_type_one_to_one;
		$options[$this->_Key_relationship_direction] = $this->_Key_relationship_direction_downward;
		
		$this->_Add_relationship($object_name, $options);
	}

	function has_many( $object_name, $options = null ) {

		$options[$this->_Key_relationship_type] = $this->_Key_relationship_type_one_to_many;
		$options[$this->_Key_relationship_direction] = $this->_Key_relationship_direction_downward;

		$this->_Add_relationship($object_name, $options);
	}

	function has_unique_key( $key = null ) {
		
		if ( $key !== null ) {
			$this->_Unique_keys[] = $key;
			$this->_Unique_columns[] = $this->column_name($key);
		}
		else {
			return in_array( $key, $this->_Unique_keys );
		}
		
	}

	function has_unique_column( $column = null ) {
		
		if ( $column !== null ) {
			$this->_Unique_keys[] = $this->column_key_by_name($column);
			$this->_Unique_columns[] = $this->column_name($column);
		}
		else {
			return in_array( $this->column_name($column), $this->_Unique_columns );
		}
		
	}

	private final function _Add_relationship( $class_name, $options ){

		try { 
			$library = null;
			$link_table			    = null;
			
			if ( !$class_name ) {
				throw new MissingParameterException('class_name');
			}
			
			if ( LL::class_name_from_location_reference($class_name) != $class_name ) {
				//
				// Library was included in class name
				//
				$library    = LL::class_library_from_location_reference($class_name);
				$class_name = LL::class_name_from_location_reference($class_name);
				
				if ( !isset($options[$this->_Key_relationship_model_library]) ) {
					$options[$this->_Key_relationship_model_library] = $library;
				}
			}
	
			if ( !isset($options[$this->_Key_relationship_class_name]) || !$options[$this->_Key_relationship_class_name] ) {
				$options[$this->_Key_relationship_class_name] = $class_name;
			}
	
			if ( !isset($options[$this->_Key_relationship_table_from]) || !$options[$this->_Key_relationship_table_from] ) {
				$options[$this->_Key_relationship_table_from] = $this->get_table_name();
			}
	
			if ( isset($options[$this->_Key_table]) && $options[$this->_Key_table] ) {
				$options[$this->_Key_relationship_table_to] = $options[$this->_Key_table];
			}
			else if ( array_val_is_nonzero($options, self::KEY_FOREIGN_TABLE_NAME) ) {
				$options[$this->_Key_relationship_table_to] = $options[self::KEY_FOREIGN_TABLE_NAME];
			}
			else {
				$options[$this->_Key_relationship_table_to] = $this->_Table_name_from_class_name($class_name);
			}
	
			if ( isset($options[$this->_Key_table_alias]) && $options[$this->_Key_table_alias] ) {
				$this->add_table_reference_alias($options[$this->_Key_table_alias], $options[$this->_Key_relationship_table_to]);
			}
	
			if ( isset($options[$this->_Key_association_link_table]) ) { 
				
				$link_options = $this->determine_link_table_columns_by_rel_data($options);
				$link_options[$this->_Key_relationship_class_name] = $class_name;
				$link_options[self::KEY_RELATIONSHIP_DATA] = $options;
				
				$this->_Link_columns[$link_options[self::KEY_LINK_FOREIGN_COLUMN]] = $link_options;
	
				Debug::Show( "Adding link column {$link_options[self::KEY_LINK_FOREIGN_COLUMN]} for model {$class_name}", Debug::VERBOSITY_LEVEL_EXTENDED );
				
				
			}
			
			$table_ref = (isset($options[$this->_Key_table_alias]) && $options[$this->_Key_table_alias]) ? $options[$this->_Key_table_alias] : $options[$this->_Key_relationship_table_to];
	
			$this->_Related_classes_by_table[$table_ref] = $options;
			
			//Deprecated
			$this->_Relationships[$class_name] = $options;
		}
		catch( Exception $e ) {
			throw $e;
		}
			
	}

	public function determine_link_table_columns_by_rel_data( $rel_data ) {

		try {

			$link_key_column_foreign = null;
			$link_key_column_local   = null;
		
			$link_table_column_foreign = null;
			$link_table_column_local   = null;
			
			if ( isset($rel_data[$this->_Key_relationship_model_library]) ) {
				$class_library = $rel_data[$this->_Key_relationship_model_library];
			}
			else {
				$class_library = null;
			}
			
			if ( isset($rel_data[$this->_Key_association_link_table]) ) {
				$link_options = $rel_data[$this->_Key_association_link_table];
			}
			else {
				throw new Exception( __CLASS__ . '-no_link_table_specified_for_m2m');
			}
			
			$class_name   = $rel_data[$this->_Key_relationship_class_name];
			
			if ( is_array($link_options) ) {			
				
				if ( isset($link_options[self::KEY_TABLE]) ) {
					$link_table 	    = $link_options[self::KEY_TABLE];
				}
				
				if ( isset($link_options[self::KEY_LINK_FOREIGN_COLUMN]) ) {
					$link_key_column_foreign = $link_options[self::KEY_LINK_FOREIGN_COLUMN];
				}
				
				if ( isset($link_options[self::KEY_LINK_LOCAL_COLUMN]) ) {
					$link_key_column_local   = $link_options[self::KEY_LINK_LOCAL_COLUMN];
				}

				if ( isset($link_options[self::KEY_LINK_TABLE_FOREIGN_COLUMN]) ) {
					$link_table_column_foreign = $link_options[self::KEY_LINK_TABLE_FOREIGN_COLUMN];
				}
				
				if ( isset($link_options[self::KEY_LINK_TABLE_LOCAL_COLUMN]) ) {
					$link_table_column_local   = $link_options[self::KEY_LINK_TABLE_LOCAL_COLUMN];
				}
				
			}
			else {
				$link_table = $link_options; //link table passed as scalar, use default naming conventions
			}
			
			if ( !$link_key_column_foreign ) {

				//
				// TODO: figure how to apply association links without
				// preloading model
				
				$rel_object = $this->load_model_by_rel_data($rel_data);
			
				/*
				if ( !($rel_object = $this->_Model_object_by_relationship_data($rel_data)) ) {
					throw new Exception( __CLASS__ . '-no_related_model_found');
				}
				*/
				
				$link_key_column_foreign = $rel_object->foreign_key_column_name();
			}

			if ( !$link_table_column_foreign ) {
				$link_table_column_foreign = $link_key_column_foreign;
			}

			if ( !$link_key_column_local ) {
				$link_key_column_local = $this->get_id_column();
			}

			if ( !$link_table_column_local ) {
				$link_table_column_local = $link_key_column_local;
			}

			if ( is_array($link_options) ) {
				$ret = $link_options; // maintain any options that we're not
									  // overriding below in $ret
			}
			else {
				//
				// no non-default options specified. 
				// The link table only was passed as scalar. 
				//
				$ret = array(); 
			}
			
			$ret[self::KEY_TABLE] = $link_table;
			$ret[self::KEY_LINK_FOREIGN_COLUMN] = $link_key_column_foreign;
			$ret[self::KEY_LINK_LOCAL_COLUMN]   = $link_key_column_local;
			$ret[self::KEY_LINK_TABLE_FOREIGN_COLUMN] = $link_table_column_foreign; 
			$ret[self::KEY_LINK_TABLE_LOCAL_COLUMN] = $link_table_column_local;

			return $ret;

		}
		catch( Exception $e ) { 
			throw $e;
		}
	}

	//
	// Deprecated in favor of relationship data by table name, 
	// because doing it by table name supports aliasing
	//
	public function relationship_data_by_class_name( $class_name ) {
		
		if ( isset($this->_Relationships[$class_name]) ) {
			return $this->_Relationships[$class_name];
		}
		else {
			trigger_error( "Referenced nonexistent relationship to {$class_name} in " . get_class($this), E_USER_ERROR );
		}
		
		return null;
	}

	public function relationship_data_by_table_name( $table ) {
		
		if ( isset($this->_Related_classes_by_table[$table]) ) {
			return $this->_Related_classes_by_table[$table];
		}
		else {
			trigger_error( "Referenced nonexistent relationship to {$table} in " . get_class($this), E_USER_ERROR );
		}
		
		return null;
	}


	public function foreign_key_column_by_table_name( $table ) {

		try { 

			$foreign_key_col = null;
			$foreign_obj = $this->_Model_object_by_table_reference($table);
			$rel_data  	 = $this->relationship_data_by_table_name($table);

			if ( isset($rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY])
					&& $rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY] ) {
				
				$foreign_key_col = $foreign_obj->column_name($rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY]);
				
			}
			else {

				if ( $foreign_obj ) {
				
					$foreign_key_col = $foreign_obj->foreign_key_column_name();
				}
			
			}
				
			return $foreign_key_col;
					
		}
		catch( Exception $e ) {
			throw $e;
		}

	}
	
	function foreign_key_column_name( $explicit_column_key = null ) {
		
		$foreign_key_col = null;
		
		if ( $explicit_column_key ) {
			$foreign_key_key = $explicit_column_key;
		}
		else {
			if ( $this->_Foreign_key ) {
				$foreign_key_key = $this->_Foreign_key;
			}
			else {
				$foreign_key_key = $this->column_key_by_name($this->get_primary_key_column_name());
			}
		}
		
		$foreign_key_col = $this->column_name($foreign_key_key);
		
		return $foreign_key_col;
		
	}
	
	/*
	function is_related_to( $class_name ) {

		if ( isset($this->_Relationships[$class_name]) && $this->_Relationships[$class_name] ) {
			return true;
		}

		return 0;
	}
	*/

	function is_uniquely_identified($options = array()) {
		
		try {
			
			$authoritative_alias = $this->get_authoritative_id_column_alias();
			
			if ( isset($this->record_row[$authoritative_alias]) && $this->record_row[$authoritative_alias] ) { 
				return true;
			}
			
			foreach( $this->_Unique_columns as $unique_col ) {
				
				/*
				$authoritative_key = $unique_field . self::SUFFIX_AUTHORITATIVE;
				
				if ( (!isset($options['explicit_id_only']) || !$options['explicit_id_only']) && isset($this->record_row[$authoritative_key]) && $this->record_row[$authoritative_key] ) { 
					return true;
				}
				else 
				*/
				
				if ( isset($this->record_row[$unique_col]) && $this->record_row[$unique_col] ) { 
					return true;
				}
			}
			
			return 0;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	function is_unique_key( $key ) {
		
		try {
			if (in_array($key, $this->_Unique_keys) ) { 
				return true;
			}
			
			return 0;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function column_has_unique_key( $col_name ) {
		
		try {

			$col_name = $this->column_name($col_name);
			
			if ( in_array($col_name, $this->_Unique_columns) ) { 
				return true;
			}
			
			return 0;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	function require_unique_identifier() {
		
		if ( !$this->is_uniquely_identified() ) {
			trigger_error( 'No Unique identifier for ' . get_class($this), E_USER_ERROR );
			exit;
		}
		
		return true;
		
	}

	function _Table_name_from_class_name( $class_name ) {

		if ( (substr($class_name, 0 - strlen(self::CLASS_NAME_SUFFIX)) == self::CLASS_NAME_SUFFIX) ) {
				$class_name = substr($class_name, 0, 0 - strlen(self::CLASS_NAME_SUFFIX) );				
		}

		$class_name = camel_case_to_underscore($class_name, array ( 'keep_number_positioning' => $this->camel_case_keep_number_positioning) );
		$class_name = strtolower($class_name);
		$class_name = pluralize($class_name);

		return $class_name;

	}

	function &apply_relationships_to_query_obj( &$query_obj, $options = null ) {

		try {
			Debug::Show( __FUNCTION__ . ' called from ' . get_class($this), Debug::VERBOSITY_LEVEL_EXTENDED );
	
			static $applied_parents = array();
			$force_parent_join 	    = false; //per-relationship only...don't set this globally 
			$parent_joined = false;
			$include_keys    = array();
			$includes		 = array();
			$parent = $this->get_parent_model();
			$parent_table = null;
			$parent_filter_options = array();
			$rel_included = false;
			$include_required = false;
			$force_parent_id_filter = ( isset($options['force_parent_id_filter']) ) ? $options['force_parent_id_filter'] : false;
	
			$parent_filter_required = false;
	
			if ( isset($options['parent_filter_required']) ) {
				$parent_filter_required = $options['parent_filter_required'];
			}		
		
			if ( $parent ) {
				$parent_table = $parent->active_table_key;
			}
			
			if ( isset($options[$this->_Key_include]) ) {
					
					if ( is_scalar($options[$this->_Key_include]) ) {
						$includes = array($options[$this->_Key_include]);
					}
					else {
						$includes = $options[$this->_Key_include];
					}
	
					$include_requred = true;
					$include_keys = array_keys($includes);
					
					if ( isset($options[$this->_Key_include]) && $options[$this->_Key_include]  ) {
						$this->apply_includes_to_query_obj( $query_obj, $options[$this->_Key_include], $options);
					}
			}
			
			if ( is_array($this->_Related_classes_by_table) ) {
				
				foreach( $this->_Related_classes_by_table as $rel_table_name => $rel_data ) {
					
					$rel_class_name = $rel_data[$this->_Key_relationship_class_name];
					$rel_type = $rel_data[$this->_Key_relationship_type];
					$rel_direction = $rel_data[$this->_Key_relationship_direction];
					//$rel_object = $this->_Model_object_by_relationship_data($rel_data);
					
					if ( isset($rel_data[$this->_Key_association_link_table]) ) {
						$link_table   = $this->extract_link_table_from_rel_option($rel_data[$this->_Key_association_link_table]);
					}
					else {
						$link_table = null;
					}
					
					$rel_included 	   = false;
					$force_parent_join = false;
					
					Debug::Show( get_class($this) . " relationship to {$rel_table_name} has type: {$rel_type} and direction: {$rel_direction}", Debug::VERBOSITY_LEVEL_EXTENDED );

					//if ( isset($options[$this->_Key_include]) && $options[$this->_Key_include]  ) {
					//	$this->apply_includes_to_query_obj( $query_obj, $options[$this->_Key_include], $options);
					//}
					
					/*
					if ( isset($options[$this->_Key_include]) && $options[$this->_Key_include] && $options[$this->_Key_include] != $this->_Key_all_relationships ) {
						
						
						if ( count($include_keys) > 0 ) {
	
							foreach( $include_keys as $cur_key ) {
								
								$include_options = array(); //$this->collect_include_options( $options );
	
								$cur_include = $includes[$cur_key];
	
								if ( is_array($cur_include) ) {
									//$include_table = $cur_include[0];
									//$include_aliases = $cur_include[1];
									$include_table = $cur_key;
									
									if ( isset($cur_include[$this->_Key_table_alias]) ) {
										$include_aliases = $cur_include[$this->_Key_table_alias];
									}
									else {
										$include_aliases = $cur_key;
									}
									
									$include_options = $cur_include;
								}
								else {
									$include_table = $cur_include;
									$include_aliases = $cur_include;
								}
	
								if ( is_scalar($include_aliases) ) {
									$include_aliases = array($include_aliases);
								}
	
								foreach( $include_aliases as $cur_alias ) {
									
									
									Debug::Show( "Checking for include table: {$cur_alias}", Debug::VERBOSITY_LEVEL_INTERNAL );
				
									$include_found = false;
									
									if ( $cur_alias != $include_table ) {
										$include_options[$this->_Key_table_alias] = $cur_alias;
									}
			
									if ( $include_table == $parent_table ) {
										$parent_joined = true;
									}
									
									if ( $include_table == $this->table_name || ($this->active_table_key && $include_table == $this->active_table_key) ) {
										continue;
									}
									
									//
									// Check to see if we're including a table through another included 
									// table, represented by table1.table2
									//
									if ( strpos($include_table, self::INCLUDE_TABLE_SEPARATOR) === false ) {
										
										if ( !$this->_Model_object_by_table_reference($include_table) ) {
											throw new Exception( __CLASS__ . "-invalid_table_included %{$include_table}%");
										}
										
										if ( $this->table_key_by_relationship_data($rel_data) == $include_table ) {
											$include_found = true;
											Debug::Show( "Applying related table: {$include_table}", Debug::VERBOSITY_LEVEL_INTERNAL );
											$rel_included = true;
											$query_obj = $this->_Apply_relationship_to_query_obj( $query_obj, $rel_data, $include_options );
										}
									}
									else {
										if ( $this->apply_separated_table_include_to_query_obj($include_table, $query_obj, $include_options) ) {
											$include_found = true;
											$rel_included = true;
										}
									}

								}
	
							}
						}
					}
					else {
						if ( isset($options[$this->_Key_include]) && $options[$this->_Key_include] == $this->_Key_all_relationships ) {
							$rel_included  = true;
							$parent_joined = true;
							$query_obj = $this->_Apply_relationship_to_query_obj( $query_obj, $rel_data, $options );
						}
					}
					*/
	
					if ( $parent ) {	
						
						//$parent_class_name = get_class($parent);
						//$my_class_name     = get_class($this);
						
						
						$rel_table_key = $this->table_key_by_relationship_data($rel_data);
						if ( $parent->active_table_key == $rel_table_key ) {
							
								if ( $link_table ) {
									
									$force_parent_join = true;
									
									if ( !$this->get_id() ) {
										
										//$query_obj = $parent->apply_id_filter_to_query_obj($query_obj, array(
										//														'table' => $link_table));
										$query_obj = $parent->apply_link_table_filter_to_query_obj( $query_obj, $rel_data, $options );
														
									}
								}
						}
							
						if ( !$parent_joined && $parent && ($parent->is_uniquely_identified() || $parent->get_parent_model()) && ($force_parent_join || $this->_Relationship_join_required_by_parent($rel_data)) ) {
							$parent_joined = true;
							$query_obj = $this->_Apply_relationship_to_query_obj( $query_obj, $rel_data, $options );
									
						}
								
						if ( $force_parent_id_filter || (!$parent_filter_required && $parent && $parent->is_uniquely_identified() && $this->_Relationship_requires_parent_id_filter($rel_data)) ) {
							Debug::Show( "Parent id filter required for {$parent->active_table_key}->{$rel_table_key}", Debug::VERBOSITY_LEVEL_EXTENDED );
							$parent_filter_required = true;
						}
	
					}
				}
	
				
				//if ( $include_required && !$include_found ) {
				//	trigger_error( "Couldn't find table for include: {$include_table}", E_USER_ERROR );
				//	exit;
				//}
				
				if ( $parent_filter_required ) {
					
					if ( $parent ) {
	
						if ( $parent->get_id() ) {
							
							//$id_column = $parent->get_id_column();
	
							$parent_rel_data = $parent->relationship_data_by_table_name($parent->child_table_key);
							
							if ( isset($parent_rel_data['foreign_key']) &&  $parent_rel_data['foreign_key'] ) {
								$id_column = $parent_rel_data['foreign_key'];
								$parent_filter_options[self::KEY_ID_COLUMN] = $id_column;
							}
	
							$parent_filter_options[$this->_Key_require_id] = true;
							
							if ( !isset($parent_filter_options[$this->_Key_table]) ) {
								
								$rel_data = $this->relationship_data_by_table_name($parent->active_table_key);
								
								if ( $rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_downward 
										&& $rel_data[$this->_Key_relationship_type] == $this->_Key_relationship_type_one_to_many ) {
											
									$parent_filter_options[$this->_Key_table] = $parent->get_table_name();
								} 
								else {
									$parent_filter_options[$this->_Key_table] = $this->get_table_name();		
								}
							
							}
							
							
							if ( !$query_obj->has_completed_setup( get_class($parent) . self::SUFFIX_QUERY_ID_FILTER) ) {
								
								Debug::Show( 'Applying parent ID filter for ' . get_class($parent) . ' in ' . get_class($this) . '::' . __FUNCTION__, Debug::VERBOSITY_LEVEL_EXTENDED );
								
								$query_obj = $parent->apply_id_filter_to_query_obj($query_obj, $parent_filter_options);
								$query_obj->add_completed_setup( get_class($parent) . self::SUFFIX_QUERY_ID_FILTER );
	
							}
						}
	
					}
				}
	
			}
	
			
			if ( $parent ) {
	
				if ( $parent_parent = $parent->get_parent_model() ) {
				
					if ( !$parent_joined ) {
						
						$parent_rel_data = $this->relationship_data_by_table_name($parent->active_table_key);
						$query_obj = $this->_Apply_relationship_to_query_obj( $query_obj, $parent_rel_data, $options );
							
					} 
	
					$nested_options['include'] = $parent_parent->get_table_name();
					//$nested_options['require_parent_join'] = true;
					$nested_options['force_parent_id_filter'] = true;
					//$nested_options['table'] = $parent->get_table_name();
					
					//$nested_options['from_child'] = true;
					//echo $parent_parent->id;

					//
					// Need to account for the situation where we have
					// $category->entry->categories->fetch_all(), 
					// which would result in an infinite loop without this check
					//
					if ( !in_array($parent->get_table_name(), $applied_parents) ) {
						$applied_parents = array( $parent->get_table_name() );
						$query_obj = $parent->apply_relationships_to_query_obj( $query_obj, $nested_options );
					}
					
					$applied_parents = array();
					
					
				}
			}
			
	
			return $query_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function apply_includes_to_query_obj( $query_obj, $includes, $options = array() ) {
		
		try {
			
			$rel_included 	   = false;
			$force_parent_join = false;
			$parent_table	   = null;

			if ( $parent = $this->get_parent_model() ) {
				$parent_table = $parent->active_table_key;
			}
			
			if ( is_scalar($includes) ) {
				$includes = array($includes);
			}

			$include_required = true; //for now, this is always true
			$include_keys = array_keys($includes);
			
			if ( count($include_keys) > 0 ) {

				foreach( $include_keys as $cur_key ) {
					
					
					$include_options = array(); //$this->collect_include_options( $options );

					$cur_include = $includes[$cur_key];
					
					if ( is_array($cur_include) ) {
						$include_table = $cur_key;
							
						if ( isset($cur_include[$this->_Key_table_alias]) ) {
							$include_aliases = $cur_include[$this->_Key_table_alias];
						}
						else {
							$include_aliases = $cur_key;
						}
							
						$include_options = $cur_include;
					}
					else {
						$include_table = $cur_include;
						$include_aliases = $cur_include;
					}

					if ( is_scalar($include_aliases) ) {
						$include_aliases = array($include_aliases);
					}

					foreach( $include_aliases as $cur_alias ) {
							
						Debug::Show( "Checking for include table: {$cur_alias}", Debug::VERBOSITY_LEVEL_INTERNAL );
		
						$include_found = false;
							
						if ( $cur_alias != $include_table ) {
							$include_options[$this->_Key_table_alias] = $cur_alias;
						}

						if ( $include_table == $parent_table ) {
							$parent_joined = true;
						}
							
						if ( $include_table == $this->table_name || ($this->active_table_key && $include_table == $this->active_table_key) ) {
							continue;
						}
							
						//
						// Check to see if we're including a table through another included 
						// table, represented by table1.table2
						//
											
						if ( strpos($include_table, self::INCLUDE_TABLE_SEPARATOR) === false ) {

							$rel_data = $this->relationship_data_by_table_name($include_table);
								
							if ( !$this->_Model_object_by_table_reference($include_table) ) {
								throw new Exception( __CLASS__ . "-invalid_table_included %{$include_table}%");
							}
								
							if ( $this->table_key_by_relationship_data($rel_data) == $include_table ) {
								$include_found = true;
								Debug::Show( "Applying related table: {$include_table}", Debug::VERBOSITY_LEVEL_INTERNAL );
								$rel_included = true;
								$query_obj = $this->_Apply_relationship_to_query_obj( $query_obj, $rel_data, $include_options );
							}
						}
						else {
							if ( $this->apply_separated_table_include_to_query_obj($include_table, $query_obj, $include_options) ) {
								$include_found = true;
								$rel_included = true;
							}
						}

					}
					
					
					if ( $include_required && !$include_found ) {
						trigger_error( "Couldn't find table for include: {$include_table} in " . get_class($this), E_USER_ERROR );
						exit;
					}
					
				}
			}

			return $query_obj;
				
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	/*
	public function collect_include_options ( $from_options ) {
		
		$include_options = array();
		
		if ( isset($from_options['join_type']) ) {
			$include_options['join_type'] = $from_options['join_type'];
		}
		
		return $include_options;
		
	}
	*/

	public function apply_link_table_filter_to_query_obj( $query_obj, $rel_data, $options = null ) {
	
		try { 
			
			$link_options = $this->determine_link_table_columns_by_rel_data($rel_data, $options );
	
			$query_obj = $this->apply_unique_filter_to_query_obj($query_obj, array(
							self::KEY_TABLE => $link_options[self::KEY_TABLE],
							self::KEY_COLUMN => $link_options[self::KEY_LINK_TABLE_FOREIGN_COLUMN],
							self::KEY_VALUE => $this->$link_options[self::KEY_LINK_FOREIGN_COLUMN],
							self::KEY_REQUIRE_VALUE => true
							)
						);
			
			return $query_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}			
	}													

	public function apply_separated_table_include_to_query_obj( $table_string, $query_obj, $options = null ) {
		
		try {
			
			$remainder = null;
			
			if ( false !== ($first_sep = strpos($table_string, self::INCLUDE_TABLE_SEPARATOR)) ) {
				$first_table = substr( $table_string, 0, $first_sep  );
				$second_table = substr( $table_string, $first_sep + 1 );
				
				if ( $model = $this->_Model_object_by_table_reference($first_table) ) {
					
					
					if ( false !== ($second_sep = strpos($second_table, self::INCLUDE_TABLE_SEPARATOR)) ) {
						$second_table = substr( $table_string, 0, $second_sep );
						$remainder    = substr( $table_string, $second_sep + 1 );
															
					}

					if ( !$model->model_object_by_table_reference($second_table) ) {
						throw new Exception( __CLASS__ . "-invalid_table_included %{$first_table}.{$second_table}%");
					}

					if ( $relationships = $model->get_relationship_data() ) {
						foreach( $relationships as $rel_class_name => $rel_data ) {
							
							$table_compare = isset($rel_data[$this->_Key_table_alias]) ? $rel_data[$this->_Key_table_alias] : $rel_data[$this->_Key_relationship_table_to];  
							
							if ( $table_compare == $second_table ) {
								Debug::Show( "Applying related table from include string: {$first_table}", Debug::VERBOSITY_LEVEL_INTERNAL );
								
								$options['from_model'] = $model;
								$query_obj = $this->_Apply_relationship_to_query_obj( $query_obj, $rel_data, $options );
								
								if ( $remainder ) {
									return $model->apply_separated_table_include_to_query_obj($remainder, $query_obj, $options);
								}
								
								return true;
							}
						}

						
					}
					
					
				}
				else {
					throw new Exception( __CLASS__ . "-invalid_table_included %{$table_string}%");
				}
								
			}
			
			return 0;			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	protected function _Relationship_requires_parent_id_filter( $rel_data ) {
		
		try {
			
			$filter_required = 0;
			
			$rel_type 	   = $rel_data[$this->_Key_relationship_type];
			$rel_direction = $rel_data[$this->_Key_relationship_direction];
			
			if ( ($rel_type == $this->_Key_relationship_type_many_to_one && $rel_direction != $this->_Key_relationship_direction_upward)  ) {

				if ( !$this->get_id() ) {
					Debug::Show( "Parent filter is required for {$rel_class_name}", Debug::VERBOSITY_LEVEL_INTERNAL );
					$filter_required = true;
				}
			}
			
			return $filter_required;
		}		
		catch( Exception $e ) { 
			throw $e;
		}
	}

	protected function _Relationship_join_required_by_parent( $rel_data ) {
		
		try {
			
			$join_required = 0;
			
			$rel_class_name = $rel_data[$this->_Key_relationship_class_name];
			$rel_type 	   = $rel_data[$this->_Key_relationship_type];
			$rel_direction = $rel_data[$this->_Key_relationship_direction];
			
			//if ( ($parent = $this->get_parent_model()) && $parent->is_uniquely_identified() ) {
			
				if ( ($rel_type == $this->_Key_relationship_type_many_to_one && $rel_direction != $this->_Key_relationship_direction_upward)  ) {

					Debug::Show( "Parent join is required for {$rel_class_name}", Debug::VERBOSITY_LEVEL_INTERNAL );
					$join_required = true;
				
				}

				if ( ($parent = $this->get_parent_model()) && !$join_required ) {
					
					//$parent_class_name = get_class($parent);
					//$my_class_name     = get_class($this);
			
					if ( isset($rel_data[$this->_Key_association_link_table]) ) {
						$link_table = $this->extract_link_table_from_rel_option($rel_data[$this->_Key_association_link_table]);
					}
					else {
						$link_table = null;
					}
						
					if ( $parent->active_table_key == $this->table_key_by_relationship_data($rel_data) ) { 
						if ( $link_table ) {
							$join_required = true;
						}
					}
				}
			//}
			
			return $join_required;
		}		
		catch( Exception $e ) { 
			throw $e;
		}
	}

	protected final function _Apply_relationship_to_query_obj( $query_obj, $rel_data, $options = null ) {

		$type = $rel_data[$this->_Key_relationship_type];
		$join_type = null;

		if ( isset($options['join_type']) ) {
			$join_type = $options['join_type'];
		}
		else {
			if ( isset($rel_data['join_type']) ) {
				$join_type = $rel_data['join_type'];
			}
		}
		
		
		$model_library = ( isset($rel_data[$this->_Key_relationship_model_library]) ) ? $rel_data[$this->_Key_relationship_model_library] : null;
		$link_table = null;
		$column_name_from = null;
		$column_name_to   = null;
		$rel_object = $this->_Model_object_by_relationship_data($rel_data);

		//echo "rel type: {$type} for " . get_class($rel_object) . ' through table ' . $table_to . ' and column: ' . $rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY] . '<br />';

		Debug::Show( 'Relationship data: ' .  print_r( $rel_data, true ), Debug::VERBOSITY_LEVEL_EXTENDED ); 

		//
		// Get our table names for this association
		//
		$table_to   = $rel_data[$this->_Key_relationship_table_to];
		$table_to_alias = $table_to;
		$table_from = $rel_data[$this->_Key_relationship_table_from];

		

		$from_model = isset($options['from_model']) && $options['from_model'] ? $options['from_model'] : $this;
		
		if ( isset($rel_data[self::KEY_LOCAL_KEY]) ) {
			$column_name_from = $from_model->column_name($rel_data[self::KEY_LOCAL_KEY]);
			
		}
				
		//
		// Get our columns for this association
		//
		if ( isset($rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY]) ) {
			$column_name_to   = $rel_object->column_name($rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY]);
			
			if ( !$column_name_from ) {			
				$column_name_from = $from_model->column_name($rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY]);
			}
			
		}

		else {
			
			
			if ( $rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_lateral ) {
							
				$column_name_to   = $rel_object->foreign_key_column_name();
				$rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY] = $rel_object->strip_column_prefix_from_name($column_name_to);
				
				if ( !$column_name_from ) {
					$column_name_from = $column_name_to;
				}

				//$column_name_from = $rel_object->foreign_key_column_name( $rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY] );			
			}
			else if ( $rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_upward ) {
			
				
				// this belongs_to rel_object
				// this should have an id column referencing rel object's ID column
			
				$column_name_to   = $rel_object->foreign_key_column_name();
				$rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY] = $rel_object->strip_column_prefix_from_name($column_name_to);
				
				if ( !$column_name_from ) {
					$column_name_from = $column_name_to;
				}
				
				
			}
			else if ( $rel_data[$this->_Key_relationship_direction] == $this->_Key_relationship_direction_downward ) {
			
				// this has_many rel_object
				// rel_object should have an ID column referencing our ID column
			
				//$column_name_to = $this->column_name_by_key($this->strip_column_prefix_from_key($rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY]));
				$column_name_to = $from_model->get_id_column();
				$rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY] = $this->_Key_id;
				
				if ( !$column_name_from ) {
					$column_name_from = $column_name_to;
				}
				
			}	
		}
		
		
		if ( isset($rel_data[$this->_Key_association_link_table]) && $rel_data[$this->_Key_association_link_table] ) {

			$this->apply_link_table_joins_to_query_obj( $query_obj, $rel_data, $options );

			$link_options = $from_model->determine_link_table_columns_by_rel_data($rel_data);
			$link_table   = $link_options[self::KEY_TABLE];
			$table_from   = $link_table;
			
			if ( !$join_type ) {
				$join_type = 'INNER';
			}
			
			$column_name_to   = $rel_object->foreign_key_column_name();
			$column_name_from   = $rel_object->foreign_key_column_name();
		}	

		
		if ( !isset($options[$this->_Key_table_alias]) || !$options[$this->_Key_table_alias] ) {
			$table_to_alias = $this->join_alias_by_table_name($table_to, $rel_data);
		}
		else {
			$table_to_alias = $options[$this->_Key_table_alias];
		}

		$to_alias_clause = ( $table_to_alias != $table_to ) ? " AS {$table_to_alias} " : null;

		if ( !isset($options['table_from_alias']) ) {
			if ( !$link_table ) {
				
				if ( $from_table_key = $rel_object->find_table_key_by_foreign_table_and_fk($table_from, $column_name_from) ) {
					$table_from = $from_table_key;				
				}
				
			}
		}
		else {
			$table_from = $options['table_from_alias'];
		}	
			
			
			
			
		if ( !isset($options[$this->_Key_table_alias]) || !$options[$this->_Key_table_alias]) {
			$options[$this->_Key_table_alias] = $table_to_alias;
		}
		
			

		//if ( !$link_table ) {

			if ( !$query_obj->has_completed_setup($table_to . $this->_Key_selections_base) ) {
				if ( !isset($options['apply_base_selections']) || $options['apply_base_selections'] == true ) {
					$rel_object->apply_base_selections_to_query_obj($query_obj, $options);
				}
			}
		
			if ( !$query_obj->has_from($table_to_alias) ) {
				
				if ( !$join_type ) {
					$join_type = self::$Default_join_type; //'LEFT';
				}
				
				if ( $join_type == 'auto' ) {
					$query_obj->from( "{$table_to}{$to_alias_clause}" );
					$query_obj->where( "{$table_to}{$to_alias_clause}.{$column_name_to} = {$table_from}.{$column_name_from}" );
				}
				else {
					$query_obj->join( "{$join_type} JOIN {$table_to}{$to_alias_clause} ON {$table_to_alias}.{$column_name_to} = {$table_from}.{$column_name_from}" );
				}
			}
		//}

		Debug::Show( "Applied relationship to {$table_to} via column: " . $rel_data[self::KEY_RELATIONSHIP_COLUMN_KEY], Debug::VERBOSITY_LEVEL_EXTENDED );

		return $query_obj;
	}

	public function apply_link_table_joins_to_query_obj( $query_obj, $rel_data, $options = null ) {
		
		try { 

			$join_type = 'INNER';
			$link_options = $this->determine_link_table_columns_by_rel_data($rel_data);
			
			$table_from = $rel_data[$this->_Key_relationship_table_from];
			$link_table = $link_options[self::KEY_TABLE];
			Debug::Show( "Relationship uses link table: {$link_options[self::KEY_TABLE]}", Debug::VERBOSITY_LEVEL_EXTENDED );
			
			if ( isset($options['join_type']) ) {
				$join_type = $options['join_type'];
			}
			else {
				if ( isset($rel_data['join_type']) ) {
					$join_type = $rel_data['join_type'];
				}
			}
			
			$query_obj->join( "{$join_type} JOIN {$link_table} ON {$link_table}.{$link_options[self::KEY_LINK_TABLE_LOCAL_COLUMN]} = {$table_from}.{$link_options[self::KEY_LINK_LOCAL_COLUMN]}");
			
			return $query_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}		

	}

	public function extract_link_table_from_rel_option( $options ) {
		
		if ( is_array($options) ) {
			if ( isset($options[self::KEY_TABLE]) ) {
				return $options[self::KEY_TABLE];
			}
		}
		else {
			if ( $options ) {
				return $options;
			}
		}
		
		return null;
	}

	private function apply_id_filter_to_query_obj( &$query_obj, $options = null ) {

		//
		// This can probably be redirected to apply_unique_filter_to_query_obj 
		// , but respect/rewrite options.

		$id = ( isset($options[$this->_Key_id]) && $options[$this->_Key_id] ) ? $options[$this->_Key_id] : $this->get_id();

		
		if ( $id ) {

			$db = $this->get_db_object();


			$id = $db->parse_if_unsafe($id);
			$id_format = $this->column_value_get_query_format($id);

			$id_column = ( isset($options[self::KEY_ID_COLUMN]) ) ? $options[self::KEY_ID_COLUMN] : $this->get_id_column();
			$table = ( isset($options[$this->_Key_table]) && $options[$this->_Key_table] ) ? $options[$this->_Key_table] : $this->get_table_name();
			$table_alias = ( isset($options[$this->_Key_table_alias]) && $options[$this->_Key_table_alias] ) ? $options[$this->_Key_table_alias] : null;



			$alias_clause = ( $table_alias ) ? " AS {$alias_clause} " : null;

			$query_obj->where( "{$table}{$alias_clause}.{$id_column} = {$id_format[$this->_Key_quote]}{$id}{$id_format[$this->_Key_quote]}" );

		}
		else {
			if ( isset($options[$this->_Key_require_id]) && $options[$this->_Key_require_id] ) {
				throw new MissingParameterException( 'id' );
			}
			else {
				throw new RowNotIdentifiedException();
			}
		}

		return $query_obj;

	}

	private function apply_unique_filter_to_query_obj( $query_obj, $options = null ) {

		try { 
			
			
			$table = ( array_val_is_nonzero($options, self::KEY_TABLE) ) ? $options[self::KEY_TABLE] : $this->get_table_name();
			$column = ( array_val_is_nonzero($options, self::KEY_COLUMN) ) ? $options[self::KEY_COLUMN] : $this->get_id_column();
			$value = ( array_val_is_nonzero($options, self::KEY_VALUE) ) ? $options[self::KEY_VALUE] : $this->get_id();
			
			if ( $value ) {

				$db = $this->get_db_object();
				$value = $db->parse_if_unsafe($value);
				$value_format = $this->column_value_get_query_format($value);

				$table_alias = ( isset($options[$this->_Key_table_alias]) && $options[$this->_Key_table_alias] ) ? $options[$this->_Key_table_alias] : null;
				$alias_clause = ( $table_alias ) ? " AS {$alias_clause} " : null;

				$query_obj->where( "{$table}{$alias_clause}.{$column} = {$value_format[$this->_Key_quote]}{$value}{$value_format[$this->_Key_quote]}" );

			}
			else {
				if ( array_val_is_nonzero($options, self::KEY_REQUIRE_VALUE) ) {
					throw new Exception( LL::Translate(__CLASS__ . '-missing_required_filter_value %' . get_class($this) . '%') );
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}

		return $query_obj;

	}

	function join_alias_by_table_name( $table, $rel_data ) {

		$alias = $this->table_key_by_relationship_data($rel_data);
		$cur_increment = $this->join_increment_by_table_name($table);

		if ( $cur_increment > 0 ) {
			$alias = $this->apply_join_increment_to_table_name( $table, $cur_increment );
		}

		return $alias;
	}

	function join_increment_by_table_name( $table ) {

		if ( !isset($this->_Join_increments[$table]) ) {
			$this->_Join_increments[$table] = 0;
		}

		return $this->_Join_increments[$table];

	}

	function apply_join_increment_to_table_name( $table, $increment ) {
	
		return "{$table}_{$increment}";

	}

	function _Model_object_by_relationship_data( $rel_data, $options = array() ) {
		
		$table_key = $this->table_key_by_relationship_data($rel_data);
	
		
		if ( isset($this->_Model_objects_by_table[$table_key]) ) {
			$model = $this->_Model_objects_by_table[$table_key];
		}
		else { 
			if ( !isset($options['autoload']) || $options['autoload'] = true ) {
				$rel_type = $rel_data[$this->_Key_relationship_type];
				
				$model_name = $rel_data[$this->_Key_relationship_class_name];
				
				if ( isset($rel_data[$this->_Key_relationship_model_library]) ) {
					$model_library = $rel_data[$this->_Key_relationship_model_library];
				}
				else {
					$model_library = null;
				}
		
				$class_ref = $model_name;
				
				if ( $model_library ) {
					$class_ref = $model_library . '/' . $class_ref;			
				}
				
				$model = $this->load_model_by_class($class_ref);
			
				$model->active_table_key = $table_key;
				$this->_Model_objects_by_table[$table_key] = $model;
		
				if ( method_exists($model, '_Init') ) {
					$model->_Init();
				}
				
				foreach( $model->get_unique_columns() as $unique_col ) {
					$this->_Model_objects_by_unique_columns[$unique_col] = $model;
				}
			
			}			
		}
		
		return $model;
	}

	public function get_relationship_data( $options = null ) {
		
		return $this->_Relationships;
		
	}

	public function load_model_by_rel_data( $rel_data, $options = array() ) {
		
		try  {
			
			$model_name = $rel_data[$this->_Key_relationship_class_name];
				
			if ( isset($rel_data[$this->_Key_relationship_model_library]) ) {
				$model_library = $rel_data[$this->_Key_relationship_model_library];
			}
			else {
				$model_library = null;
			}
		
			$class_ref = $model_name;
				
			if ( $model_library ) {
				$class_ref = $model_library . '/' . $class_ref;			
			}

			return $this->load_model_by_class( $class_ref, $options );
		
					
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function load_model_by_class( $class_ref, $options = array() ) {
		
		try  {
			
	
			$found_model = false;		
			$library    = LL::class_library_from_location_reference($class_ref);
			$class_name = LL::class_name_from_location_reference($class_ref);
				
			Debug::Show( "Retrieving model: {$class_name} in" . get_class($this), Debug::VERBOSITY_LEVEL_INTERNAL );
	
			//
			// To help with naming conflicts, 
			// we first look for a class suffixed with 'Model'
			//
			$look_for_classes = array( $class_name . self::CLASS_NAME_SUFFIX, $class_name );
			
			foreach( $look_for_classes as $cur_class_name ) {
				
				if ( !class_exists($cur_class_name, false) ) {
				
					$cur_class_ref = $cur_class_name;
					
					if ( $library ) {
						$cur_class_ref = $library . '/' . $cur_class_name;	
					}
				
					if ( LL::Include_model($cur_class_ref) ) {
						$found_model = true;
						$class_name = $cur_class_name;
						break;
					}
				}
				else {
					$class_name = $cur_class_name;
					$found_model = true;
					break;
				}
				
			} 
			
			if ( !$found_model ) {
				trigger_error( "{$class_name} Not Found", E_USER_ERROR );
			}
			else {
				
				
				self::$Loading_internal_model = true;
	
				$model = new $class_name;
	
				if ( !is_subclass_of($model, __CLASS__) ) {
					throw new InvalidParameterException( get_class($model) . ' is not a DataModel.' );
				}
	
				self::$Loading_internal_model = false;
	
				return $model;
			}
		
					
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	/*
	function _Model_object_by_name( $class_name, $object_library = null ) {

		$class_ref = ( $object_library ) ? $object_library . '/' . $class_name : $class_name;

		return $this->_Model_object_by_class( $class_ref );
	}

	function _Model_object_by_class( $class_ref ) {

		$library    = LL::class_library_from_location_reference($class_ref);
		$class_name = LL::class_name_from_location_reference($class_ref);
			
		if ( !isset($this->_Model_objects[$class_name])  ) {

			Debug::Show( "Retrieving model: {$class_name} in" . get_class($this), Debug::VERBOSITY_LEVEL_INTERNAL );

			if ( !class_exists($class_name, false) ) {

				LL::require_model($class_ref);
			}

			if ( !class_exists($class_name, false) ) {
				trigger_error( "{$class_name} Not Found", E_USER_ERROR );
			}
			else {

				self::$Loading_internal_model = true;

				$model = new $class_name;

				$this->add_model_by_class_name($class_name, $model);
				$model->add_model_by_class_name( get_class($this), $this );

				self::$Loading_internal_model = false;

				if ( method_exists($model, '_Init') ) {
					$model->_Init();
				}



			}
	
		}
	
		return $this->_Model_objects[$class_name];
	}
	
		
	function add_model_by_class_name( $class_name, $model ) {

		$this->_Model_objects[$class_name] = $model;
		
		$table_name = $model->get_table_name();
		
		$this->_Model_objects_by_table[$table_name] = $model;
		
		foreach( $model->get_unique_keys() as $unique_key ) {
			$unique_field = $model->column_name($unique_key);
			$this->_Model_objects_by_unique_columns[$unique_field] = $model;
		}

	}
	*/

	public function column_prefix_by_options( $options ) {
		try {
			
			$col_prefix = null;
			
			if ( isset($options[self::KEY_COLUMN_NAME_PREFIX]) && $options[self::KEY_COLUMN_NAME_PREFIX] ) {
				$col_prefix = $options[self::KEY_COLUMN_NAME_PREFIX];
			} 
			
			return $col_prefix;
    		
		}
		catch( Exception $e ) {
			
		}
		
	}

    public function apply_base_selections_to_query_obj( $query_obj, $options = array() ) {

		Debug::Show( "Applying base selections for " . get_class($this), Debug::VERBOSITY_LEVEL_INTERNAL);
		

		if ( isset($options[$this->_Key_table_alias]) ) {
			$table_name = $options[$this->_Key_table_alias];
		}
		else {
			$table_name = $this->get_table_name();
		}
	
		$column_prefix = $this->column_prefix_by_options($options);
	
		if ( $column_prefix ) {
			$column_names = $this->get_column_names();
	    	
	    	foreach( $column_names as $col_name ) {
	    		$query_obj->select( "{$table_name}.{$col_name} AS {$column_prefix}{$col_name}" );
	    	}
		}
		else {
			$query_obj->select( "{$table_name}.*" );
		}
		
		$pk_col = $this->get_primary_key_column_name();
		
		if ( $pk_col && $this->table_has_column_name($pk_col) ) {
			$authoritative_alias = $this->get_authoritative_id_column_alias($options);
			$query_obj->select( "{$table_name}.{$pk_col} AS {$authoritative_alias}" );
		}

		if ( !is_array($this->selections) ) {
			$selections = array( $this->selections );
		}
		else {
			$selections = $this->selections;
		}

		foreach( $selections as $select ) {
			$query_obj->select( $select );
		}

        $query_obj->add_completed_setup( $table_name . $this->_Key_selections_base );

        return $query_obj;

    }

        function apply_base_joins_to_query_obj( &$query_obj ) {

		try {
			$query_obj = $this->apply_relationships_to_query_obj($query_obj);
		}
		catch (Exception $e) {
			return $e;
		}

		return $query_obj;

        }

	function fetch_include_all_related_tables( $yesno = null ) {

		if ( $yesno !== null ) {
			if ( $yesno ) {
				$this->_Fetch_include_all_related_tables = true;
			}
			else {
				$this->_Fetch_include_all_related_tables = false;
			}
		}

		return $this->_Fetch_include_all_related_tables;

	}

        function &fetch_all( $options = null ) {

						
			try {
			
				Debug::Show( __FUNCTION__ . ' called in ' . get_class($this), Debug::VERBOSITY_LEVEL_EXTENDED );
				
				$db = $this->get_db_object();
			
				
				if ( array_val_is_nonzero($options, self::KEY_REQUIRE_UNIQUE_IDENTIFIER) ) {
					$this->require_unique_identifier();					
				}

				try {
					$query_obj = $this->get_applied_query_obj($options);
				}
				catch( ParentRowDoesntExistException $rde ) {
					$new_it = $this->get_fresh_iterator();
					return $new_it;
				}
				
				
				$query_obj->auto_reset = false;
				
            	$sql_query = $query_obj->generate_sql_query();
				
				if ( is_array($sql_query) ) {
					$query_string = $sql_query['query'];
				}
				else {
					$query_string = $sql_query;
				}

				if ( isset($options['count_column']) && $options['count_column'] ) {
					$this->db->count_query_column($query_string, $options['count_column']);
				}
				else if ( isset($options['count_field']) && $options['count_field'] ) {
					//
					// count_field is deprecated
					//
					$this->db->count_query_column($query_string, $options['count_field']);
				}

				if ( isset($options['count_select']) && $options['count_select'] ) {
					$this->db->count_query_select($query_string, $options['count_select']);
				}

				if ( isset($options['count_alias']) && $options['count_alias'] ) {
					$this->db->count_query_alias($query_string, $options['count_alias']);
				}				

				if ( isset($options['count_query']) && $options['count_query'] ) {
					$this->db->set_count_query($query_string, $options['count_query']);
				}

            	$return_type = isset($options[$this->_Key_return_type]) ? $options[$this->_Key_return_type] : $this->_Key_return_type_iterator;

				if ( $return_type != $this->_Key_return_type_query_obj ) {
					$this->set_last_query($query_string);
					$this->set_last_query_obj($query_obj);
					
					//Debug::Show( get_class($this) . '::' . __FUNCTION__ . ': ' . $sql_query, Debug::VERBOSITY_LEVEL_BASIC);
	
					if ( !($result = $db->query($sql_query)) ) {
						throw new SQLQueryException( $sql_query );
	            	}
	
					$this->_Last_query_result = $result;
				}
            

				switch( $return_type ) {
					case $this->_Key_return_type_query_obj:
						return $query_obj;
						break;
					case $this->_Key_return_type_resultset:
						return $result;
						break;
					case $this->_Key_return_type_array:
						return $result->fetchAll();
						
						/*
						$ret = array();
						
						while ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
							$ret[] = $row;
						}
						
						return $ret;
						*/
						
						break;
					case $this->_Key_return_type_iterator:
					default:
						$iterator = $this->get_fresh_iterator();
						$iterator->set_db_resultset($result);
						return $iterator;
						break;
				}



    		}
			catch (Exception $e) {
				throw $e;
            }        
        }

	function fetch_single( $options = null ) {
			
		try {
			
			$query_obj = $this->query_obj_from_option_hash($options);
			$query_obj->limit( 1 );
			
			$options[$this->_Key_query_obj] = $query_obj;
			
			$result = $this->fetch_all( $options );
			
            $return_type = isset($options[$this->_Key_return_type]) ? $options[$this->_Key_return_type] : $this->_Key_return_type_iterator;

			switch( $return_type ) {
					case $this->_Key_return_type_resultset:
						if ( $this->db->num_rows($result) <= 0 ) {
							return null;
						}
						break;
					case $this->_Key_return_type_iterator:
					default:
						if ( $result->count <= 0 ) {
							return null;
						}
						$result = $result->next();
						break;
				
			}
			
			return $result;
			
		}
		catch (Exception $e) {
			throw $e;
        }
		
	}

	function fetch_single_by( $column_key, $column_val, $options = null ) {
			
		try {
			
			
			$query_obj = $this->query_obj_from_option_hash($options);
			$query_obj->limit( 1 );
			
			$options[$this->_Key_query_obj] = $query_obj;
			
			$result = $this->fetch_all_by( $column_key, $column_val, $options );
			
			if ( method_exists($result, 'next') ) {
				return $result->next();
			}
			
			return $result;
			
		}
		catch (Exception $e) {
			throw $e;
        }
		
	}

	public function fetch_procedure_result( $call, $options = array() ) {

		try { 
		
			$res = $this->db->query($call);
			
           	$return_type = isset($options[$this->_Key_return_type]) ? $options[$this->_Key_return_type] : $this->_Key_return_type_iterator;

			switch( $return_type ) {
					case $this->_Key_return_type_resultset:
						return $res;
						break;
					case $this->_Key_return_type_iterator:
					default:
						$it  = $this->get_fresh_iterator();
						$it->set_db_resultset($res);
						return $it;
						break;
			}

		}
		catch (Exception $e) {
			throw $e;
        }		
	}

	function get_applied_query_obj( $options = array()  ) {
		
		try {
			
			if ( isset($options[$this->_Key_query_obj]) && array_val_is_nonzero($options, self::KEY_QUERY_OBJ_FINAL) ) {
				return $options[$this->_Key_query_obj];
			}
			
			$from_table   = $this->get_table_name();
			$table_alias  = $from_table;
			$table_alias_clause = null;
			$query_obj = $this->query_obj_from_option_hash($options);

			
			if ( !isset($options[self::KEY_APPLY_RELATIONSHIPS]) || ($options[self::KEY_APPLY_RELATIONSHIPS] !== false && $options[self::KEY_APPLY_RELATIONSHIPS] !== 0) ) {
				$query_obj = $this->apply_relationships_to_query_obj($query_obj, $options);
			}
			
			if ( !$query_obj->has_order() ) {
				if ( $this->_Order_by_default ) {
					$query_obj->order_by($this->_Order_by_default);
				}
			}

			
			if ( !array_val_is_nonzero($options, self::KEY_IGNORE_PARENT_FILTER) &&  ($parent = $this->get_parent_model()) ) {

				$rel_data = $this->relationship_data_by_table_name($parent->active_table_key);

				//
				// Note: get_id() will try to fetch the record by default,
				// so to prevent an infinite loop we set the NO_RECORD_FETCH option.
				//

				if ( (!$this->get_id(array(self::KEY_NO_RECORD_FETCH => true)) || $rel_data[$this->_Key_relationship_type] = $this->_Key_relationship_type_many_to_one) ) {
			
					if ( $rel_data[$this->_Key_relationship_direction] != $this->_Key_relationship_direction_downward ) {
						if ( !isset($rel_data[$this->_Key_association_link_table]) || !$rel_data[$this->_Key_association_link_table] ) {
					
							if ( $parent->is_uniquely_identified() ) {

								if ( !$query_obj->has_completed_setup(get_class($parent) . self::SUFFIX_QUERY_ID_FILTER) ) {
								
								
									try { 
										
										$parent_rel_data = $parent->relationship_data_by_table_name($parent->child_table_key);
										
										if ( isset($parent_rel_data['foreign_key']) && $parent_rel_data['foreign_key'] ) {
											$filter_options[self::KEY_ID_COLUMN] = $parent_rel_data['foreign_key'];
										}

										$rel_table_key = $this->table_key_by_relationship_data($parent_rel_data);
										$filter_options[$this->_Key_table] = $rel_table_key; 
	
										Debug::Show( '#2 Applying parent ID filter to ' . get_class($parent) . ' in ' . get_class($this) . '::' . __FUNCTION__, Debug::VERBOSITY_LEVEL_EXTENDED );
										if ( isset($filter_options[self::KEY_ID_COLUMN]) ) {
											Debug::Show( '#2 Parent filter ID column is: ' . $filter_options[self::KEY_ID_COLUMN] );
										}
	
										if ( $rel_table_key != $from_table ) {
											$table_alias = $rel_table_key;
											$table_alias_clause = " AS {$rel_table_key} ";
										}
	
										//$filter_options[]
										
										$query_obj= $parent->apply_id_filter_to_query_obj( $query_obj, $filter_options );
										$query_obj->add_completed_setup( get_class($parent) . self::SUFFIX_QUERY_ID_FILTER);
									}
									catch( RowNotIdentifiedException $re ) {
										throw new ParentRowDoesntExistException();
									}
								}
							}
							else {
								if ( array_val_is_nonzero($options, self::KEY_REQUIRE_UNIQUE_PARENT) ) {
									throw new Exception( __CLASS__ . '-unique_parent_not_identified' );
								}
							}
						}
					}				 
				}
			}

			$options[$this->_Key_table_alias] = $table_alias;

			if ( (!isset($options['selections_final']) || !$options['selections_final']) 
					&& (!isset($options['apply_base_selections']) || $options['apply_base_selections']) ) {			
				$this->apply_base_selections_to_query_obj($query_obj, $options);
			}

			$query_obj->from( "{$from_table}{$table_alias_clause}" );
			
			

			return $query_obj;
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}

	function &fetch_all_by( $col_key, $col_value = null, $options = null ) {

		$db = $this->get_db_interface();
		$quote = null;
		
		$column_name = $this->column_name($col_key);
		
		/*
		if ( !$this->table_has_column_name($col_key) ) { 
			$column_name = $this->column_name_by_key($col_key);
		}
		else {
			$column_name = $col_key;
		}
		*/

	
		
		if ( $id_column = $this->get_id_column() ) {
			if ( ($column_name == $id_column) && $this->_Require_numeric_id ) {
				if ( !is_numeric($col_value) ) {
					throw new NonNumericValueException("\$column_name: {$column_name} \$col_value: {$col_value}");					
				}
			}
		}
		
		if ( !isset($options[$this->_Key_query_obj]) || !$options[$this->_Key_query_obj] ) {
			$query_obj = $this->db->new_query_obj();
		}
		else {
			$query_obj = $options[$this->_Key_query_obj]->clone_query_obj();
		}
		
		$table_name = $this->get_table_name();
		
		$value_format = $this->column_value_get_query_format($col_value);

		$query_obj->where( "{$table_name}.{$column_name} {$value_format[$this->_Key_comparator]} {$value_format[$this->_Key_quote]}{$value_format[$this->_Key_value]}{$value_format[$this->_Key_quote]}" );

		$options[$this->_Key_query_obj] = $query_obj;
							
		return $this->fetch_all( $options );

	}

	function column_value_get_query_format( $column_value ) {

		$format = array();

		if ( $column_value === null ) {
			$column_value = 'NULL';
			$comparator = 'IS';
			$quote = '';
		}
		else {
			$quote = ( $this->column_value_gets_quoted($column_value) ) ? '\'' : '';
			$comparator = '=';
		}

		$db = $this->get_db_interface();

		$format[$this->_Key_quote] = $quote;
		$format[$this->_Key_comparator] = $comparator;
		$format[$this->_Key_value] = $db->parse_if_unsafe($column_value);

		return $format;

	}

	function column_value_gets_quoted( $column_value, $options = null ) {

		LL::Require_class('PDO/PDOStatementHelper');
		
		$quoted = true;
		$column_name = null;

		if ( isset($options[self::KEY_COLUMN]) && $options[self::KEY_COLUMN] ) {
			$column_name = $this->column_name( $options[self::KEY_COLUMN] );	
		}
		
		if ( $column_name && isset($this->_Column_db_param_types[$column_name]) ) {
			
			$explicit_type = $this->_Column_db_param_types[$column_name];
			$bind_type = PDOStatementHelper::PDO_bind_type_by_letter($explicit_type);
			
		}
		else {
			$bind_type = PDOStatementHelper::PDO_bind_type_by_variable( $column_value );
		}

		if ( $bind_type === null ) {
			//
			// bind type was 'f' (function)
			// or 'u' (unbindable)
			//
 			$quoted = false;
		}
		else {
			switch( $bind_type ) {
				case PDO::PARAM_INT:
					$quoted = false;
					break;
				case PDO::PARAM_BOOL:
					$quoted = false;
					break;			
				case PDO::PARAM_INT:
					$quoted = false;
					break;		
				case PDO::PARAM_NULL:
					$quoted = false;
					break;
			}
		}
		
				
		return $quoted;
		

	}

	public function set_db_param_type( $col_key, $type ) {

		LL::Require_class('PDO/PDOStatementHelper');

		//$type = PDOStatementHelper::PDO_bind_type_by_letter($type);
		
		$col_name = $this->column_name($col_key);
		$this->_Column_db_param_types[$col_name] = $type;
		
		
	}



	function order_by_default( $order ) {

		$this->_Order_by_default = $order;

	}

	function get_table_name( $options = null ) {

		if ( !$this->_Table_name ) {

			$class_name = ( isset($options[self::$Key_class_name]) ) ? $options[self::$Key_class_name] : get_class($this);

			$this->_Table_name = $this->_Table_name_from_class_name($class_name);

		}

		return $this->_Table_name;

	}

	function get_table_key() {

		return $this->get_table_name();

	}

	function form_key_from_table_name( $table_name ) {

		return depluralize($table_name);

	}

	/**
	 * Returns a new, configured InputFormValidator object
	 * but does not reference or alter $this->_Form.
	 * 
	 */
	private function _Get_fresh_local_form() {
		
		return $this->get_form( true, true );
	}

	function get_fresh_form() {
		
		return $this->get_form( true );
		
	}

	function get_form( $force_fresh = false, $local = false ) {

		try {
			
			$form = null;
			
			if ( !$this->_Form || $force_fresh ) {
			
				LL::require_class('Form/InputFormValidator');
	
				$form_name = $this->get_form_name();
				$form = new InputFormValidator($form_name);
			
				if ( !$local ) {
					$this->_Form = $form;
				}	
			}
			else {
				$form = $this->_Form;
			}

			if ( !$form->has_completed_setup( $this->get_form_setup_key() ) ) {
				$form = $this->apply_constraints_to_form($form);
			}
			
			return $form;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function get_form_value_for_repopulate( $field_key, $options = null ) {
		
		$options['for_repopulate'] = true;
		return $this->get_form_value( $field_key, $options );
		
	}

	public function get_form_value( $field_key, $options = null ) {
		
		try {
			
			$field_values = $this->get_form_values();
				
			if ( isset($field_values[$field_key]) ) {
				if ( array_val_is_nonzero($options, 'for_repopulate') && !array_val_is_nonzero($options, 'allow_html_tags') ) {
					$form = $this->get_form();
					return $form->parse_form_text($field_values[$field_key]);
				}
				else{
					return $field_values[$field_key];
				}
			}
			
			return null;
		}
		catch( Exception $e) {
			throw $e;
		}
	}

	public function unset_form_value( $field_key ) {

		try {

				$form 	    = $this->get_form();
				$form_field = $this->form_field_name_by_field_key($field_key);
				
				$form->unset_field($form_field);
			
		}
		catch( Exception $e) {
			throw $e;
		}
		
	}

	public function set_form( &$form ) {
		
		$this->_Form = $form;
		
	}

	public function set_form_value( $field_key, $val ) {
		
		try {

				$form 	  = $this->get_form();
				$form_key = $this->form_field_name_by_field_key($field_key);
				
				$form->set($form_key, $val);
				
			
		}
		catch( Exception $e) {
			throw $e;
		}
	}
	
	public function get_form_values() {

		try {
			$form 	  = $this->get_form();
			$data_key = $this->get_hashtable_key();
			$dataset  = $form->get_dataset();
			
			if ( isset($dataset[$data_key]) ) {
				return $dataset[$data_key];
				
			}
		
			return array();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	function get_unique_keys() {
	
		return $this->_Unique_keys;	
	}

	function get_unique_columns() {
	
		return $this->_Unique_columns;	
	}

	function get_primary_key() {

		return $this->primary_key;
		/*
		if ( !$this->_Primary_key ) {

			if ( !($this->_Primary_key = $this->column_name('id')) ) {
				$this->_Primary_key = 'id';
			}

		}

		return $this->_Primary_key;
		*/
	}

	function set_primary_key( $key ) {

		$this->_Primary_key = $this->column_name_by_key( $key );

	}

	function get_form_name() {

		if ( !$this->_Form_name ) {
			$this->_Form_name = $this->form_name_from_class_name(get_class($this));
		}

		return $this->_Form_name;

	}

	function form_name_from_class_name( $class_name ) {

		return strtolower(camel_case_to_underscore($class_name, array ('keep_number_positioning' => $this->camel_case_keep_number_positioning)));

	}
	
	protected final function _Apply_constraint_type( $type_key, $field_key, $options = null ) {

		LL::Require_class('Data/DataConstraint');

		$constraint = new DataConstraint();
		
		$constraint->set_field_key( $field_key );
		$constraint->set_model( $this );
		$constraint->set_type( $type_key );
		$constraint->apply_constraint_hash($options);

		$this->add_constraint_obj( $constraint );
		
	}

	function set_friendly_name( $input_name, $friendly_name ) {

		if ( $form = $this->get_form() ) {
			$input_key = $this->input_key_to_hashtable_ref($input_name);
			$form->set_friendly_name( $input_key, $friendly_name );
		}

	}

	public final function validates_result_of( $field_key, $options = null ) {

		return $this->_Apply_constraint_type( 'callback', $field_key, $options );

	}

	public final function validates_numericality_of( $field_key, $options = null ) {

		return $this->_Apply_constraint_type( 'numericality', $field_key, $options );

	}

	public final function validates_presence_of( $field_key, $options = null ) {

		return $this->_Apply_constraint_type( 'presence_of', $field_key, $options );

	}

	public final function validates_format_of( $field_key, $options = null ) {

	LL::Require_class('Data/DataConstraint');

		return $this->_Apply_constraint_type( DataConstraint::CONSTRAINT_TYPE_FORMAT, $field_key, $options );

	}

	public final function validates_date( $field_key, $options = null ) {
		
		LL::Require_class('Data/DataConstraint');
		
		return $this->_Apply_constraint_type(DataConstraint::$Key_constraint_type_date, $field_key, $options);
		
	}
	
	public final function validates_matching_values( $field_key, $field_key_2, $options = null ) {
		
		LL::Require_class('Data/DataConstraint');	
		
		$options[DataConstraint::KEY_FIELD_KEY_2] = $field_key_2;
		
		return $this->_Apply_constraint_type(DataConstraint::CONSTRAINT_TYPE_MATCHING_VALUES, $field_key, $options);
		
	}
	
	/*
	function splits_datetime_field( $field_key, $date_field_key, $time_field_key ) {
		
		$this->_Date_fields[$field_key] = $date_field_key;
		$this->_Time_fields[$field_key] = $time_field_key;
		
	}
	*/

	function validates_length_of( $field_key, $options ) {
	
		return $this->_Apply_constraint_type( 'length', $field_key, $options );
	
	}

	function apply_field_length_restrictions_from_fields() {
		
		$db = $this->get_db_interface();
		
		$table = $this->get_table_name();
		
		if ( method_exists($db, 'list_fields_max_chars') ) {
			$max_chars_list = $db->list_fields_max_chars($table);
		
			if ( $max_chars_list ) {
				if ( is_array($max_chars_list) ) {

					foreach( $max_chars_list as $field_name => $max_chars ) {
						$field_key = $this->column_key_by_name($field_name);
						$this->validates_length_of( $field_key, array('max' => $max_chars) );
					}
				}
			}
			else {
				throw new Exception( __CLASS__ . '-couldnt_get_max_field_chars_list');
			}
		}
		
	}
	
	function add_constraint_obj( &$constraint_obj ) {

		$field_key = $constraint_obj->get_field_key();
		$type	   = $constraint_obj->get_type();
		
		$this->_Data_constraints[$field_key][$type] = $constraint_obj;

		
	}
	
	function data_constraints_by_field_key( $field_key ) {
		
		if ( isset($this->_Data_constraints[$field_key]) ) {
			return $this->_Data_constraints[$field_key];
		}
		
		return null;
	}

	function data_constraint_by_field_key( $field_key, $type ) {
		
		if ( isset($this->_Data_constraints[$field_key][$type]) ) {
			return $this->_Data_constraints[$field_key][$type];
		}
		
		return null;
	}

	function apply_constraints_to_form( &$form ) {

		try {
			if ( is_array($this->_Data_constraints) ) {

				foreach( $this->_Data_constraints as $field_key => $constraints ) {

					if ( is_array($constraints) ) {
						foreach($constraints as $cur_constraint_obj ) {
							
							$cur_constraint_obj->apply_to_form($form);
						}
					}
				}

			}

			$form->add_completed_setup( $this->get_form_setup_key() );

			return $form;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function set_record_required( $val ) {

		if ( $val ) {
			$this->_Record_required = true;
		}
		else {
			$this->_Record_required = false;
		}

	}

	function is_record_required() {

		if ( $this->_Record_required ) {
			return true;
		}

		return 0;

	}

	function set_record_row( $data ) {
		
		$id_column = $this->get_id_column();
		
		if ( isset($data[$id_column]) ) {
			$this->set_id($data[$id_column]);
		}	

		$this->was_changed(true);		
		$this->_Record_row_set_explicitly = true;	
		$this->record_row = $data;
	
	}

	public function set_last_query( $query ) {
		
		$this->_Last_query = $query;
		
	}

	public function get_last_query() {
		
		return $this->_Last_query;
		
	}

	public function set_last_query_obj( $query_obj ) {
		
		$this->_Last_query_obj = $query_obj;
		
	}

	public function get_last_query_obj() {
		
		return $this->_Last_query_obj;
		
	}

	public function get_last_query_result() {
		
		return $this->_Last_query_result;
		
	}

	function set_column_name_by_key( $key, $column_name ) {
		
		$this->_Column_key_map[$key] = $column_name;
	}

	function set_column_value_by_name( $name, $value ) {

		return $this->set_column_value( $this->column_key_by_name($name), $value );

	}


	function get_column_value( $key, $options = array() ) {

		try { 
			
			
			if ( $this->_Record_row_set_explicitly || $this->get_record($options) ) {
			
				//Debug::Show( get_class($this) . ' Row: ' . print_r( $this->record_row, true ), Debug::VERBOSITY_LEVEL_BASIC );
				
				if ( isset($this->record_row[$key]) ) {
					Debug::Show( get_class($this) . ' Found record row key: ' . $key . ' with value: ' . $this->record_row[$key], Debug::VERBOSITY_LEVEL_INTERNAL );
					return $this->record_row[$key];
				}
				else {
					
					
					$column_name = $this->column_name_by_key($key);
	
					Debug::Show( "Searching for column in row (#1): {$column_name}", Debug::VERBOSITY_LEVEL_INTERNAL );
				
					$search_cols[] = $column_name;
				
					if ( $column_name == $this->get_primary_key_column_name() ) {
						$authoritative_alias = $this->get_authoritative_id_column_alias();
						$search_cols[] = $authoritative_alias;
					}
				
					foreach( $search_cols as $cur_name ) {
						if ( isset($this->record_row[$cur_name]) ) {
							return $this->record_row[$cur_name];
						}
						else {
							
							$column_name = $this->strip_column_prefix_from_name($cur_name);
						
							Debug::Show( "Searching for column in row (#2): {$cur_name}", Debug::VERBOSITY_LEVEL_INTERNAL );
						
							if ( isset($this->record_row[$cur_name]) ) {
								return $this->record_row[$cur_name];
							}
						}
					}
				}
						
			}
				
			
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}


	function get_column_value_by_name( $name ) {

		return $this->get_column_value( $name );

	}

	function get_form_setup_key() {
	
		return get_class($this);

	}

	function add( $options = array() ) {

		try {  

			$db = $this->get_db_object()->connect_w();
			
			$txn_started = false;
			$new_id = null;

			if ( !$db->in_transaction() ) {
				
				if ( !$db->start_transaction() ) {
					throw new DBException( 'bad_transaction_start' );
				}

				$txn_started = true;
			}

			$query_obj = $db->new_query_obj();

			$record_row = $this->record_row;
			
			if ( !is_array($record_row) || (count($record_row) <= 0) ) {
				$record_row = array();
			}
			
			if ( isset($options[self::KEY_RECORD_DATA]) && is_array($options[self::KEY_RECORD_DATA]) ) {
				$record_row = array_merge( $record_row, $options[self::KEY_RECORD_DATA] );
			}

			if ( !is_array($record_row) ) {
				throw new Exception( __CLASS__ . '-invalid_record_data' );
			}
		
			$id_column = $this->get_id_column();

			foreach( $record_row as $column_name => $column_val ) {
				
				if ( !$this->table_has_column_name($column_name) ) {
					if ( isset($this->_Link_columns[$column_name]) ) {
						continue;
					}
					throw new ColumnDoesNotExistException("Couldn't find column: {$column_name} in table: " . $this->get_table_name());
				}
				
				if ( ($this->_Allow_explicit_id || ($column_name != $id_column)) ) {
					Debug::Show( __METHOD__ . " - column name: {$column_name} | column val: {$column_val}", Debug::VERBOSITY_LEVEL_EXTENDED );
					$explicit_type = ( isset($this->_Column_db_param_types[$column_name]) ) ? $this->_Column_db_param_types[$column_name] : null;
					$query_obj->add_insert_data( $column_name, $column_val, $explicit_type );
				}
				else {
					throw new InvalidParameterException( 'Explicit ID assigned to data record. If you want to allow this, use $model->allow_explicit_id(true)' );
				}
			}

			// This array used only for debugging
			$insert_columns = ( Debug::$Enabled ) ? $query_obj->get_insert_column_values() : array();

			$table_name = $this->get_table_name();
			
			try { 
				//
				// For backward compatibilyt - older DB objects
				// might return false instead of throwing exception
				//

				if ( !$query_obj->auto_insert($table_name) ) {
					if ( $txn_started ) {
						$db->rollback();
					}
					
					throw new Exception();
				}

			}
			catch( Exception $e ) {
					Debug::Show( __METHOD__ . " [<span style=\"color:red;\">BAD INSERT</span>] {$query_obj->last_auto_insert_query()}", Debug::VERBOSITY_LEVEL_BASIC );
					Debug::Show( __METHOD__ . " [INSERT DATA] " . print_r($insert_columns, true), Debug::VERBOSITY_LEVEL_BASIC );

					if ( $txn_started) {
						$db->rollback();
					}
					
					throw $e;
			}

			//Moved to SQLQueryBUilder
			//Debug::Show( __METHOD__ . "[INSERT] {$query_obj->last_auto_insert_query()}", Debug::VERBOSITY_LEVEL_BASIC );
			//Debug::Show( __METHOD__ . " [INSERT DATA] " . print_r($insert_columns, true), Debug::VERBOSITY_LEVEL_BASIC );

			if ( $this->get_primary_key_column_name() ) {

				if ( $new_id = $db->last_insert_id($this->get_primary_key_column_name(), $table_name) ) {
					if ( $this->get_id_column() ) {
						$this->set_id( $new_id );
					}
				}
			
				$assn_data = $this->_Collect_association_values_for_record( $record_row );
				$this->_Add_association_links( array('dataset' => $assn_data) );
			}
			
			
		
			if ( $txn_started) {
				$db->commit();
			}

			$this->was_saved( true );
			
			$this->get_record( array ($this->_Key_force_new => true) );
			
			return $new_id;
			
		}
		catch (Exception $e) {
			if ( $txn_started) {
				$db->rollback();
			}
			throw $e;
		}

	}

	protected function _Collect_association_values_for_record( $record_row ) {

		$assn_data = array();
			
		foreach( $this->_Link_columns as $foreign_column => $column_info ) {
					
			if ( isset($record_row[$foreign_column]) ) {
				$assn_data[$foreign_column]=  $record_row[$foreign_column];					
			}
			/*
			else {
				$col_check = $this->column_name_by_key($foreign_column);
					
				if ( isset($record_row[$col_check]) ) {
					$assn_data[$this->strip_column_prefix_from_name($col_check)] = $record_row[$col_check];	
				}
			}
			*/
		}
				
		return $assn_data;
	}

	public function replace_associated_values( $table, $value, $options = array() ) {
	
			$options['replace'] = true;
			return $this->add_associated_value( $table, $value, $options );
	}

	/**
	 * 
	 * adds rows to the "link table" between the tables
	 * (e.g. it will add an entry to "book_author_link" )
	 * 
	 */
	public function add_associated_value( $table, $value, $options = array() ) {
		
		try {

			$query_obj = $this->query_obj_from_option_hash($options);
			$db 		 = $this->get_db_object();
			$txn_started = false;
			$do_replace  = false;
			
			if ( !$this->is_uniquely_identified() ) {
				throw new Exception ( __CLASS__ . '-row_not_uniquely_identified');
			}
			
			if ( !($rel_data = $this->relationship_data_by_table_name($table)) ) {
				throw new Exception( __CLASS__ . "-no_relationship_found_for_table %{$table}%" );
			}
			
			if ( !isset($rel_data[$this->_Key_association_link_table]) ) {
				throw new Exception( __CLASS__ . "-no_link_table_for_association %{$to_class}%");
			}
			
			if ( !$db->in_transaction() ) {
				if ( !$db->start_transaction() ) {
					throw new DBException( 'bad_transaction_start' );
				}

				$txn_started = true;
			}

			if ( isset($options['replace']) && $options['replace'] ) {
				$do_replace = true;
			}

			$link_data = $this->determine_link_table_columns_by_rel_data($rel_data);

			$link_table    = $link_data[self::KEY_TABLE];
			
			if ( isset($options[self::KEY_LINK_LOCAL_COLUMN]) ) {
				$link_column_local = $options[self::KEY_LINK_LOCAL_COLUMN];
			}
			else {
				$link_column_local = $link_data[self::KEY_LINK_LOCAL_COLUMN];
			}

			if ( isset($options[self::KEY_LINK_TABLE_FOREIGN_COLUMN]) ) {
				$link_column_foreign = $options[self::KEY_LINK_TABLE_FOREIGN_COLUMN];
			}
			else {
				$link_column_foreign = $link_data[self::KEY_LINK_TABLE_FOREIGN_COLUMN];
			}
			
			$link_value_local = $this->$link_column_local;					 					
				
			if ( !$link_value_local ) {
				throw new Exception ( __CLASS__ . "-link_table_needs_value_for column: {$link_column_local}" );
			}
				
			if ( !is_array($value) ) {
					
				$foreign_values = array( $value );
			}
			else {
				$foreign_values = $value;
			}
			
			$replaced = false;
		
			foreach( $foreign_values as $cur_foreign_val ) {
				
				if ( is_array($cur_foreign_val) ) {
					foreach( $cur_foreign_val as $field_name => $field_val ) {

						if ( $do_replace && !$replaced ) {

							$del_query = $this->db->new_query_obj();
							$del_query->delete();
							$del_query->from( $link_table );
							$del_query->where(
								 array( "{$link_table}.{$link_column_local} = ?", $link_value_local )
							);
							$del_query->run();	
					
							$replaced = true;
						}

						$query_obj->add_insert_data($field_name, $field_val);
					}
				}
				else {

					if ( $do_replace && !$replaced ) {
					
						$del_query = $this->db->new_query_obj();
						$del_query->delete();
						$del_query->from( $link_table );
						$del_query->where(
							 array( "{$link_table}.{$link_column_local} = ?", $link_value_local )
						);
						
						$del_query->run();						
					
						$replaced = true;						
					}

					$query_obj->add_insert_data($link_column_foreign, $cur_foreign_val);
					$query_obj->add_insert_data($link_column_local, $link_value_local );
				}
				
				if ( !$query_obj->auto_insert($link_table) ) {
					throw new SQLQueryException( $query_obj->last_auto_insert_query() );
				}				
			}
				
			Debug::Show( "Association link query: " . $query_obj->last_auto_insert_query(), Debug::VERBOSITY_LEVEL_BASIC );
				
			if ( $txn_started ) $db->commit();
			
			return true;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	protected function _Update_association_links( $options = array() ) {

		$options['update'] = true;
		return $this->_Add_association_links( $options );
	}

	/**
	 * 
	 * When adding data for many to many relationships, 
	 * this function searches for columns that need to be added
	 * to the link table that bridges the two other tables
	 * it calls add_associated_value if it finds any
	 */	
	protected function _Add_association_links( $options = array() ) {
		
		try {

			$query_obj = $this->query_obj_from_option_hash($options);
			$db 		 = $this->get_db_object();
			$txn_started = false;
			
			if ( count($this->_Link_columns) == 0 ) {
				return true;
			}
			
			if ( !$this->get_id() ) {
				throw new MissingParameterException ( __CLASS__ . '-missing_id');
			}

			if ( !$db->in_transaction() ) {
				if ( !$db->start_transaction() ) {
					throw new DBException( 'bad_transaction_start' );
				}

				$txn_started = true;
			}
			
			foreach( $this->_Link_columns as $foreign_field => $field_info ) {
				
				$class_name    = $field_info[$this->_Key_relationship_class_name];

				if ( !($link_value_foreign = $field_info[$this->_Key_value]) ) {
				
					$dataset = $options['dataset']; 
					
					if ( !isset($dataset[$foreign_field]) || !($link_value_foreign = $dataset[$foreign_field])) {
						
						if ( isset($field_info[self::KEY_REQUIRED]) && $field_info[self::KEY_REQUIRED] == true ) {
							throw new Exception ( __CLASS__ . "-missing_foreign_id_for_link_table %{$class_name}% %{$foreign_field}%", "\$class_name: {$class_name}");
							if ( $txn_started ) $db->rollback();
						}
						else {
				
							continue;
						}
					}
					
				}
			
				if ( isset($options['update']) && $options['update'] ) {
					$options['replace'] = true;
				}
			
				$rel_data = $field_info[self::KEY_RELATIONSHIP_DATA];
			
				$table_ref = ( isset($rel_data[$this->_Key_table_alias]) ) ? $rel_data[$this->_Key_table_alias] : $rel_data[$this->_Key_relationship_table_to]; 
			
				return $this->add_associated_value( $table_ref, $link_value_foreign, $options );
			}	
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}

	/**
	 * 
	 * Determines whether or not the form appears to have 
	 * more than one entry (row) for this model
	 * 
	 */
	public function form_count_multiple_row_entries() {
		
		try {
				$form_values = $this->get_form_values();
				
				foreach( $form_values as $field_key => $field_val ) {
					if ( is_array($field_val) ) {
						return count($field_val);
					}
					else {
						return null;
					}
				}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	protected final function _Process_incoming_form_data() {
		
		try {
		
		    LL::Require_class('Form/FormDataProcessor');
				
			$processor = new FormDataProcessor();
			$processor->set_model($this);
				
			return $processor->process_incoming();
		}
		catch( Exception $e ) {
			throw $e;
		}
			
	}

	public function add_from_form( $options = null ) {

		try {
      		$db = $this->get_db_object();
      		$form = $this->get_form();
			$original_form_data = $form->get_dataset();
			$this->set_record_required(true);
			
			// multiple form entries are currently disabled
			if ( false && ($this->form_entry_index === null) && null !== ($entry_count = $this->form_count_multiple_row_entries()) ) {
				
				$data_key    = $this->get_hashtable_key();
				$dataset     = $form->get_dataset();	
				$cur_index   = 0;
				//$tmp_model   = clone $this;
				$added_rows = array();
				
				for ( $cur_index = 0; $cur_index < $entry_count; $cur_index ++ ) {
					
					$new_form    = clone $form;
					$new_dataset = $dataset;
					
					$new_dataset[$data_key] = $dataset[$data_key][$cur_index]; 		
					
					$this->set_form( $new_form );
					$this->form_entry_index = $cur_index;
					
					$added_rows[] = $this->add_from_form();
					
				}
				
				//
				// Recreate our original dataset
				//
				$this->set_form($form);
				$this->form_entry_index = null;
				
				//unset( $tmp_model );
				
				return $added_rows;
			}
			else {
				
				if ( !$form->has_completed_setup($this->get_form_setup_key()) ) {
					try {
						$form = $this->apply_constraints_to_form($form);
					}
					catch (Exception $e) {
						throw $e;
					}
				}

				if ( !$this->validate_form() ) {
					
					throw new UserDataException( $form->get_messages() );
				}

		
					
				$form         	  = $this->_Process_incoming_form_data();
				$field_values 	  = $this->get_form_values();
				$magic_quotes_gpc = get_magic_quotes_gpc();

				
				foreach( $field_values as $field_key => $field_val ) {
					if ( !$this->is_field_allowed_for_insert($field_key) ) {
						unset($field_values[$field_key]);
						//throw new Exception( __CLASS__ . '-disallowed_form_key', "\$field_key: {$field_key}");
					}
				
					if ( $magic_quotes_gpc ) {
						$field_values[$field_key] = stripslashes($field_val);
					}

				}
			
				if ( is_array($this->record_row) ) {
				
					$record_data = array_merge( $this->record_row, $this->field_key_hashtable_to_record_row($field_values) );
					
					//$this->record_row = array_merge( $this->field_key_hashtable_to_record_row($field_values), $this->record_row );
				}
				else {
					$record_data = $this->field_key_hashtable_to_record_row($field_values);
					
					
				}
			
				//
				// Reset our form data because the form processor may
				// have added/removed/changed items
				///
				$form->set_dataset( $original_form_data );	
			
				return $this->add( array(self::KEY_RECORD_DATA => $record_data) );
			}
		}
		catch( Exception $e ) {
			$form->set_dataset( $original_form_data );
			throw $e;
		}

		return true;

	}

	function validate_form() {
	
		try {
			$form = $this->get_form();
			
			return $form->validate_input();
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	
	}

	function update( $options = null ) {

		$db = $this->get_db_object()->connect_w();

		$txn_started = false;

		try {
			
			//
			// We use get_record_without_related_tables() here
			// to get a clean view of this row, without anything
			// that may have been explicitly added to the record row
			// with a custom select
			//
			
			$record_data = $this->get_record_without_related_tables();
			
			if ( isset($options[self::KEY_RECORD_DATA]) ) {
				$record_data = array_merge( $record_data, $options[self::KEY_RECORD_DATA] );
			}
			
		
		}
		catch (Exception $e) {
			throw $e;
		}
		
		if ( count($record_data) > 0 ) {

			if ( !$db->in_transaction() ) {
				if ( !$db->start_transaction() ) {
					throw new DBException( 'bad_transaction_start' );
				}

				$txn_started = true;
			}


			$id = $this->get_id();

			if ( !$id ) {
				if ( isset($record_data[$this->_Key_id]) ) {
					$id = $record_data[$this->_Key_id];
				}
			}

			if ( !$id ) {
				if ( $txn_started ) {
					$db->rollback();
				}
				throw new Exception( __CLASS__ . '-missing_id' );
			}

			if ( $this->_Require_numeric_id ) {
				if ( !is_numeric($id) ) {
					if ( $txn_started ) {
						$db->rollback();
					}
					throw new NonNumericValueException("\$id: {$id}");					
				}
			}

			if ( !($id_column = $this->get_id_column()) ) {
				if ( $txn_started) $db->rollback();
				throw new Exception( __CLASS__ . '-missing_id_field' );
			}


			$query_obj = $db->new_query_obj();

			foreach( $record_data as $field_name => $field_val ) {
			
				if ( !$this->table_has_column_name($field_name) ) {
					if ( isset($this->_Link_columns[$field_name]) ) {
						continue;
					}
					throw new ColumnDoesNotExistException("Couldn't find column: {$field_name} in table: " . $this->get_table_name());
				}
				
				if (  ($field_name != $id_column) ) {
					
					Debug::Show( __METHOD__ . " - field name: {$field_name} | field val: {$field_val}", Debug::VERBOSITY_LEVEL_EXTENDED );
					
					$explicit_type = ( isset($this->_Column_db_param_types[$field_name]) ) ? $this->_Column_db_param_types[$field_name] : null;
					$query_obj->add_update_data( $field_name, $field_val, $explicit_type );
				}
			}

			$table_name = $this->get_table_name();

			if ( !is_numeric($id) ) {
				$quote = '\'';
				$id = $db->parse_if_unsafe($id);
			}
			else {
				$quote = '';
			}

			// This array used only for debugging
			$update_columns = ( Debug::$Enabled ) ? $query_obj->get_update_column_values() : array();
		
			try { 
				if ( !$query_obj->auto_update($table_name, "WHERE {$id_column} = {$quote}{$id}{$quote}") ) {
					Debug::Show( __METHOD__ . " [<span style=\"color:red;\">BAD UPDATE</span>] {$query_obj->last_auto_update_query()}", Debug::VERBOSITY_LEVEL_BASIC );
					Debug::Show( __METHOD__ . " [UPDATE DATA] " . print_r($update_columns, true), Debug::VERBOSITY_LEVEL_BASIC );
					throw new Exception(); //older db objects don't throw exceptions, so do it here
				}
			}
			catch( Exception $e ) {
					if ( $txn_started ) $db->rollback();

					throw $e;
				
			}
			
		
			
			//
			// Update associations for many to many relationships
			//
			$assn_data = $this->_Collect_association_values_for_record( $record_data );
			$this->_Update_association_links( array('dataset' => $assn_data) );
			
			Debug::Show( __METHOD__ . "[UPDATE] {$query_obj->last_auto_update_query()}", Debug::VERBOSITY_LEVEL_BASIC );
			Debug::Show( __METHOD__ . " [UPDATE DATA] " . print_r($update_columns, true), Debug::VERBOSITY_LEVEL_BASIC );

			if ( $txn_started ) {
				$db->commit();
			}

			$this->record_row = $record_data;
			$this->was_saved( true );
		}
		else {
			if ( $this->is_record_required() ) {
				throw new NoRecordDataException( __CLASS__ . '-no_record_data' );
			}
		}
		return true;

	}

	function save() {

		try {
			
			$this->collect_associated_columns();
			
			if ( $this->record_exists() ) {
				return $this->update();
			}
			else {
				return $this->add();
			}
		}
		catch (Exception $e) {
			throw $e;
		}

		return true;

	}

	function collect_associated_columns() {
	
		foreach( $this->_Model_objects_by_unique_columns as $field => $model ) {
			
			if ( $this->table_has_column_name($field) ) {
			
				Debug::Show("Collecting associated field $field from " . get_class($model), Debug::VERBOSITY_LEVEL_INTERNAL);
				
				if ( !$this->$field ) {
					if ( $model->$field ) {
						$this->$field = $model->$field;
						$this->was_changed(true);
					}
				}
			}
		}
	
	}

	function validate_record() {
		
		$this->collect_associated_columns();
		
		$form = $this->_Get_fresh_local_form();
		$form->set_dataset( $this->Record_row );
		
		if ( !$form->has_completed_setup($this->get_form_setup_key()) ) {
			try {
				$form = $this->apply_constraints_to_form($form);
			}
			catch (Exception $e) {
				throw $e;
			}
		}

		if ( !$this->validate_form() ) {
			throw new UserDataException( $form->get_messages() );
			return 0;
		}
		
		return true;
		
	}

	function delete( $options = null ) {

		if ( !($id = $this->get_id()) ) {
			throw new Exception( __CLASS__ . '-missing_id' );
		}
		else {

			try {
				$db = $this->get_db_object()->connect_w();
				
				$query_obj = $db->new_query_obj();
				$query_obj->limit(1);
				
				$options[$this->_Key_query_obj] = $query_obj;
				
				$ret = $this->delete_all_by($this->get_primary_key_column_name(), $id, $options);
				return $ret;
			}
			catch (Exception $e) {
				throw $e;
			}
		}

	}

	public function delete_all( $options = array() ) {

		try {		
			
			if ( !isset($options['allow_blank_where']) || $options['allow_blank_where'] == false ) {
				if ( (!isset($options['where']) || !$options['where'])  
						&& (!isset($options['query_obj']) || !$options['query_obj']->has_where()) ) {
							throw new InvalidParameterException('no where clause given in ' . __METHOD__ . '. Set option allow_blank_where=true or specify where clause');
				}
			}
			
			$db	      	  = $this->get_db_object();
			$query_obj    = $this->query_obj_from_option_hash($options);
			$table_name   = $this->get_table_name();

			$query_obj->delete();
			$query_obj->from( $table_name );	


			$sql_query = $query_obj->generate_sql_query();
			
			if ( !($dbh = $db->connect_w()) ) {
				throw new DBConnException();
			}

			if ( !$result = $db->query($sql_query) ) {
				throw new SQLQueryException( $sql_query );
			}

			Debug::Show( $sql_query, Debug::VERBOSITY_LEVEL_BASIC );

		}
		catch (Exception $e) {
			throw $e;
		}

		return true;

	}

	function delete_all_by( $field_key, $field_value = false, $options = null ) {

		try {		
			
			$db	      	  = $this->get_db_object();
			$query_obj    = $this->query_obj_from_option_hash($options);
			$table_name   = $this->get_table_name();
			$field_name   = $this->column_name($field_key);

			$query_obj->delete();
			$query_obj->from( $table_name );	

			if ( $field_value === false ) {
				if ( !isset($options[$this->_Key_allow_table_delete]) || !$options[$this->_Key_allow_table_delete] ) {
					throw new Exception( __CLASS__ . '-attempt_to_delete_full_table' );
				}
			}
			else {
				$value_format = $this->column_value_get_query_format($field_value);

				$query_obj->where( "{$table_name}.{$field_name} {$value_format[$this->_Key_comparator]} {$value_format[$this->_Key_quote]}{$value_format[$this->_Key_value]}{$value_format[$this->_Key_quote]}" );
			}

			$sql_query = $query_obj->generate_sql_query();
			
			if ( !($dbh = $db->connect_w()) ) {
				throw new DBConnException();
			}

			if ( !$result = $db->query($sql_query) ) {
				throw new SQLQueryException( $sql_query );
			}

			Debug::Show( $sql_query, Debug::VERBOSITY_LEVEL_BASIC );

		}
		catch (Exception $e) {
			throw $e;
		}

		return true;

	}

	function query_obj_from_option_hash( &$options, $clone = true, $auto_create = true ) {
		
		LL::require_class('SQL/SQLOptions');
		
		$options['db_obj'] = $this->db;
		
		return SQLOptions::Query_obj_from_option_hash($options, $clone, $auto_create);

	}

	function update_from_form($options = null) {

		try { 
      		$db   = $this->get_db_object();
      		
			$form = $this->get_form();
			$original_form_data = $form->get_dataset();

			if ( !$form->has_completed_setup($this->get_form_setup_key()) ) {
				try {
					$form = $this->apply_constraints_to_form($form);
				}
				catch (Exception $e) {
					throw $e;
				}
			}
			
			if ( !$this->validate_form() ) {
				throw new UserDataException( $form->get_messages() );
			}
			

			
			//$field_values = $this->_Process_incoming_form_data();
			$form         	  = $this->_Process_incoming_form_data();
			$field_values 	  = $this->get_form_values();
			$magic_quotes_gpc = get_magic_quotes_gpc();			

			//if ( !$this->get_id() ) {
				$primary_key_field = $this->get_primary_key_column_name();
				$primary_key_key = $this->column_key_by_name($primary_key_field);
				
				if ( isset($field_values[$primary_key_field]) && $field_values[$primary_key_field] ) {
					$this->set_id( $field_values[$primary_key_field] );
					$this->get_record( array('force_new' => true) );					
				}
				else if ( isset($field_values[$primary_key_key]) && $field_values[$primary_key_key] ) {
					$this->set_id( $field_values[$primary_key_key] );
					$this->get_record( array('force_new' => true) );
				}
				
			//}
			foreach( $field_values as $field_key => $field_val) {

				if ( !$this->is_field_allowed_for_update($field_key) ) {
					unset($field_values[$field_key]);
					//throw new Exception( __CLASS__ . "-disallowed_form_key %{$field_key}%" );
				}
					
				if ( $magic_quotes_gpc ) {
					$field_values[$field_key] = stripslashes($field_val);
				}
				
				
			}

			//$field_values = $this->field_key_hashtable_to_record_row($field_values);

			//
			// We use get_record_without_related_tables() here
			// to get a clean view of this row, without anything
			// that may have been explicitly added to the record row
			// with a custom select
			//
			$record_data = $this->get_record_without_related_tables();
			
			if ( is_array($record_data) ) {
				$record_data = array_merge( $record_data, $this->field_key_hashtable_to_record_row($field_values) );
			}
			else {
			$record_data = $this->field_key_hashtable_to_record_row($field_values);
			}

			

			//
			// Reset our form data because the form processor may
			// have added/removed/changed items
			///
			$form->set_dataset( $original_form_data );	
			
			
			$this->set_record_required( true );
			
			
			return $this->update( array(self::KEY_RECORD_DATA => $record_data) );
		}
		catch (Exception $e) {
			$form->set_dataset( $original_form_data );	
			throw $e;
		}
		
	}

	
	function get_hashtable_key() {

		return depluralize($this->get_table_name());

	}

	function get_form_input_key() {

		return $this->get_hashtable_key();

	}

	function input_name_belongs_to_model( $key ) {

		if ( $key == $this->get_form_input_key() ) {
			return true;
		}
	
		/*
		$input_key = $this->get_form_input_key();

		if ( substr($key, 0, strlen($input_key)) == $input_key ) {
			return true;
		}
		*/

		return 0;
	}

	function strip_field_key_from_input_name($input_name) {

		return trim(strstr($input_name, '['), '[]');

	}

	function html_field_id_by_input_name( $input_name ) {

		$input_name = trim( $input_name, '[]' );
		$input_name = str_replace( '[', '_', $input_name );

		return $input_name;
	}

	function html_field_id_by_field_key( $key ) {
		
		return depluralize($this->table_name) . '_' . $key;
		
	}

	function input_key_to_hashtable_ref($input_name) {

		LL::require_class('Util/ArrayString');

		if ( ArrayString::String_contains_array_key_reference($input_name) ) {
			$second_key = ArrayString::Extract_array_name_from_string($input_name);
			$key_string = ArrayString::Extract_array_keys_from_string($input_name);
			
			//array_unshift( $key_arr, $second_key );
		
			return $this->get_form_input_key() . "[{$second_key}]" . $key_string;	
			
		}
		else {
			return $this->get_form_input_key() . "[{$input_name}]";
		}

	}

	public function form_field_name_by_field_key( $field_key ) {
		
		return $this->input_key_to_hashtable_ref( $field_key );
	}

	public function form_field_name_by_db_field( $field ) {
		
		return $this->input_key_to_hashtable_ref( $this->column_key_by_name($field) );
	}

	/**
		deprecated alias to column_name
	*/
	public function db_field_name( $key_or_explicit_name ) {
		return $this->column_name( $key_or_explicit_name );
	}

	function column_name( $key_or_explicit_name ) {
		
		$column_name = $key_or_explicit_name;
		
		if ( !$this->table_has_column_name($column_name) ) {

			$prefixed = $this->column_name_by_key( $key_or_explicit_name );
			
			if ( $this->table_has_column_name($prefixed) ) {
				$column_name = $prefixed;
			}
			
		}
		
		return $column_name;
		
		
	} 

	function column_prefix_apply_to_key( $key ) {
		
		if ( $this->column_name_prefix ) {
			return $this->column_name_prefix . $key;
		}
		
		return $key;
	}

	function column_name_by_key( $key ) {
		
		$column_name_var = DataModel::PREFIX_VAR_DB_COLUMN_NAME . $key;
		$column_name = $key;
		$reflector = new ReflectionObject($this);

		if ( $reflector->hasProperty($column_name_var) ) {
			$column_name = $this->$column_name_var;
		}
		else {
			if ( isset($this->_Column_key_map[$key]) && $this->_Column_key_map[$key] ) {
				$column_name = $this->_Column_key_map[$key];
			}	
			else if ( $this->column_name_prefix ) {
				if ( !$this->column_name_has_prefix($column_name) ) {
					$column_name = $this->column_prefix_apply_to_key($key);
				}
			}
		}

		Debug::Show( "Translated DB KEY: {$key} to {$column_name} in " . get_class($this), Debug::VERBOSITY_LEVEL_INTERNAL );

		unset($reflector);

		return $column_name;

	}
	
	function column_name_has_prefix( $field_name ) {
		
		if ( $this->column_name_prefix ) {
			if ( substr($field_name, 0, strlen($this->column_name_prefix)) == $this->column_name_prefix ) {
				return true;
			}	
		}
		
		return 0;
	}

	function column_key_by_name( $name ) {

		return $this->strip_column_prefix_from_name($name);

	}

	/**
	 * Deprecated alias
	 */
	function strip_db_field_prefix_from_key( $key ) {

		return $this->strip_column_prefix_from_name( $key );
	}

	function strip_column_prefix_from_name( $name ) {

		if ( $this->column_name_prefix ) {
			if ( substr($name, 0, strlen($this->column_name_prefix)) == $this->column_name_prefix ) {
				$name = substr($name, strlen($this->column_name_prefix));
			}
		}

		return $name;

	}

	function record_row_to_form_hashtable( $row, $options = null ) {

		return $this->record_row_to_field_key_hashtable($row, $options);
	}

	function record_row_to_field_key_hashtable( $row, $options = null ) {

		$hash = array();

		if ( is_array($row) ) {
			foreach( $row as $name => $val ) {
				$key = $this->column_key_by_name($name);
				$hash[$key] = $val;
				$hash[$name] = $val;
			}
		}

		if ( isset($options['include']) ) {
			$include = $options['include'];
			if ( is_scalar($include) ) {
				$include = array( $include );
			}
			
			foreach( $include as $table ) {
				if ( $related_model = $this->$table ) {
					if ( is_subclass_of($related_model, __CLASS__) && $related_model->is_uniquely_identified() ) {
						
						
						// We don't want the same includes passed
						// to get_record for nested models since they only 
						// apply to the parent unless the table name was
						// specified and matches this related model.
						$nested_options = $options;
						$nested_options['include'] = $this->extract_includes_for_model($related_model, $options['include']); 
						
						if ( $related_row = $related_model->get_record($nested_options) ) {
							
							foreach( $related_row as $field_name => $val ) {
								
								$hash[$table][$field_name] = $val;
								$key = $related_model->column_key_by_name($field_name);
								$hash[$table][$key] = $val;
							}
						}
						
					}
				}
			}
		}
		
		return $hash;
	
	}

	public function extract_includes_for_model( $model, $includes ) {
		
		$ret_includes = array();
		
		if ( is_array($includes) ) {
			foreach( $includes as $cur_include ) {
				
				if ( false !== ($sep_pos = strpos($cur_include, self::INCLUDE_TABLE_SEPARATOR)) ) {
					$include_table = substr($cur_include, 0, $sep_pos);
										
					if ( $include_table == $model->table_name ) {
						$ret_includes = substr($cur_include, $sep_pos + 1);
					}
					
				}
			
						
			}
		}
		
		return $ret_includes;
	}
		

	public function record_exists() {
		
		try {
			
			if ( $this->is_uniquely_identified() ) {
				if ( $result = $this->fetch_record_result() ) {
					//
					// We use num_rows here instead of fetch 
					// because we don't know the cursor position
					//
					if ( $this->db->num_rows($result) > 0 ) {
						return true;
					}
				}
			}
			
			return 0;
				
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function field_key_hashtable_to_record_row( $data ){
		
		if ( !is_array($data) ) {
			trigger_error( 'Non array passed to ' . __METHOD__, E_USER_WARNING );
			throw new InvalidParameterException( "\$data: {$data}" );
		}
		else {
			
			foreach( $data as $key => $val ) {
				$new_key = $this->column_name($key);
				
				if ( $this->table_has_column_name($new_key) ) {
					$data[$new_key] = $val;
				
					if ( $new_key != $key ) {
						unset($data[$key]);
					}
				}
			}
		}

		return $data;

	}

	public function field_key_from_form_input_name( $input_name, $options = array() ) {
		
		LL::Require_class('Util/ArrayString');

		if ( ArrayString::String_contains_array_key_reference($input_name) ) {				
			
			$array_name = ArrayString::Extract_array_name_from_string($input_name);
			
			if ( $array_name == $this->get_hashtable_key() ) {
			
				$keys = ArrayString::Extract_array_keys_from_string_as_array($input_name);
		
				if ( isset($keys[0]) ) {
					return $keys[0];
				}
			}
			
			return null;
			
		}
		else {
			return $input_name;
		}
		
		
	}

	function populate_template( &$template, $options = null ) {

		try {
			
			//
			// Older versions of Lamplighter defaulted to populate the template as an array,
			// but we now do it as an object. However, the array mode is still supported in certain scenarios
			//
			if ( Config::Get('model.populate_template_as_array') 
				|| isset($options['row']) && $options['row']
				|| isset($options['param_type']) && $options['param_type'] == 'array')  {
					
					if ( isset($options['row']) ) {
						$row = $options['row'];
					}
					else {
						$row = $this->get_record( $options );
					}
					
					if ( $row && is_array($row) ) {
					//if ( $model = $this->fetch_single_by('id', $this->id, $options) ) {
						
						if ( isset($options['param_name']) && $options['param_name'] ) {
							$hash_name = $options['param_name'];
						}
						else {
							
							if ( !isset($options[self::KEY_HASH_NAME]) ) {
								
								$hash_name    = $this->get_hashtable_key($options);
							}
							else {
								$hash_name = $options[self::KEY_HASH_NAME];
							}
						}
						
						$row = $this->record_row_to_field_key_hashtable($row, $options);
						
						if ( array_val_is_nonzero($options, self::KEY_USE_GLOBAL_TEMPLATE_PARAMS) ) {				
			
							$template->add_array_by_ref( $row );
						}

						
						$template->add_param( $hash_name, $row );
						
					}
			}
			else {
				$param_name = ( isset($options['param_name']) && $options['param_name'] ) ? $options['param_name'] : strtolower(depluralize($this->get_table_name()));
				$template->add_param( $param_name, $this );
			}

			$cur_index = 0;
	
			if ( isset($options['include']) ) {
				
				if ( !is_array($options['include']) ) {
					$options['include'] = array($options['include']);
				}

				$include_keys = array_keys($options['include']);
				
				foreach( $options['include'] as $table ) {
				
					if ( is_array($table) ) {
						$include_options = $table;
						$table = $include_keys[$cur_index];
					}
					else {
						$include_options = array();
					}
					
					if ( $model = $this->get_child_model($table) ) {
						$model->populate_template( $template, $include_options );
					}
					
					$cur_index++;
				}
			}

			return $template;
		}
		catch( Exception $e ) {

			
			throw $e;
		}
		
	}
	
	function populate_form( &$template, $options = null ) {

		try {

			return true; // method currently unused, may be removed. 
						 // form values are pulled in by FormDataHelper.

			if ( !isset($options[self::$Key_form]) || !$options[self::$Key_form] ) {
				$form = $this->get_form();
			} 
			else {
				$form = $options[self::$Key_form];
			}
			
			$form_key    = $this->get_form_input_key();
			
			$field_data  = $form->get($form_key);
			
			if ( $row = $this->get_record() ) {
			
				$row = $this->record_row_to_form_hashtable($row, $options);

				
				//$data[$this->get_form_input_key()] = $row;

				if ( $field_data && is_array($field_data) ) {
					$field_data = array_merge($row, $field_data);
				}
				else {
					$field_data = $row;
				}

			}
		
			
			if ( !array_val_is_nonzero($options, self::$Key_skip_character_parse) ) {
				if ( is_array($field_data) ) {
					$field_data = array_map( 'htmlspecialchars', $field_data );
				}
			}
				
			$template->add_param( $form_key, $field_data );
			//$form_array[$form_key] = $field_data;
			
			//$form->repopulate_template_from_db($template, $form_array);
		}
		catch( Exception $e ) {
			
			throw $e;
		}

		return $template;
	}

	function was_saved( $save_val = null ) {

		if ( $save_val !== null ) {
			if ( $save_val ) {
				$this->_Was_saved = true;
				$this->was_changed(false);
			}
			else {
				$this->_Was_saved = false;
			}
		}

		return $this->_Was_saved;

	}

	function was_changed( $val = null ) {

		if ( $val !== null ) {
			if ( $val ) {
				$this->_Was_changed = true;
				$this->was_saved(false);
				$this->was_reset(false);
			}
			else {
				$this->_Was_changed = false;
			}
		}

		return $this->_Was_changed;

	}

	function was_reset( $val = null ) {

		if ( $val !== null ) {
			if ( $val ) {
				$this->_Was_reset = true;
				
				$this->was_saved(false);
				$this->was_changed(false);
			}
			else {
				$this->_Was_reset = false;
			}
		}

		return $this->_Was_reset;

		
	}

	function apply( $foreign_obj ) {

		$db = $this->get_db_object();
		$txn_started = false;
		$foreign_class = get_class($foreign_obj);

		
		if ( !($foreign_key_field = $this->foreign_key_column_by_table_name($foreign_obj->get_table_name())) ) {
			throw new Exception( __CLASS__ . '-no_foreign_key_found', "\$foreign_class: {$foreign_class}" );
		}

		Debug::Show( "Applying {$foreign_class} to " . get_class($this) . ". Foreign key is: {$foreign_key_field}", Debug::VERBOSITY_LEVEL_EXTENDED );
		
		if ( !$this->table_has_column_name($foreign_key_field) ) {
			throw new Exception( __CLASS__ . '-invalid_foreign_key', "\$foreign_key_field: {$foreign_key_field}" );
		}

		if ( !$db->in_transaction() ) {
			if ( !$db->start_transaction() ) {
				throw new DBException( 'bad_transaction_start' );
			}

			$txn_started = true;
		}

		if ( $foreign_obj->was_changed() ) {
			try {
				$foreign_obj->save();
			}
			catch (Exception $e) {
				throw $e;
			}
		}


		if ( !($foreign_id = $foreign_obj->get_id()) ) {
			throw new Exception( __CLASS__ . '-no_foreign_id_found', "\$foreign_class: {$foreign_class}" );			
		}

		$this->$foreign_key_field = $foreign_id;

		return true;
	}
	
	private function _Update_form_field_access($field_key, $options) {
	
		$cur_options = null;
	
		if ( isset($this->_Form_field_access[$field_key]) ) {
			
			$options = array_merge($this->_Form_field_access[$field_key], $options);
		}
		
		$this->_Form_field_access[$field_key] = $options; 
	
	}
	
	protected function _Mark_form_field_permissions_as_explicit( $query_type ) {
		
		$this->_Form_field_explicit_permissions[] = $query_type;
		
	}
	
	private function _Query_type_has_explicit_form_field_permissions( $query_type ) {
		
		if ( in_array($query_type, $this->_Form_field_explicit_permissions) ) {
			return true;
		}
		
		return 0;
	}
	
	function allow_explicit_id( $val = null ) {

		if ( $val !== null ) {

			if ( $val == true ) {
				$this->_Allow_explicit_id = true;
			}
			else if ( $val == false ) {
				$this->_Allow_explicit_id = false;
			}
		}

		return $this->_Allow_explicit_id;

	}

	function allow_form_insert( $field_keys ) {
		
		$this->_Mark_form_field_permissions_as_explicit($this->_Key_query_type_insert);
		
		if ( is_scalar($field_keys) ) $field_keys = array($field_keys);
		
		$options = array( $this->_Key_query_type_insert => $this->_Key_allow );
		
		foreach( $field_keys as $cur_key ) {
			$field_name = $this->column_name($cur_key);
			$this->_Update_form_field_access($field_name, $options);
		}
		
	}
	
	function allow_form_update( $field_keys ) {
		
		$this->_Mark_form_field_permissions_as_explicit($this->_Key_query_type_update);
		
		if ( is_scalar($field_keys) ) $field_keys = array($field_keys);
		
		$options = array( $this->_Key_query_type_update => $this->_Key_allow );
		
		foreach( $field_keys as $cur_key ) {
			$field_name = $this->column_name($cur_key);
			$this->_Update_form_field_access($field_name, $options);
		}
		
	}
	

	function disallow_form_insert( $field_keys ) {
		
		//$this->_Mark_form_field_permissions_as_explicit($this->_Key_query_type_insert);
		
		if ( is_scalar($field_keys) ) $field_keys = array($field_keys);
		
		$options = array( $this->_Key_query_type_insert => $this->_Key_disallow );
		
		foreach( $field_keys as $cur_key ) {
			$field_name = $this->column_name($cur_key);
			$this->_Update_form_field_access($field_name, $options);
		}
		
	}
	
	function disallow_form_update( $field_keys ) {
		
		//$this->_Mark_form_field_permissions_as_explicit($this->_Key_query_type_update);
		
		if ( is_scalar($field_keys) ) $field_keys = array($field_keys);
		
		$options = array( $this->_Key_query_type_update => $this->_Key_disallow );
		
		foreach( $field_keys as $cur_key ) {
			$field_name = $this->column_name($cur_key);
			$this->_Update_form_field_access($field_name, $options);
		}
		
	}


	function allow_form_access( $field_keys ) {
		
		$this->allow_form_insert($field_keys);
		$this->allow_form_update($field_keys);
		
	}
	
	function disallow_form_access( $field_keys ) {
		
		$this->disallow_form_insert($field_keys);
		$this->disallow_form_update($field_keys);
		
	}


	function is_field_allowed_for_insert( $field_key ) {
		
		return $this->_Is_field_allowed_for_query_type( $field_key, $this->_Key_query_type_insert );
		
	}
	
	function is_field_allowed_for_update( $field_key ) {
		
		return $this->_Is_field_allowed_for_query_type( $field_key, $this->_Key_query_type_update );
		
	}
	
	private function _Is_field_allowed_for_query_type( $field, $query_type, $options = null ) {
		
		$field_name = $this->column_name($field);
		
		$access_setting = null;
		
		if ( isset($this->_Form_field_access[$field_name]) ) {
			if ( isset($this->_Form_field_access[$field_name][$query_type]) ) {
				$access_setting = $this->_Form_field_access[$field_name][$query_type];
			}	
		}

		
		if ( $access_setting == $this->_Key_disallow ) {
			return 0;
		}
		else if ( $access_setting == $this->_Key_allow ) {
			return true;
		}
		else {
			if ( $this->_Query_type_has_explicit_form_field_permissions($query_type) ) {
				return 0;
			}
			else {
				return true;
			}
		}
	}

	public function get_time_and_date_columns() {
	
		try {
			
			if ( !$this->_Time_and_date_columns ) {

				if ( is_array($field_names = $this->get_column_names()) ) {
				
					$db   = $this->get_db_object();
					$table = $this->get_table_name();
				
					foreach( $field_names as $cur_name ) {
						
						if ( $db->is_date_field($table, $cur_name) ) {
							$this->_Time_and_date_columns[$cur_name] = array( 'datatype' => 'date' );
						
						}

						if ( $db->is_time_field($table, $cur_name) ) {
							$this->_Time_and_date_columns[$cur_name] = array( 'datatype' => 'time' );
							
						}
					
						if ( $db->is_datetime_field($table, $cur_name) ) {
							$this->_Time_and_date_columns[$cur_name] = array( 'datatype' => 'datetime' );
						}

					}	
				}
			
			}
			
			return $this->_Time_and_date_columns;
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function is_time_or_date_field( $field_key ) {
		
		$field_name = $this->column_name($field_key);
		$time_date_fields = $this->get_time_and_date_columns();
		
		if ( isset($time_date_fields[$field_name]) ) {
			return true;
		}
		
		return 0;
	}
	
	public function time_or_date_field_details( $field_key ) {

		$field_name = $this->column_name($field_key);
		$time_date_fields = $this->get_time_and_date_columns();
		
		if ( isset($time_date_fields[$field_name]) ) {
			return $time_date_fields[$field_name];
		}
		
		return null;
	}
	
		
	function prefix_column_name( $prefix ) {
		
		$this->column_name_prefix = $prefix;
		
	}
	
	function count_all( $options = null ) {

			try {

	       		$db = $this->get_db_object();

				try { 
					$query_obj = $this->get_applied_query_obj($options);
				}
				catch( ParentRowDoesntExistException $re ) {
					return 0;
				}
				
				//$id_field = $this->get_primary_key_column_name();
				//$table    = $this->get_table_name();
				
				$query_obj->clear_selections();
				
				$query_obj->select( "COUNT(*) AS count");
				
				foreach( $this->_Count_query_select as $select ) {
					$query_obj->select($select);
				}
				
				$query_obj->ignore_limit( true );
				$query_obj->ignore_order( true );
				
            	$sql_query = $query_obj->generate_sql_query();

				$result = $db->query($sql_query);
		
				return $db->fetch_col( 'count', 0, $result);
			}
			catch ( ColumnDoesNotExistException $e ) {
				return 0;
			}
			catch( Exception $e ) {
				throw $e;
			}
	}
	
	function count_by( $col_key, $val, $options = null ) {
		
		try {
			
			$db 		= $this->get_db_object();
			$col_name = $this->column_name($col_key);
			$query_obj  = $this->query_obj_from_option_hash($options);
			$table	    = $this->get_table_name();
			
			$value_format = $this->column_value_get_query_format($val);

			$query_obj->where( "{$this->table_name}.{$col_name} {$value_format[$this->_Key_comparator]} {$value_format[$this->_Key_quote]}{$value_format[$this->_Key_value]}{$value_format[$this->_Key_quote]}" );
			
			$options[$this->_Key_query_obj] = $query_obj;
			
			return $this->count_all( $options );
			
			
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}

	public function query( $query_or_obj, $options = array() ) {
		
		try { 
			$ret = array();
			
			if ( is_object($query_or_obj) ) {
				$query = $query_or_obj->generate_sql_query();
			}
			else {
				$query = $query_or_obj;
			}
			
			Debug::Show( "Query: {$query}", Debug::VERBOSITY_LEVEL_BASIC );
			
			$db = $this->get_db_interface();
			
			$query_res = $db->query( $query );
			$num_rows  = $db->num_rows($query_res);
			
			
			if ( $num_rows > 0 ) {
				if ( $num_rows > 1 ) {
					while ( $row = $db->fetch_unparsed_assoc($query_res) ) {
						$ret[] = $row;
					}
				}
				else {
					$row = $db->fetch_unparsed_assoc($query_res);
					$ret = $row;
				}
			}
			
			return $ret;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function count_query_select( $select ) {
		
		if ( !in_array($select, $this->_Count_query_select) ) {
			$this->_Count_query_select[] = $select;
		}
		
		//$this->db->count_query_select($select);
		
	}
	
	
	public function active_table_aliases_by_options( $options ) {
		
		try {
	    	
	    	$ret = array();
	    	
	    	$ret['table_name'] = $this->get_table_name();
	    	
	    	if ( isset($options[$this->_Key_table_alias]) ) {
				$ret['table_alias'] = $options[$this->_Key_table_alias];
			}
			else {
				$ret['table_alias'] = $ret['table_name'];	
			}
	    	
	    	$ret['field_prefix'] = $this->column_prefix_by_options($options);
	    	$ret['column_prefix'] = $this->column_prefix_by_options($options);
			
			return $ret;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}
	
	public function get_calling_controller() {
		
		return $this->_Calling_controller;
		
	}

	public function set_calling_controller( ApplicationController $c ) {
		
		$this->_Calling_controller = $c;
		
	}
	
}

}

class CoulumnDoesntExistException extends Exception {
	
}

class RowDoesntExistException extends Exception {
	
}

class RowNotIdentifiedException extends Exception {
	
}

class ParentRowDoesntExistException extends Exception {
	
}

class NoRecordDataException extends Exception {
}

/* Fuse Compatibility */
class FuseDataModel extends DataModel {
	
}
?>
