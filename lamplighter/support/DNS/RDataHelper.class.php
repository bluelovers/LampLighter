<?php

class RDataHelper {

	public static function append_trailing_dot( $rdata ) {
		
		if ( substr($rdata, -1) != '.' ) {
			$rdata .= '.';
		}
		
		return $rdata;
		
	}
}
?>