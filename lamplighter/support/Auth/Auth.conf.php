<?php

	$overwrite_val = Config::$Overwrite;
	
	Config::$Overwrite = false;
	
	Config::Set('auth.administrators_groupname', 'Administrators');
	Config::Set('auth.administrator_username', 'Administrator');

	Config::Set('auth.password_format', '');
	Config::Set('auth.password_max_length', 32);
	Config::Set('auth.password_min_length', 4);
	Config::Set('auth.password_encryption_type', 1);

	Config::Set('auth.username_format', '[A-Za-z0-9_\-]+');
	Config::Set('auth.username_max_length', 32);
	Config::Set('auth.username_min_length', 4);
	
	Config::Set('auth.session_uid_field', 'id');
	Config::Set('auth.enable_encrypted_passwords', true);
	Config::Set('auth.enable_clear_passwords', true);
	Config::Set('auth.passwords_case_sensitive', false);
	Config::Set('auth.allow_blank_passwords', false);
	Config::Set('auth.password_digest_method', null);
	
	Config::Set('auth.user_class', 'Auth/User');
	Config::Set('auth.user_table', 'users');
	Config::Set('auth.user_session_class', 'Auth/AuthSession');
	Config::Set('auth.user_preference_class', 'Auth/UserPreference');
	Config::Set('auth.user_preference_table', 'user_preferences');
	Config::Set('auth.user_preference_type_class', 'Auth/UserPreferenceType');
	Config::Set('auth.user_preference_type_table', 'user_preference_types');
	Config::Set('auth.user_preference_value_class', 'Auth/UserPreferenceValue');
	Config::Set('auth.user_preference_value_table', 'user_preference_values');
	
	Config::Set('auth.login_timeout', 0);
	Config::Set('auth.login_timeout_enabled', false);
	Config::Set('auth.login_timeout_qs_key', 'login_timeout');
	Config::Set('auth.login_timeout_uri', null);
	Config::Set('auth.login_page_uri', 'auth/login');
	
	Config::Set('auth.login_redirect_page_default', 'auth/login/complete');
	Config::Set('auth.logout_redirect_page_default', 'auth/logout/complete');
	Config::Set('auth.login_redirect_transparent', true);

	Config::Set('auth.login_cookie_name', 'login');
	Config::Set('auth.login_cookie_expiration', 'session');
	Config::Set('auth.login_cookie_path', '/');
	Config::Set('auth.login_cookie_domain', null);

	Config::Set('auth.login_cookie_index_user_id', 1);
	Config::Set('auth.login_cookie_index_password_digest', 2);
	Config::Set('auth.login_cookie_index_update_time', 3);
	
	Config::Set('auth.post_key_username', 'username');
	Config::Set('auth.post_key_password', 'password');
	
	Config::Set('auth.session_auto_validate', 1);
	
	Config::Set('auth.invalid_permission_page_uri', 'auth/no_permission');
	
	Config::Set('auth.auto_validate_login', true);
	
	Config::Set('auth.key_redirect_uri', 'redirect_to');
	Config::Set('auth.key_login_redirected', 'redirected');
	Config::Set('auth.key_message', 'message');
	
	Config::Set('auth.message_login_required', 'Auth-login_required');
	
	//
	// For deprecated AuthConfig class
	//
	
	if ( class_exists('AuthConfig', false) ) {
		$ac_reflector = new ReflectionClass('AuthConfig');
		
		$ac_properties = $ac_reflector->getProperties();
		if ( count($ac_properties) > 0 ) {
			foreach( $ac_properties as $property ) {
				$property_name = $property->name;
				$config_val = AuthConfig::$$property_name;
			
				Config::Set('auth.' . strtolower($property_name), $config_val);
				Config::Set('auth.enabled', AuthConfig::$Authentication_enabled);
			}
		}
	}
	
	Config::$Overwrite = $overwrite_val;
?>