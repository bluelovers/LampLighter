<?php

LL::Require_class('Data/DataModel');

class AppSettingContextType extends DataModel {

    protected $_Table_name='setting_context_types';
    protected $_Prefix_db_field_name='setting_context_type_';
    
    public function _Init() {
    	
    	$this->has_many('Settings/AppSetting', array('table' => 'settings'));
    	
    	$this->has_unique_key( 'key' );
    	
    }
}
?>