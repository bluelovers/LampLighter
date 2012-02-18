<?php

class AuthConstants {

	const PASSWORD_ENCRYPTION_TYPE_DIGEST = 1;
	const PASSWORD_ENCRYPTION_TYPE_UNIX_CRYPT = 2;

	const DIGEST_METHOD_MD5 = 1;
	const DIGEST_METHOD_SHA1 = 2;
	
	const KEY_COOKIE_EXPIRATION_SESSION = 'session';
	
	const KEY_REDIRECT_TRANSPARENT = 'transparent';
	const KEY_REDIRECT_CONTROLLER_METHOD = 'controller_method';
	const KEY_EXPLICIT_REDIRECT = 'redirect_to';
	
	
}
?>