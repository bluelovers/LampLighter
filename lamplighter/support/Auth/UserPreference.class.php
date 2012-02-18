<?php

LL::Require_class('Data/DataModel');

class UserPreference extends DataModel {

	protected $_Prefix_db_field_name = 'user_pref_';

    public function _Init() {
    	
    	LL::Require_class('Auth/AuthLoader');
    	AuthLoader::Load_config();
    	
    	$this->belongs_to(Config::Get_required('auth.user_class'), 
    							array('table' => Config::Get_required('auth.users_table'))
    						
    					);
    	
    	$pref_type_table = Config::Get('auth.user_preference_type_table') ? Config::Get('auth.user_preference_type_table') : 'user_preference_types';
    	
    	$this->belongs_to(Config::Get('auth.user_preference_type_class'), array('table' => $pref_type_table));
    	
    	//$this->add_table_reference_alias('type', 'user_preference_types');
    }
    
    
}
?>