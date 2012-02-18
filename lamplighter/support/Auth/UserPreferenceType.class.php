<?php

LL::Require_class('Data/DataModel');

class UserPreferenceType extends DataModel {

	protected $_Prefix_db_field_name='pref_type_';

    protected function _Init() {
    	
		$this->has_many(Config::Get('auth.user_preference_class'), array('table' => Config::Get('auth.user_preference_table')));
    	$this->has_many(Config::Get('auth.user_preference_value_class'), array('table' => Config::Get('auth.user_preference_value_table')));
    	
    	$this->has_many('Auth/GroupPreference');
    	
    	$this->has_unique_key('key');
    }
    
    public function get_potential_values( $options = array() ) {
    	
    	try {
    		
    		if ( !$this->is_uniquely_identified() ) {
    			throw new InvalidParameterException( 'preference type id' );
    		}
    		
    		LL::Require_model(Config::Get_required('auth.user_preference_value_class'));
    		
    		$value_class_name = LL::class_name_from_location_reference(Config::Get('auth.user_preference_value_class'));
			$value_model = new $value_class_name;
			
			$query_obj = $this->query_obj_from_option_hash($options);
			$query_obj->where( "{$value_model->table_name}.{$this->db_field_name_id}=?", $this->id );

			$options['query_obj'] = $query_obj;
			
			return $value_model->fetch_all( $options );					
    		
    	}
    	catch( Exception $e ){
    		throw $e;
    	}
    	
    }
}
?>