<?php

class MessageTranslator {

	static $Loaded_messages = array();

	public static function Find( $context, $message_ref, $options = array() ) {
		
		$message_file_prefix = ( defined('MESSAGE_FILE_PREFIX') ) ? constant('MESSAGE_FILE_PREFIX') : 'messages-';
		$message_file_ext    = ( defined('MESSAGE_FILE_EXT') ) ? constant('MESSAGE_FILE_EXT') : '.inc.php';
		$language = null;

		if ( isset(self::$Loaded_messages[$context]) ) {
			if ( isset(self::$Loaded_messages[$context][$message_ref]) ) {
				return self::$Loaded_messages[$context][$message_ref];
			}
			else {
				return null;
			}
		}
		
		static $message_paths = array();

		$arr_name = $context . '_' . 'messages';

		//
		// Setup paths
		//
		if ( count($message_paths) <= 0 ) {

			if ( defined('APP_CONFIG_PATH') ) {
				$message_paths[] = constant('APP_CONFIG_PATH') . DIRECTORY_SEPARATOR . 'messages';
			}

			if ( defined('GLOBAL_MESSAGE_PATH') ) {
				$message_paths[] = constant('GLOBAL_MESSAGE_PATH');
			}
		}

		if ( isset($options['language']) ) {
			$language = $options['language'];
		}
		else { 
			if ( Config::Get('app.language') ) {
				$language = Config::Get('app.language');
			}
		}


		//
		// Try to include message file
		//
		if ( count($message_paths) > 0 ) {

			foreach ( $message_paths as $site_message_path ) {

				$message_filename = $message_file_prefix . $context . $message_file_ext;
				$message_file_path = $site_message_path; 
				
				if ( $language ) {
					$message_file_path .= DIRECTORY_SEPARATOR . $language;
				}

				$message_file_path .= DIRECTORY_SEPARATOR . $message_filename;

				if ( @include($message_file_path) ) {

					$message_arr = $$arr_name;
					
					if ( isset($message_arr[$message_ref]) ) {
						
						self::$Loaded_messages[$context] = $message_arr;
						
						return $message_arr[$message_ref];
					}
				}
					
			}
		}

		return null;
	}

	public static function Translate() {

		//
		// Function can take an undetermined number of argument that looks like:
		//
		// message[, value1, value2, valueN, options]
		// The first argument is always the message to translate,
		// the last argument is the options array
		//
		$args = func_get_args();
		$message_var_string = '';
		$given_message_params = array();
		$options = array();

		if ( count($args) <= 0 ) {
			trigger_error( 'No message passed to ' . __METHOD__, E_USER_WARNING);
			return null;
		}
		else {
			$message = $args[0];
			
			for( $j = 1; $j < count($args) -1; $j++ ) {
				$given_message_params[] = $args[$j]; 
			}
			
			if ( isset($args[$j]) && $args[$j] ) {
				$options = $args[$j];
			}
				
		}
		

		$null_if_no_translation = isset($options['null_if_no_translation']) && $options['null_if_no_translation'] ? true : false;
		$message_context = null;
		$message_ref	 = null;
		$message_context_delimiter = '-';
		$message_var_enclose       = '%';
		$converted_message	   = null;

		$var_enclose = preg_quote($message_var_enclose, '/');

		//
		// Find a context delimiter (i.e. 'category-message')
		//
		if ( preg_match("/(?<!\\\){$message_context_delimiter}/", $message) ) {

		 	//
		 	// Split the category (e.g. 'file') from the message
		 	// identifier (e.g. 'too_large')
		 	//
		 	list ( $message_context, $message_ref ) = preg_split( "/(?<!\\\){$message_context_delimiter}/", $message, 2 );
			
			//
			// Check to see if the message was passed with message values
			// embedded in the message string
			// e.g. file-too_large %50kb%
			//  
						
			if ( !(false === strpos($message, $message_var_enclose)) ) {
				list ( $message_ref, $message_var_string ) = preg_split("/(?<!\\\){$var_enclose}/i", $message_ref, 2, PREG_SPLIT_DELIM_CAPTURE ); 
				
				$message_var_string = ( $message_var_string ) ? $message_var_enclose . $message_var_string : ''; 
				$message_ref  = trim($message_ref);
			}
			
			//echo "var_string - $message_var_string<BR/>";
			//echo "ref - $message_ref<BR/>";

			if ( $converted_message = self::Find($message_context, $message_ref, $options) ) {

				//
				// Check to see if the message has any placeholders
				// for our variables
				//
				if ( preg_match("/(?<!\\\){$var_enclose}[0-9]/", $converted_message) ) {

					$replacement_arr = array();

					if ( $message_var_string ) {
						//
						// function was called with message values embedded in 
						// the string (e.g. "files-too_large %50kb%")
						//
						
						preg_match_all( "/(?<!\\\){$var_enclose}((.*)){$var_enclose}/siU", $message_var_string, $reg_matches );
						
						if ( count($reg_matches[0]) ) {

							for ( $j=0; $j < count($reg_matches[0]); $j++ ) {

								$replacement_arr[] = $reg_matches[1][$j];

							}
						}
						
					}
					else {
						//
						// Function was called with message values passed as 
						// parameters
						// e.g. Translate( 'files-too_large', 50 )
						//
						$replacement_arr = $given_message_params;
					}

					
					if ( count($replacement_arr) > 0 ) {

						for ( $j=0; $j < count($replacement_arr); $j++ ) {

							$var_replacement = $replacement_arr[$j];
							$var_index       = $j + 1;
							$converted_message = preg_replace("/(?<!\\\){$var_enclose}{$var_index}/", $var_replacement, $converted_message);

						}
					}
				}
					
				//
				// Remove any var references that weren't replaced
				//
				$converted_message = preg_replace("/(?<!\\\){$var_enclose}[0-9]+/", '', $converted_message);

			}
			else {
				if ( $null_if_no_translation ) {
					return null;
				}

			}
			
		}
		else {
			if ( $null_if_no_translation ) {
				return null;
			}
		}
		$converted_message = ( !$converted_message ) ? $message : $converted_message;

        return $converted_message;

	}
	
}
?>