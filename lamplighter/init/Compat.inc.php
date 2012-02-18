<?php

class FUSE extends LL {
	
}

//
// Used by Require_class(), etc, 
// to find references to legacy file names
//
$_FUSE_FILE_NAME_REWRITES = array(

	'FuseControllerMixin.class.php' => 'ControllerMixinParent.class.php',
	'FuseForm.class.php' => 'InputForm.class.php',
	'FuseFormValidator.class.php' => 'InputFormValidator.class.php',
	'FuseFormMailer.class.php' => 'FormMailer.class.php',
	'FusePhotoController.class.php' => 'PhotoManagementController.class.php'
);

$_constant_rewrites = array(

	'FUSE_TEMPLATE_BASE_PATH' => 'TEMPLATE_BASE_PATH',
	'FUSE_TEMPLATE_CACHE_PATH' => 'TEMPLATE_CACHE_PATH',
	'FUSE_TEMPLATE_AUTO_REGENERATE_INCLUDES' => 'TEMPLATE_AUTO_REGENERATE_INCLUDES',
	'FUSE_TEMPLATE_FORCE_REGENERATE' => 'TEMPLATE_FORCE_REGENERATE',
	'FUSE_TEMPLATE_STATIC_DIR_CHMOD' => 'TEMPLATE_STATIC_DIR_CHMOD',
	'FUSE_TEMPLATE_STATIC_FILE_CHMOD' => 'TEMPLATE_STATIC_FILE_CHMOD',
	'FUSE_FORM_FORCE_VALIDATION_REGENERATE' => 'FORM_FORCE_VALIDATION_REGENERATE',
	'FUSE_PLATFORM' => 'APPLICATION_OS',
	'FUSE_BASE_PATH' => 'LL_BASE_PATH',
	'FUSE_FORM_JAVASCRIPT_BASE_PATH' => 'FORM_JAVASCRIPT_BASE_PATH',
	'FUSE_FORM_JAVASCRIPT_BASE_URI' => 'FORM_JAVASCRIPT_BASE_URI',
	'FUSE_DB_USERNAME' => 'DB_USERNAME',	
	'FUSE_DB_USERNAME_R' => 'DB_USERNAME_R',	
	'FUSE_DB_USERNAME_W' => 'DB_USERNAME_W',
	'FUSE_DB_PASSWORD' => 'DB_PASSWORD',
	'FUSE_DB_PASSWORD_R' => 'DB_PASSWORD_R',
	'FUSE_DB_PASSWORD_W' => 'DB_PASSWORD_W',
	'FUSE_DB_HOSTNAME' => 'DB_HOSTNAME',	
	'FUSE_DB_NAME' => 'DB_NAME',
	'FUSE_DB_DRIVER' => 'DB_DRIVER',
	
);

foreach( $_constant_rewrites as $_legacy => $_current ) {
	
	if ( defined($_legacy) ) {
		if ( !defined($_current) ) {
			define ($_current, $_legacy);
		}
	}
	
}

define ('ERROR_LEVEL_ANY', 1);
define ('ERROR_LEVEL_IGNORABLE', 2);
define ('ERROR_LEVEL_USER', 4);
define ('ERROR_LEVEL_GENERAL', 8);
define ('ERROR_LEVEL_INTERNAL', 16);
define ('ERROR_LEVEL_WARN', 32);
define ('ERROR_LEVEL_WARNING', 32);
define ('ERROR_LEVEL_FATAL', 64);
define ('ERROR_LEVEL_PERMISSION', ERROR_LEVEL_FATAL );
define ('ERROR_LEVEL_PERMISSIONS', ERROR_LEVEL_PERMISSION );

if ( !defined('ERROR_LEVEL_SQL') ) {
	define('ERROR_LEVEL_SQL', constant('ERROR_LEVEL_INTERNAL'));
}

define ('DEBUG_LEVEL_DEFAULT', constant('ERROR_LEVEL_GENERAL') );


?>
