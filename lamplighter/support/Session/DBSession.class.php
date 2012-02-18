<?php

LL::Require_class('Data/DataModel');

class DBSession extends DataModel {

	protected $_Table_name = 'sessions';
	
	protected $_Allow_explicit_id  = true;
	protected $_Require_numeric_id = false;
	protected $_Prefix_db_field_name = 'session_';
	
    protected function _Init() {
    	
    	$this->has_many('Session/DBSessionEntry', array('table' => 'session_entries') );
    	
    }
    
    public function add() {
    	
    	try {
    		
    		$this->id = $this->generate_id();
    		
    		if ( isset($_SERVER['REMOTE_ADDR']) ) {
    			$this->ip_address = $_SERVER['REMOTE_ADDR'];
    		}
    		
    		echo "id is: {$this->id}<br />";
    		
    		return parent::add();
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    }
    
    public function generate_id() {
    	
    	return md5(uniqid(rand(), true));
    	
    }
}
?>