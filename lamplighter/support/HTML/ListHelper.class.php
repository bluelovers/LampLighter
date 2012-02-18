<?php

LL::require_class('HTML/TemplateHelper');

class ListHelper extends TemplateHelper {

	static $Cur_cycle_index = null;
	static $Delete_confirm_message = 'Are you sure you want to delete that item?';

	//
	// Static helper functions
	//
	static function cycle( $first, $second = null ) {
		
		if ( is_array($first) ) {
			
			if ( self::$Cur_cycle_index === null ) {
				self::$Cur_cycle_index = 1;
			}
			
			if ( self::$Cur_cycle_index > count($first) ) {
				self::$Cur_cycle_index = 1;	
			}
						
			echo $first[self::$Cur_cycle_index-1];
			self::$Cur_cycle_index++;
		}
		else {
			if ( self::$Cur_cycle_index == 1) {
				self::$Cur_cycle_index = 2;
				echo $second;
			}
			else {
				self::$Cur_cycle_index = 1;
				echo $first;
			}
		}
		
		
	}
	
	static function delete_confirm( $controller_name, $controller_path, $options = null ) {
		
		if ( isset($options['message']) ) {
			$message = $options['message'];
		} 
		else {
			$message = self::$Delete_confirm_message;
		}
		
		if ( isset($options['action_name']) ) {
			$delete_action = $options['action_name'];
		}
		else {
		
			$delete_action = 'Delete';
			
			if ( !preg_match('/[A-Z]/', $controller_name) ) {
				$delete_action = strtolower($delete_action);
			}
		}
		
		if ( isset($options['redirect']) ) {
			$delete_redirect = $options['redirect'];
		}
		else {
			$delete_redirect = constant('SITE_BASE_URI') . "/{$controller_name}/{$delete_action}/{$controller_path}";
		}
		
		$message = str_replace('\'', '\\\'', $message);
		
		echo "if ( confirm('{$message}') ) document.location.href='{$delete_redirect}'";
		
		 
		
	}
	
	static function format_http_link( $link ) {
		
		echo format_http_link($link);
		
	}

/*
	static function Paginate_by_method( $class, $method, $params = null, $options = null ) {
		
		try {
			
			$model = self::Load_class( $class );
			
			if ( !$params || is_scalar($params) ) {
				$param_arr = array($params);
			}
			else {
				$param_arr = $params;
			}
				
			$result = call_user_func_array(array($model, $method), $param_arr);
			
			
		
		}
		catch( Exception $e ) {
			echo LL::get_errors();
		}
	}

*/

	static function Pagination_setup( $class, $options = null )  {
		
		try {
			
			$controller = self::get_calling_controller();
			
			if ( $controller->paginate ) {
				if ( !isset($options['pager_name']) ) {
					$options['pager_name'] = $class;
				}

				//if ( !isset($options['query_obj']) ) {
				//	$options['query_obj'] = $controller->get_pagination_query_obj();
				//}
			
				if ( $controller && $pager = $controller->get_pagination_obj($options) ) {
					
					$script = $pager->generate_setup_script();
				
					echo '<script type="text/javascript">' . "\n";
					echo $script . "\n";
					echo '</script>';
				
				}
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	static function Pagination_generate( $class, $options = null )  {
		
		try {
			
			$controller = self::get_calling_controller();

			if ( $controller->paginate ) {
				if ( !isset($options['pager_name']) ) {
					$options['pager_name'] = $class;
				}
			
				if ( $controller && $pager = $controller->get_pagination_obj($options) ) {
				
					if ( !isset($options['render']) || $options['render'] == 'script' ) {
						
						echo '<script type="text/javascript">';
						echo $pager->get_js_object_var_name() . '.generate_pagination();' . "\n";
						echo '</script>';
					}
					else if ( $options['render'] == 'html' ) {
						
						print $pager->generate_html();
						
					}				
				}
			}
			
		}
		catch( Exception $e ) {
			echo LL::get_errors();
		}
		
	}

	public static function Range_dir_name( $value, $options = null ) {
		
		try { 
		    
		    LL::Require_class('File/FilePath');
    
		    echo FilePath::Range_dir_name($value, $options);
		}
		catch( Exception $e ) {
			echo LL::get_errors();
		}
		
		
	}
	
	public static function SubstrEcho( $value, $start, $len = null, $options = null ) {

		LL::Require_class('Output/OutputHelper');

		return OutputHelper::Substr_echo( $value, $start, $len, $options );
	}


}

?>