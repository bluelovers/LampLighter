<?php

LL::Require_file('Auth/Auth.conf.php');
LL::Require_class('Auth/AuthConstants');

class UserPrivQueryHelper {
	
	const KEY_JOIN = 'join';
	const KEY_WHERE = 'where';
	const KEY_GROUP_BY = 'group_by';
	
	const KEY_CONTEXT = 'context';
	const KEY_CONTEXT_GROUP = 'group';
	const KEY_CONTEXT_USER = 'user';
	const KEY_CONTEXT_USER_RESTRICTION = 'user_restriction';

	const KEY_CONTEXT_TYPE_KEY = 'context_type_key';
	const KEY_CONTEXT_VAL	   = 'context_val';
	const KEY_PRIV_TYPE_KEY	   = 'priv_type_key';
	const KEY_PRIV_VAL	   	   = 'priv_val';
	
	const KEY_PRIV_TYPE_FIELD_REF = 'priv_type_field_ref';
	const KEY_PRIV_VAL_FIELD_REF  = 'priv_val_field_ref';
	
	static $Cached_group_count = array();
	

	public static function Priv_context_where_clauses_by_options( $given_options ) {

		try {
			
			if ( isset($options[self::KEY_CONTEXT_TYPE_KEY]) ) {
				
			}
			
		}	
		catch( Exception $e ) {
			throw $e;
		}	
		
	}



	public static function Priv_context_where_clause_blank( DataModel $priv_model, $options = null ) {

		try {
			
			static $priv_context_type;
			static $type_id_field;
			
			$where_clause = '';
			$table 	      = $priv_model->table_name;
			$val_field    = $priv_model->db_field_name_context_val;
			
			if ( !$priv_context_type ) {
				LL::Require_class('Auth/UserPrivContextType');
				
				$priv_context_type = new UserPrivContextType();
				$type_id_field	   = $priv_context_type->db_field_name_id;
			}

			if ( !$table || !$val_field || !$type_id_field ) {
				throw new Exception( __CLASS__ . '-missing_required_context_table_or_field', "\$priv_model: " . get_class($priv_model) );
			}

			$where_clause = " {$table}.{$type_id_field} IS NULL AND {$table}.{$val_field} IS NULL ";
	
			return $where_clause;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Priv_context_where_clause_by_key( $type_key, $context_val, DataModel $priv_model, $options = null ) {

		try {
			
			static $priv_context_type;
			static $type_id_field;
			
			$where_clause = '';
			$table 	      = $priv_model->table_name;
			$val_field    = $priv_model->db_field_name_context_val;
			
			if ( !$priv_context_type ) {
				LL::Require_class('Auth/UserPrivContextType');
				
				$priv_context_type = new UserPrivContextType();
				$type_id_field	   = $priv_context_type->db_field_name_id;
			}

			$priv_context_type->key = $type_key;

			if ( !$priv_context_type->id ) {
				throw new Exception( __CLASS__ . '-invalid_priv_context_type', "\$type_key: {$type_key}" );
			}

			if ( !$table || !$val_field || !$type_id_field ) {
				throw new Exception( __CLASS__ . '-missing_required_context_table_or_field', "\$priv_model: " . get_class($priv_model) );
			}

			$where_clause = " {$table}.{$type_id_field} IS NULL ";
			
			if ( $context_val ) {
				
				$context_val = $priv_model->db->parse_if_unsafe($context_val);
				
			 	$where_clause .= " AND {$table}.{$val_field} = '{$context_val}' ";
	
			}
			else {
				$where_clause .= " AND {$table}.{$val_field} IS NULL ";
			}
	
			return $where_clause;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public static function User_priv_sql_clauses( $user, $options = null) {
        
		try { 
			
			LL::Require_class('Auth/AuthValidator');

			LL::Require_model('Auth/UserPrivilege');
			LL::Require_model('Auth/GroupPrivilege');
			LL::Require_model('Auth/UserRestriction');
			LL::Require_model('Auth/UserPrivilegeType');

			$group_priv 	  = new GroupPrivilege();
			$user_priv  	  = new UserPrivilege();
			$user_restriction = new UserRestriction();
			$priv_type		  = new UserPrivilegeType();
			
			$select_clause = '';
			$join_clause  = '';
			$where_clause = '';
			$group_par	  = '';
			$user_par	  = '';
			$priv_val	  = null;
			$priv_val_is_set = isset($options[self::KEY_PRIV_VAL]);
			$group_context_where_clause = NULL;
			$user_context_where_clause  = NULL;
			$user_restr_context_where_clause = NULL;
			
			$priv_key_is_field_ref = false;
			$priv_val_is_field_ref = false;

			if ( !AuthValidator::User_object_is_valid($user) || !$user->id || !is_numeric($user->id) ) {
				throw new Exception( __CLASS__ . '-Invalid_user_object_or_missing_uid' );
			}

			$db = $user->get_db_interface();
			
			if ( !self::User_has_any_group($user) ) {
				throw new Exception( __CLASS__ . '-no_groups_associated_with_user' );
			}

			//
			// Validate our privilege type key, if we were given one
			//
			if ( array_val_is_nonzero($options, self::KEY_PRIV_TYPE_KEY) ) {

				$priv_type_key = $options[self::KEY_PRIV_TYPE_KEY];

				if ( !AuthValidator::Priv_type_key_has_valid_format($priv_type_key) ) {
					throw new Exception( __CLASS__ . '-invalid_priv_type_key', "\$priv_type_key: {$priv_type_key}");
				}
			}
			else {
		
				//
				// Instead of a key literal, we may have been passed
				// a field reference to another table
				// (e.g. other_table.other_field ).
				// if so, this becomes our "privilege type key" 
				//

				if ( array_val_is_nonzero($options, self::KEY_PRIV_TYPE_FIELD_REF) ) {
					
					$priv_key_is_field_ref = false;
					$priv_type_key = $options[self::KEY_PRIV_TYPE_FIELD_REF];	
				}
				else {
						throw new Exception( __CLASS__ . '-missing_priv_type_key_or_table_reference');
				}
			}

			//
			// If our priv value is also referencing another table, 
			// make sure we know that
			//	
			if ( array_val_is_nonzero($options, self::KEY_PRIV_VAL_FIELD_REF) ) {
				
				$priv_val_is_field_ref = true;
				$priv_val = self::KEY_PRIV_VAL_FIELD_REF;
			}
			else {
				
				if ( array_val_is_nonzero($options, self::KEY_PRIV_VAL) ) {
					$priv_val = $options[self::KEY_PRIV_VAL];
					$priv_val = $db->parse_if_unsafe($priv_val);
				}
				
			}
			
			//------------------------------
			// None of this is necessary
			// for an administrator
			//------------------------------
        	if ( $user->is_administrator() !== true ) {

				$context_options = array();

				if ( isset($options[self::KEY_CONTEXT_TYPE_KEY]) && $options[self::KEY_CONTEXT_TYPE_KEY] ) {
					
					$context_type_key = $options[self::KEY_CONTEXT_TYPE_KEY];
					$context_val	  = ( isset($options[self::KEY_CONTEXT_VAL]) ) ? $options[self::KEY_CONTEXT_VAL] : null;
		
					$group_context_where_clause = self::Priv_context_where_clause_by_key($context_type_key, $context_val, $group_priv, $context_options);

					$user_context_where_clause  = self::Priv_context_where_clause_by_key($context_type_key, $context_val, $user_priv, $context_options);
				
					$user_restr_context_where_clause = self::Priv_context_where_clause_by_key($context_type_key, $context_val, $user_restriction, $context_options);
			
				}
				else {
				
					$group_context_where_clause = self::Priv_context_where_clause_blank($group_priv, $context_options);
				
					$user_context_where_clause  = self::Priv_context_where_clause_blank($user_priv, $context_options);
				
					$user_restr_context_where_clause = self::Priv_context_where_clause_blank($user_restriction, $context_options);

				}        

				if ( !$group_context_where_clause || !$user_context_where_clause || !$user_restr_context_where_clause ) {
					throw new Exception( __CLASS__ .  '-couldnt_get_context_where_clause' );
				}

				//
				// Start our join
				// 
				if ( !isset($options['skip_user_table_join']) || !$options['skip_user_table_join'] ) {
					$join_clause = " INNER JOIN {$user->table_name} ON {$user->table_name}.{$user->db_field_name_id} = {$user->id} ";
				}

				//
				// Add in the Group link table
				//
				$join_clause .= " LEFT JOIN user_group_link ON user_group_link.{$user->db_field_name_id}={$user->id} ";
				
				//---------------------------------------------------------------
				// Only quote string literals, not other table fields or numbers.
				//--------------------------------------------------------------
				$priv_key_quote = ( $priv_key_is_field_ref ) ? '' : '\'';
				$value_quote    = ( $priv_val_is_field_ref ) ? '' : '\'';

				$join_clause .=  " LEFT JOIN {$priv_type->table_name} ON {$priv_type->table_name}.{$priv_type->db_field_name_key}={$priv_key_quote}{$priv_type_key}{$priv_key_quote} ";

				//--------------------------------------------
				// Check the default privileges for the group
				//--------------------------------------------

	           $join_clause .= " LEFT JOIN {$group_priv->table_name} ON {$group_priv->table_name}.{$priv_type->db_field_name_id}={$priv_type->table_name}.{$priv_type->db_field_name_id} ";

				$where_clause .= '( '; //open priv clause
		        $where_clause .= "({$group_priv->table_name}.{$group_priv->db_field_name_group_id}=user_group_link.{$user->groups->db_field_name_group_id}"; // start group clause

				if ( $priv_val ) {
					$where_clause .= " AND ({$group_priv->table_name}.{$group_priv->db_field_name_val} = {$value_quote}{$priv_val}{$value_quote}) ";
				}
				else {
					if ( $priv_val_is_field_ref ) {
						$where_clause .= " AND {$priv_val} IS NOT NULL AND {$priv_val} != 0 ";
					}
					else {
						$where_clause .= " AND {$group_priv->table_name}.{$group_priv->db_field_name_val} IS NOT NULL AND {$group_priv->table_name}.{$group_priv->db_field_name_val} > 0 ";
					}
				}

				$where_clause .= ( $group_context_where_clause ) ? " AND $group_context_where_clause " : '';
				$where_clause .= ')'; // end group clause

				//
				// Start user privilege clause
				//
	           	$join_clause .= " LEFT JOIN {$user_priv->table_name} ON {$user_priv->table_name}.{$priv_type->db_field_name_id}={$priv_type->table_name}.{$priv_type->db_field_name_id} ";

				$where_clause .= ' OR ';
        	    $where_clause .= "({$user_priv->table_name}.{$user_priv->db_field_name_user_id}={$user->id}"; 

				if ( $priv_val ) {
					$where_clause .= " AND ({$user_priv->table_name}.{$user_priv->db_field_name_val} = {$value_quote}{$priv_val}{$value_quote})";
				}
				else {
					if ( $priv_val_is_field_ref ) {
						$where_clause .= " AND {$priv_val} IS NOT NULL AND {$priv_val} != 0 ";
					}
					else {
						$where_clause .= " AND {$user_priv->table_name}.{$user_priv->db_field_name_val} IS NOT NULL AND {$user_priv->table_name}.{$user_priv->db_field_name_val} != 0 ";
					}					
				}

				$where_clause .= " AND $user_context_where_clause ";
				$where_clause .= ') )'; //end user clause & general priv clause

				$join_clause  .= " LEFT JOIN {$user_restriction->table_name} ON {$user_restriction->table_name}.{$user_restriction->db_field_name_user_id}={$user->id} AND {$user_restriction->table_name}.{$priv_type->db_field_name_id}={$priv_type->table_name}.{$priv_type->db_field_name_id} ";

				
				if ( $priv_val ) {

        	    	$join_clause .= " AND ( {$user_restriction->table_name}.{$user_restriction->db_field_name_val}={$value_quote}{$priv_val}{$value_quote} ) ";

				}
				else {
					 
					if ( $priv_val_is_field_ref ) {
                    	$join_clause .= " AND !ISNULL({$priv_val}) AND {$priv_val} != 0 "; 
	                }
					else {
						$join_clause .= " AND !ISNULL({$user_restriction->table_name}.{$user_restriction->db_field_name_val}) AND {$user_restriction->table_name}.{$user_restriction->db_field_name_val} != 0 ";						
					}
	                
				}
		
				$join_clause .= " AND $user_restr_context_where_clause ";
				$where_clause .= " AND ( {$user_restriction->table_name}.{$user_restriction->db_field_name_id} IS NULL )";

				if ( $priv_val ) {
					//$select_clause .= " IF ({$user_priv->table_name}.{$user_priv->db_field_name_val} = '{$priv_val}', {$user_priv->table_name}.{$user_priv->db_field_name_val}, null) AS {$user_priv->db_field_name_val} ";
					//$select_clause .= " , IF ({$group_priv->table_name}.{$group_priv->db_field_name_val} = '{$priv_val}', {$group_priv->table_name}.{$group_priv->db_field_name_val}, null) AS {$group_priv->db_field_name_val} ";
					$select_clause .= "( CASE WHEN {$user_priv->table_name}.{$user_priv->db_field_name_val} = '{$priv_val}' THEN {$user_priv->table_name}.{$user_priv->db_field_name_val} ELSE NULL END) AS {$user_priv->db_field_name_val}";
					$select_clause .= ", ( CASE WHEN {$group_priv->table_name}.{$group_priv->db_field_name_val} = '{$priv_val}' THEN {$group_priv->table_name}.{$group_priv->db_field_name_val} ELSE NULL END) AS {$group_priv->db_field_name_val}";

				}

			}       	

			$ret = array();
			$ret['select'] = $select_clause;
			$ret[self::KEY_JOIN] = $join_clause;
			$ret[self::KEY_WHERE] = $where_clause;
			$ret[self::KEY_GROUP_BY] = " {$user->table_name}.{$user->db_field_name_id} ";

			return $ret;
			
		}
		catch( Exception $e ) {

			//
			// Exiting here is a security measure.
			// if this function fails, we don't want to 
			// accidentally grant permission.
			//

			trigger_error( $e->getMessage(), E_USER_ERROR );
			exit;
		}

	}

	public static function User_has_any_group( $user, $options = array() ) {
		
		if ( !$user->id ) {
			throw new Exception( __CLASS__ . 'missing_user_id' );
		}
		
		$group_cache_enabled = $user->group_cache_is_enabled();
		
		if ( !$group_cache_enabled || !isset(self::$Cached_group_count[$user->id]) ) {
		
			$iterator 	 = $user->groups->fetch_all();
			$group_count = $iterator->count;
			
			if ( $group_cache_enabled ) {
				self::$Cached_group_count[$user->id] = $group_count;
			}
		}
		else {
			$group_count = self::$Cached_group_count[$user->id];
		}
			
		if ( $group_count > 0 ) {
			return true;
		}
		
		return false;
		
	}
}
?>