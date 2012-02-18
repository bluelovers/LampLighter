<?php

class Redirect {

	public function To( $redirect_to, $options = array() ) {
		
		try {

	        $http_host   = ( isset($_SERVER['HTTP_HOST']) ) ? $_SERVER['HTTP_HOST'] : NULL;
    	    $request_uri = ( isset($_SERVER['REQUEST_URI']) ) ? $_SERVER['REQUEST_URI'] : NULL;
			$protocol    = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && (strtolower($_SERVER['HTTPS']) != 'no') ) ? 'https' : 'http';
			
			if ( !preg_match('/^[A-Za-z0-9]+:\/\//', $redirect_to) ) {
	

				LL::Require_class('File/FilePath');
	
				if ( substr($redirect_to, 0, 1) != '/' ) {

					if ( !$http_host ) {
    	               trigger_error( 'Couldn\'t find environment variable HTTP_HOST for redirect in ' . __METHOD__, E_USER_ERROR );
	   		           exit(1);
    	    		}

					//LL::Require_class('URI/QueryString');
					//$uri_path  = QueryString::Remove_from_URI($request_uri);
    	            //$http_path = "{$http_host}{$uri_path}/{$redirect_to}";
    	            
    	            LL::Require_class('URI/URIParse');
    	            $http_path = URIParse::Strip_scheme(constant('SITE_BASE_URI')) . "/{$redirect_to}";
	       		}
        		else {
					
					//LL::Require_class('URI/URIParse');
        			
        			$http_path = "{$http_host}{$redirect_to}";
        			
            	    //$http_path = URIParse::Strip_scheme(constant('SITE_BASE_URI')) . "{$redirect_to}";
        		}

        		$redirect_path = FilePath::Expand($http_path, '/');
        		
	 	       	$redirect_uri = "{$protocol}://{$redirect_path}";
			}
			else {
				$redirect_uri = $redirect_to;
			}

        	header("Location: {$redirect_uri}");
        	exit(0);
		}		
		catch( Exception $e ) {
				throw $e;
		}
		
	}

}
?>