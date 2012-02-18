<?php

LL::Require_class('Data/DataModel');

class AppSetting extends DataModel {

    protected $_Table_name = 'settings';
    protected $_Prefix_db_field_name = 'setting_';
    
    protected function _Init() {
    	
    	$this->belongs_to('Settings/AppSettingType', array('table' => 'setting_types'));
    	$this->belongs_to('Settings/AppSettingContextType', array('table' => 'setting_context_types'));
    	
    	
    }
    
   	
    
}
?>