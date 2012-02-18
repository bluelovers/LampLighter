<?php

LL::Require_file('Auth/Auth.conf.php');

class AuthLoader {
	
	static $Config_loaded = false;
	
	public static function Load_config() {
		
		if ( !self::$Config_loaded ) {
	
			LL::Require_file('Auth/Auth.conf.php');
			LL::require_class('Auth/AuthConstants');
		
			self::$Config_loaded = true; //make sure this comes before instantiating $user below
			
			if ( !Config::Get('auth.users_table') ) {
				Config::Set('auth.users_table', self::Get_users_table() );
			}	
		}
		
	}
	
	public static function Get_users_table() {

		if ( !Config::Get('auth.users_table') ) {
			$user = self::Load_user_object();
			return $user->table_name;
		}	
		else {
			return Config::Get('auth.users_table');
		}
		
	}
	
	public function Load_user_object( $options = null ) {
    
    	try {
    		
    		$class_name = LL::class_name_from_location_reference(Config::Get_required('auth.user_class'));

			LL::require_class(Config::Get_required('auth.user_class'));
				
			return new $class_name;
   		
    		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }

    public function Load_user_session_object( $options = null ) {
    
    	try {
    		
    		
    		$class_name = LL::class_name_from_location_reference(Config::Get_required('auth.user_session_class'));

			LL::require_class(Config::Get_required('auth.user_session_class'));

			return new $class_name;
    		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }
    

    
    public function Get_user_session_class_name( $options = null ) {
    
    	try {
    		
    		return LL::class_name_from_location_reference(Config::Get_required('auth.user_session_class'));
    		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }

	public static function Get_user_class_name() {
		
		try {
			
		    return class_name_from_location_reference(Config::Get_required('auth.user_class'));
		    		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
	}

	/*
    public function Get_fresh_user_object( $options = null ) {
    
    	try {
    		
    		$options['force_new'] = true;
    		
			return self::Get_user_object($options);
			 		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }
*/

/*
    public function Load_user_object_authenticated( $options = null ) {
    
    	try {
    		
    		$class_name = LL::Class_name_from_location_reference(Config::Get_required('auth.user_class')_authenticated);

			LL::require_class(AuthConfig::$User_class_authenticated);
				
			return new $class_name;
    		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }
    
*/
    
/*
    public function Get_fresh_user_object_authenticated( $options = null ) {
    
    	try {
    		
    		$options['force_new'] = true;
    		
			return self::Get_user_object_authenticated($options);
			 		
    	}	
    	catch ( Exception $e ) {
    		throw $e;
    	}
    	
    }
*/
}
?>