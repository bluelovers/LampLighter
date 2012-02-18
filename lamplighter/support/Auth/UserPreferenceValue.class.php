<?php

LL::Require_class('Data/DataModel');

class UserPreferenceValue extends DataModel {

	public $field_name_prefix = 'pref_value_';

    public function _Init() {
    	
    	$pref_type_table = Config::Get('auth.user_preference_type_table') ? Config::Get('auth.user_preference_type_table') : 'user_preference_types';
    	
    	$this->belongs_to(Config::Get('auth.user_preference_type_class'), array('table' => $pref_type_table));
    	
    }
    
    
}
?>