<?php

class WebConnector {

	var $_HTTP_version	  = '1.1';
	var $_Connection_timeout  = 30;
	var $_Connection_port     = 80;
	var $_Connection_protocol;
	var $_Connection_host;

	var $_Data_timeout_s = 30;
	var $_Data_bufsize   = 128;	


	function set_remote_port( $port ) {

		$this->_Connection_port = $port;

	}

	function set_remote_host( $host ) {

		$this->_Connection_host = $host;
	}

	function set_protocol( $protocol ) {

		$this->_Connection_protocol = $protocol;
	}

	function set_http_version( $version ) {

		$this->_HTTP_version = $version;
	}

	function set_data_timeout_secs( $timeout ) {

		$this->_Data_timeout_s = $timeout;

	}

	function send_http_get_request( $uri ) {

		try {
			if ( !$uri ) {
				throw new MissingParameterException('uri');
			}
	
			$fp = $this->open_connection();
	
			$http_version_string = $this->generate_http_version_string();
			$host		     = $this->get_remote_host();
	
			$buffer   = '';
			$bufsize  = $this->get_data_bufsize();
	
			$request  = "GET {$uri} {$http_version_string}\r\n";	
			$request .= "Host: {$host}\r\n";
			$request .= "Connection: Close\r\n";
			$request .= "\r\n";
	
			//echo $request . "\n\n";
	
			if ( !fwrite($fp, $request) ) {
				throw new WriteException('couldnt_write_to_stream');
			}
	
		   	while ( !feof($fp) ) {
			       $buffer .= fgets($fp, $bufsize);
			}
	
			fclose($fp);
	
			return $buffer;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function &open_connection() {

		try {
			$connection_timeout = $this->get_connection_timeout();
			$data_timeout_s	    = $this->get_data_timeout_secs();
			$host		    = $this->get_remote_host();
			$protocol           = $this->get_protocol();
			$port		    = $this->get_remote_port();
	
			if ( !$host ) {
				throw new ConnectionException( 'no_host_specified' );
			}
	
			if ( !$port ) {
				throw new ConnectionException( 'no_port_specified' );
			}
	
			$host_string = ( $protocol ) ? "{$protocol}://{$host}" : $host;
			
			$fp = fsockopen( $host_string, $port, $errno, $errstr, $connection_timeout );
	
			if ( !$fp ) {
				throw new ConnectionException("{$errno}: {$errstr}" );
			}
	
			if ( $data_timeout_s ) {
	
				if ( !stream_set_timeout($fp, $data_timeout_s) ) {
					throw new ConnectionException('couldnt_set_stream_timeout');
				}
			}
	
			return $fp;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function generate_http_version_string() {

		if ( $http_version = $this->get_http_version() ) {

			return "HTTP/{$http_version}";

		}

		return null;

	}

	function get_protocol() {

		return $this->_Connection_protocol;

	}

	function get_http_version() {
		
		return $this->_HTTP_version;

	}

	function get_connection_timeout() {

		return $this->_Connection_timeout;

	}

	function get_data_timeout_secs() {

		return $this->_Data_timeout_s;

	}

	function get_remote_host() {

		return $this->_Connection_host;
	}

	function get_remote_port() {

		return $this->_Connection_port;

	}

	function get_data_bufsize() {
	
		return $this->_Data_bufsize;

	}

	function strip_http_header( $data ) {

		$lines = explode( "\n", $data );

		if ( count($lines) > 0 ) {

			foreach( $lines as $cur_line ) {
				if ( preg_match('/^[\r\n]$/', $cur_line) ) {
					array_shift($lines);
					break;
				}
				else {
					array_shift($lines);
				}
	
			}

		}

		return implode( "\n", $lines );
	}
	
}

?>
