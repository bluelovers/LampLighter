<?php

LL::Require_class('Sys/SysProcess');

class ActiveSysProcess extends SysProcess {

	public function __construct() {
		
		$this->pid = $this->get_pid();
		
		parent::__construct();
	}


	public function daemonize( $options ) {
    
    	try { 
    		
    		$chdir = null;
    		$clear_umask = 1;
    		
    		if ( isset($options['chdir']) ) {
    			$chdir = $options['chdir'];
    		}
    		
    		if ( isset($options['clear_umask']) ) {
    			$clear_umask = $options['clear_umask'];
    		}
    	
	        $child = pcntl_fork();
                                        
    	    if ( $child == -1 ) {
                throw new Exception( __CLASS__ . '-couldnt_fork' );
	        }
    	    else if ( $child ) {
                exit(0); // kill parent
        	}
                                 
        	posix_setsid(); // become session leader
                        
			if ( $chdir ) {
	        	chdir($chdir);
			}

			if ( $clear_umask ) {
	        	umask(0); // clear umask
			}

			declare(ticks = 1);

			return posix_getpid();
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}

	}


	public function get_pid() {
		
		return posix_getpid();
		
	}

}
?>