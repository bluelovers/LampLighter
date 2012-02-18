<?php

LL::Require_class('SQL/SQLQueryBuilder');

class SQLQueryBuilder_mysql extends SQLQueryBuilder {

	//
	// Public 
	//
	var $force_mysql_ident_quotes = false;

	//
	// Private
	//
	var $_Ident_quote_char = '`';


	public function __construct( $parent_db_obj = null ) {

		parent::__construct( $parent_db_obj );

	}

	function auto_insert( $table_name ) {

		try { 
			
			
			LL::Require_class('SQL/QueryConstants');
			
			$options['table'] = $table_name;
			$options['type']  = QueryConstants::QUERY_INSERT;
			$options['data']  = $this->_Auto_insert_data;

			return $this->auto_query($table_name, $options);
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function auto_update( $table_name, $where_clause = null ) {

		try { 
			
			LL::Require_class('SQL/QueryConstants');
			
			$options['table'] = $table_name;
			$options['type']  = QueryConstants::QUERY_UPDATE;
			$options['data']  = $this->_Auto_update_data;
			$options['where'] = $where_clause;

			return $this->auto_query($table_name, $options);
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function auto_query( $table_name, $options ) {

		try { 

			LL::Require_class('SQL/QueryConstants');

			$active_query_type = ( isset($options['type']) ) ? $options['type'] : null;
			$operation_name = ( $active_query_type == QueryConstants::QUERY_UPDATE ) ? 'update' : 'insert';
			
			$field_list 	 = '';
			$value_list 	 = '';
			$sql_query       = null;

			if ( !isset($options['table']) || !$options['table'] ) {
				throw new MissingParameterException( 'Table name not set for auto query' );
			}

			$data_array = $options['data'];

			if ( !is_array($data_array) || (count($data_array) <= 0) ) {
				throw new MissingParameterException( __CLASS__ . "-no_data_array_given %{$operation_name}% %{$options['table']}%" );
			}
			else {

				if ( $active_query_type == QueryConstants::QUERY_UPDATE ) {

					$ret = $this->_Prepare_auto_update_query( $options );
					$this->auto_update_query = $ret['query'];
					$sql_query = $ret['query'];
					$sth = $ret['statement'];

				}
				else if ( $active_query_type == QueryConstants::QUERY_INSERT ) {
					
					$ret = $this->_Prepare_auto_insert_query( $options );
					$this->auto_insert_query = $ret['query'];
					$sql_query = $ret['query'];
					$sth = $ret['statement'];

				}
				else {
					throw new SQLQueryException( 'invalid_auto_query_type' );
				}

				if ( !$sth ) {
					throw new SQLQueryException( 'no_auto_query_generated' );
				}
				else {

					Debug::Show( __METHOD__ . "[AUTO QUERY] {$sql_query}", Debug::VERBOSITY_LEVEL_BASIC );
                    Debug::Show( __METHOD__ . " [QUERY DATA] " . print_r($data_array, true), Debug::VERBOSITY_LEVEL_BASIC );
						
					if ( !$query_result = $sth->execute() ) {
						$error_msg =  __CLASS__ . '-couldnt_prepare: ' . $ret['query'];
						
						if ( $error_info = $sth->errorInfo() ) {
						 	if ( isset($error_info[2]) ) {
						 		$error_msg .= ' : ' . $error_info[2];
						 	}
						 
							throw new SQLQueryException( $error_msg );
						}
					}
					
					if ( $active_query_type == QueryConstants::QUERY_INSERT ) {

						$last_insert_id = $this->_Parent_db_obj->last_insert_id();
						$retval = ( $last_insert_id ) ? $last_insert_id : true;

						return $retval;
					}
					else {
						return true;
					}
					
				}
			}

			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	private function _Prepare_auto_insert_query( $options = array() ) {

		try {
			LL::Require_class('PDO/PDOStatementHelper');
	
			$ident_quote    = ( $this->ident_quotes_on_auto_insert ) ? $this->_Ident_quote_char : null;
	
			$sql_query  = null;
			$data_array = $options['data'];
			$value_list = '';
			$field_list = '';
			$params = array();
			
			if ( is_array($data_array) ) {
			
				$sql_query = "INSERT INTO {$ident_quote}{$options['table']}{$ident_quote} ";
				$param_index = 1;
	
				foreach( $data_array as $field_name => $field_info ) {
					
					$bind_type = PDOStatementHelper::PDO_Bind_type_by_field_info($field_info);
					
					//echo " binding {$field_name} as {$bind_type} with value: {$field_info['value']}<br />\n\n";
					
					$field_list .= "{$ident_quote}$field_name{$ident_quote}, ";
	
					
					if ( $bind_type ) {
						$params[] = array('index' => $param_index, 'value' => $field_info['value'], 'bind_type' => $bind_type);
						$value_list .= '?, ' ;
						$param_index++;
					}
					else {
						if ( "{$field_info['value']}" == '' ) {
							$value_list .= 'NULL, ';
						}
						else {
							$value_list .= $field_info['value'] . ', ';
						}
					}
					
				}
	
				//
				// Remove trailing comma + space
				//
				$value_list = rtrim( $value_list, ', ' );
				$field_list = rtrim( $field_list, ', ' );
	
				$sql_query .= "( $field_list ) VALUES ( $value_list )";
				
			}
	
			if ( $this->_Auto_clear_insert_data ) {
				$this->_Auto_insert_data = array();
			}
	
			$pdo = $this->_Parent_db_obj->connect_w();
			$sth = $pdo->prepare( $sql_query );

			foreach( $params as $index => $cur_param) {
				$sth->bindValue($cur_param['index'], $cur_param['value'], $cur_param['bind_type'] );	
			}
			
			return array( 'statement' => $sth, 'query' => $sql_query, 'pdo' => $pdo );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	private function _Prepare_auto_update_query( $options = array() ) {
	
		try { 
		
			LL::Require_class('PDO/PDOStatementHelper');
			LL::Require_class('SQL/QueryParse');
			LL::Require_class('SQL/QueryConstants');
			
			$sql_query    = null;
			$ident_quote  = ( $this->ident_quotes_on_auto_update ) ? $this->_Ident_quote_char : null;
			$where_clause = ( isset($options['where']) ) ? $options['where'] : null;

			if ( !$where_clause AND $this->auto_update_requires_where ) {
				throw new Exception( 'auto_update called without where statement. Add where statement or unset auto_update_requires_where.' );
			}

			if ( is_array($where_clause) ) {
				$where_clause = join(' AND ', $where_clause);
			}

			$data_array = $options['data'];
			$params = array();
			$param_index = 1;
			
			if ( is_array($data_array) ) {

				$sql_query = "UPDATE {$ident_quote}{$options['table']}{$ident_quote} SET ";

				foreach( $data_array as $field_name => $field_info ) {

					$bind_type = PDOStatementHelper::PDO_Bind_type_by_field_info($field_info);
					
					$sql_query .= "{$ident_quote}{$field_name}{$ident_quote}=";

					if ( $bind_type ) {
						$params[] = array('index' => $param_index, 'value' => $field_info['value'], 'bind_type' => $bind_type);
						$sql_query .= '?, ' ;
						$param_index++;
					}
					else {
						if ( "{$field_info['value']}" == '' ) {
							$sql_query .= 'NULL, ';
						}
						else {
							$sql_query .= $field_info['value'] . ', ';
						}
						
					}
				}

				$sql_query = rtrim( $sql_query, ', ' );

				$where_clause = QueryParse::Strip_sql_clause($where_clause, QueryConstants::CLAUSE_WHERE);
				$sql_query .= ( $where_clause ) ? " WHERE {$where_clause}" : '';

			}

			
			if (  $this->_Auto_clear_update_data ) {
				$this->_Auto_update_data = array();
			}
	
			$pdo = $this->_Parent_db_obj->connect_w();
	
			$sth = $pdo->prepare( $sql_query );
			//$sth->prepare( $sql_query );

			foreach( $params as $cur_param) {
				$sth->bindValue($cur_param['index'], $cur_param['value'], $cur_param['bind_type'] );	
			}


			return array( 'statement' => $sth, 'query' => $sql_query, 'pdo' => $pdo );
		}
		catch ( Exception $e ) {
			throw $e;
		}
	}



} //end query class


?>