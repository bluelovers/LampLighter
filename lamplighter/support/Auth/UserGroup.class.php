<?php

LL::Require_class('Data/DataModel');

class UserGroup extends DataModel {
	
	protected $_Prefix_db_field_name = 'group_';

    protected function _Init() {
    	

    	LL::Require_class('Auth/AuthLoader');
    	AuthLoader::Load_config();
    	
    	$user_class = Config::Get('auth.user_class');
    	
    	$this->has_many($user_class, array 
    								( 'through' => 'user_group_link', 
    								  'table' => Config::Get('auth.users_table') 
    								  
    								 )
    								
    								);
    	
    	$this->has_many('Auth/GroupPrivilege');
    	$this->has_many('Auth/GroupPreference');
    	
    	$this->has_unique_key('name');
    }
    
}
?>