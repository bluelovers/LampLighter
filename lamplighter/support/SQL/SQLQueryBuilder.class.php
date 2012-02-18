<?php

LL::Require_class('SQL/QueryParse');

abstract class SQLQueryBuilder {
	
	const KEY_WHERE_VALUE = 'val';
	const KEY_WHERE_VALUE_TYPE  = 'type';

	public $query_where_clauses = array();
	public $query_join_clauses = array();
	public $query_selections = array();
	public $query_select_options = array();
	public $query_ordering = array();
	public $query_from_tables = array();
	public $query_cases = array();
	public $query_appends = array();
	public $query_having = array();
	public $subquery_selections = array();

	public $ignore_order = false;
	public $ignore_limit = false;
	public $table_id;
	public $group_by;
	public $order_first;
	public $order_last;
	public $distinct;

	public $select_only;
	public $select_first;
	public $select_last;
	public $select_for_update = false;
	public $from_only;

    public $where_joiner_and = 'AND';
    public $where_joiner_or  = 'OR';
        
	protected $_Limit_start = NULL;
	protected $_Limit_end   = NULL;

	protected $_Processed = false;
	
	protected $_Query_type = NULL;
	protected $_Table_name = NULL;
	protected $_Completed_setups;

	//
	
	public $query_string;
	public $last_query;
	public $parse_auto_insert_data;
	public $parse_auto_update_data;

	public $auto_update_requires_where = 1;
	public $auto_insert_query;
	public $auto_update_query;
	public $ident_quotes_on_auto_insert = true;
	public $ident_quotes_on_auto_update = true;

	public $auto_reset = 1;
	var $reset_query_setup_on_generate = 1;

    protected $_Key_where_joiner = 'j';
    protected $_Key_where_clause = 'c';

	protected $_Auto_insert_data;
	protected $_Auto_update_data;
	protected $_Auto_clear_insert_data = 1;
	protected $_Auto_clear_update_data = 1;

	protected $_Ident_quote_char = '"';
	protected $_Parent_db_obj;

	public function __construct( $parent_db_obj ) {

		$this->ident_quotes_on_auto_insert = ( Config::Is_set('sql.ignore_magic_quotes_runtime') ) ? Config::Get('sql.ignore_magic_quotes_runtime') : 0;
		$this->ident_quotes_on_auto_insert = ( Config::Is_set('sql.ident_quotes_on_auto_insert') ) ? Config::Get('sql_query.ident_quotes_on_auto_insert') : $this->ident_quotes_on_auto_insert;
		$this->ident_quotes_on_auto_insert = ( Config::Is_set('sql.ident_quotes_on_auto_update') ) ? Config::Get('sql_query.ident_quotes_on_auto_update') : $this->ident_quotes_on_auto_update;


		if ( is_object($parent_db_obj) ) {
			$this->_Parent_db_obj = $parent_db_obj;
		}

	}

	function query_leader_by_type( $query_type ) {

		LL::Require_class('SQL/QueryConstants');

		switch( $query_type ) {
			case QueryConstants::QUERY_SELECT:
				return 'SELECT';
				break;
			case QueryConstants::QUERY_DELETE:
				return 'DELETE';
				break;
			case QueryConstants::QUERY_UPDATE:
				return 'UPDATE';
				break;
			case QueryConstants::QUERY_CREATE_TABLE:
				return 'CREATE TABLE';
				break;
			case QueryConstants::QUERY_CREATE_TEMPORARY_TABLE:
				return 'CREATE TEMPORARY TABLE';
				break;
		}

		return NULL;
	}

	public function set_parent_db_obj( $obj ) {
		
		$this->_Parent_db_obj = $obj;
		
	}
	

	public function select_first( $selection ) {

		if ( $selection === false || $selection === null ) {
			$this->select_first = null;
		}
		else {

			$this->select_first = $selection;
		}

		return true;
	}
	
	public function select_last( $selection ) {

		if ( $selection === false || $selection === null ) {
			$this->select_last = null;
		}
		else {

			$this->select_last = $selection;
		}

		return true;
	}

	function select_only( $selection ) {

		if ( $selection == false || $selection === null ) {
			$this->select_only = null;
		}
		else {
			$this->select_only = $selection;
		}

	}
	
	function reset_where() {

		$this->query_where_clauses = array();
	}

	function where ( $where, $where_val = null) {

		if ( is_array($where) ) {
			$where_val = $where[1];
			$where = $where[0];
		}


		$this->query_where_clauses[] = array( $this->_Key_where_clause => $where, self::KEY_WHERE_VALUE => $where_val );
	}

	function set_table_name( $name ) {

		$this->_Table_name = $name;

	}

	function get_table_name() {

		return $this->_Table_name;

	}


	function get_query_type() {

		return $this->_Query_type;
	}

	function set_query_type( $type ) {

		$this->_Query_type = $type;

	}

	function add_completed_setup( $setup_name ) {

		$this->_Completed_setups[] = $setup_name;
		
	}

	function has_completed_setup( $setup_name ) {

		if ( is_array($this->_Completed_setups) ) {
			if ( in_array($setup_name, $this->_Completed_setups) ) {
				return true;
			}
		}

		return 0;

	}

	function join( $join_clause ) {

		if ( !is_array($this->query_join_clauses) || !in_array($join_clause, $this->query_join_clauses) ) {	
			$this->query_join_clauses[] = $join_clause;
		}

		return true;

	}


	function select_option( $option ) {

		$this->query_select_options[] = $option;

	}

	function select( $selection = '' ) {

		if ( $selection ) {
			$this->query_selections[] = QueryParse::Strip_sql_clause($selection);
			//$this->select = $selection;
			return true;
		}
		else {
			if ( count($this->query_selections) > 0 ) {
				return true;
			}
		}

		return false;				
		
	}

	public function having( $having ) {
		
		$this->query_having[] = $having;
		
	}

	function select_subquery( $selection ) {

		$this->subquery_selections[] = $selection;
	}

	function is_selection_distinct() {

		if ( $this->distinct ) {
			return true;
		}
		
		return false;

	}

	function set_selection_distinct( $val ) {

		if ( $val ) {
			$this->distinct = ' DISTINCT ';
		}
		else {
			$this->distinct = false;
		}

	}

	function has_select() {	

		if ( count($this->query_selections) > 0 ) {
			return true;
		}

		return false;				

		
	}

	function clear_selections() {

		$this->query_selections = array();
		$this->select_only = null;
		$this->select_first = null;
		$this->select_last = null;
	}

	function clear_selection( $which_selection ) {

		for( $j = 0; $j < count($this->query_selections); $j++ ) {
			$cur_sel = $this->query_selections[$j];
			if ( $cur_sel == $which_selection ) {
				unset( $this->query_selections[$j] );
				break;
			}
		}

	}

	function remove_limit() {

		$this->_Limit_start = null;
		$this->_Limit_end = null;

		$this->limit = null;

	}

	function has_limit() {

		if ( $this->_Limit_start || $this->_Limit_end || $this->limit ) {
			return true;
		}
	
		return false;

	}

	function order_by( $order_by = '' ) {

		if ( $order_by ) {

			$this->query_ordering[] = $order_by;
			return true;
		}
		else {
			return $this->has_order();
		}
		
	}

	function order_first( $order ) {

		$this->order_first = $order;
	}

	function order_last( $order ) {

		$this->order_last = $order;
	}

	function group_by( $group_by ) {

		$this->group_by = $group_by;

	}

	function has_group() {

		if ( $this->group_by ) {
			return true;
		}

		return false;

	}

	function has_order() {

		if ( count($this->query_ordering) > 0  ) {
			return true;
		}

		return false;
	}
	
	function has_where() {

		if ( count($this->query_where_clauses) > 0  ) {
			return true;
		}

		return false;
	}
	

	function has_from( $table = null ) {

		if ( $table == null ) {
			if ( count($this->query_from_tables) > 0 ) {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			if ( is_array($this->query_from_tables) && (count($this->query_from_tables) > 0) ) {
				if ( in_array($table, $this->query_from_tables) ) {
					return true;
				}					
			}
		}

		return false;
	}


        function has_join( $table ) {

                if ( count($this->query_join_clauses) > 0 ) {
                        if ( !$table ) {
                                return true;
                        }

                        foreach( $this->query_join_clauses as $cur_clause ) {

                                if ( preg_match("/JOIN\s+{$table}\s+/i", $cur_clause) ) {
                                        return true;
                                }

				if ( !isset($this->_Parent_db_obj->_Allowed_table_name_chars) ) {
					trigger_error( 'has_join() requires that parent DB object has _Allowed_table_name_chars set', E_USER_WARNING );
					return false;
				}
				else {
	                                if ( preg_match("/JOIN\s+[{$this->_Parent_db_obj->_Allowed_table_name_chars}]+\s+AS\s*{$table}/i",$cur_clause) ) {
        	                                return true;
                	                }
				}

                        }
                }

		return 0;
        }

	public function delete() {

		LL::Require_class('SQL/QueryConstants');

		return $this->set_query_type( QueryConstants::QUERY_DELETE );

	}

	function limit( $limit ) {

		if ( !is_numeric($limit) ) {
		
			LL::Require_class('SQL/QueryConstants');
		
			//
			// set_limit_start() and set_limit_end() are now the correct way to use limits.
			// this will convert a limit like 0,30 to set_limit_start() and set_limit_end() compatible values
			//

			$limit = QueryParse::Strip_sql_clause( $limit, QueryConstants::CLAUSE_LIMIT );
			$limit = trim($limit);
			$limit = str_replace( ' ', '', $limit );
			
			if ( strpos($limit, ',') !== false ) {
				$limit_arr = explode(',', $limit);

				if ( count($limit_arr) > 2 ) {
					trigger_error( "Error parsing limit: {$limit}", E_USER_WARNING );
				}
				else {	

					if ( isset($limit_arr[0]) ) {
						if ( isset($limit_arr[1]) && $limit_arr[1] ) {
							$this->set_limit_start($limit_arr[0]);
							$this->set_limit_end($limit_arr[1]);
						}
						else {
							$this->set_limit_end($limit_arr[0]);
						}
					}
					else {
						trigger_error( "Could not parse limit: {$limit}", E_USER_WARNING );
					}
				}
			}
			else {
				if ( !is_numeric($limit) ) {
					trigger_error( "Could not parse limit: {$limit}", E_USER_WARNING );
				}
				else {
					$this->set_limit_end($limit);
				}
			}
		}
		else {
			$this->set_limit_end($limit);
		}

	}

	function set_limit_start( $limit_start ) {

		if ( !is_numeric($limit_start) ) {
			trigger_error( "Non numeric limit value: {$limit_start}", E_USER_WARNING );
		}
		else {
			$this->_Limit_start = $limit_start;
		}

	}

	function set_limit_end( $limit_end ) {

		if ( !is_numeric($limit_end) ) {
			trigger_error( "Non numeric limit value: {$limit_end}", E_USER_WARNING );
		}
		else {
			$this->_Limit_end = $limit_end;
		}

	}

	function get_limit_start() {

		return $this->_Limit_start;
	}

	function get_limit_end() {

		return $this->_Limit_end;
	}

	function from ( $table_name ) {

		if ( !is_array($this->query_from_tables) || !in_array($table_name, $this->query_from_tables) ) {
			$this->query_from_tables[] = QueryParse::Strip_sql_clause($table_name);
		}
	
		return true;
	}

	function query_case ( $query_case ) {
		$this->query_cases[] = $query_case;
	}

	function select_for_update( $param = null ) {

		if ( $param ) {
			$this->select_for_update = true;
		}
		else if ( $param === null ) {
			//
			// Called with no parameter 
			//
			return $param;
		}
		else {
			$this->select_for_update = false;
		}

		return true;

	}

	function query_append( $append_sql ) {

		$this->query_appends[] = $append_sql;
	}

	function processed( $true_false = NULL ) {

		if ( $true_false === true ) {
			$this->_Processed = true;
		}
		else if ( $true_false === false ) {
			$this->_Processed = false;
		}

		return $this->_Processed;
	}

	function ignore_order( $val ) {

		if ( $val ) {
			$this->ignore_order = true;
		}
		else {
			$this->ignore_order = false;
		}		

	}

	function from_only( $table ) {

		$this->from_only = $table;

	}

	function clear_from_only() {

		$this->from_only = NULL;

	}

	function get_query_selections() {

		return $this->query_selections;

	}

	function get_query_order_columns() {

		if ( !is_array($this->query_ordering) ) {
			$this->query_ordering = array();
		}

		$order_arr = $this->query_ordering;

		if ( $this->order_first ) {
			array_unshift( $order_arr, $this->order_first );
		}
	
		if ( $this->order_last ) {
			$order_arr[] = $this->order_last;
		}

		return $order_arr;

	}

	function select_group_concat( &$gc_obj, $select_as = NULL ) { 

		$gc_select = $this->_Parent_db_obj->generate_group_concat_select( $gc_obj );

		if ( $select_as ) {
			$gc_select = "{$gc_select} AS {$select_as}";
		}

		$this->select( $gc_select );
		

	}


	public function auto_clear_insert_data( $tf ) {
		
		if ( $tf ) {
			$this->_Auto_clear_insert_data = true;
		}
		else {
			$this->_Auto_clear_insert_data = false;
		}
	}

	public function auto_clear_update_data( $tf ) {
		
		if ( $tf ) {
			$this->_Auto_clear_update_data = true;
		}
		else {
			$this->_Auto_clear_update_data = false;
		}
	}

    public function &clone_query_obj() {
		
		$new_obj = clone $this;
	
        return $new_obj;

    }

	function apply_query_setup_hash( &$query_setup, $options = array() ) {

		if ( !isset($options['query_obj']) || !$options['query_obj']) {
			$query_obj = $this;
		}
		else {
			$query_obj = $options['query_obj'];
		}
	
		if ( is_array($query_setup) ) {
		
			foreach( $query_setup as $key => $val ) {
				
				
				if ( $key == 'where' ) {
				
					if ( is_array($val) ) {
						if ( is_array($val[0]) ) {
							
							//
							//
							// Our where clause looks like
							// 'where' =>
							//	array(
							//		array('one=?', 1), 
							//		array('two=?', 2)
							//	)
							//
							foreach( $val as $inner_val ) {
							
								if ( is_array($inner_val) ) {
									
									$this->where( $inner_val[0], $inner_val[1]);
								}
								else {
									
									$this->where($inner_val);
								}
							}	
						}
						else {
							
							//
							// A simple array was passed. This either means
							// we have an array of full where clauses like
							// ( 'id=1', 'category=2' )
							// or we have a single bound param like
							// ( 'id=?', $id )
							
							//
							// Check for a ? (after stripping all literals)
							// to see if this is a bound parameter
							// 
							LL::Require_class('Util/StringUtils');
							$stripped_val = StringUtils::Strip_literals($val[0]);
							
							if ( strpos($stripped_val, '?') !== false ) {
								//
								// val[0] is a string like 'id=?'
								// val[1] is the value
								// so treat it as a bound query:
								//
								$this->where( $val[0], $val[1] );
							}
							else {
								//
								// This is simply an array of where clauses
								//
								
								foreach( $val as $clause ) {
									$this->where( $clause );		
								}
							}
							
						}
						
					}
					else {
						$this->where( $val );
					}
					
					unset($query_setup['where']);
				}
				else {
					if ( method_exists($this, $key) ) {
						
						if ( is_scalar($val) ) {
							$val_arr = array($val);
						}
						else {
							$val_arr = $val;
						}
						
						foreach( $val_arr as $cur_val ) {
							
							$this->$key($cur_val);
							
							unset($query_setup[$key]);
						}
					}
				}
			}
		}

		return $query_obj;
	}

	function ignore_limit( $truefalse ) {

		if ( $truefalse ) {
			$this->ignore_limit = true;
		}
		else {
			$this->ignore_limit = false;
		}		

	}

	public function last_query() {
		return $this->last_query;
	}


    function reset_query_setup() {

		trigger_error( __METHOD__ . ' is no longer supported. Instantiate a new SQLQuery object instead.', E_USER_WARNING );

		return true;
      }


	function add_insert_data( $field_name, $field_value, $type = NULL, $parse = NULL ) {

		if ( !$parse ) {
			$parse = $this->parse_auto_insert_data;
		}

		$this->_Auto_insert_data[$field_name]['name']  = $field_name;
		$this->_Auto_insert_data[$field_name]['value'] = $field_value;
		$this->_Auto_insert_data[$field_name]['type'] = $type;
		$this->_Auto_insert_data[$field_name]['parse'] = $parse;

	}

        function has_update_data() {

                if ( count($this->_Auto_update_data) > 0 ) {
                        return true;
                }

                return false;

        }

        function has_insert_data() {

                if ( count($this->_Auto_insert_data) > 0 ) {
                        return true;
                }

                return false;

        }

	public function get_insert_data() {
		
		return $this->_Auto_insert_data;
		
	}
	
	public function get_update_data() {
		
		return $this->_Auto_update_data;
	}

	/**
	 * Deprecated alias
	 */
	public function get_insert_field_values() {
		return $this->get_insert_column_values();
	}

	public function get_insert_column_values() {
		
		$ret = array();
		
		if ( is_array($this->_Auto_insert_data) ) {
			foreach( $this->_Auto_insert_data as $field_name => $info ) {
				$ret[$field_name] = $info['value'];				
			}
		}
		
		return $ret;
		
	}
	
	/**
	 *  Deprecated alias to get_update_column_values()
	 */
	public function get_update_field_values() {
		return get_update_column_values();
	}
	
	public function get_update_column_values() {
	
		
		$ret = array();
		
		if ( is_array($this->_Auto_update_data) ) {
			foreach( $this->_Auto_update_data as $field_name => $info ) {
				$ret[$field_name] = $info['value'];				
			}
		}
		
		return $ret;
	}

	function add_update_data( $field_name, $field_value, $type = NULL, $parse = 0 ) {

		if ( !$parse ) {
			$parse = $this->parse_auto_update_data;
		}

		$this->_Auto_update_data[$field_name]['name']  = $field_name;
		$this->_Auto_update_data[$field_name]['value'] = $field_value;
		$this->_Auto_update_data[$field_name]['type'] = $type;
		$this->_Auto_update_data[$field_name]['parse'] = $parse;

	}

	function add_update_data_if_value( $field_name, $field_value = '', $quote = NULL, $parse = 0 ) {

		if ( has_value($field_value) ) {
			return $this->add_update_data($field_name, $field_value, $quote, $parse);
		}

	}

	function add_insert_data_if_value( $field_name, $field_value = '', $quote = NULL, $parse = 0 ) {

		if ( has_value($field_value) ) {
			return $this->add_insert_data($field_name, $field_value, $quote, $parse);
		}

	}

        function clear_insert_data() {
                $this->_Auto_insert_data = array();
        }
                
        function clear_update_data() {
                $this->_Auto_update_data = array();
        }


	function reset_auto_queries() {
		$this->reset_auto_insert();
		$this->reset_auto_update();
	}

	function reset_auto_insert() {
		$this->_Auto_insert_data = array();
	}

	function reset_auto_update() {
		$this->_Auto_update_data = array();
	}

    public function copy_where( &$query_obj ) {

                if ( is_array($this->query_where_clauses)) {
                        foreach( $this->query_where_clauses as $where_data ) {
                                $query_obj->where( $where_data[$this->_Key_where_clause], $where_data[$this->_Key_where_joiner] );
                        }
                }

                return $query_obj;

    }

    public function copy_joins( &$query_obj ) {

        	if ( is_array($this->query_join_clauses)) {
            	foreach( $this->query_join_clauses as $cur_join ) {
                	$query_obj->join( $cur_join );
                }
            }

            return $query_obj;

    }

	public function last_auto_insert_query() {
	
		return $this->auto_insert_query;
	}

	public function last_auto_update_query() {
	
		return $this->auto_update_query;
	}

	public function generate_sql_query( $options = array() ) {

		try {
			LL::Require_class('SQL/QueryConstants');
	
			$param_index = 1;
			$query_type = $this->get_query_type();
			
			if ( !$query_type ) {
				$query_type = QueryConstants::QUERY_SELECT;
			}

			if ( !$query_leader = $this->query_leader_by_type($query_type) ) {
				trigger_error( "Invalid query type: {$query_type} in " . __FUNCTION__, E_USER_WARNING );
				return false;
			}
		
			$bound_params_found = false;
			$sql_query        = NULL;
			$case_string      = '';
			$select_options   = '';
			$query_append = '';
			$group_by_clause = '';
			$limit_clause    = '';
			$final_where_clause  = '';
			$final_select_clause = '';
			$final_join_clause   = '';
			$final_from_clause   = '';
			$order_string = '';
			$subquery_selects = '';
			$limit_start = NULL;
			$limit_end   = NULL;
			$having_clause = '';

			//$this->select     = $this->_Parent_db_obj->strip_sql_clause($this->select);
			//$this->join     = ( $this->join AND $this->join_on ) ? "{$this->join} ON " . $this->_Parent_db_obj->strip_sql_clause($this->join_on) : $this->join;
			//$this->where    = ( $this->where ) ? 'WHERE ' . $this->_Parent_db_obj->strip_sql_clause($this->where) : '';
			//$this->order_by = ( $this->order_by ) ? 'ORDER BY ' . $this->_Parent_db_obj->strip_sql_clause($this->order_by) : '';

			$group_by_clause = ( $this->group_by ) ? 'GROUP BY ' . QueryParse::Strip_sql_clause($this->group_by) : '';

			//
			// Process where clauses
			//
			if ( count($this->query_where_clauses) ) {

				foreach( $this->query_where_clauses as $clause_info ) {

					$all_where_vals = array();
					$cur_where = $clause_info[$this->_Key_where_clause];
					$where_val = $clause_info[self::KEY_WHERE_VALUE];
					
					if ( $cur_where ) {
					
						LL::Require_class('Util/StringUtils');
						$where_no_literals = StringUtils::Strip_literals($cur_where);
					
						if ( $where_val && (strpos($where_no_literals, '?') !== false) ) {
							//
							// We are binding parameters
							//
							$bound_params_found = true;
							$where_val_info = array();
							
							if ( is_array($where_val) ) {
							
								//
								// We were passed an associative array
								//
								if ( isset($where_val[self::KEY_WHERE_VALUE]) ) {
									$where_val_info['bind_type']  = PDOStatementHelper::PDO_bind_type_by_letter($where_val[self::KEY_WHERE_VALUE_TYPE]);
									$where_val_info['val'] = $where_val[self::KEY_WHERE_VALUE];
									$all_where_vals[] = $where_val_info;
								}
								else {
								
									//
									// There are multiple bindable values for this clause
									//
									foreach( $where_val as $inner_val ) {
										$where_val_info = array();
										
										//
										// This value has an explicit associative array
										//
										if ( is_array($inner_val) && isset($inner_val[self::KEY_WHERE_VALUE]) ) {
											$where_val_info['bind_type']  = PDOStatementHelper::PDO_bind_type_by_letter($inner_val[self::KEY_WHERE_VALUE_TYPE]);
											$where_val_info['val'] = $inner_val[self::KEY_WHERE_VALUE];
											$all_where_vals[] = $where_val_info;
										}
										else {
											
											
											LL::Require_class('PDO/PDOStatementHelper');
											$where_val_info['bind_type'] = PDOStatementHelper::PDO_bind_type_by_variable($inner_val);
											$where_val_info['val'] = $inner_val;
								
											$all_where_vals[] = $where_val_info;
										}
										
									}
								}
								
								
							}
							else {
								LL::Require_class('PDO/PDOStatementHelper');
								$where_val_info['bind_type'] = PDOStatementHelper::PDO_bind_type_by_variable($where_val);
								$where_val_info['val'] = $where_val;
								
								$all_where_vals[] = $where_val_info;
							}
						
						
							foreach( $all_where_vals as $cur_where_val ) {
								$params[] = array('index' => $param_index, 'value' => $cur_where_val['val'], 'bind_type' => $cur_where_val['bind_type']);
								$param_index++;
							}
														
						}
						
						$cur_where = QueryParse::Strip_sql_clause($cur_where);
		
						$final_where_clause .= ( $final_where_clause ) ? " AND $cur_where" : " WHERE {$cur_where} ";
					}
				}

			}


			//
			// Process JOINs 
			//
			if ( count($this->query_join_clauses) > 0 ) {

				foreach( $this->query_join_clauses as $cur_join ) {

					$cur_join = QueryParse::Strip_sql_clause($cur_join);
					$final_join_clause .= " $cur_join ";
				}

			}


			if ( $this->select_only ) {
				$final_select_clause = QueryParse::Strip_sql_clause($this->select_only);
			}
			else {
				if ( $this->select_first ) {
					if ( count($this->query_selections) > 0 ) {
						array_unshift( $this->query_selections, $this->select_first );
					}
					else {
						$this->query_selections[] = $this->select_first;
					}						
				}

				if ( $this->select_last ) {
					$this->query_selections[] = $this->select_last;
				}

				if ( count($this->query_selections) ) {
					foreach( $this->query_selections as $select_col ) {

						$select_col = preg_replace('/,\s*$/', '', $select_col);
				
						$final_select_clause .= " {$select_col}, ";
					}

					//strip trailing comma space
					$final_select_clause = substr( $final_select_clause, 0, -2 );

				}

				if ( count($this->subquery_selections) > 0 ) {
					foreach( $this->subquery_selections as $select_subquery ) {

						if ( !preg_match('/^\s*\(/', $select_subquery) ) {
							$select_subquery = '(' . $select_subquery . ')';
						}

						if ( !preg_match('/\)\s*$/', $select_subquery) ) {
							$select_subquery .= ')';
						}

						$subquery_selects .= $select_subquery . ', ';
					}
		
					$subquery_selects = substr( $subquery_selects, 0, -2 ); //strip trailing comma space
					
					if ( count($this->query_selections) > 0 ) {
						$subquery_selects = ", {$subquery_selects}";
					}

				}


				if ( count($this->query_cases) > 0 ) {
					foreach( $this->query_cases as $cur_case ) {
						if ( !preg_match('/^\s*CASE/i', $cur_case) ) {
							$cur_case = "CASE {$cur_case}";
						}

						if ( !preg_match('/END\s*$/i', $cur_case) ) {
							$cur_case .= ' END';
						}

						$case_string .= "$cur_case ";
					}
				}

				$distinct_clause = ( $this->distinct ) ? ' DISTINCT ' : NULL;

				$final_select_clause = "{$distinct_clause}{$final_select_clause}{$subquery_selects} {$case_string}";
					
			}

			if ( $this->select_for_update ) {
				$this->select_option( 'FOR UPDATE' );
			}

			if ( count($this->query_select_options) > 0 ) {
				foreach( $this->query_select_options as $cur_option ) {
					$select_options .= " {$cur_option} ";
				}
			}

			//
			// Process FROM clause
			//
			if ( $this->from_only ) {
				$final_from_clause = " FROM {$this->from_only} ";
			}
			else {
				if ( count($this->query_from_tables) ) {

					foreach( $this->query_from_tables as $from_table ) {

						$from_table = QueryParse::Strip_sql_clause($from_table);
						$final_from_clause .= "$from_table, ";
					}

					$final_from_clause = preg_replace('/,\s*$/', '', $final_from_clause);
				
					$final_from_clause = " FROM {$final_from_clause} ";
			
					if ( $this->table_id ) {
						$final_from_clause .= $this->table_id;
					}
				}
			}
			

			//
			// Process ORDER clause
			//

			if ( !$this->ignore_order ) {			

				if ( $this->order_first ) {
					$order_string .= QueryParse::Strip_sql_clause($this->order_first) . ', ';
				}

				if ( count($this->query_ordering) > 0 ) {
					foreach( $this->query_ordering as $cur_order ) {

						$order_string .= QueryParse::Strip_sql_clause($cur_order) . ', ';


					}
				}

				if ( $this->order_last ) {
					$order_string .= QueryParse::Strip_sql_clause($this->order_last) . ', ';
				}

				if ( $order_string ) {
					// strip trailing ', '
					$order_string = preg_replace('/,\s*$/', '', $order_string );
					$order_string = " ORDER BY {$order_string}";
				}
			}

			//
			// Process HAVING clause
			//
			if ( count($this->query_having) > 0 ) {
				
				foreach ( $this->query_having as $having ) {
					$having_clause .= $having . ' AND ';  
				}
		
				$having_clause = ' HAVING ' . rtrim($having_clause, 'AND ');
			}

			//
			// Process LIMIT clause
			//

			if ( !$this->ignore_limit ) {
				
				$limit_clause = '';
				$limit_start = $this->get_limit_start();
				$limit_end = $this->get_limit_end();
				
				if ( $limit_start !== null ) {
					$limit_clause .= $limit_start;
					if ( $limit_end ) {
						$limit_clause .= ',';
					}
				}

				if ( $limit_end ) {
					$limit_clause .= $limit_end;
				}

				if ( $limit_end || $limit_start ) {
					$limit_clause = "LIMIT {$limit_clause}";
				}

			}

			if ( count($this->query_appends) > 0 ) {
				foreach( $this->query_appends as $cur_append ) { 
					$query_append .= " {$cur_append} ";
				}
			}

			$table_name = $this->get_table_name();

			switch( $query_type ) {
				case QueryConstants::QUERY_CREATE_TEMPORARY_TABLE:
					$select_query_leader = $this->query_leader_by_type(  QueryConstants::QUERY_CREATE_TEMPORARY_TABLE );
					$sql_query = "{$query_leader} {$table_name} ({$select_query_leader} {$final_select_clause} {$final_from_clause} {$final_join_clause} {$final_where_clause} {$group_by_clause} {$having_clause} {$order_string} {$limit_clause} {$select_options}) {$query_append}";
					break;
				default:
					$sql_query = "{$query_leader} {$table_name} {$final_select_clause} {$final_from_clause} {$final_join_clause} {$final_where_clause} {$group_by_clause} {$having_clause} {$order_string} {$limit_clause} {$select_options} {$query_append}";
					break;
			}


			$this->processed( true );


			$this->query_string = $sql_query;
			$this->last_query = $sql_query;
	
			
			if ( $bound_params_found ) {
				
				//
				// There are bound parameters in this query
				//
				
				$pdo = $this->_Parent_db_obj;
				
				if ( Config::Get('db.auto_connect_w') && !$this->_Parent_db_obj->is_writable() ) {
					switch( $query_type ) {
						
						case QueryConstants::QUERY_DELETE:
						case QueryConstants::QUERY_UPDATE:
						case QueryConstants::QUERY_CREATE_TABLE:
						case QueryConstants::QUERY_CREATE_TEMPORARY_TABLE:
						case QueryConstants::QUERY_INSERT:
						case QueryConstants::QUERY_ALTER:
							$pdo = $this->_Parent_db_obj->connect_w();
							break;
					}
				}
				else {
					$pdo = $this->_Parent_db_obj;
				}
				
				$sth = $pdo->prepare( $sql_query );
				$sth->bound_params = $params;
				
				
				foreach( $params as $cur_param) {
					Debug::Show("Binding: " . $cur_param['index'] . ' : ' . $cur_param['value'], DEBUG::VERBOSITY_LEVEL_BASIC );
					$sth->bindValue($cur_param['index'], $cur_param['value'], $cur_param['bind_type'] );	
				}
				
				return array( 'query' => $sql_query, 'sth' => $sth );
			}
			else {
				return $sql_query;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}	
		
	}

	public function run() {
		
		try {
			
			return $this->_Parent_db_obj->query($this->generate_sql_query());
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

}

class UpdateWithoutWhereException extends Exception {
}
?>