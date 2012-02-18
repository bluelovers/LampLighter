<?php

class SQLOptions {

	const KEY_CLONE	 = 'clone';
	const KEY_AUTO_CREATE = 'auto_create';
	const KEY_QUERY_OBJ = 'query_obj';
	
    public static function Query_obj_from_option_hash( &$options, $my_options = null, $old_auto_create = true ) {
    	
    	$query_obj = null;

		if ( is_array($my_options) ) {
			$clone 		 = ( isset($my_options[self::KEY_CLONE]) ) ? $my_options[self::KEY_CLONE] : true;
			$auto_create = ( isset($my_options[self::KEY_AUTO_CREATE]) ) ? $my_options[self::KEY_AUTO_CREATE] : true;
		}
		else {
			$clone 		 = ( $my_options === null ) ? true : $my_options;
			$auto_create = $old_auto_create;
			
		}
		if ( !isset($options[self::KEY_QUERY_OBJ]) || !$options[self::KEY_QUERY_OBJ] ) {
			if ( $auto_create ) {
				if ( isset($options['db_obj']) && $options['db_obj'] ) {
					$db = $options['db_obj'];
					$query_obj = $db->new_query_obj();
				}
				else {
					$query_obj = new SQLQuery();	
				}
			}
		}
		else {
			if ( $clone ) {
				$query_obj = $options[self::KEY_QUERY_OBJ]->clone_query_obj();
			}
			else {
				$query_obj = $options[self::KEY_QUERY_OBJ];
			}
		}

		$query_obj->apply_query_setup_hash($options);

		return $query_obj;
    	
    }
}
?>