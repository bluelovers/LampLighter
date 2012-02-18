<?php

interface UserAuth {
	
	public static function Validate_password( $uid, $pw );
	
	public static function Get_encrypted_password( $uid );
	public static function Encrypt_password( $password, $options );
	public static function Generate_token_by_password( $password, $options );
	public static function Get_password( $uid );
	
	public static function Uid_exists( $uid );
		
}

interface ActiveUser {

	public function validate_login( $uid, $password );
	
	public function validate_token( $uid, $token, $options = null );
	public function is_logged_in();
	
	public function get_authenticated_uid();
	
}

?>
