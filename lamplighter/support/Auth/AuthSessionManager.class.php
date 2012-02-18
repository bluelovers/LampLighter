<?php

LL::Require_file('Auth/Auth.conf.php');

class AuthSessionManager {

	static $Active_session;

	public static function Get_active_session( $options = array() ) {
		
		try {
			
			if ( !self::$Active_session ) {
				LL::Require_class('Auth/AuthLoader');
				self::$Active_session = AuthLoader::Load_user_session_object();

				if ( isset($options['auto_validate']) ) {
					$auto_validate = $options['auto_validate'];
				}
				else {
					$auto_validate = Config::Get_required('auth.session_auto_validate');
				}
				
				if ( $auto_validate ) {
					self::$Active_session->validate( $options );
				}
			}
			
			return self::$Active_session;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

}
?>