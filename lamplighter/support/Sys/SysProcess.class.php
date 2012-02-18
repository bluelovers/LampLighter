<?php

class SysProcess {

	public $pid;
	public $pid_file;

	public function __construct() {
		
		
	}

	public function is_running() {

		try { 
			if ( !$this->pid ) {
				throw new Exception( __CLASS__ . '-no_pid_set' );
			}
			
			if ( is_numeric($this->pid) ) {
        		if( posix_kill($this->pid,0) ) {
					return true;
				}
			}

			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function read_pid_file() {
        
		try { 
	    
			$pid = 0;

			if ( !$this->pid_file ) {
				throw new Exception( 'general-missing_parameter %$pid_file%' );
			}

			if ( file_exists($this->pid_file) ) {

				if ( !is_readable($this->pid_file) ) {
					throw new Exception( 'processes-pid_file_undreadable', "\$pid_file: $pid_file" );
				}

        		if ( !($fp = fopen($this->pid_file, 'r')) ) {
					throw new Exception( 'processes-couldnt_open_pid_file' );
				}

                $pid = fgets($fp,1024);
			
                if ( !fclose($fp) ) {
					throw new Exception( __CLASS__ . '-couldnt_close_pid_file', "\$pid_file: $pid_file" );
				}
         
       		}

			return $pid;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function remove_pid_file () {

		try { 
			if ( !$this->pid_file ) {
				throw new Exception( 'general-missing_parameter %$pid_file%' );
			}

			if ( !unlink($this->pid_file) ) {
				throw new Exception( "processes-couldnt_remove_pidfile %{$pid_file}%" );
			}

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}


	function write_pid_file( $options ) {
		
		try { 
		
			$fail_if_running = ( !isset($options['fail_if_running']) || $options['fail_if_running'] == true ) ? true : false;
	
			if ( !$this->pid_file ) {
				throw new Exception( 'general-missing_parameter %$pid_file%' );
			}

			if ( !is_numeric($this->pid) ) {
        		throw new Exception ( 'general-non_numeric_value', "\$pid: $pid" );
	        }

			if ( $fail_if_running ) {
				if ( $active_pid = $this->read_pid_file() ) {
				
					$active_process = new Process();
					$active_process->pid = $active_pid;
				
					if ( $active_proces->is_running() ) {
						throw new Exception ( __CLASS__ . "-pidfile_process_running %{$pid_file}%", "\$pid_file: $pid_file, \$active_pid: $active_pid" );
					}
				
					unset($active_process);
				}
			}	

        	if( !($fp = fopen($this->pid_file,'w+')) ) {
				throw new Exception( __CLASS__ . '-couldnt_open_pidfile', "\$pid_file: $pid_file" );
        	}
        
        	if ( !(fwrite($fp, $this->pid)) ) {
				throw new Exception( 'processes-couldnt_write_pidfile', "\$pid_file: $pid_file" );
			}

            if ( !fclose($fp) ) {
				throw new Exception( __CLASS__ . '-couldnt_close_pid_file', "\$pid_file: $pid_file" );
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function set_eusername( $which_username, $options = null ) {

		try { 
			
			$options['effective_only'] = true;
			
			return $this->set_process_username( $which_username, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function set_username( $which_username, $options = null ) {

		try {
			
			$effective_only = ( isset($options['effective_only']) && $options['effective_only'] ) ? true : false; 

			if ( !$which_username ) {
				throw new Exception( 'general-missing_parameter %$which_username%' );
			}

        	if ( !($posix_userinfo = posix_getpwnam($which_username)) ) {
                throw new Exception( __CLASS__ . '-no_userinfo', "\$which_username: $which_username" );
	        }
           
    	    if ( !($which_uid = $posix_userinfo['uid']) ) {
                throw new Exception( __CLASS__ . '-no_uid', "\$which_username: $which_username" );
        	} 
   
			if ( $effective_only ) {
	    	    if ( !posix_seteuid($which_uid) ) {
        		        throw new Exception( __CLASS__ . '-couldnt_set_euid', "\$which_uid: $which_uid" );
		        }     
			}
			else {
	        	if ( !posix_setuid($which_uid) ) {
        	        throw new Exception( __CLASS__ . '-couldnt_set_uid', "\$which_uid: $which_uid" );
		        }     
			}

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function set_egroupname( $which_groupname, $options = null ) {

		try { 
			
			$options['effective_only'] = true;
			
			return $this->set_process_groupname( $which_groupname, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function set_groupname( $which_groupname, $options = null ) {

		try { 
			
			$effective_only = ( isset($options['effective_only']) && $options['effective_only'] ) ? true : false; 

			if ( !$which_groupname ) {
				throw new Exception( 'general-missing_parameter %$which_groupname%' );
			}

        	if ( !($posix_groupinfo = posix_getgrnam($which_groupname)) ) {
                throw new Exception( __CLASS__ . '-no_groupinfo', "\$which_groupname: $which_groupname" );
	        }
           
    	    if ( !($which_gid = $posix_groupinfo['gid']) ) {
                throw new Exception( __CLASS__ . '-no_gid', "\$which_groupname: $which_groupname" );
	        } 

			if ( $effective_only ) {   
	        	if ( !posix_setegid($which_gid) ) {
        	        throw new Exception( __CLASS__ .  '-couldnt_set_egid', "\$which_gid: $which_gid" );
                	
	        	}     
			}
			else {
	        	if ( !posix_setgid($which_gid) ) {
        	        throw new Exception( __CLASS__ .  '-couldnt_set_gid', "\$which_gid: $which_gid" );
		        }     
			}

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function set_identity( $which_username, $which_groupname, $options = null ) {

		try { 
			$this->set_groupname($which_groupname, $options);
			$this->set_username($which_username, $options);

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function set_eidentity( $which_username, $which_groupname, $options = null ) {
	
		try { 
			$this->set_egroupname($which_groupname, $options);
			$this->set_eusername($which_username, $options);

			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

}


?>