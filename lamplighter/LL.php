<?php

if ( !defined('STATIC_CACHE_ENABLED') || constant('STATIC_CACHE_ENABLED') == 1 ) {

	$check_cache = true;
	
	require_once( 
					dirname(__FILE__) 
					. DIRECTORY_SEPARATOR
					. 'support'
					. DIRECTORY_SEPARATOR
					. 'StaticCache' 
					. DIRECTORY_SEPARATOR 
					. 'StaticFile.class.php'
				);

	if ( $check_cache ) {
		if ( StaticFile::Load_by_URI() ) {
			//
			// File is statically cached
			//
			exit;
		}
	}				
	
}

require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'LL-main.php' );

?>