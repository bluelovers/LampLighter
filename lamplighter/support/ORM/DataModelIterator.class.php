<?php

LL::require_class('ORM/ObjectIterator');

class DataModelIterator extends ObjectIterator {

	protected $_DB_resultset;
	protected $_DB_interface;
	protected $_Previous_results = array();
	
	public $key_field = 'id';
	
	protected $_Key_array = array();
	
	public function set_key_array( $keys ) {
		
		$this->_Key_array = $keys;
		$this->set_count( count($keys) );
		
	}
	
	function get_db_interface() {
		try {
			if ( !$this->_DB_interface ) { 	
				$this->load_iterating_class();
				$class_name = $this->get_iterating_class_name();
				$model 		= new $class_name;
				
				$this->_DB_interface = $model->get_db_interface();
			}
			
			return $this->_DB_interface;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	
	function set_db_interface( $obj ) {
		$this->_DB_interface = $obj;
	}
	
	function set_db_resultset( $result ) {
		
		$this->_DB_resultset = $result;
		
	}
	
	public function get_count() {
	
		try { 
			
			$db = $this->get_db_interface();
			if ( $result = $this->get_db_resultset() ) {
				return $db->num_rows($result);
			}
			
			return null;
		
		}
		catch( Exception $e)  {
			throw $e;
		}
		
	}
	
	function &get_db_resultset() {
		return $this->_DB_resultset;
	}
	
		
	function next() {

		try { 		
			
			$active_index = $this->get_active_index();
		
			$result = $this->get_db_resultset();
				
			if ( $active_index === null  ) { 
				if ( isset($this->_Previous_results[$result->queryString]) ) {
					$this->query_refresh();
					$result = $this->get_db_resultset();
				}
				else {
					//
					// Mark down that we already saw this query
					//
					$this->_Previous_results[$result->queryString] = array();
				}
			}
		
			if ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
				if ( $active_index === null ) {
					$this->set_active_index(0);
				}
				else {
					$this->set_active_index( $active_index + 1);
				}
				
				return $this->instantiate_row($row);
			}
			else {
				if ( $active_index === null ) {
					//
					// This query had no rows from the start,
					// so cache the number of rows so we  
					// don't execute it again
					$this->_Previous_results[$result->queryString]['rows'] = 0;
				}
				else {
					$this->set_active_index(null);
					return null;
				}
			}
		
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	public function query_refresh() {
		
		try {
			
			//
			// Because PDO doesn't buffer queries, refetch 
			// the same query.  
			// 
			
			$db = $this->get_db_interface(); 
			//$result = $this->get_db_resultset();
			$result = $db->refetch_statement ( $this->get_db_resultset() );
			
			$this->set_db_resultset($result); 

		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}
	
	function previous() {

		$active_index = $this->get_active_index();
		
		if ( $active_index > 0 ) {
			return $this->seek($active_index-1);
		}

		return null;
	}
  	
	function end() {

		if ( $result = $this->get_db_resultset() ) {
			$db = $this->get_db_interface();
				
			$num_rows = $db->num_rows($result);
			return $this->seek($num_rows-1);
				
		}
		else {
			return $this->seek(count($this->_Key_array) -1);
		}
	}
	
	function seek( $pos ) {
		
		try { 

			$active_index = $this->get_active_index();
			
			$result = $this->get_db_resultset();
				
			if ( isset($this->_Previous_results[$result->queryString]) ) {
				$this->query_refresh();
			}
			else {
				$this->_Previous_results[$result->queryString] = array();
			}
			
			if ( $active_index === null ) {
				$pos = 0;
			}
			
			if ( $pos != ($active_index + 1) ) {
				
				$db = $this->get_db_interface();
				$db->data_seek($result, $pos);
			}
				
			$this->set_active_index($pos);

			if ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
				return $this->instantiate_row($row);
			}
			else {
				$this->set_active_index(null);
				return null;
			}

		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function instantiate_row( $row ) {
		
		try {
			$this->load_iterating_class();			
			
			if ( !$this->_Active_object || $this->force_instantiate_on_each ) {
		
				$class_name = $this->get_iterating_class_name();
				$model 		= new $class_name;
			
			}
			else {
				
				$model = $this->_Active_object;
				$model->reset_record_data();
				$model->reset_related_models();
			}

			$model->set_record_row($row);
			$this->set_active_object($model);

			return $model;
		
		}
		catch( Exception $e ) {
			throw $e;
		}

		
	}
	
	/*
	function instantiate_active_row() {
		
		try {
			$db = $this->get_db_interface();
			
				if ( $this->load_iterating_class() ) {			
				
					if ( !$this->_Active_object || $this->force_instantiate_on_each ) {
				
						$class_name = $this->get_iterating_class_name();
						$model 		= new $class_name;
					
					}
					else {
						
						$model = $this->_Active_object;
						$model->reset_record_data();
						$model->reset_related_models();
					}

					if ( $result = $this->get_db_resultset() ) {
			
						//$active_row = $db->fetch_unparsed_assoc($result);
						$active_row = $result->fetch(PDO::FETCH_ASSOC);
						$id_field = $model->db_field_name('id');
						$model->set_record_row($active_row);

					}
					else if ( count($this->_Key_array) > 0 ) {
						if ( isset($this->_Key_array[$this->index]) ) {
							$key_field = $this->key_field;
							$model->$key_field = $this->_Key_array[$this->index];
						}
					}
					else {
						throw new Exception( 'No valid data array passed to iterator');
					}
					//if ( isset($active_row[$id_field]) ) {
					//	$model->set_id( $active_row[$id_field] );
					//}
					
					$this->set_active_object($model);
		
					return $model;
				}
			
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}

		
	}
	*/
	
	function set_model( $model ) {
		return $this->set_active_object($model);
	}
	
}

?>
