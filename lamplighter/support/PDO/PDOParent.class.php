<?php

LL::Require_interface( 'PDO/PDOParentInterface');

abstract class PDOParent extends PDO implements PDOParentInterface {

	public $column_info_use_cache = true;
	public $transactions_track = true;
	public $config_name = 'default';
	public $config_data = array();
	public $is_writable = false;

	protected $_Escapable_chars = array('"', ';', '\'', 'n', 'r', 'x1a', 'x00');

	protected $_Last_seek = null;
	protected $_Last_result;

	protected $_Results;
	protected $_Fetched_queries;
	protected $_Cached_row_counts;

	protected $_Transaction_active = false;
	protected $_Writable_obj; 
	protected $_Count_query_select = array(); //for special cases when a COUNT(*) query also needs
	                                //another selection

	protected $_Count_query_alias = array(); //for special cases when a COUNT(*) query needs a special alias

	protected $_Count_query_column = array(); //for special cases when a COUNT() query needs to use something other than *
	protected $_Count_queries = array(); //for special case when a COUNT() query is explicitly set 
	protected $_Explicit_num_rows = array(); //for special case when we can't autogenerate a COUNT() query

	protected static $Cached_column_info;
	
	public $writable_statements = 
		array(
			'alter',
			'create',
			'drop',
			'delete',
			'insert', 
			'update'
		);

	public final function new_query_obj() {

		try  {		

			$driver = strtolower($this->getAttribute( PDO::ATTR_DRIVER_NAME ));
		
			$class_name = 'SQLQueryBuilder_' . $driver;
			LL::Require_class( "SQL/{$class_name}");
		
			return new $class_name($this);
		}
		catch( Exception $e ) {
			throw $e;
		} 
		
	} 
	
	public static function Get_unique_query_key( $query ) {
		return md5($query);
	}
	
	public final function safe_for_sql( $value ) {
		
		static $escapable_regex_string;

		if ( !$escapable_regex_string ) {
			$escapables = $this->_Escapable_chars;

			if ( !$escapables || !is_array($escapables) || (count($escapables) <= 0) ) {
				trigger_error( 'No escapable characters set for DB object. Fatal.' , E_USER_ERROR );
				exit(1);
			}

			for ( $j = 0; $j < count($escapables); $j++ ) {
				$escapables[$j] = preg_quote($escapables[$j], '/');
			}
		
			$escapable_regex_string = implode( '|', $escapables );

		}

       	if ( preg_match("/(?<!\\\)\\\(?!({$escapable_regex_string}|\\\))/", $value) ) {
        	return false;
        }

        return true;
		
	}
	
	public final function parse_if_unsafe( $val ) {

		if ( !$this->safe_for_sql($val) ) {
			$val = trim($this->quote($val), '\'');
		}
		
		return $val;
	}

	public final function get_last_error( $dbh = null ) {
		
		return $this->errorInfo();
		
	}
	
	public final function data_seek( PDOStatement $stmt, $index ) {
		
		try { 
			$query_key = self::Get_unique_query_key($stmt->queryString);
			
			if ( !isset($this->_Results[$query_key]) ) {
				
				Debug::Show('Using cached result for: ' . $stmt->queryString, Debug::VERBOSITY_LEVEL_INTERNAL);
				
				$result = $this->refetch_statement( $stmt );
				
				$this->_Results[$query_key]['rows'] = $result->fetchAll();    
			}
			
			$this->_Results[$query_key]['cursor_pos'] = $index;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	//
	// Used when we have to call the same query, as in a 
	// data_seek().
	// 
	public function refetch_statement( PDOStatement $stmt ) {
		try {
			
				$sth = null;
			
				//
				// Need to re-bind original params to the query. 
				// The parameters are kept track of using a custom member
				// added to the sth object by 
				// SQLQueryBuilder::generate_sql_query();
				//
				if ( isset($stmt->bound_params) && $stmt->bound_params ) {
				
					$sth = $this->prepare( $stmt->queryString );
				
					//
					// There are bound parameters in this query
					//
					foreach( $stmt->bound_params as $cur_param) {
						$sth->bindValue($cur_param['index'], $cur_param['value'], $cur_param['bind_type'] );	
					}
				
				}
				//echo $query_string . '<br /><br />';
				
				return $this->query (
								array('query' => $stmt->queryString , 
									  'sth' => $sth
								)
							);
							
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public final function get_absolute_result_count( PDOStatement $result, $options = array() ) {
		
		try {
			
			$options['absolute_count'] = true;
			return $this->num_rows( $result, $options );
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public final function num_rows( PDOStatement $result, $options = array() ) {
		
		try { 
			
			LL::Require_class('SQL/QueryParse');
			
			$original_query = $result->queryString;
			$count_query_obj = $this->new_query_obj();
			$more_select = '';
			$limit_clause = null;
			$sth = null;
			
			
			$query_key = self::Get_unique_query_key($original_query);
			if ( Config::Get('pdo.cache_row_count') == true 
				&& isset($this->_Cached_row_counts[$query_key]) ) {
					return $this->_Cached_row_counts[$query_key];
			}
			else {
				if ( isset($this->_Explicit_num_rows[$query_key]) ) {
					return $this->_Explicit_num_rows[$query_key];
				}
				else if ( isset($this->_Count_queries[$query_key]) && $this->_Count_queries[$query_key] ) {
					$query_string = $this->_Count_queries[$query_key];	
				}
				else {
					$query_arr = QueryParse::Parse_string($original_query)->get_array();

					if ( isset($query_arr['limit']) ) {
						$limit_clause = $query_arr['limit'];
						unset($query_arr['limit']);
					}					
	        
	        		if ( isset($this->_Count_query_column[$query_key]) ) {
	        			$count_field = $this->_Count_query_column[$query_key];
	        		}
	        		else {
	        			$count_field = '*';
	        		}

	        		if ( isset($this->_Count_query_alias[$query_key]) ) {
	        			$count_alias = $this->_Count_query_alias[$query_key];
	        		}
	        		else {
	        			$count_alias = 'count';
	        		}
										
					/*
					 *  This, unfortunately, doesn't work 
					 *  because the sql spec says that duplicate column names
					 *  cannot exist in a subquery, even if those duplicate column
					 *  names are ultimately harmless
					
					$query_string = implode('', $query_arr);
					
					$count_query_string = "
							 SELECT COUNT({$count_field}) AS count
							 FROM ( {$query_string} ) as counted_query"; 
					*/
	        			        		
					
					$selections = array("SELECT COUNT({$count_field}) AS {$count_alias} ");
	        		
	        
	        		if ( isset($this->_Count_query_select[$query_key]) ) {
	        			foreach ( $this->_Count_query_select[$query_key] as $select ) {
	        				$selections[] = $select;
	        			}
	        		}
	        		
	        		$query_arr['select'] = join(',', $selections) . ' ';
	        
	        		$query_string = implode('', $query_arr);
	        		
					
					//
					// Need to re-bind original params to the query. 
					// The parameters are kept track of using a custom member
					// added to the sth object by 
					// SQLQueryBuilder::generate_sql_query();
					//
					if ( isset($result->bound_params) && $result->bound_params ) {
					
						$sth = $this->prepare( $query_string );
					
						//
						// There are bound parameters in this query
						//
						foreach( $result->bound_params as $cur_param) {
							$sth->bindValue($cur_param['index'], $cur_param['value'], $cur_param['bind_type'] );	
						}
					
					}
					
				}
				
				//echo $query_string . '<br /><br />';
				Debug::Show( $query_string, Debug::VERBOSITY_LEVEL_BASIC );
				
				$result = $this->query (
								array('query' => $query_string , 
									  'sth' => $sth
								)
							);
				
				$row = $result->fetch( PDO::FETCH_ASSOC );

				//
				// If the query has a limit, we need to return the limited # rows here
				// unless the absolute count is less than our limit, in which case we
				// return that.
				if ( !isset($options['absolute_count']) || $options['absolute_count'] == false ) {
					if ( $limit_clause ) {
						preg_match('/(LIMIT\s*)?([0-9]+)(,\s*([0-9]+))?/i', $limit_clause, $matches);
				
						if ( isset($matches[4]) ) {
							return ( $row[$count_alias] < $matches[4] ) ? $row[$count_alias] : $matches[4];
						}
						else {
							return ( $row[$count_alias] < $matches[2] ) ? $row[$count_alias] : $matches[2];
						}
					}
				}
		
				$this->_Cached_row_counts[$query_key] = $row[$count_alias];
		
				return $row[$count_alias];
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public final function fetch_assoc( PDOStatement $stmt ) {
		
		try { 
			return $this->fetch_unparsed_assoc( $stmt );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public final function fetch_unparsed_assoc( PDOStatement $stmt, $options = array() ) {
	
		try { 
			$query_key = self::Get_unique_query_key($stmt->queryString);
			
			if ( isset($this->_Results[$query_key]) && $active_result =& $this->_Results[$query_key] ) {
				
				if ( isset($active_result['cursor_pos']) && isset($active_result['rows']) ) {
					if ( $active_result['cursor_pos'] < count($active_result['rows']) ) {
						$ret = $active_result['rows'][$active_result['cursor_pos']];
						$active_result['cursor_pos']++;
						return $ret;
					}
					else {
						return null;
					}
				}
			}
			
			return $stmt->fetch( PDO::FETCH_ASSOC );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public final function query_direct( $query ) {
		try {
			return parent::query($query);
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public final function query( $query_info, $options = array() ) {
		try { 
			
			
			if ( is_array($query_info) ) {
				$query = $query_info['query'];
				$sth   = $query_info['sth'];
			}
			else {
				$query = $query_info;
				$sth   = null;
			}
			
			
			if ( Config::Get('db.auto_connect_w') && !$this->is_writable() ) {
			
				LL::Require_class('SQL/QueryParse');
				$query_parts = QueryParse::Parse_string($query)->get_array();
				
				if ( isset($query_parts[0]) && in_array(strtolower($query_parts[0]), $this->writable_statements) ) { 	
					//
					// We're executing a query that needs to write, 
					// like INSERT / UPDATE / etc
					//
					Debug::Show(  __METHOD__ . ':' . 'Found writable query part "' . $query_parts[0] . '" , connecting to write-enabled database', Debug::VERBOSITY_LEVEL_EXTENDED);
					$pdo = $this->connect_w();
				}
				else {
					$pdo = $this;
				}
					
			}
			else {
				$pdo = $this;
			}
			
			
			$query_key = self::Get_unique_query_key($query);
			
			if ( isset($this->_Results[$query_key]) ) {
				unset($this->_Results[$query_key]);
			}
	
			Debug::Show(  __METHOD__ . ':' . $query, Debug::VERBOSITY_LEVEL_BASIC);
			
			if ( $sth && $sth->bound_params ) {
				Debug::Show( __METHOD__ . ': Query Parameters: ' . print_r($sth->bound_params, true), Debug::VERBOSITY_LEVEL_BASIC);
			}
	
			if ( !$sth ) {
				
				$ret = $pdo->query_direct($query);
				
				if ( !$ret ) {
					throw new SQLQueryException( $query, $this );
				}
				
				return $ret;
			}
			else {
				
				if ( !$sth->execute() ) {
					throw new SQLQueryException( $query, $sth );
				}
				
				return $sth;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function datetime_format( $month = 0, $day = 0, $year = 0, $hour = 0, $minute = 0, $second = 0 ) {

    	$date_format = $this->date_format( $month, $day, $year );
		$time_format = $this->time_format( $hour, $minute, $second );

        return "{$date_format} {$time_format}";
        
    }

	public function time_format( $hour, $minute, $second ) {
			
    	$var_array = array( 'hour', 'minute', 'second');

        foreach( $var_array as $cur_var_name ) {

        	if ( !$$cur_var_name || !is_numeric($$cur_var_name) || (strlen(strval($$cur_var_name)) > 2) ) {
            	$$cur_var_name = '00';
            }
            else {
				$$cur_var_name = str_pad($$cur_var_name, 2, '0', STR_PAD_LEFT);

            }
        }
                
        return "{$hour}:{$minute}:{$second}";
	}

	public function date_format( $month = 0, $day = 0, $year = null ) {
                         
   		$var_array = array( 'month', 'day' );
                 
        foreach( $var_array as $cur_var_name ) {
                
        	if ( !$$cur_var_name || !is_numeric($$cur_var_name) || (strlen(strval($$cur_var_name)) > 2) ) {
            	$$cur_var_name = '00';
            }
            else {
        		$$cur_var_name = str_pad($$cur_var_name, 2, '0', STR_PAD_LEFT);
            }
       	}

		if ( !$year || !is_numeric($year) ) {
       		$year = '0000';
        }
        else {
        	if ( strlen($year) != 4 ) {
            	if ( strlen($year) == 2 ) {

                	//
                    // Assume current decade for 2 digit years.
                    //
                    
                    $cur_year = date( 'Y', time() );
                    $year = substr(strval($cur_year), 0, 2) . $year;
                }
                else {
                	$year = '0000'; //no idea what to do with a year that's not 2 or 4 digits.
                }
        	}
        }
         
                
        return "{$year}-{$month}-{$day}";
	}
	
	public function table_name_is_valid( $table_name ) {
	
		return preg_match( '/^[A-Za-z0-9_\-]+$/', $table_name );

	}	
    
    public function fetch_field( $field_name, $row_num = 0, $stmt ) {
    
    	return $this->fetch_col( $field_name, $row_num, $stmt );
    }
    
    public function fetch_col( $field_name, $row_num = 0, $stmt ) {
    
    	try {
	    	if ( $row_num != 0 ) {
	    		trigger_error( 'Row number is not supported when calling ' . __METHOD__ . ' when using PDO', E_USER_ERROR );
	    	}
	    	
	    	
	    	$row = $this->fetch_assoc($stmt);
	    	
	    	if ( isset($row[$field_name]) ) {
	    		return $row[$field_name];
	    	}
	    	else {
	    		throw new ColumnDoesNotExistException( $field_name ); 
	    	}
    	}
    	catch( Exception $e ) {
    		throw $e;	
    	}
    }
    
    public function last_insert_id( $name = null, $table_name = null ) {
    	
    	try { 
    		return $this->lastInsertId($name);
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    		
    }
    
    public function start_transaction() {
    	
    	try {
    		return $this->beginTransaction();	
    	}
    	catch( Exception $e ) {
    		throw $e;
    	} 
    	
    }
    
    public function beginTransaction() {
    	
    	try {
    		if ( !$this->transactions_track || !$this->_Transaction_active ) {
	    	
	    		$ret = parent::beginTransaction();
    			
    			if ( !$ret ) {
    				throw new DBException( __CLASS__ . '-couldnt_start_transaction' );
    			}

    			$this->_Transaction_active = true;
    			return $ret;
    		}	
    	}
    	catch( Exception $e ) {
    		throw $e;
    	} 
    	
    }

    public function commit() {
    	
    	try {
    		if ( !$this->transactions_track || $this->_Transaction_active ) {
    			$ret = parent::commit();

    			if ( !$ret ) {
    				throw new DBException( __CLASS__ . '-couldnt_commit_transaction' );
    			}

    			$this->_Transaction_active = false;
    			return $ret;
    		}	
    	}
    	catch( Exception $e ) {
    		throw $e;
    	} 
    	
    }

    public function rollback() {
    	
    	try {
    		if ( !$this->transactions_track || $this->_Transaction_active ) {
    			$ret = parent::rollback();

    			if ( !$ret ) {
    				throw new DBException( __CLASS__ . '-couldnt_rollback_transaction' );
    			}

    			$this->_Transaction_active = false;
    			return $ret;
    		}	
    	}
    	catch( Exception $e ) {
    		throw $e;
    	} 
    	
    }
    
    public function in_transaction() {
    	
    	return $this->transaction_active();
    	
    }
    
    public function transaction_active() {
    	return $this->_Transaction_active;
    }

	public function connect_r( $options = array() ) {
		
		if ( isset($options['reconnect']) && $options['reconnect'] ) {
				
			LL::Require_class('PDO/PDOFactory');
			$pdo = PDOFactory::Instantiate($this->config_name);
		
			Debug::Show('Database Connect - [READ]', Debug::VERBOSITY_LEVEL_BASIC);
						
		}
		else {
			$pdo = $this;
		}	
		
		if ( LL::Hook_exists('DB', 'after_connect_r') ) {
			LL::Call_hook('DB', 'after_connect_r', $pdo);
		}
		else {
			LL::Call_hook('DB', 'after_connect', $pdo);
		}
		
		return $pdo;
		
	}
	
	//
	// For clustering or security purposes, sometimes 
	// separate usernames for reading and writing are used. 
	// This method will return a new instantiation of this object, 
	// using the 'write' configuration for this db 
	//
	public function connect_w( $options = array() ) {
		
		try {
				
			if ( $this->is_writable ) {
				return $this;
			}
			
			if ( !$this->_Writable_obj || (isset($options['reconnect']) && $options['reconnect']) ) {
				Debug::Show('Database Connect - [WRITE]', Debug::VERBOSITY_LEVEL_BASIC);
				
				LL::Require_class('PDO/PDOFactory');
				
				$this->_Writable_obj = PDOFactory::Instantiate($this->config_name, array('for_write' => true));

				if ( LL::Hook_exists('DB', 'after_connect_w') ) {
					LL::Call_hook('DB', 'after_connect_w', $this);
				}
				else {
					LL::Call_hook('DB', 'after_connect', $this);
				}

			}
			
			return $this->_Writable_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

    public function set_count_query( $query, $count_query ) {

		$query_key = self::Get_unique_query_key($query);
		
		$this->_Count_queries[$query_key] = $count_query;
    	
    }
    
    public function count_query_select( $query, $select ) {

		$query_key = self::Get_unique_query_key($query);
		
		if ( !isset($this->_Count_query_select[$query_key]) ) {
			$this->_Count_query_select[$query_key] = array();
		}
		
		if ( !in_array($select, $this->_Count_query_select[$query_key]) ) {
			$this->_Count_query_select[$query_key][] = $select;
		}    	
    	
    }

    public function count_query_column( $query, $column ) {

		$query_key = self::Get_unique_query_key($query);
		
		$this->_Count_query_column[$query_key] = $column;
		
    }
    
    public function count_query_alias( $query, $alias ) {

		$query_key = self::Get_unique_query_key($query);
		
		$this->_Count_query_alias[$query_key] = $alias;
		    	
    	
    }
    
    public function is_writable( $yn = null ) {
    	
    	if ( $yn !== null ) {
    		$this->is_writable = $yn;
    	}
    	
    	return $this->is_writable;
    	
    }
     
    //
    // Aliases
    //
    public function list_tables( $options = array() ) {
    	
    	return $this->get_tables( $options );
    	
    }
    

}


?>