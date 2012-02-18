<?php

class AuthConfig {

	static $Authentication_enabled = false;

	static $Administrators_groupname = 'Administrators';

	static $Password_format 	= '';
	static $Password_max_length = 32;
	static $Password_encryption_type = 1;

	static $Username_format 	= '[A-Za-z0-9_\-]';
	static $Username_max_length = 32;
	
	static $Enable_encrypted_passwords = true;
	static $Enable_clear_passwords 	   = true;
	static $Passwords_case_sensitive   = false;
	static $Allow_blank_passwords	   = false;
	static $Password_digest_salt	   = null;
	static $Password_digest_method	   = null;
	
	static $User_class = 'Auth/User';
	//static $User_class_authenticated = 'Auth/AuthenticatedUser';
	static $User_session_class = 'Auth/AuthSession';
	
	static $Login_timeout = 0;
	static $Login_timeout_enabled = false;
	static $Login_timeout_qs_key = 'login_timeout';
	static $Login_timeout_uri;
	static $Login_page_uri = '/Auth/Login';
	
	static $Login_redirect_page_default = '/Auth/Login/Complete';
	static $Logout_redirect_page_default = '/Auth/Logout/Complete';
	static $Login_redirect_transparent = true;

	static $Login_cookie_name = 'login';
	static $Login_cookie_expiration = 'session';
	static $Login_cookie_path	= '/';
	static $Login_cookie_domain = null;

	static $Login_cookie_index_user_id 		   = 1;
	static $Login_cookie_index_password_digest = 2;
	static $Login_cookie_index_update_time	   = 3;
	
	static $Post_key_username = 'username';
	static $Post_key_password = 'password';
	
	static $Session_auto_validate = 1;
	
	static $Invalid_permission_page_uri = '/Auth/No_Permission';
	
	//static $Auto_validate_login = true;
	
	static $Key_redirect_uri = 'redirect_to';
	static $Key_login_redirected = 'redirected';
	static $Key_message = 'message';
	
	static $Message_login_required = 'Auth-login_required';
	
	
}


?>