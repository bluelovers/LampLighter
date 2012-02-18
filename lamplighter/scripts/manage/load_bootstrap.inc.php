<?php
	
	require_once( dirname(__FILE__) 
							. DIRECTORY_SEPARATOR 
							. '..'
							. DIRECTORY_SEPARATOR 
							. 'config'
							. DIRECTORY_SEPARATOR
							. 'bootstrap.inc.php' );
							
	Config::Set('debug.silent', true);
?>
