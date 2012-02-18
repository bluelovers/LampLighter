<?php

LL::Require_class('Data/DataModel');

class AppSettingType extends DataModel {

	protected $_Table_name = 'setting_types';
    protected $_Prefix_db_field_name='setting_type_';
    
    protected function _Init() {
    	
    	$this->has_many('Settings/AppSetting', array('table' => 'settings'));
    	
    	$this->has_unique_key( 'key' );
    	
    } 
}
?>