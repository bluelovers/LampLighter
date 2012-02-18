<?php

class SQLSearch {

	const COMPARE_DIRECT = 1;
	const COMPARE_LIKE = 2;
	const COMPARE_LIKE_INSENSITIVE = 3;
	const COMPARE_FULLTEXT = 4;
	const COMPARE_DATE_INTERVAL = 5;
	const COMPARE_IN = 6;
	const COMPARE_GT = 7;
	const COMPARE_GTE = 8;
	const COMPARE_LT = 9;
	const COMPARE_LTE = 10;
	const COMPARE_MYSQL_FULLTEXT_BOOLEAN = 11; //not yet supported
	const COMPARE_PARSED_BOOLEAN = 12;
	const COMPARE_PARSED_BOOLEAN_INSENSITIVE = 13;
	const COMPARE_WILDCARD_RIGHT = 14;
	const COMPARE_WILDCARD_LEFT = 15;
	const COMPARE_WILDCARD_RIGHT_I = 16;
	const COMPARE_WILDCARD_LEFT_I = 17;
	const COMPARE_IS_NULL = 18;
	
	public $session_id;
	public $search_id = NULL;
	public $search_key;
	public $session_key;
	public $table;
	public $table_id;
	public $direct_compare;
	public $valid_search_fields;
	public $error;
	public $query_return;
	public $key_prefix;
	public $start_limit;
	public $end_limit;
	public $search_query;
	public $search_count = 0;
	public $count_query;
	public $join_statement;
	public $select;
	public $session_obj;
	public $wildcard_char = '%';	
	public $auto_count = true;
	public $ignore_future_dates = false;
	public $form_submit_method = 'post';
	public $error_on_double_conjunction = false;

	protected $_Search_options;
	protected $_Search_data;
	protected $_Default_compare_type;
	protected $_Cookie_prefix = 'search';
	protected $_Active_session;
	protected $_Get_search_data_called = false;

	protected $_Internal_prefix = 'SQLSearch';		
	protected $_Session_prefix = '';
	protected $_New_session = 0;
	protected $_Added_join_clauses;
	protected $_Added_group_clauses;
	protected $_Added_select_clauses;
	protected $_Added_order_clauses;
	protected $_Query_obj_generated = false;
	protected $_Reserved_search_words = array('and', 'or');
	
	
	protected $_Query_obj;
	protected $_DB;

	public function __construct() {

		$this->key_prefix = ( defined('SQL_SEARCH_KEY_PREFIX') ) ? constant('SQL_SEARCH_KEY_PREFIX') : 'search_';
		
		//$this->query_return = '*';

		$this->_Search_data = array();
		
		$this->_Search_options = array();
		$this->valid_search_fields = array();
		
		$this->_Added_join_clauses = array();
		$this->_Added_group_clauses = array();
		$this->_Added_select_clauses = array();
		$this->_Added_order_clauses = array();

	}

	public function __get( $key ) {
		
		if ( $key == 'query_obj' ) {
			return $this->get_query_obj();
		}
		
		return null;
		
	}

	public function __set( $key, $val ) {
		
		if ( $key == 'query_obj' ) {
			$this->set_query_obj( $val );
		}
		
	}

	public function get_db_interface( $options = array() ) {
		
		try { 
			return $this->_DB;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function set_db_interface( $db ) {
		
		try { 
			$this->_DB = $db;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}


	public function get_query_obj() { 

		try { 
			
			$query_obj = $this->_Query_obj;
			
			if ( !$query_obj->has_completed_setup('search_' . $this->get_search_id()) ) {
				$this->apply_search_to_query_obj( $query_obj );
			}
	
			return $this->_Query_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function set_query_obj( $q ) {
		
		$this->_Query_obj = $q;
		
	}

	public function generate_query_obj( $query_obj = null ) {

		try {
			
			if ( $query_obj ) {
				$this->set_query_obj( $query_obj );
			}
			
			return $this->get_query_obj();	
		}
		catch( Exception $e ) {
			throw $e;
		}

	}
	
	public function apply_search_to_query_obj( $query_obj ) {

		try { 
			
			LL::Require_class('SQL/QueryValue');
			
			$db = $this->get_db_interface();
	
			if ( !$this->table OR !$this->valid_search_fields ) {
				throw new Exception( "Valid fields and/or table not set for generate_search_query" );
			}
	
			$search_fields_found = 0;
	
			if ( !$this->_Get_search_data_called ) {
				$this->get_search_data();
			}
	
			if ( count($this->_Search_data) <= 0 ) {
				throw new UserDataException( __CLASS__ . '-no_search_data' );
			}
	
			foreach ( $this->_Search_data AS $field_name => $cur_data ) {
	
				//$field_name = $cur_data['field'];
				//echo "$field_name: "; print_r( $cur_data ); echo '<BR>';
				$case_sensitive = true;			
				$left_wildcard_char = NULL;
				$right_wildcard_char = NULL;
				$wildcards_set = false;
	
				if ( !$cur_data['ignore'] && $this->is_valid_search_field($field_name) ) {
	
					$search_key = $this->remove_key_prefix( $field_name );
					$original_key = $search_key;
	
					if ( $alias = $cur_data['alias'] ) {
						$search_val = $this->_Search_data[$alias]['value'];
					}
					else {
						$search_val = $cur_data['value'];
					}
	
					if ( $this->list_match($search_key, $this->ignore_zero) ) {
						if ( is_numeric($search_val) && $search_val == 0 ) {
							continue;
						}
					}
	
	
					if ( has_value($search_val) || $cur_data['allow_no_value'] ) {
		
						if ( isset($cur_data['map_func']) && is_array($cur_data['map_func']) && (count($cur_data['map_func']) > 0) ) {
							foreach( $cur_data['map_func'] as $cur_func ) {
								$search_val = $cur_func($search_val);
							}
						}
	
						if ( isset($cur_data['join']) && is_array($cur_data['join']) && (count($cur_data['join']) > 0) ) {
							foreach( $cur_data['join'] as $cur_join ) {
								if ( !in_array($cur_join, $this->_Added_join_clauses) ) {
									$query_obj->join( $cur_join );
									$this->_Added_join_clauses[] = $cur_join;
								}
							}
						}
	
						if ( isset($cur_data['group']) && is_array($cur_data['group']) && (count($cur_data['group']) > 0) ) {
							foreach( $cur_data['group'] as $cur_group ) {
								if ( !in_array($cur_group, $this->_Added_group_clauses) ) {
									$query_obj->group_by($cur_group);
									$this->_Added_group_clauses[] = $cur_group;
								}
							}
						}
	
						if ( isset($cur_data['select']) && is_array($cur_data['select']) && (count($cur_data['select']) > 0) ) {
							foreach( $cur_data['select'] as $cur_select ) {
								if ( !in_array($cur_select, $this->_Added_select_clauses) ) {
									$query_obj->select($cur_select);
									$this->_Added_select_clauses[] = $cur_select;
								}
							}
						}
	
						if ( isset($cur_data['order_by']) && is_array($cur_data['order_by']) && (count($cur_data['order_by']) > 0) ) {
							foreach( $cur_data['order_by'] as $cur_order ) {
								if ( !in_array($cur_order, $this->_Added_order_clauses) ) {
									$query_obj->order_by($cur_order);
									$this->_Added_order_clauses[] = $cur_order;
								}
							}
						}
						
						if ( isset($cur_data['rewrite']) && $cur_data['rewrite'] ) {
							$search_key = $this->_rewrite_field_name( $search_key );
						}
						
	
						if ( $this->table_id ) {
							$search_key = "{$this->table_id}.{$search_key}";
						}
	
						$val_fmt = QueryValue::Get_format($search_val);
	
						if ( isset($cur_data['param_type']) && ($cur_data['param_type'] == 'u' || $cur_data['param_type'] == 'f') ) {
							$quote = '';
						}
						else {
							$quote = $val_fmt['quote'];
						}
						
						$search_val = $val_fmt['value'];
	
						switch ( $cur_data['compare'] ) {
							case self::COMPARE_DIRECT:
								$query_obj->where( "{$search_key} = {$quote}{$search_val}{$quote}" );
								break;
							case self::COMPARE_FULLTEXT:
								$fulltext_select = "MATCH ({$search_key}) AGAINST( '{$search_val}' )";
								$query_obj->where( "MATCH ({$search_key}) AGAINST( '{$search_val}' )" );
								break;
							case self::COMPARE_DATE_INTERVAL:
	
								$from_date = $this->get_search_from_date($original_key);
								$to_date = $this->get_search_to_date($original_key);
	
								if ( $from_date || $to_date ) {
									if ( $from_date != $to_date ) {
										if ( $from_date ) {
											$query_obj->where( "DATE({$search_key}) >= '$from_date'" );
										}
	
										if ( $to_date ) {
											//echo "<br>GOT TO DATE: $to_date<br>";
											$query_obj->where( "DATE({$search_key}) <= '$to_date'" );
										}
									}
									else {
										$query_obj->where( "DATE({$search_key}) = '{$from_date}'" );
									}
								}
								else {
									$search_fields_found--;	
								}
	
								break;
							case self::COMPARE_IN: 
								$search_string = '';
								if ( !is_array($search_val) ) {
									
									if ( isset($cur_data['search_delim']) && $cur_data['search_delim'] ) {
										$search_delim = $cur_data['search_delim'];
									}
									else {
										$search_delim = ',';
									}
	
									$search_arr = explode($search_delim, $search_val);
								}
								else {
									$search_arr = $search_val;
								}
	
								foreach( $search_arr as $cur_search_item ) {
									if ( $cur_search_item && ($cur_search_item != $search_delim) ) {
										$cur_search_item = trim($cur_search_item);
										
										$item_fmt = QueryValue::Get_format($cur_search_item);
										$search_string .= "{$item_fmt['quote']}{$item_fmt['value']}{$item_fmt['quote']},";
									}
								}
								
								$search_string = substr($search_string, 0, -1); //strip trailing comma
	
								$query_obj->where( "{$search_key} IN ( {$search_string} )" );
								break;
							case self::COMPARE_GT:
								$query_obj->where( "{$search_key} > {$quote}{$search_val}{$quote}" );
								break;
							case self::COMPARE_GTE:
								$query_obj->where( "{$search_key} >= {$quote}{$search_val}{$quote}" );
								break;
							case self::COMPARE_LT:
								$query_obj->where( "{$search_key} < {$quote}{$search_val}{$quote}" );
								break;
							case self::COMPARE_LTE:
								$query_obj->where( "{$search_key} <= {$quote}{$search_val}{$quote}" );
								break;
							case self::COMPARE_LIKE_INSENSITIVE:
								$left_wildcard_char = $this->wildcard_char;
								$right_wildcard_char = $this->wildcard_char;
								$wildcards_set = true;
								// Drop through on purpose
							case self::COMPARE_WILDCARD_RIGHT_I:
								if ( !$wildcards_set ) {
									$left_wildcard_char  = NULL;
									$right_wildcard_char = $this->wildcard_char;
									$wildcards_set = true;
								}
								//drop through on purpose
							case self::COMPARE_WILDCARD_LEFT_I:
								if ( !$wildcards_set ) {
									$left_wildcard_char  = $this->wildcard_char;
									$right_wildcard_char = NULL;
									$wildcards_set = true;
								}
								$case_sensitive = false;			
								$search_val = str_replace( $this->wildchar_char, '\\' . $this->wildcard_char, $search_val );													
								$search_val = strtolower($search_val);
								$query_obj->where( "lower({$search_key}) LIKE '{$left_wildcard_char}{$search_val}{$right_wildcard_char}'" );
								break;
							case self::COMPARE_PARSED_BOOLEAN_INSENSITIVE:
								$case_sensitive = false;
								//Drop through is on purpose
							case self::COMPARE_PARSED_BOOLEAN:
								if ( $bool_clause = $this->boolean_string_to_sql_clause($search_key, $search_val, $case_sensitive) ) {
									$query_obj->where( " ($bool_clause) " );
								}
								else {
									return false;
								}
								break;
							case self::COMPARE_IS_NULL:
								$query_obj->where( "{$search_key} IS NULL" );
								break;
							case self::COMPARE_WILDCARD_RIGHT:
								if ( !$wildcards_set ) {
									$left_wildcard_char  = NULL;
									$right_wildcard_char = $this->wildcard_char;
									$wildcards_set = true;
								}
								//drop through on purpose
							case self::COMPARE_WILDCARD_LEFT:
								if ( !$wildcards_set ) {
									$left_wildcard_char  = $this->wildcard_char;
									$right_wildcard_char = NULL;
									$wildcards_set = true;
								}
								//
								// Drop through on purpose
								//
							case self::COMPARE_LIKE:
							default:
								if ( !$wildcards_set ) {
									$left_wildcard_char = $this->wildcard_char;
									$right_wildcard_char = $this->wildcard_char;
									$wildcards_set = true;
								}
								$search_val = str_replace( $this->wildchar_char, '\\' . $this->wildcard_char, $search_val );
								$query_obj->where( "{$search_key} LIKE '{$left_wildcard_char}{$search_val}{$right_wildcard_char}'" );
								break;
						}
	
						$search_fields_found ++;
	
					}
	
				}
			}
	
			if ( !$search_fields_found && (!$query_obj->has_where()) ) {
				throw new UserDataException( 'No fields entered into search. Please try searching again.' );
			}
	
			//
			// Add the table to the query
			//
			$query_obj->from( $this->table );
	
			//
			// Generate the COUNT query
			//
			$count_query_obj = $query_obj->clone_query_obj();
	
			$count_query_obj->select_only( 'count(*) AS count' );
			$count_query_obj->auto_reset = true;
			$count_query_obj->ignore_limit( true );
	
			$this->count_query = $count_query_obj->generate_sql_query();
	
			//$query_obj->ignore_limit( false );
			//$query_obj->clear_selections();
	
			//
			// Generate the search query
			//
	
			if ( $fulltext_select ) {
				$query_obj->select( $fulltext_select );
			}


			if ( $this->table_id ) {
				$query_obj->select( "{$this->table_id}.{$this->query_return}" );
			}
			else {
				$query_obj->select( $this->query_return );
			}
	
			$limit_statement = ( $this->start_limit OR $this->end_limit ) ? "LIMIT {$this->start_limit}" : '';
			$limit_statement = ( $limit_statement AND $this->end_limit) ? $limit_statement . ", {$this->end_limit}" : '';
	
			if ( $limit_statement ) {
				$query_obj->limit( $limit_statement );
			}
	
			$query_obj->auto_reset = false;
			
			$query_obj->add_completed_setup('search_' . $this->get_search_id());
			
			$this->_Query_obj = $query_obj;
			$this->_Query_obj_generated = true;
	
			return $query_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function generate_search_query( $options = array() ) {

		try { 

			if ( !is_array($options) ) {
				// Deprecated call
				$this->set_query_obj($options);
			}
			else {
				if ( isset($options['query_obj']) && $options['query_obj'] ) {
					$this->set_query_obj($options['query_obj']);
				}
			}

			$query_obj = $this->get_query_obj();
			
			if ( !is_object($query_obj) ) {
				throw new InvalidParameterException('query_obj');
			}
	
			if ( $search_query = $query_obj->generate_sql_query() ) {
				$this->search_query = $search_query;
				return $search_query;
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
		

	}

	public function start_search_session() {

		try { 
			$session = $this->get_session_obj();
			
			$this->_New_session = 1;
	
			$this->search_id  = $this->generate_search_id();
			$this->session_key = $this->generate_session_key();
	
			$this->get_search_data(); 
	
			//${$this->search_id} = array();
			//$search_hash =& ${$this->search_id};
	
			$search_hash = array();
			$search_hash['data']    = $this->_Search_data;
			$search_hash['options'] = $this->_Search_options;
	
			$session->set( $this->session_key, $search_hash );
			$session->set( 'search_active', 1 );
	
			$this->_Active_session = $session;
			$this->session_id = $session->id();
			
			return $this->search_id;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function generate_search_id() {

		return uniqid();


	}

	public function generate_session_key( $search_id = 0 ) {

		try { 
			if ( !$search_id ) {
				$search_id = $this->generate_search_id();
			}
	
			return $this->_Internal_prefix . '_' . $this->_Session_prefix . $search_id;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_session_prefix( $prefix ) {
		$this->_Session_prefix = $prefix;
	}


	public function set_search_id( $search_id ) {

		try { 
			$this->search_id  = $search_id;
			$this->session_key = $this->generate_session_key( $search_id );
		}
		catch( Exception $e ) {
			throw $e;
		}		

	}

	public function active_session_obj() {

		return $this->_Active_session;

	}

	public function get_session_obj() {
	
		try { 
			if ( !$this->session_obj ) {
				LL::Require_class( 'Session/BrowserSession' );
	
				$session = new BrowserSession( array('start' => true) );
	
				return $session;
			}
			else {
				return $this->session_obj;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function add_search_array( $which_arr, $stripslashes = 0 ) {

		try { 
			if ( count($which_arr) > 0 ) {
	
				foreach( $which_arr as $key => $value ) {
	
	
					if ( $this->is_search_key($key) && $this->is_valid_search_field($key) ) {
				
						$key = $this->remove_key_prefix($key);
	
						$quote = $this->_Search_data[$key]['quote'] OR $quote = -1;
						$parse = $this->_Search_data[$key]['parse'] OR $parse = -1;
						$compare = $this->_Search_data[$key]['compare'] OR $compare = $this->_Default_compare_type;
	 					$rewrite = $this->_Search_data[$key]['rewrite'] OR $rewrite = '';
	
						$this->set_field_options( $key, $compare, $quote, $parse, $rewrite );
	
						if ( $stripslashes ) {
							$value = stripslashes($value);
						}
	
						$this->set_search_value( $key, $value );
					}
				}
	
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function is_search_key( $value ) {


		if ( substr($value, 0, strlen($this->key_prefix)) == $this->key_prefix ) {
			return true;
		}
		else {
			return false;
		}

	}


	public function get_option( $key_name ) {

		try { 
			if ( isset($this->_Search_options[$key_name]) ) {
				return $this->_Search_options[$key_name];
			}
			else {
	
				//if ( !$this->_Get_search_data_called ) {
					$this->get_search_data();
				//}
	
				if ( isset($this->_Search_options[$key_name]) ) {
					return $this->_Search_options[$key_name];
				}
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}
	
	public function set_option ( $name, $value ) {

		$this->_Search_options[$name] = $value;


	}


	public function _rewrite_field_name ( $field_name ) {

		if ( $rewrite_info = $this->_Search_data[$field_name]['rewrite'] ) {

			$rewrite_val = $rewrite_info['sql_field'];

			if ( $rewrite_info['table'] ) {
				$rewrite_val = "{$rewrite_info['table']}.{$rewrite_val}";
			}

			return $rewrite_val;

		}

		return $field_name;
	}

	public function list_match( $needle, $haystack ) {

		if ( is_array($haystack) ) {

			if ( in_array($needle, $haystack) ) {
				return true;
			}

		}
		else {
			if ( $needle == $haystack ) {
				return true;
			}
		}

		return false;


	}


	public function add_search_key_prefix( $which_key ) {

		return "{$this->key_prefix}{$which_key}";

	}

	public function remove_key_prefix( $which_value ) {

		try { 
			if ( is_array($which_value) ) {
	
				$parsed_values = array();
	
				foreach ( $which_value as $input_name ) {
	
					if ( $this->key_has_search_prefix($input_name) ) {
						$input_name = substr($input_name, strlen($this->key_prefix));
					}
	
					array_push( $parsed_values, $input_name );
	
				}
			
				return $parsed_values;
			}
			else {
				if ( strstr($which_value, ',') ) {
					$which_value = str_replace( ' ', '', $which_value);
					$keys = explode( ',', $which_value);
					$key_string = '';
	
					if ( count($keys) > 0 ) {
						foreach($keys as $cur_key) {
							$key_string = $this->remove_key_prefix($cur_key) . ',';
						}
	
						$key_string = preg_replace('/,$/', '', $key_string);
					}
				}
	
				if ( $this->key_has_search_prefix($which_value) ) {
					$which_value = substr($which_value, strlen($this->key_prefix));
				}
	
	
				return $which_value;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function key_has_search_prefix( $which_key ) {


		if ( substr($which_key, 0, strlen($this->key_prefix)) == $this->key_prefix ) {
			return true;
		}

		return 0;

	}

	function query_returns( $value ) {

		$this->query_return = $value;
		return true;
	}

	function query_return( $value ) {

		//alias to query_returnS
		return $this->query_returns($value);

	}


	public function set_valid_search_field( $field_name ) {
	
		try { 
			if ( is_array($field_name) ) {
				if ( count($field_name) ) {
					foreach ( $field_name as $cur_field_name ) {
						$this->valid_search_fields[] = $cur_field_name;
					}
				}
			}
			else {
				if ( strstr($field_name, ',') ) {
					$field_name = str_replace( ' ', '', $field_name );
					$search_fields = explode(',', $field_name);
					
					foreach( $search_fields as $cur_field_name ) {
						$this->valid_search_fields[] = $cur_field_name;
					}
					//array_merge( $this->valid_search_fields, $search_fields );
	
				}
				else {
					$this->valid_search_fields[] = $field_name;
				}
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_valid_search_fields( $field_name ) {
		//Alias
		return $this->set_valid_search_field( $field_name );
	}

	public function valid_search_field( $field_name ) {
	//Alias
		return $this->set_valid_search_field( $field_name );
	}


	public function set_cookie_id( $cookie_id ) {

		$this->cookie_id = $cookie_id;

	}

	public function set_start_limit( $start_limit ) {

		$this->start_limit = $start_limit;
		
	}

	public function set_end_limit( $end_limit ) {

		$this->end_limit = $end_limit;
		
	}

	public function set_ignore_zero( $field_name ) {

		$this->ignore_zero[] = $field_name;
		return true;

	}

	public function ignore_zero( $field_name ) {

		return $this->set_ignore_zero($field_name);

	}

	public function set_table_name( $table_name ) {
	
		$this->table = $table_name;
		
	}

	public function table_name( $table_name ) {

		return $this->set_table_name( $table_name );

	}

	public function set_table_id( $table_id ) {

		$this->table_id = $table_id;
		return true;
	}

	
	public function is_valid_search_field( $field_name ) {

		$search_key = $this->remove_key_prefix($field_name);

		if ( in_array($search_key, $this->valid_search_fields) ) {
			return true;
		}

		return false;

	}

	public function is_valid_field_name( $field_name ) {

		try { 
			$db = $this->get_db_interface();
	
			if ( method_exists($db, 'is_valid_field_name') ) {
				return $db->is_valid_field_name($field_name);
			}
			else {
				if ( preg_match('/[^A-Za-z0-9_\-]/', $field_name) ) {
					return false;
				}
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	//
	// Individual Field option methods
	//
	public function alias( $field_name, $alias ) {
		$this->_Search_data[$field_name] = $this->_Search_data[$alias];
		$this->_Search_data[$field_name]['alias'] = $alias;

		$this->set_field_options( $alias );
	}

	public function rewrite_field( $input_field_name, $sql_field_name, $table = null ) {

		$this->_Search_data[$input_field_name]['rewrite']['sql_field'] = $sql_field_name;

		if ( $table ) { 
			$this->_Search_data[$input_field_name]['rewrite']['table'] = $table;
		}
	}

	public function db_rewrite( $input_field_name, $sql_field_name, $table = null ) {

		return $this->rewrite_field( $input_field_name, $sql_field_name, $table );

	}


	public function rewrite( $input_field_name, $sql_field_name, $table = null ) {

		return $this->rewrite_field( $input_field_name, $sql_field_name, $table );

	}

	public function dont_quote( $field_name ) {
		$this->_Search_data[$field_name]['param_type'] = 'u';
	}


	public function search_direct( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_DIRECT;
	}

	public function search_fulltext( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_FULLTEXT;
	}

	public function search_like( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_LIKE;
	}

	public function search_parsed_boolean( $field_name, $case_sensitive = true ) {
	
		$this->set_valid_search_field($field_name);
		
		if ( $case_sensitive ) {
			$this->_Search_data[$field_name]['compare'] = self::COMPARE_PARSED_BOOLEAN;
		}
		else {
			$this->_Search_data[$field_name]['compare'] = self::COMPARE_PARSED_BOOLEAN_INSENSITIVE;
		}
	}

	public function search_wildcard_right( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_WILDCARD_RIGHT;
	}

	public function search_wildcard_right_i( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_WILDCARD_RIGHT_I;
	}

	public function search_wildcard_left( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_WILDCARD_LEFT;
	}

	public function search_wildcard_left_i( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_WILDCARD_LEFT_I;
	}

	public function search_like_insensitive( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_LIKE_INSENSITIVE;
	}

	public function search_in( $field_name, $delim = ',' ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_IN;
		$this->_Search_data[$field_name]['search_delim'] = $delim;
	}

	public function search_greater_than( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_GT;
	}

	public function search_greater_than_equal( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_GTE;
	}

	public function search_less_than( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_LT;
	}

	public function search_less_than_equal( $field_name ) {
		
		$this->set_valid_search_field($field_name);
		$this->_Search_data[$field_name]['compare'] = self::COMPARE_LTE;
	}

	public function search_date_interval( $field_name ) {

		$this->_Search_data[$field_name]['compare'] = self::COMPARE_DATE_INTERVAL;
		$this->_Search_data[$field_name]['allow_no_value'] = true;

		$this->set_valid_search_field( "{$field_name}" );

		$this->set_valid_search_field( "{$field_name}_from_month" );
		$this->ignore_search_field ( "{$field_name}_from_month" );

		$this->set_valid_search_field( "{$field_name}_from_day" );
		$this->ignore_search_field ( "{$field_name}_from_day" );

		$this->set_valid_search_field( "{$field_name}_from_year" );
		$this->ignore_search_field ( "{$field_name}_from_year" );

		$this->set_valid_search_field( "{$field_name}_to_month" );
		$this->ignore_search_field ( "{$field_name}_to_month" );

		$this->set_valid_search_field( "{$field_name}_to_day" );
		$this->ignore_search_field ( "{$field_name}_to_day" );

		$this->set_valid_search_field( "{$field_name}_to_year" );
		$this->ignore_search_field ( "{$field_name}_to_year" );
		

	}

	public function set_field_comparison_method( $field_name, $method ) {

		$this->_Search_data[$field_name]['compare'] = $method;

	}

	public function ignore_search_field( $which_field ) { 
	
		$this->_Search_data[$which_field]['ignore'] = true;
	}

	public function get_search_from_date( $field_name ) {

		try { 
			$db = $this->get_db_interface();
	
			if ( $search_data = $this->get_search_data() ) {
				
				if ( $this->is_search_interval_present($field_name, 'from') ) {
					return $db->date_format( $search_data["{$field_name}_from_month"]['value'], $search_data["{$field_name}_from_day"]['value'], $search_data["{$field_name}_from_year"]['value'] );
				}
	
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function is_search_interval_present( $field_name, $tofrom = 'from', $require_all = 0 ) {

		try { 
			$search_data = $this->get_search_data();
	
			if ( !$require_all ) {
				if ( $search_data["{$field_name}_{$tofrom}_month"]['value'] || $search_data["{$field_name}_{$tofrom}_day"]['value'] || $search_data["{$field_name}_{$tofrom}_year"]['value'] ) {
					return true;
				}
			}
			else {
				if ( $search_data["{$field_name}_{$tofrom}_month"]['value'] && $search_data["{$field_name}_{$tofrom}_day"]['value'] && $search_data["{$field_name}_{$tofrom}_year"]['value'] ) {
					return true;
				}
	
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function get_search_to_date( $field_name ) {

		try { 
			$db = $this->get_db_interface();
	
			if ( $search_data = $this->get_search_data() ) {
	
				if ( $this->is_search_interval_present($field_name, 'to', 1) ) {
	
					if ( $this->ignore_future_dates ) {
	
						$search_time = mktime( 0, 0, 0, $search_data["{$field_name}_to_month"]['value'], $search_data["{$field_name}_to_day"]['value'], $search_data["{$field_name}_to_year"]['value'] );
						$today_time = mktime( 0, 0, 0, date('n', time()), date('d', time()), date('Y', time()) );
	
						if ( $search_time >= $today_time ) {
							return false;
						}
	
					}
	
	
					return $db->date_format( $search_data["{$field_name}_to_month"]['value'],$search_data["{$field_name}_to_day"]['value'], $search_data["{$field_name}_to_year"]['value'] );
				}
	
			}
	
			return false;
		}
		catch( Exception $e ) {
			throw $e;
		} 
	}


	public function set_search_value( $field_name, $value ) {

		$this->_Search_data[$field_name]['value'] = $value;
	}

	public function table( $which_table = '' ) {

		if ( $which_table ) {
			$this->table = $which_table;
		}

		return $this->table;

	}

	public function select( $what ) {

		$this->select[] = $what;

	}

	public function save_search_form( &$form ) {

		try { 
			if ( !is_object($this->active_session_obj()) ) {
				$this->start_search_session();
			}
	
	        $session_obj = $this->active_session_obj();
	
			$search_data = $this->get_search_data();
	
			foreach( $this->valid_search_fields as $field_name ) {
	
				$search_key = "{$this->key_prefix}{$field_name}";
	
				$session_obj->set( $search_key, $form->get($search_key) );
	
				if ( $search_data[$field_name]['compare'] == self::COMPARE_DATE_INTERVAL ) {
	
					$session_obj->set( "{$search_key}_from_day", $form->get("{$search_key}_from_day") );
					$session_obj->set( "{$search_key}_from_month", $form->get("{$search_key}_from_month") );
					$session_obj->set( "{$search_key}_from_year", $form->get("{$search_key}_from_year") );
	
					$session_obj->set( "{$search_key}_to_day", $form->get("{$search_key}_to_day") );
					$session_obj->set( "{$search_key}_to_month", $form->get("{$search_key}_to_month") );
					$session_obj->set( "{$search_key}_to_year", $form->get("{$search_key}_to_year") );
	
				}
			}
	
			return true;		
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_search_data( $ignore_session = 0 ) {

		try { 
		
			if ( !$ignore_session && !$this->_New_session ) {
	
				$this->_Active_session = $this->get_session_obj();
	
				$this->_Search_hash = $this->_Active_session->get( $this->generate_session_key($this->search_id) );
	
				//echo $this->generate_session_key($this->search_id) . '<br />';
				//print_r( $_SESSION );
				//print_r( $_SESSION[$this->generate_session_key($this->search_id)] );
	
				if ( is_array($this->_Search_hash['data']) ){ 
					
					$this->_Search_data = array_merge( $this->_Search_data, $this->_Search_hash['data'] );
				}
	
				if ( is_array($this->_Search_hash['options']) ){ 
					$this->_Search_options = array_merge( $this->_Search_data, $this->_Search_hash['options'] );
				}
				
	
	
			}
			else {
				//
				// We Strip slashes on POST/GET to prevent double parsing.
				// Values are parsed by generate_sql_query
	
				$strip_slashes = ( get_magic_quotes_gpc() ) ? 1 : 0;
	
				if ( $this->form_submit_method == 'post' ) {
					$this->add_search_array( $_POST, $strip_slashes);
				}
				else if ( $this->form_submit_method == 'get' ) {
					$this->add_search_array( $_GET, $strip_slashes);
	
				}
			}
		
			$this->_Get_search_data_called = true;
	
			return $this->_Search_data;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function cookie_prefix( $new_prefix ) {
	
		$this->_Cookie_prefix = $new_prefix;

	}

	public function ignore_field( $field_name ) {

		$this->_Search_data[$field_name]['ignore'] = 1;

	}

	public function unignore_field( $field_name ) {

		$this->_Search_data[$field_name]['ignore'] = 0;

	}

	public function get_count( $count_query = '' ) {

		try { 
			
			$db = $this->get_db_interface();
	
			$count_query = ( $count_query ) ? $count_query : $this->count_query;
	
			if ( $count_query ) {
				$result = $db->query($count_query);
				return $db->fetch_col( 'count', 0, $result );
			}
			else {
				throw new InvalidParameterException('count_query');
			}	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_form_submit_method( $method ) {

		$this->form_submit_method = $method;		

	}

	public function join_if_field_present( $field_name, $join_clause ) {

		try { 	
			if ( !$field_name ) {
				throw new MissingParameterException('field_name');
			}
	
			if ( !$join_clause ) {
				throw new MissingParameterException('join_clause');
			}
		
			if ( is_array($field_name) ) {
				foreach( $field_name as $cur_field_name ) {
					$this->join_if_field_present($cur_field_name, $join_clause);
				}
			}
			else {
				$this->_Search_data[$field_name]['join'][] = $join_clause;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function group_if_field_present( $field_name, $group_clause ) {

		try { 		
			if ( !$field_name ) {
				throw new MissingParameterException('field_name');
			}
	
			if ( !$group_clause ) {
				throw new MissingParameterException('group_clause');
			}
		
			if ( is_array($field_name) ) {
				foreach( $field_name as $cur_field_name ) {
					$this->group_if_field_present($cur_field_name, $group_clause);
				}
			}
			else {
				$this->_Search_data[$field_name]['group'][] = $group_clause;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function select_if_field_present( $field_name, $selection ) {

		try { 
			if ( !$field_name ) {
				throw new MissingParameterException('field_name');
			}

			if ( !$selection ) {
				throw new MissingParameterException('selection');
			}
		
			if ( is_array($field_name) ) {
				foreach( $field_name as $cur_field_name ) {
					$this->select_if_field_present($cur_field_name, $selection);
				}
			}
			else {
				$this->_Search_data[$field_name]['select'][] = $selection;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function order_by_if_field_present( $field_name, $order_by ) {

		try { 
			if ( !$field_name ) {
				throw new MissingParameterException('field_name');
			}
	
			if ( !$order_by ) {
				throw new MissingParameterException('order_by');
			}
		
			if ( is_array($field_name) ) {
				foreach( $field_name as $cur_field_name ) {
					$this->order_by_if_field_present($cur_field_name, $order_by);
				}
			}
			else {
				$this->_Search_data[$field_name]['order_by'][] = $order_by;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function apply_field_map_func( $field_name, $func_name ) {
		
		$this->_Search_data[$field_name]['map_func'][] = $func_name;

	}

	public function repopulate_template( &$template, $prepend_prefix = 1 ) {
		
		return $this->apply_search_data_to_template( $template, $prepend_prefix );
		
	}

	public function apply_search_data_to_template( &$template, $prepend_prefix = 1 ) {

		try { 
			if ( !$this->_Get_search_data_called ) {
				$this->get_search_data();
			}
	
			if ( count($this->_Search_data) > 0 ) {
	
				foreach ( $this->_Search_data AS $field_name => $cur_data ) {
	
					if ( $prepend_prefix ) {
						$field_name = "{$this->key_prefix}{$field_name}";
					}
	
					$template->add_param( $field_name, $cur_data['value'] );
				}
			}
	
			return $template;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}




    public function boolean_string_to_sql_clause( $field_name, $search_string, $case_sensitive = true ) {

		try { 
			$db = $this->get_db_interface();
	        $search_clause = '';
	        $last_token_is_conjunction = false;
			$in_literal = false;
			$literal_string = '';
	
	        if ( !$field_name ) {
				throw new MissingParameterException('field_name');
			}
	
	        $string_arr = preg_split( '/(\s)/', $search_string, -1, PREG_SPLIT_DELIM_CAPTURE );
	
	        if ( count($string_arr) > 0 ) {
	        
	        	foreach( $string_arr as $cur_token ) {
	            	$add_closing_par = false;
	                $cur_clause = '';
	
					if ( $in_literal ) {
						if ( preg_match('/"\s*(\))?$/', $cur_token, $matches) ) {
							//
							// We've reached the end of the literal
							//
							//echo "end of literal: {$cur_token}<br />";
	
							$cur_token = rtrim($cur_token);
	
							$add_closing_par = ( $matches[1] ) ? true : false;
							$substr_val = ( $add_closing_par ) ? -2 : -1;						
							
							$literal_string .= substr($cur_token, 0, $substr_val);
	
							//echo "literal string is: {$literal_string}<br />";
							$cur_token  = $literal_string;
							$literal_string = '';
							$in_literal = false;
						}
						else {
							$literal_string .= $cur_token;
							continue;
						}
	
					}
					else {
	
						//if ( preg_match('/[^\s]/', $cur_token) ) {
	
							$cur_token = ltrim($cur_token);
	
			                if ( substr($cur_token, 0, 1) == '(' ) {
	        		        	//
	                		    // Token starts with a parentheses
	                		    //
	                            	$cur_clause .= '(';
		                            $cur_token = substr( $cur_token, 1 );
	    	                }
	
		        	        if ( substr($cur_token, -1) == ')' ) {
	        	        		//
	                    	    // Token ends with parentheses
		                        //
	        	                $cur_token = substr( $cur_token, 0, -1 );
	            	            $add_closing_par = true;
		                	}
	
							if ( substr($cur_token, 0, 1) == '"' ) {
								//
								// Starting a phrase
								//
								//echo "start of literal: {$cur_token}<br />";
	
								if ( substr($cur_token, -1) == '"' ) {
	
									//
									// This is a one word phrase,
									// No need to start a literal, 
									// but append & prepend spaces to our token
									// to treat it as its own word.
		
									$cur_token = rtrim($cur_token);
	
	
									$cur_token = substr($cur_token, 1 );
									$cur_token = substr($cur_token, 0, -1);
									$cur_token = " {$cur_token} ";
	
	
								}
								else {
									$in_literal = true;
									$literal_string .= substr($cur_token, 1); //strip the leading quote
									continue;
								}
							}
							else {
								$cur_token = rtrim($cur_token);
							}
	
						//}
						//else {
						//	continue;
						//}
	
					}
	
	
					if ( $cur_token && preg_match('/[^\s]/', $cur_token) ) {
						
						//echo "token: |{$cur_token}|<br />"; 
	
		            	if ( in_array(strtolower($cur_token), $this->_Reserved_search_words) ) {
	
							if ( $last_token_is_conjunction ) {
	
								//
								// We encountered two conjunctions in a row, which makes no sense
								// Either raise an error, or just ignore the extra one...
								//
								if ( $this->error_on_double_conjunction ) {
									throw new Exception( __CLASS__ . '-invalid_search_string' );
								}
							}
							else {
								$last_token_is_conjunction = true;
	        	        	    $cur_clause .= $cur_token;
							}
	                    }
		                else {
	        	        
	        	           $cur_token = $db->parse_if_unsafe($cur_token);
	
							if ( !$last_token_is_conjunction && (strlen($search_clause) > 0) ) {
								$cur_clause .= " AND ";
							}
	
		                    if ( !$case_sensitive ) {
	        	            	$cur_token = strtolower($cur_token);
	                	        $cur_clause .= " LOWER({$field_name}) LIKE '{$this->wildcard_char}{$cur_token}{$this->wildcard_char}' ";
		
	        	           	}
	                	    else {
	                        	$cur_clause .= " {$field_name} LIKE '{$this->wildcard_char}{$cur_token}{$this->wildcard_char}' ";
	                        }
	
							$last_token_is_conjunction = false;
	
						}
	
	        	       	if ( $add_closing_par ) {
	                		$cur_clause .= ') ';
	                   	}
	
	                    $search_clause .= $cur_clause;
					}
				}
	 		}
		
	        return $search_clause;
		}
		catch( Exception $e ) {
			throw $e;
		}
    }

	public function get_search_token_arr_from_boolean_string( $search_string ) {
	
		try { 
			$tokens      = array();	
	
			if ( $search_string ) {
	
				preg_match_all('/"(.+)"/U', $search_string, $phrase_matches);
	
				if ( count($phrase_matches[0]) > 0 ) {
					for( $j = 0; $j < count($phrase_matches[0]); $j++ ) {
						$cur_token = $phrase_matches[1][$j];
						$cur_token = trim( $cur_token );
	
						$search_string = str_replace( $phrase_matches[0][$j], '', $search_string );
	
						$tokens[] = $cur_token;	
					}
				}
				
				$string_arr = explode( ' ', $search_string );
	
				if ( count($string_arr) > 0 ) {
					foreach( $string_arr as $cur_token ) {
						if ( $cur_token && !in_array(strtolower($cur_token), $this->_Reserved_search_words) ) {
	
							$cur_token = trim($cur_token);
	
							if ( substr($cur_token, 0, 1) == '(' ) {
								$cur_token = substr($cur_token, 1);
							}
							if ( substr($cur_token, -1) == ')' ) {
								$cur_token = substr($cur_token, 0, -1);
							}
	
							$tokens[] = trim($cur_token);
						}
					}
				}
				
			}
	
			return $tokens;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_search_id() {
		
		return $this->search_id;

	}

    public function get_search_value( $key ) {

    	if ( !$this->_Get_search_data_called ) {
        	$this->get_search_data();
        }

        if ( isset($this->_Search_data[$key]['value']) ) {
        	return $this->_Search_data[$key]['value'];
        }

        return NULL;

    }

	public function set_ignore_future_dates( $val ) {

		if ( $val ) {
			$this->ignore_future_dates = true;
		}
		else {
			$this->ignore_future_dates = false;
		}
	}

	public function set_field_filter_type( $field, $filter_type ) {
		
		switch( strtolower(trim($filter_type)) ) {
			case 'date_interval':
				$this->search_date_interval($field);
				break;
			case 'fulltext':
				$this->search_fulltext($field);
				break;
			case 'gt':
			case 'greater_than':
				$this->search_greater_than($field);
				break;
			case 'greater_than_equal':
			case 'gte':
				$this->search_greater_than_equal($field);
				break;
			case 'in':
				$this->search_in($field);
				break;
			case 'less_than':
			case 'lt':
				$this->search_less_than($field);
				break;
			case 'direct':
				$this->search_direct( $field );
				break;
			case 'like_insensitive':
				$this->search_like_insensitive($field);
				break;
			case 'parsed_boolean':
				$this->search_parsed_boolean($field);
				break;
			case 'wildcard_left':
				$this->search_wildcard_left($field);
				break;
			case 'wildcard_left_i':
			case 'wildcard_left_insensitive':
				$this->search_wildcard_left_i($field);
				break;
			case 'wildcard_right':
				$this->search_wildcard_right($field);
				break;
			case 'wildcard_right_insensitive':
			case 'wildcard_right_i':
				$this->search_wildcard_right_i($field);
				break;
			case 'like':
			default:
				$this->search_like($field);
				break;
		}
		
	}
} // end search class

/* 
 * Fuse Compatibility
 */
class FuseSQLSearch extends SQLSearch {
	
} 
 

?>