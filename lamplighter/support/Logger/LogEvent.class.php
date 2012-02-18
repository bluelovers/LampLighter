<?php

class LogEvent {

	static $Output_path;

	static function Get_output_file() {

		if ( self::$Output_path ) {
			return self::$Output_path;
		}				
		
		if ( Config::Get('logger.output_path') ) {
			return Config::Get('logger.output_path');
		}
		
		$default_log_dir = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'logs';
		
		if ( is_dir($default_log_dir) ) {
			return $default_log_dir . DIRECTORY_SEPARATOR . constant('APP_ENVIRONMENT') . '.log';
		}
		
		return null;
		
	}

	static function Info( $msg, $options = array() ) {
		
		try {
			
			$options['level'] = 'Info';		
			self::Write( $msg, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	static function Error( $msg, $options = array() ) {
		
		try {
			
			$options['level'] = 'Error';		
			self::Write( $msg, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	static function Exception( $msg, $options = array() ) {
		
		try {
			
			$options['level'] = 'Exception';		
			self::Write( $msg, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	static function Format_message( $msg, $options = array() ) {
		
		$level = ( isset($options['level']) ) ? $options['level'] : '';
		
		$log_line = '[' . date('D M d, Y H:i:s') . ']';
		
		if ( $level ) {
			$log_line .= ' ' . $level; 
		} 
		
		$log_line .= ': ' . $msg;
		
		return $log_line;
		
	}
	
	static function Write( $msg, $options = array() ) {
		
		try {
			
			if ( !Config::Is_set('logger.enabled') || Config::Get('logger.enabled') != 0 ) {
				if ( $output_file = self::Get_output_file() ) {
					$msg = self::Format_message($msg, $options);
					if ( is_writable($output_file) ) {
						if ( $fp = fopen( $output_file, 'a' ) ) {
							fwrite($fp, $msg . "\n" );
							fclose($fp);
						}
					}
				}
			}			
		}
		catch( Exception $e ) {
			throw $e;
		} 
		
	}

}
?>