<?php

LL::Require_class('Data/DataModel');

class User extends DataModel {

	protected $_Prefix_db_field_name = 'user_'; 
	protected $_Cached_groups;
	protected $_Cached_prefs;
	
	public $_cache_groups = null; //leave as null - defaults to true on no value set
	public $_cache_prefs  = true;
	
	public function __construct() {
		
		$this->_Load_config();
		
		return parent::__construct();
	}
	
	public function __get( $key ) {
		
		if ( $key == 'group_id' || $key == 'user_group_id' ) {
			if ( Config::Get('auth.user_has_single_group') ) {
				return $this->_Get_single_group_id();
			}
		}
		
		return parent::__get($key);
		
		
	}
	
	//
	// Only used if auth.user_has_single_group is enabled
	//
	protected function _Get_single_group_id() {

		if ( $this->id ) {
		
			if ( $this->group_cache_is_enabled() && isset($this->_Cached_groups[$this->id]) ) {
				return $this->_Cached_groups[$this->id][0]['id'];				
			}
			else {
				
				$group_it = $this->user_groups->fetch_all();
				
				if ( $group = $group_it->next() ) {
					$this->_Cached_groups[$this->id][0] = array('id' => $group->id, 'name' => $group->name );				
					return $group->id;
				}
			}
		}
		
		return null;
		
	}
	
	protected function _Load_config() {
		
		LL::Require_file('Auth/Auth.conf.php');
		LL::require_class('Auth/AuthConstants');
		LL::require_class('Auth/AuthValidator');
	}
	
	protected function _Init() {
		
		$this->has_many('Auth/UserGroup', array( 'through' => 'user_group_link') );
		
		$this->has_many(Config::Get('auth.user_preference_class'), array('table' => Config::Get('auth.user_preference_table')));
		
		$this->has_many('Auth/UserPrivilege');
		$this->has_many('Auth/UserRestriction');
		
		$this->has_unique_key('name');
		
		$this->add_table_reference_alias('preferences', Config::Get('auth.user_preference_table'));
		$this->add_table_reference_alias('privileges', 'user_privileges');
	}

	public function is_authenticated() {
		return false;
	}

	public function validate_password( $given_pw ) {

		try { 
				
			$valid_pw	= null;

			if ( !$this->record_exists() ) {
				throw new NotFoundException( __CLASS__ . '-user_record_not_found' );
			}

			if ( !(AuthValidator::Password_has_valid_format($given_pw)) ) {
				throw new Exception( __CLASS__ . '-invalid_password_format', "\$given_pw: $given_pw" );
			}

			if ( !Config::Get_required('auth.enable_encrypted_passwords') && !Config::Get_required('auth.enable_clear_passwords') ) {
				trigger_error( 'Either clear passwords or encrypted passwords must be enabled.', E_USER_ERROR );
				exit(1);
			}
		
			$enc_pw_found = false;
		
			if ( Config::Get_required('auth.enable_encrypted_passwords') ) {

				if ( $valid_pw = $this->password_encrypted ) {
					$enc_pw_found = true;
					$given_pw_enc_data = self::Encrypt_password($given_pw, array('salt' => $this->password_salt) );
					$given_pw = $given_pw_enc_data['password_encrypted'];
				}

			}
			
			if ( Config::Get_required('auth.enable_clear_passwords') ) {

				if ( !$enc_pw_found ) {
					$valid_pw = $this->password;

					if ( !Config::Get_required('auth.passwords_case_sensitive') ) {
						$given_pw = strtolower($given_pw);
						$valid_pw = strtolower($valid_pw);
					}
				}

			}
			
			if ( !$valid_pw || !$given_pw ) {

				if ( !Config::Get_required('auth.allow_blank_passwords') ) {
					throw new Exception( __CLASS__ . '-blank_password_found', "\$id: {$this->id}");
				}

			}

			if ( $given_pw != $valid_pw ) {
				return false;
			}
			else {
				return $valid_pw;
			}
		}
		catch( Exception $e ) {
			throw $e;
		}

	}


	public function validate_password_digest( $given_digest ) {
		
		try {
			
			if ( Config::Get('auth.enable_encrypted_passwords') ) {
				$pw_compare = $this->password_encrypted;
			}
			else {
				$pw_compare = call_user_func(array(LL::Class_name_from_location_reference(Config::Get_required('auth.user_class')), 'Generate_password_digest'), $this->password, $this->password_salt);
			}
			
			if ( $this->record_exists() && $given_digest && ($pw_compare == $given_digest) ) {
				return true;
			}
			
			return false;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	

	public function get_or_generate_encrypted_password() {
	
		try { 

			if ( !$this->record_exists() ) {
				throw new NotFoundException( __CLASS__ . '-user_record_not_found' );
			}

	
			if ( Config::Get_required('auth.enable_encrypted_passwords') && $this->password_encrypted ) {

				return $this->password_encrypted;

			}
			else {
		
				if ( !AuthValidator::Password_has_valid_format($this->password) ) {
					throw new Exception ( __CLASS__ . '-invalid_password_format' );
				}
				
				$encrypted_password = self::Encrypt_password($this->password);
				
				return $encrypted_password;	
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_password_digest() {
		
		//
		// For now, these functions are the same, so just redirect:
		//
		
		return $this->get_or_generate_encrypted_password();
		
	}

	public function set_password( $password, $options = array() ) {
		
		try {
	
			LL::Require_class('Auth/AuthValidator');
	
			if ( !AuthValidator::Password_has_valid_format($password) ) {
				throw new InvalidParameterException( 'password' );
			}
			else {
				if ( Config::Get('auth.enable_clear_passwords') ) {
					$this->password = $password;
				}
				
				if ( Config::Get('auth.enable_encrypted_passwords') ) {
					$encrypted = self::Encrypt_password($password);
					$this->password_encrypted = $encrypted['password_encrypted'];
					
					if ( $this->has_column('password_salt') ) {
						$this->password_salt = $encrypted['salt'];
					}
				}
				
				$this->update();
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_preference( $pref_type_key, $options = null ) {

		try {

			if ( !$this->record_exists() ) {
				throw new NotFoundException( __CLASS__ . '-user_record_not_found' );
			}


			$pref_type_key = $this->db->parse_if_unsafe($pref_type_key);

			$fetch_options['where'] = "{$this->user_preferences->user_preference_types->table_name}.{$this->user_preferences->user_preference_types->db_field_name_key}='{$pref_type_key}'";
			$fetch_options['include'] = $this->user_preferences->user_preference_types->table_name;
			$fetch_options['require_unique_parent'] = true;
		
			$ignore_default = isset($options['ignore_default']) && $options['ignore_default'] ? true : false; 
			$cache_prefs = isset($options['cache_prefs']) ? $options['cache_prefs'] : $this->_cache_prefs;
			
			if ( $cache_prefs && isset($this->_Cached_prefs[$this->id] ) ) {
				if ( isset($this->_Cached_prefs[$this->id][$pref_type_key]) && $this->_Cached_prefs[$this->id][$pref_type_key]['val'] !== null) {
					return $this->_Cached_prefs[$this->id][$pref_type_key]['val'];
				}
				else {
					if ( !$ignore_default ) {
						return $this->_Cached_prefs[$this->id][$pref_type_key]['default'];
					}
				}
			}
			else {

				if ( !$cache_prefs ) {

					$pref = $this->user_preferences->fetch_single( $fetch_options );

					if ( $pref && $pref->val !== null ) {
						return $this->unparse_pref_val($pref->val);
					}
					else {
				
						LL::Require_class(Config::Get('auth.user_preference_type_class'));
						$class_name = LL::class_name_from_location_reference(Config::Get('auth.user_preference_type_class'));
					
						$preference_type = new $class_name;
				
						$preference_type->key = $pref_type_key;
				
						if ( $preference_type->default_val ) {
							return $this->unparse_pref_val($preference_type->default_val,array('is_default' => true));
						}
					}
				}
				else {
					
					LL::Require_class(Config::Get('auth.user_preference_type_class'));
					$class_name = LL::class_name_from_location_reference(Config::Get('auth.user_preference_type_class'));
					
					$preference_type = new $class_name;
					$type_table = $preference_type->table_name;
				
					$prefs = $preference_type->fetch_all(
															array(
																'include' => array(
																		$this->user_preferences->table_name => array('join_type' => 'LEFT')
																), 
																'where' => array("{$this->user_preferences->table_name}.{$this->db_field_name_id} = ?", $this->id)
															) 
														);
					
					while ( $pref = $prefs->next() ) {
						
						$this->_Cached_prefs[$this->id][$pref->pref_type_key]['val'] = null;
						$this->_Cached_prefs[$this->id][$pref->pref_type_key]['default'] = null;
						
						if ( $pref->user_pref_val !== null) {
							$this->_Cached_prefs[$this->id][$pref->pref_type_key]['val'] = $this->unparse_pref_val($pref->user_pref_val);
						}
						else {
							if ( $pref->pref_type_default_val !== null ) {
								$this->_Cached_prefs[$this->id][$pref->pref_type_key]['default'] = $this->unparse_pref_val($pref->pref_type_default_val, array('is_default' => true));
							}
							
						}
						
					}
					
					if ( isset($this->_Cached_prefs[$this->id][$pref_type_key]) ) {
						if ( $this->_Cached_prefs[$this->id][$pref_type_key]['val'] !== null) {
							return $this->_Cached_prefs[$this->id][$pref_type_key]['val'];
						}
						else {
							if ( !$ignore_default ) {
								return $this->_Cached_prefs[$this->id][$pref_type_key]['default'];
							}
						}
					}					
				}
				
				//}
			}
			
			return null;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_preference( $pref_type_key, $val, $options = array() ) {
		
		try {

			LL::Require_class(Config::Get('auth.user_preference_type_class'));
			$class_name = LL::class_name_from_location_reference(Config::Get('auth.user_preference_type_class'));
					
			$pref_type = new $class_name;
			$pref_type->key = $pref_type_key;
			
			if ( !$pref_type->is_uniquely_identified() || !$pref_type->record_exists() ) {
				throw new InvalidParameterException( 'pref_type_key' );
			}
			else {

				LL::Require_class(Config::Get('auth.user_preference_class'));
				$class_name = LL::class_name_from_location_reference(Config::Get('auth.user_preference_class'));
					
				$pref = new $class_name;
				
				$query_obj = $pref->db->new_query_obj();
				$query_obj->delete();
				$query_obj->from( $pref->table_name );
				$query_obj->where( "{$pref->table_name}.pref_type_id=?", $pref_type->id );
				$query_obj->where( "{$pref->table_name}.user_id=?", $this->id );
				$query_obj->run();
				
				$pref->user_id = $this->id;
				$pref->pref_type_id = $pref_type->id;
				$pref->user_pref_val = $this->parse_pref_val($val);
				$pref->save();
				
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function unparse_pref_val( $val, $options = array() ) {
		
		return unserialize($val);
		
	}

	public function parse_pref_val( $val, $options = array() ) {
		
		return serialize($val);
		
	}

	public function is_administrator() {
		
		try { 

			if ( Config::Get_required('auth.administrators_groupname') && $this->belongs_to_group(Config::Get('auth.administrators_groupname')) ) {
					return true;
			}
		
			return 0;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function group_cache_is_enabled( $options = array() ) {

		$enabled = true;

		if ( is_array($options) && array_key_exists('force_new', $options) ) {
			if ( $options['force_new'] ) {
				$enabled = false;
			}
		}
		else {
			if ( $this->_cache_groups !== null ) {
				if ( $this->_cache_groups == false ) {
					$enabled = false;
				}
			}
			else {
				if ( Config::Is_set('auth.group_cache_enabled') ) {
					if ( Config::Get('auth.group_cache_enabled') == false ) {
						$enabled = false;
					}
				}
			}
		
		}
		
		return $enabled;
		
	}

	public function belongs_to_group( $group_name_given, $options = array() ) {
		
		try {

			$force_new = false;
			$cache_groups = true;
			$groups = array();

			//
			// Determine whether or not we should be using the cached groups
			//
			if ( !$this->group_cache_is_enabled($options) ) {
				$force_new = true;
				$cache_groups = false;
			}

			//
			// Determine whether we're doing a case sensitive check
			// default is false
			//
			$case_sensitive = false;
			
			if ( isset($options['case_sensitive']) && $options['case_sensitive'] ) {
				$case_sensitive= true;
			}

			if ( !$case_sensitive ) {
				$group_name_given = strtolower( $group_name_given );
			}
			
			if ( !$this->is_uniquely_identified() ) {
				return false;
			}
			
			$id = $this->id;
			
			if ( !isset($this->_Cached_groups[$id]) || $force_new ) {

				$group_iterator = $this->groups->fetch_all();
				
				while ( $group = $group_iterator->next() ) {

					$groups[] = array('id' => $group->id,  'name' => $group->name );
				}
						
				if ( $cache_groups ) {
					$this->_Cached_groups[$id] = $groups;
				}	
				
			}
			else {
				$groups =& $this->_Cached_groups[$id];
			}
			
			if ( is_array($groups) && $groups ) {
				foreach( $groups as $group_info)  {
				
					$group_name_compare = $group_info['name'];
				
					if ( !$case_sensitive ) {
						$group_name_compare = strtolower($group_name_compare);
					}
					
					if ( $group_name_compare && $group_name_compare == $group_name_given ) {
						return true;
					}
				}
			}
			
			return 0;	
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	/**
	 *  Alias to has_access 
	 */
	public function has_privilege( $priv_type_key, $options = null ) {

		try {
			return $this->has_access($priv_type_key, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	public function has_access( $priv_type_key, $options = null ) {

		try { 
			
			LL::Require_class('Auth/UserPrivQueryHelper');
			
			if ( !$this->record_exists() ) {
				throw new NotFoundException( __CLASS__ . '-user_record_not_found' );
			}

			
			if ( !$this->record_exists() ) {
				throw new Exception( __CLASS__ . '-user_id_nonexistent' );
			}
			
			if ( $this->is_administrator() ) {
				return true;
			}

			if ( !($priv_value_field_user  = $this->privileges->db_field_name_val) ) {
				throw new Exception( __CLASS__ . '-missing_user_priv_value_field');						
			}
					
			if ( !($priv_value_field_group = $this->group->privileges->db_field_name_val) ) {
				throw new Exception( __CLASS__ . '-missing_group_priv_value_field');						
			}
			
			$query = $this->db->new_query_obj();
		
			$options['skip_user_table_join'] = true;
			$options[UserPrivQueryHelper::KEY_PRIV_TYPE_KEY] = $priv_type_key;
		
			$user_priv = new UserPrivilege;
			$group_priv = new GroupPrivilege;
		
			$priv_clauses = UserPrivQueryHelper::User_priv_sql_clauses( $this, $options );

			$query->select( "{$this->table_name}.{$this->db_field_name_id}" );
			//$query->select( "{$this->privileges->table_name}.{$priv_value_field_user}" );
			//$query->select( "{$this->group->privileges->table_name}.{$priv_value_field_group}" );
			$query->select($priv_clauses['select']);
			$query->from( $this->table_name );
			$query->join ( $priv_clauses['join'] );
			$query->where ( $priv_clauses['where'] );
			$query->where( "{$this->table_name}.{$this->db_field_name_id}={$this->id}" );
			$query->group_by( "{$priv_clauses['group_by']}");
	
			$sql_query = $query->generate_sql_query();

			//echo "----------------<br />\n\n";
			//echo $sql_query; 
			//echo "----------------<br />\n\n";

			if ( !($result = $this->db->query($sql_query)) ) {
				throw new SQLQueryException( $sql_query );
			}
			else {
				if ( $this->db->num_rows($result) > 0 ) {
					
					$row = $this->db->fetch_unparsed_assoc($result);
				
					//
					// We want to make sure priv values are case sensitive, 
					// so check them with !=, rather than relying on the DB.
					//
					if ( isset($options[UserPrivQueryHelper::KEY_PRIV_VAL]) ) {
			 
			 			$priv_value = $options[UserPrivQueryHelper::KEY_PRIV_VAL];
						$found_priv_value = 0;

						$result_keys = array_keys($row);
					
						if ( !in_array($priv_value_field_user, $result_keys) || !in_array($priv_value_field_group, $result_keys) ) {
							throw new Exception( __CLASS__ . '-missing_priv_value_fields_in_result' );
						}

						if ( $row[$priv_value_field_user] ) {
							
							$found_priv_value = true;
							if ( strval($row[$priv_value_field_user]) !== strval($priv_value) ) {
								return false;
							}
						}
						else {
							if ( $row[$priv_value_field_group] ) {
								$found_priv_value = true;
								if ( strval($row[$priv_value_field_group]) !== strval($priv_value) ) {
									return false;
								}
							}
						}
												
						if ( !$found_priv_value ) {
							return false;
						}
					}
				
					return true;
				}
			}
			
			return false;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}


	public function has_restriction( $restriction_key, $options = array() ) {

		try { 

			LL::Require_class('Auth/UserPrivQueryHelper');
			LL::Require_class('Auth/UserPrivilegeType');
			
			if ( !$this->record_exists() ) {
				throw new NotFoundException( __CLASS__ . '-user_record_not_found' );
			}

			
			if ( $this->is_administrator() ) {
				return 0;
			}

			$priv_type	 = new UserPrivilegeType();
			$query_obj   = $this->db->new_query_obj();

			if ( !$restriction_key || !is_valid_key($restriction_key) ) {
				throw new Exception( 'general-invalid_key', "\$restriction_key: {$restriction_key}" );
			}	

			$restriction_key = $this->db->parse_if_unsafe($restriction_key);

			$fetch_options['include'] = array( 'user_privilege_types' );
			
			$query_obj->where( "WHERE {$priv_type->table_name}.{$priv_type->db_field_name_key} = '{$restriction_key}'" );

			if ( is_array($options) && isset($options[UserPrivQueryHelper::KEY_PRIV_VAL]) && $options[UserPrivQueryHelper::KEY_PRIV_VAL] !== null ) {
				
				$value_format = $this->column_value_get_query_format($options[UserPrivQueryHelper::KEY_PRIV_VAL]);
				
				$query_obj->where( "{$this->user_restrictions->table_name}.{$this->user_restrictions->db_field_name_val} {$value_format['comparator']} {$value_format['quote']}{$value_format['value']}{$value_format['quote']}" );
			}
			else {
				$query_obj->where( "{$this->user_restrictions->table_name}.{$this->user_restrictions->db_field_name_val} IS NOT NULL " . 
									" AND {$this->user_restrictions->table_name}.{$this->user_restrictions->db_field_name_val} != 0" );
			}

			//
			// Make sure we apply the right context to the query
			//

			if ( is_array($options) && isset($options[UserPrivQueryHelper::KEY_CONTEXT_TYPE_KEY]) && $options[UserPrivQueryHelper::KEY_CONTEXT_TYPE_KEY] ) {
				
				if ( isset($options[UserPrivQueryHelper::KEY_CONTEXT_VAL]) ) {
					$context_val = $options[UserPrivQueryHelper::KEY_CONTEXT_VAL];
				}
				else {
					$context_val = null;
				}
				
				$query_obj->where(UserPrivQueryHelper::Priv_context_where_clause_by_key($options[UserPrivQueryHelper::KEY_CONTEXT_TYPE_KEY], $context_val, $this->user_restrictions));

			}
			else {
				$query_obj->where(UserPrivQueryHelper::Priv_context_where_clause_blank($this->user_restrictions));
			}

			$fetch_options['query_obj'] = $query_obj;

			$restriction = $this->user_restrictions->fetch_single( $fetch_options );

			if ( $restriction ) {
				return true;
			}
		
			return 0;
			
		}
		catch( Exception $e ) {
			
			throw $e;
		}
	}


	public static function Generate_password_digest( $password, $digest_salt = null, $options = null ) {

		try { 
			
			$generated_digest = null;

			if ( !(AuthValidator::Password_has_valid_format($password)) ) {
				throw new Exception( __CLASS__ . '-invalid_password_format' );
			}

			//print_r( Config::$Config_vars ); 
	
			if ( !$digest_salt ) {
				//
				// Check for deprecated site-wide password salt
				//
				if ( !($digest_salt = Config::Get('auth.password_digest_salt')) ) {
					throw new Exception ( __CLASS__ . '-missing_digest_salt' );
				}
			}

			if ( !Config::Get_required('auth.passwords_case_sensitive') ) {
				$password  = strtolower($password);
			}
		
			$digest_string = $password . $digest_salt;
				
			switch( Config::Get_required('auth.password_digest_method') ) {
				case AuthConstants::DIGEST_METHOD_SHA1:
					if ( !$generated_digest = sha1($digest_string) ) {
						throw new Exception( __CLASS_ . '-sha1_failed');
					}
					break;
				case AuthConstants::DIGEST_METHOD_MD5:
				default:
					if ( !$generated_digest = md5($digest_string) ) {
						throw new Exception( __CLASS_ . '-md5_failed');
					}
					break;
								
			}

			return $generated_digest;
		}
		catch( Exception $e ) { 
			throw $e;
		}

	}

	public static function Encrypt_password( $password, $options = array() ) {

		try { 
			
			if ( !(AuthValidator::Password_has_valid_format($password)) ) {
				throw new Exception( __CLASS__ . '-invalid_password_format' );
			}

			if ( Config::Get('auth.crypt_salt') ) {
				//
				// Deprecated use of site-wide crypto salt
				//
				$pw_salt = Config::Get('auth.crypt_salt');
				
			}
			else {
				if ( isset($options['salt']) && $options['salt'] ) {
					$pw_salt = $options['salt'];
				}
				else {
					$pw_salt = md5(uniqid());
				}
			}
			
			if ( Config::Get('auth.password_encryption_type') == AuthConstants::PASSWORD_ENCRYPTION_TYPE_DIGEST ) {
				$encrypted_pw = self::Generate_password_digest( $password, $pw_salt, $options );
			}				
			else if ( Config::Get('auth.password_encryption_type') == SELF::PASSWORD_ENCRYPTION_TYPE_UNIX_CRYPT ) {
				
				if ( !($encryped_pw = crypt($password, $pw_salt)) ) {
					throw new Exception( __CLASS__ . '-password_crypt_failed');
				}
			}
			else {
				trigger_error( 'Invalid or missing encryption type', E_USER_ERROR );
				exit(1);
			}
	
			return array( 'password_encrypted' => $encrypted_pw, 'salt' => $pw_salt );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	

}
?>