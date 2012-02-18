<?php

LL::Require_class('Data/DataModel');

class UserPrivilege extends DataModel {

	protected $_Prefix_db_field_name = 'user_priv_';

    public function _Init() {
    	
    	LL::Require_class('Auth/AuthLoader');
    	AuthLoader::Load_config();
    	
    	$this->belongs_to(
    					Config::Get_required('auth.user_class'), 
    						array('table' => Config::Get_required('auth.users_table'))
    					);
    					
    	$this->belongs_to('Auth/UserPrivilegeType');
    	
    	$this->belongs_to('Auth/UserPrivContextType');
    	
    	$this->add_table_reference_alias( 'types', 'user_priv_types' );
    }
}
?>