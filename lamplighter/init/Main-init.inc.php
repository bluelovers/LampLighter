<?php

/**
 * Initialization routines for AFTER the Lamplighter class has been loaded
 *
 * This file sets up necessary constants and configuration options
 * for Lamplighter
 *
 * PHP version 5
 *
 */

//
// Set up error reporting
//
if ( defined('ERROR_REPORTING') ) {
	error_reporting( constant('ERROR_REPORTING') );
}
       
if ( defined('DISPLAY_ERRORS') ) {
	if ( DISPLAY_ERRORS > 0 && strtolower(DISPLAY_ERRORS) != 'off' ) {
		$onoff = 'On';
	} 
	else {
		$onoff = 'Off';
	}
	
	ini_set('display_errors', $onoff );
}

//
// Require Fuse-compatibility init
//
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Compat.inc.php' );

if ( (defined('APP_ENVIRONMENT') && strtolower(constant('APP_ENVIRONMENT') == 'devel')) 
		|| (defined('APP_DEBUG') && constant('APP_DEBUG') > 0) ) {

       if ( !defined('TEMPLATE_AUTO_REGENERATE_INCLUDES') ) {
			define ('TEMPLATE_AUTO_REGENERATE_INCLUDES', 1);
       }
       
       if ( !defined('TEMPLATE_FORCE_REGENERATE') ) {
       	define ('TEMPLATE_FORCE_REGENERATE', 1);
       }
       
       
       define('FORM_FORCE_VALIDATION_REGENERATE', 1);

		if ( !defined('ERROR_REPORTING') ) {
			error_reporting( E_ALL ^ E_NOTICE ^ E_DEPRECATED );
		}
       
        if ( !defined('DISPLAY_ERRORS') ) {
	        ini_set('display_errors', 'On');
        }

}
else {

		if ( !defined('ERROR_REPORTING') ) {
			error_reporting( 0 );
		}
       
        if ( !defined('DISPLAY_ERRORS') ) {
	        ini_set('display_errors', 'On');
        }
       
       
}

//
// APP_BASE_PATH, as opposed to PROJECT_BASE_PATH, 
// is now the preferred constant name for the main application root.
//

if ( defined('PROJECT_BASE_PATH') ) {
	if ( !defined('APP_BASE_PATH') ) {
		define('APP_BASE_PATH', constant('PROJECT_BASE_PATH') );
	}
	
}

if ( defined('APP_BASE_PATH') ) {
	if ( !defined('PROJECT_BASE_PATH') ) {
		define('PROJECT_BASE_PATH', APP_BASE_PATH );
	}
}

if ( !defined('APP_CONFIG_PATH') ) {
	define('APP_CONFIG_PATH', APP_BASE_PATH . DIRECTORY_SEPARATOR . 'config' );
}

if ( !defined('APP_PUBLIC_PATH') ) {
	define('APP_PUBLIC_PATH', constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'public' );
}


//------------------------------------
// Input types
// These are deprecated in favor of
// class constants in InputForm. For now, 
// These still need to be here.
//------------------------------------
define ('FORM_INPUT_TYPE_TEXT', 1);
define ('FORM_INPUT_TYPE_TEXTBOX', 1);
define ('FORM_INPUT_TYPE_HIDDEN', 1);
define ('FORM_INPUT_TYPE_RADIO', 2);
define ('FORM_INPUT_TYPE_CHECKBOX', 3);
define ('FORM_INPUT_TYPE_LISTBOX', 4);
define ('FORM_INPUT_TYPE_SELECT', 4);
define ('FORM_INPUT_TYPE_DROPDOWN', 4);
define ('FORM_INPUT_TYPE_FILE', 5);
define ('FORM_INPUT_TYPE_TEXTBOX_ARRAY', 6);
define ('FORM_INPUT_TYPE_LISTBOX_ARRAY', 7);



if ( !defined('APPLICATION_OS') ) {
	if ( strpos(strtolower(php_uname()), 'windows') !== false ) {
		define('APPLICATION_OS', 'WIN');
	}
	else {
		define('APPLICATION_OS', 'UNIX');
	}
}

if ( !defined('LL_BASE_PATH') ) {
	define('LL_BASE_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) );
}


if ( !defined('LL_SUPPORT_PATH') ) {
	define('LL_SUPPORT_PATH', LL_BASE_PATH . DIRECTORY_SEPARATOR . 'support');
}

if ( !defined('GLOBAL_MESSAGE_PATH') ) {
	define('GLOBAL_MESSAGE_PATH', LL_BASE_PATH . DIRECTORY_SEPARATOR . 'messages' );
}

//
// Common Functions
//
require_once( constant('LL_SUPPORT_PATH') . DIRECTORY_SEPARATOR . 'Common/functions-common.inc.php' );

//
// Exceptions
require_once( constant('LL_BASE_PATH') . DIRECTORY_SEPARATOR . 'Exceptions.php' );

//
// Config class
//
require_once( constant('LL_SUPPORT_PATH') . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Config.class.php');

//
// Default Configuration
//
require_once( constant('LL_BASE_PATH') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'LL.conf.php');

//
// Debug class
//
require_once( constant('LL_SUPPORT_PATH') . DIRECTORY_SEPARATOR . 'Debug' . DIRECTORY_SEPARATOR . 'Debug.class.php');

//
// Add search paths
//
LL::Add_function_path( constant('LL_SUPPORT_PATH') );
LL::Add_class_path( constant('LL_SUPPORT_PATH') );

if ( !defined('DATA_MODEL_BASE_PATH') ) {
	define ('DATA_MODEL_BASE_PATH', constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'models');
}

if ( !defined('CONTROLLER_BASE_PATH') ) {
	define ('CONTROLLER_BASE_PATH', constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'controllers');
}

if ( defined('DATA_MODEL_BASE_PATH') ) {
	LL::Add_class_path(constant('DATA_MODEL_BASE_PATH'));
}

if ( defined('CONTROLLER_BASE_PATH') ) {
	LL::Add_class_path(constant('CONTROLLER_BASE_PATH'));
}

LL::Add_class_path( constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'include' );

//
// Set up application environment
//


if ( defined('APP_ENVIRONMENT') && (strtolower(substr(constant('APP_ENVIRONMENT'), 0, 5)) == 'devel') ) {
	if ( !defined('APP_DEBUG') ) {
		define('APP_DEBUG', 1);
	}
}

if ( !defined('FORM_JAVASCRIPT_BASE_PATH') ) {
	if ( defined('APP_BASE_PATH') ) {

		$public_path = constant('APP_PUBLIC_PATH') . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . 'forms';
		
		if ( is_dir($public_path) ) {
			//
			// scripts are stored in /public directory, which is preferred
			//
			define ('FORM_JAVASCRIPT_BASE_PATH', $public_path );
		}
		else {
			//
			// scripts are stored in project base, which is deprecated
			//
			define ('FORM_JAVASCRIPT_BASE_PATH', constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . 'forms' );
		}
	}
}
if ( !defined('FORM_JAVASCRIPT_BASE_URI') ) {
	if ( defined('SITE_BASE_URI') ) {
		define ('FORM_JAVASCRIPT_BASE_URI', constant('SITE_BASE_URI') . '/script/forms'  );
	}
}

if ( !defined('TEMPLATE_BASE_PATH') ) {
	if ( defined('APP_BASE_PATH') ) {
		define ('TEMPLATE_BASE_PATH', constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'views' );
	}
}

if ( !defined('TEMPLATE_CACHE_PATH') ) {
	if ( defined('APP_BASE_PATH') ) {
		define ('TEMPLATE_CACHE_PATH', constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'cache');
	}
}


//------------------
// Template Globals
//------------------
if ( defined('SITE_BASE_URI') ) {
	LL::require_class('HTML/TemplateGlobals');
	TemplateGlobals::add_param( 'SITE_BASE_URI', constant('SITE_BASE_URI') );
	TemplateGlobals::add_param( 'SITE_BASE_URL', constant('SITE_BASE_URI') );
}


if ( !function_exists('__autoload') && !defined('LL_BYPASS_AUTOLOAD') ) {
function __autoload( $class_name ) {
	
	if ( strtolower(substr($class_name, 0, 4)) == 'fuse' ) {
		
	}
	
}
}

?>
