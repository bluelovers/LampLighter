<?php
function read_user_response( $length = null ) {
	
	if ( !$length ) {
		$length = 255;
	}
	
	fseek(STDIN, 0);
	$response = trim(fread(STDIN, $length));
	
	return $response;
} 

function user_response_is_yes() {
	
	if ( substr(strtolower(read_user_response()), 0, 1) == 'y' ) {
		return true;
	}
	
	return false;
	
}
?>
