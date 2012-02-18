<?php

class SettingsManager {

	const KEY_SETTING_CONTEXT_TYPE_KEY  = 'context_type_key';
	const KEY_SETTING_CONTEXT_VALUE 	= 'context_value';
	const KEY_AUTO_CREATE_SETTING_TYPE	= 'auto_create_setting_type';
	const KEY_IGNORE_DEFAULT_VALUE		= 'ignore_default';
	
	static $Setting_obj;

	public static function Get_fresh_setting_obj( $options = null ) {
		
		$options['reset'] = true;
		
		return self::get_setting_obj($options);
	}

	public static function Get_setting_obj( $options = null ) {
		
		try {
			
			if ( !self::$Setting_obj ) {
				
				LL::require_model('Settings/AppSetting');
				
				self::$Setting_obj = new AppSetting();
			}
			
			
			if ( array_val_is_nonzero($options, 'reset') ) {
				self::$Setting_obj->reset_record_data();
			}

			return self::$Setting_obj;			
		}
	    catch( Exception $e ) {
    		throw $e;
    	}	
	}

	public static function Get_setting_resultset( $type_key, $options = null ) {
		
		try {
			
			if ( !$type_key ) {
				throw new Exception( 'general-missing_parameter %$type_key%');
			}
			
			$setting_obj = self::Get_fresh_setting_obj();
    		
    		$setting_type = $setting_obj->get_related_model('Settings/AppSettingType');
    		
    		$query_obj = $setting_obj->query_obj_from_option_hash($options);
			$type_key  = $setting_obj->db->parse_if_unsafe($type_key);

			$context_type_key = ( isset($options[self::KEY_SETTING_CONTEXT_TYPE_KEY]) ) ? $options[self::KEY_SETTING_CONTEXT_TYPE_KEY] : null;
			$context_value 	  = ( isset($options[self::KEY_SETTING_CONTEXT_VALUE]) ) ? $options[self::KEY_SETTING_CONTEXT_VALUE] : null;

			

			$query_obj = self::Apply_setting_context_to_query_obj( $query_obj, $context_type_key, $context_value );
			$query_obj->where( "{$setting_type->table_name}.{$setting_obj->db_field_name_type_key}='{$type_key}'");
    		
    		$options['query_obj'] = $query_obj;
    		$options['return']  = 'resultset';
   
    		$options['include'] = 'settings';
       		
    		$result = $setting_type->fetch_all( $options );
    		
    		return $result;
    		
		}
    	catch( Exception $e ) {
    		throw $e;
    	}
	}

    public static function Get_setting( $type_key, $options = null ) {
    	
    	try {
    		$result = self::Get_setting_resultset($type_key, $options);
    		
    		$setting_obj  = self::Get_setting_obj();
  	  		$setting_type = $setting_obj->get_related_model('Settings/AppSettingType');
  
   			$row = $setting_obj->db->fetch_unparsed_assoc($result);
   			
   			
   			if ( isset($row[$setting_obj->db_field_name_value]) && $row[$setting_obj->db_field_name_value]) {
   				return $row[$setting_obj->db_field_name_value];
   			}
   			
   			
   			if ( !array_val_is_nonzero($options, self::KEY_IGNORE_DEFAULT_VALUE) ) {
   				return self::Get_default_setting( $type_key, $options );
   			}
   			
   			return null;
   				
   			//}
  			/*
    		while ( $row = $setting_obj->db->fetch_unparsed_assoc($result) ) {
    			
    			if ( $row[$setting_obj->db_field_name_value] ) {
    				return $row[$setting_obj->db_field_name_value];
    			}
    			else if ( $row[$setting_type->db_field_name_default_value] ) {
    				return $row[$setting_type->db_field_name_default_value];
    			}
    			
    		}
    		
    		return null;
    		
    		*/
    		
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    }

    public static function Get_default_setting( $type_key, $options = null ) {
    	
    	try {
    		
    		$setting_obj  = self::Get_setting_obj();
  	  		
  	  		$setting_type = $setting_obj->setting_types;
  			$setting_type->key = $type_key;
  			
  			if ( $setting_type->default_value ) {
  				return $setting_type->default_value;
  			}
  			
    		return null;
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    }
    
    public static function Apply_setting_context_to_query_obj( $query_obj, $context_type_key = null, $context_value = null, $options = null ) {
    	
    	try {

			$setting_obj  = self::Get_setting_obj();
			$context_type = $setting_obj->get_related_model('Settings/AppSettingContextType');
    		
    		if ( $context_type_key !== null ) {
    			$context_type->key = $context_type_key;
    			if ( !($context_type->id) ) {
    				throw new Exception( __CLASS__ . '-no_id_for_context_type_key', "\$context_type_key: {$context_type_key}" );
    			}
    			
    			$query_obj->where( "{$setting_obj->table_name}.{$context_type->db_field_name_id}={$context_type->id}"); 
    		}
    		else {
    			$query_obj->where( "{$setting_obj->table_name}.{$context_type->db_field_name_id} IS NULL");
    		}
    		
    		if ( $context_value !== null ) {
    			
    			$context_value = $setting_obj->db->parse_if_unsafe($context_value);
    			$query_obj->where( "{$setting_obj->table_name}.{$setting_obj->db_field_name_context_value}='{$context_value}'"); 
    		}
    		else {
    			$query_obj->where( "{$setting_obj->table_name}.{$setting_obj->db_field_name_context_value} IS NULL");
    		}
    	
    		return $query_obj;	
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    }

	public static function Update_setting( $type_key, $val, $options = null ) {
	
		try {
	
			$setting_obj = self::Get_fresh_setting_obj();
			$result 	 = self::Get_setting_resultset($type_key, $options);
			$query_obj   = $setting_obj->db->new_query_obj();
			$update	     = false;
			$setting_id  = null;
			
			$context_type_key = ( isset($options[self::KEY_SETTING_CONTEXT_TYPE_KEY]) ) ? $options[self::KEY_SETTING_CONTEXT_TYPE_KEY] : null;
			$context_value 	  = ( isset($options[self::KEY_SETTING_CONTEXT_VALUE]) ) ? $options[self::KEY_SETTING_CONTEXT_VALUE] : null;
			
			if ( $setting_obj->db->num_rows($result) > 0  ) {
				
				$row = $setting_obj->db->fetch_unparsed_assoc($result);

				$setting_id = $row[$setting_obj->db_field_name_setting_id];

				if ( $setting_id ) {
					$update = true;
				}
			}
			
			if ( $update ) {				
				
				if ( !$setting_id || !is_numeric($setting_id) ) {
					throw new NonNumericValueException( "\$setting_id: {$setting_id}");
				}
				
				$setting_obj->id = $setting_id;
				$setting_obj->value = $val;
				$setting_obj->context_type_key = $context_type_key;
				$setting_obj->context_value    = $context_value;
				
				return $setting_obj->save();
				
				//$query_obj->add_update_data( $setting_obj->db_field_name_setting_value, $val );
				//$query_obj->add_update_data( $setting_obj->db_field_name_context_type_key, $context_type_key );
				//$query_obj->add_update_data( $setting_obj->db_field_name_context_value, $context_val );
				
				//if ( !$query_obj->auto_update("WHERE {$setting_obj->table_name}.{$setting_obj->db_field_name_id}={$setting_id}") ) {
				//	throw new SQLQueryException( $query_obj->last_auto_update_query() );
				//}
				
				//return true;

			}
			else {
				
				LL::require_model('Settings/AppSettingType');
				
				$setting_type = new AppSettingType();
				$setting_type->key = $type_key;
				
				if ( !$setting_type->id ) {
					if ( array_val_is_nonzero($options, self::KEY_AUTO_CREATE_SETTING_TYPE) ) {
						$setting_obj->type_id = $setting_type->save();
					}
					else {
						throw new Exception( __CLASS__ . '-invalid_setting_type', "\$type_key: {$type_key}");
					}
				}
				else {
					$setting_obj->type_id = $setting_type->id;
				}
				
				$setting_obj->value = $val;
				$setting_obj->context_type_key = $context_type_key;
				$setting_obj->context_value    = $context_value;
				
				return $setting_obj->save();
				
			}
			    
		}
		catch( Exception $e ) {
    		throw $e;
    	}
    
		
	}
   
}
?>