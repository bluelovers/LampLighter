<?php

class BrowserSession {

	public $session_cooke_domain = '/';
	public $SID;

	protected $_Session_started = false;

	public function __construct( $options ) {

		if ( !is_array($options) ) {
			$autostart = $options;
		}
		else {
			$autostart = isset( $options['start'] ) ? $options['start'] : true;	
		}

		if ( $autostart ) {
			$this->start();
		}


	}
	
	public function __set( $key, $val ) {
		
		$this->register($key, $val);
	}

	public function name( $name = null ) {

		if ( $name ) {
			session_name($name);
		}

		return session_name();

	}

	public function start() {

		if ( !$this->_Session_started ) {
			session_start();
			$this->_Session_started = true;
			$this->SID = $this->id();
		}

		return $this->SID;
	}

    public function id( $id = null ) {

    	if ( $id === null ) {
        	return session_id();
        }
        else {
        	return session_id( $id );
        }
    }

    public function set_id( $id ) {

		return $this->id($id);

    }

	public function regenerate_id( $destroy_old = false ) {

    	$this->start();

        return session_regenerate_id( $destroy_old );

    }


	function set( $key, $value ) {

		return $this->register( $key, $value );
	}

	function get( $key ) {

		if ( isset($_SESSION[$key]) ) {
			return $_SESSION[$key];
		}
		
		return null;
		
	}

	function register( $key, $value ) {

		try { 
			$this->start();
			$_SESSION[$key] = $value;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	function unregister( $key ) {

		try { 
			$this->start();
			unset( $_SESSION[$key] );
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	function clear() {
		
		try { 
			$this->start();
		
			session_unset();
		
			if ( is_array($_SESSION) ) {
				foreach( $_SESSION as $key => $val ) {
					unset( $_SESSION[$key] );
				}
			}		
		
			if ( isset($_SESSION) ) {
				$_SESSION = array();
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
		
	}

	function destroy() {
	
		try { 
			$this->start();
			$this->clear();
		
			if ( isset($_COOKIE[$this->name()]) ) {
				setcookie($this->name(), '', time() - 86400, $this->session_cookie_domain );
			}
		
			session_destroy();
			$this->reset_obj();
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	public function reset_obj() {
	
		$this->_Session_started = false;
		$this->SID 		= null;
	}	

	
	public function register_post_vars( $except = null ) {

		try { 
            $post_arr = $_POST;

            if ( get_magic_quotes_gpc() ) {
                    if ( is_array($post_arr) && (count($post_arr) > 0) ) {
                            foreach( $post_arr as $key => $val ) {
                                    if ( is_scalar($val) ) {
                                            $post_arr[$key] = stripslashes($val);
                                    }
                                    else {
                                            $post_arr[$key] = array_map( 'stripslashes', $val);
                                    }
                            }
                    }
            }

            return $this->register_assoc_arr( $post_arr, $except );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function register_assoc_arr( $arr, $options = null ) {
		
		try { 
			$except = null;
	
			if ( $options ) {
				if ( isset($options['except']) ) {
					//
					// Correct way of passing options, as an 
					// associative array
					//
					$except = $options['except'];
				}
				else { 
					//
					// Legacy call where $except was the 2nd param
					//
					$except = $options;
				}
			}
	
			if ( $except ) {
				if ( is_scalar($except) ) {
					$except = array( $except );
				}
			}
			else {
				$except = array();
			}
	
			if ( count($arr) > 0 ) {
				foreach( $arr as $arr_key => $arr_val ) {
	
					if ( !in_array($arr_key, $except) ) {
	
						$this->register( $arr_key, $arr_val );
					}		
	
				}
			}
			
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function session_to_assoc_arr() {
		
		return $this->to_assoc_arr();
		
	}

	public function to_assoc_arr () {

		//
		// in case this function ever does anything more 
		// than just looping through $_SESSION, 
		// other code won't have to be changed.
		//

		$session = array();	

		if ( count($_SESSION) ) {
		
			foreach( $_SESSION as $key => $val ) {

				$session[$key] = $this->get($key);

			}
		}


		return $session;

	}


	public function commit() {

		return $this->write_close();

	}

	public function write_close() {

		session_write_close();

	}
}

