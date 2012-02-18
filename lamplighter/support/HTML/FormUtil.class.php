<?php

class FormUtil {

	function Assoc_arr_to_hidden_inputs( $arr, $options = null  ) {

        $html = '';
		$strip_vars = array();

		if ( array_val_is_nonzero($options, 'strip_vars') ) {
			$strip_vars = $options['strip_vars'];
		}

		if ( is_scalar($strip_vars) ) {
        	$strip_vars = array( $strip_vars );
        }

        if ( is_array($arr) && (count($arr) > 0) ) {
                foreach( $arr as $key => $val ) {

                        if ( !in_array($key, $strip_vars) ) {

                                if ( is_array($val) ) {
                                        foreach( $val as $inner_val ) {
                                                
                                                if ( !array_val_is_nonzero($inner_val, 'skip_parse') ) {
                                                	$inner_val = htmlspecialchars($inner_val);
                                                }
                                                
                                                $html .= "<input type=\"hidden\" name=\"{$key}[]\" value=\"{$inner_val}\" />\n";
                                        }
                                }
                                else {
	                                if ( !array_val_is_nonzero($val, 'skip_parse') ) {
                                    	$val = htmlspecialchars($val);
                                    }


                                        $html .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\" />\n";
                                }

                        }

                }
        }

        return $html;

	}

}

?>