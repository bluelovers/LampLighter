<?php

LL::Require_class('Data/DataModel');

class GroupPreference extends DataModel {

	protected $_Prefix_db_field_name = 'group_pref_';

    public function _Init() {
    	
    	LL::Require_class('Auth/AuthLoader');
    	AuthLoader::Load_config();
    	
    	$this->belongs_to('Auth/UserGroup');
    	$this->belongs_to(Config::Get('auth.user_preference_type_class'));
    	
    	$this->add_table_reference_alias('type', 'user_preference_types');
    }
}
?>