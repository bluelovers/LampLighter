<?php

class StringUtils {

	public static function Strip_literals( $str ) {

		return preg_replace('/(?<!\\\)\'(.*)(?<!\\\)\'/U', '', $str);
	}
		
}
?>