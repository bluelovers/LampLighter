<?php

LL::Require_class('Data/DataModel');

class UserPrivilegeType extends DataModel {

	protected $_Prefix_db_field_name='priv_type_';

    protected function _Init() {
    	    	
    	LL::Require_class('Auth/AuthLoader');
    	AuthLoader::Load_config();
    	
    	$this->has_many('Auth/UserPrivilege');
    	$this->has_many('Auth/GroupPrivilege');
    	
    	$this->has_unique_key('key');
    }
}
?>