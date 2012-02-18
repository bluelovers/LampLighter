<?php

class Debug {

	const VERBOSITY_LEVEL_ALL = -1;

	const VERBOSITY_LEVEL_BASIC = 1;
	const VERBOSITY_LEVEL_EXTENDED = 2;
	const VERBOSITY_LEVEL_INTERNAL = 3;

	static $Enabled = false;
	static $Messages_at_shutdown = true;
	static $Verbosity_level = 1;
	static $Initialized = false;
	
	static $Messages = array();
	static $Shutdown_registered = false;
	
	public static function Initialize() {
		
		//if ( !self::$Initialized ) {
		//	self::$Initialized = true;
			
			if ( Config::Is_set('debug.enabled') ) {
				self::$Enabled = Config::Get('debug.enabled');
			}
			else {
				if ( defined('APP_DEBUG') && constant('APP_DEBUG') ) {
					self::$Enabled = true;					
				}
			}

		//}
		
	}
	
	public static function Show( $message, $verbosity = null ) {
		
		self::Initialize();

		if ( Config::Get('debug.verbosity_level') ) {
			self::$Verbosity_level = Config::Get('debug.verbosity_level');
		}
		
		if ( self::$Enabled ) {
			if ( $verbosity <= self::$Verbosity_level || $verbosity === self::VERBOSITY_LEVEL_ALL ) {
				
				$message = "DEBUG [{$verbosity}]: $message";
				if ( self::$Messages_at_shutdown ) {
					self::$Messages[] = $message;
					
					if ( !self::$Shutdown_registered ) {
						register_shutdown_function( array(__CLASS__, 'Display_messages') );
						self::$Shutdown_registered = true;
					}
				}
				else {
					 echo $message . Config::Get('debug.message_newline');
				}
			}
		}	
		
	}
	
	public static function Display_messages() {
		
		if ( !Config::Get('debug.silent') ) {

			$text_only = false;

			if ( Config::Get('output.text_only') || Config::Get('debug.text_only_output') ) {
				$text_only = true;
			} 

			if ( !$text_only ) {
				echo '<div style="border: 2px solid #EEEEEE; clear:both; float:none; height: 300px; overflow:auto; clear:both; margin-top: 12px; background-color: #FFFFFF; padding: 6px; color: #444444;">';
				echo '<div style="background-color: #EEEEEE; padding: 5px; font-weight: bold; text-align:center;"><div style="margin-bottom: 4px;">Debug</div><span style="font-weight: normal; font-size: 0.8em;">Use Config::Set(\'debug.silent\', true) or change APP_ENVIRONMENT to something other than \'devel\' to remove these messages</span></div>' . "\n"; 
			}
		
			$count = 1;
		
			foreach ( self::$Messages as $message ) {
				
				if ( $text_only ) {
					echo $message . Config::Get('debug.message_newline');
				}
				else {
					
					$bg_color = ( ($count % 2) == 0 ) ? '#F3F3F3' : '#FFFFFF';  
					
					echo "<div style=\"background-color: {$bg_color}; margin-top: 5px; padding: 4px;\">\n";
					echo $message . "\n";
					echo '</div>' . "\n";
					
					$count++;
				}
			}
		
			if ( !Config::Get('output.text_only') && !Config::Get('debug.text_only_output') ) {
				echo '</div>';
			}
		}
	}

}
?>