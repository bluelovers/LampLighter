<?php

LL::Require_class('Data/DataModel');

class GroupPrivilege extends DataModel {

	protected $_Prefix_db_field_name = 'group_priv_';

    public function _Init() {
    	
    	LL::Require_class('Auth/AuthLoader');
    	AuthLoader::Load_config();
    	
    	$this->belongs_to('Auth/UserGroup');
    	$this->belongs_to('Auth/UserPrivilegeType');
    }
}
?>