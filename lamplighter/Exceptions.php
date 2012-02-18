<?php

class ExceptionHandler {

	static $Handling = false;

	public static function Handle( $exception ) {
		
		try { 
			$type = get_class($exception);
			$output = '';
			$auth_options = array();
			
			if ( $type == 'UserLoginRequiredException'
					 || $type == 'NoActiveUserFoundException' 
					 || $type == 'UserNoPermissionException'
					 
					 ) {
			
					LL::Require_class('URI/URIReflector');
					$auth_options['redirect_to'] = URIReflector::Get_active_uri();
			
					LL::Require_class('Auth/AuthRedirector');
				
					if ( $type == 'UserNoPermissionException') {
						AuthRedirector::Invalid_permission_redirect($auth_options);
					}
					else {
						AuthRedirector::Login_page_redirect($auth_options);		
					}
				
					exit;	
			}
	
			$output = self::Exception_format_message( $exception, array(
					'html' => 1,
					'text' => 1
				)
			);

			LL::Require_class('Logger/LogEvent');
			LogEvent::Exception($output['text']);
			
			if ( defined('APP_DEBUG') && constant('APP_DEBUG') > 0 ) {
				if ( !Config::Get('output.text_only') ) {
					echo $output['html'];
				}
				else {
					echo $output['text'];
				}
			}
			

			
		}
		catch( Exception $e ) {
			
			//
			// This means the exception handler threw an exception
			// 
			if ( ini_get('display_errors') == 1 ) {
				echo $e->getMessage();
			}
			
			exit;
		}
	}

	public static function Exception_format_message( $exception, $options = array() ) {
		
		try {
			
			$html = 0;
			$text = 1;
						
			$html_tab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			
			$html_output .= '<div style="padding: 4px;background-color: #F7F7F7; border: 1px solid #CCCCCC;">';
			$html_output .= '<h2>Unhandled Exception</h2>';
			
			$message = $exception->getMessage(); 
			
			$html_output .= "<pre>{$message}</pre>";
			$text_output .= $message;			
			
			$html_output .= '<br />' . 'Type: ' . get_class($exception) . '<br />';
			$text_output .= "\n" . 'Type: ' . get_class($exception) . "\n";
			
			$html_output .= $html_tab . str_replace("\n", '<br />' . $html_tab, $exception->getTraceAsString()) . '<br />';
			$text_output .= "\t" . str_replace("\n", "\n\t", $exception->getTraceAsString()) . "\n";
			
			$html_output .= '</div>';

			if ( isset($options['html']) ) {
				$html = $options['html'];
			}

			if ( isset($options['text']) ) {
				$text = $options['text'];
			}
			
			if ( $text && $html ) {
				$ret = array();
				$ret['text'] = $text_output;
				$ret['html'] = $html_output;
				return $ret;
			}
			else if ( $html ) {
				return $html_output;
			}
			else {
				return $text_output;
			}

			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public static function Log_exception( $exception, $options = array() ) {
	
		try {
			
			$output = self::Exception_format_message( $exception, array(
					'text' => 1
				)
			);

			LL::Require_class('Logger/LogEvent');
			LogEvent::Exception($output);
			
		}	
		catch( Exception $e ) {

			//
			// This means the exception handler threw an exception
			// 
			if ( ini_get('display_errors') == 1 ) {
				echo $e->getMessage();
			}
			
			exit;
		}
		
	}

	public static function Error_location_str( $exception ) {
		
		$location = '';
		
		
		foreach( $exception->getTrace() as $index => $trace_entry ) {
			
			$location .= $trace_entry['file'] . ':' . $trace_entry['line'] . Config::Get('output.message_newline');
			
			if ( $trace_entry['class'] ) {
				$location .= $trace_entry['class'] . '::' . $trace_entry['function'];
			}
			else {
				$location .= $trace_entry['function'];
			}
			
			$location .= Config::Get('output.message_newline');
			
			$location .= Config::Get('output.message_newline');
			
		}

				
		return $location;
	}

}

set_exception_handler( array('ExceptionHandler', 'Handle') );


//
// Database Related
//
class DBException extends Exception {
	
	public function __construct( $msg, $details = null ) {

		parent::__construct( $msg . Config::Get('output.message_newline') . $details  );

	}
	
}


class DBConnException extends Exception {

	public function __construct( $msg ) {
		
		if ( !$msg ) {
			$msg = 'db-connection_error';
		}
		
		parent::__construct( $msg );
	}
}


class SQLQueryException extends DBException {
	public function __construct( $query, $dbh = null ) {
		
		$message = $query;
		
		
		if ( $dbh instanceof PDO || $dbh instanceof PDOStatement ) {
			$error = $dbh->errorInfo();
			$message .= Config::Get('output.newline') . 
						$error[1] . ': ' . $error[2];
		}
		
		return parent::__construct($message);
		
	}
}

class InvalidTableNameException extends DBException {
	
}

class MissingPDODriverException extends DBException {
	
}

class ColumnDoesNotExistException extends DBException {
	
}


//
// Input / parameter related
//
class NonNumericValueException extends Exception {

}

class UserDisplayedException extends Exception {
	
}

class UserDataException extends UserDisplayedException {

}


class MissingParameterException extends Exception {

	public function __construct( $message ) {
		
		parent::__construct('Missing Parameter: ' . $message);
		
	}

}

class InvalidParameterException extends Exception {
	
}

//
// File Related
//

class FileOverwriteException extends Exception {
	
}

class InvalidPathException extends Exception {
	
}

class FileInaccessibleException extends Exception {
	
	
}

//
// User Related
//
class UserLoginRequiredException extends Exception {
	
}

class UserNoPermissionException extends Exception {
	
}

class NoActiveUserFoundException extends NotFoundException {
	
}

// 
// Other
//
class CallbackException extends Exception {
		
}

class NotFoundException extends Exception {
		
}

class ConnectionException extends Exception {
		
}

class WriteException extends Exception {
	
	public function __construct( $path ) {
		
		parent::__construct('Error Writing: ' . $path);
		
	}
	
	
}

class ReadException extends Exception {
	
}

class EmailSendException extends Exception {
	
}


?>