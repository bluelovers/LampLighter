<?php

class URIRouter {
	
	const CALLBACK_NAME_PRE_ACTION = 'pre_action';
	const VAR_ALLOWED_CHAR_CLASS = 'A-Za-z0-9_';

	static $ActiveRoutes = array();	
	static $Default_route;
	static $Check_request_uri = true;

	static $App_controller_file_ext = 'class.php';
	static $App_controller_suffix   = 'Controller';


	static $Route_base_uri;
	static $Route_base_real;
	static $Route_error_template	= 'misc/error';
	static $Invalid_route_controller = 'ErrorDocumentController';
	static $Invalid_route_method	 = 'error_404';
	static $Header_404_on_invalid_route = true;

	static $Routes_case_sensitive   = true;

	public static function Router_initialize() {
				
		if ( !self::$Route_base_uri ) {
			if ( defined('SITE_BASE_URI') ) {
				self::$Route_base_uri = constant('SITE_BASE_URI');
			}
		}
		
		if ( !self::$Route_base_real ) {
			if ( defined('APP_BASE_PATH') ) {
				self::$Route_base_real = constant('APP_BASE_PATH');
			}
		}
	}

	function route_connect( $route_uri, $route_setup ) {

		try {
			$new_route_obj = new URIRoute(); 
	
			$new_route_obj->set_route_uri( $route_uri );
			$new_route_obj->set_route_setup( $route_setup );
	
			$new_route_obj->apply_route_setup();
			
			if ( $route_uri != '/' ) {
				$route_uri = ltrim($route_uri, '/');
			}
	
			$first_uri_char = substr( $route_uri, 0, 1 );
	
			if ( !isset($route_setup[URIRoute::$Key_route_case_sensitive]) ) {
				$new_route_obj->case_sensitive(self::$Routes_case_sensitive);
			}
	
			if ( $first_uri_char == ':' || $first_uri_char == '{' ) {
				//
				//This route starts with a variable
				//
				self::$ActiveRoutes['_vars'][] = $new_route_obj;	
				
			}
			else {
				if ( !$new_route_obj->case_sensitive() ) {
				
					if ( preg_match('/^[A-Z]+$/', $first_uri_char) ) {
						self::$ActiveRoutes[strtoupper($first_uri_char)][] = $new_route_obj;
						self::$ActiveRoutes[strtolower($first_uri_char)][] = $new_route_obj;
					}
					else {
						self::$ActiveRoutes[$first_uri_char][] = $new_route_obj;
					}
				}
				else {
					self::$ActiveRoutes[$first_uri_char][] = $new_route_obj;
				}
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function route_default( $route_setup ) {

		try { 
			$new_route_obj = new URIRoute(); 
	
			$new_route_obj->is_default = true;
			$new_route_obj->set_route_setup( $route_setup );
	
			$new_route_obj->apply_route_setup();
	
			self::$Default_route = $new_route_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	
	public static function Route( $options = array() ) {
	
		try { 
			
			LL::Require_class('URI/QueryString');
			
			$strip_route_base = false;
			
			if ( isset($options['uri']) ) {
				$route_uri = $options['uri'];
				$strip_route_base = true;
			}
			else {
				if ( (!isset($_GET['request_uri']) || !($route_uri = $_GET['request_uri'])) ) {
					if ( (!isset($_GET['uri']) || !($route_uri = $_GET['uri'])) ) {
						// Old projects used $_GET['uri']
						if ( self::$Check_request_uri ) {
						 	// Older projects used $_SERVER['REQUEST_URI']
						 	if ( isset($_SERVER['REQUEST_URI']) && ($route_uri = $_SERVER['REQUEST_URI']) ) {
								$strip_route_base = true;
							}
						}
					}
				}
			}

			$_SERVER['QUERY_STRING'] = QueryString::Strip_var('request_uri');
			$_SERVER['QUERY_STRING'] = QueryString::Strip_var('uri');
			
			//
			// $_SERVER['REQUEST_URI'] isn't set on Windows
			//
			if ( !isset($_SERVER['REQUEST_URI']) || !$_SERVER['REQUEST_URI']) {
				$request_uri = $route_uri;
				if ( substr($request_uri, 0, 1) != '/') {
					$request_uri = '/' . $request_uri;
				}
				
				$_SERVER['REQUEST_URI'] = $request_uri;
			}

			
			//else {
			
				self::Router_initialize();
			
				$possible_routes = array();
				
				$route_uri = self::Strip_repeating_slashes_from_uri($route_uri);
				$route_uri = self::Strip_query_string_from_uri($route_uri);
				
				
				
				if ( $strip_route_base ) {
					$route_uri = self::Strip_route_base_from_uri($route_uri);
				}
				
				$route_uri = ltrim($route_uri, '/');

				if ( !$route_uri ) {
					$route_uri = '/';
				}


				$first_uri_char = substr( $route_uri, 0, 1 );
				$route_found = false;

				if ( isset(self::$ActiveRoutes[$first_uri_char]) ) {
					$possible_routes = array_merge( $possible_routes, self::$ActiveRoutes[$first_uri_char] );
				}				
				
				if ( isset(self::$ActiveRoutes['_vars']) ) {
					$possible_routes = array_merge( $possible_routes, self::$ActiveRoutes['_vars'] ); 
				}

				if ( isset(self::$ActiveRoutes['*']) ) {
					$possible_routes = array_merge( $possible_routes, self::$ActiveRoutes['*'] );
				}
	
				if ( count($possible_routes) > 0 ) {
					foreach( $possible_routes as $cur_route_obj ) {
							
						$route_uri = self::Prepend_uri_slash($route_uri);
						
						if ( $cur_route_obj->route_uri_is_match($route_uri) ) {
								$route_found = true;
								
								self::_perform_requested_route_action($cur_route_obj, $options);
								break;

						}
					}
				}
			
			//}
			
			/*
			if ( $route_uri != '/' && self::Route_real_path_exists_by_uri($route_uri) ) {
				header_redirect_relative("{$route_uri}?routed");
				exit(0);
			}
			*/

			if ( !$route_found ) {
				if ( self::$Default_route ) {
					self::_perform_requested_route_action(self::$Default_route, $options);
				}
				else {
					self::Route_Not_Found();
				}
			}
		}
		
		//catch ( CallbackException $ce ) {
		//	throw $ce;
		//}
		catch( Exception $e ) {
			throw $e;
		}


	}

	public static function Strip_query_string_from_uri( $uri ) {
		
		if ( ($qs_pos = strpos($uri, '?')) !== false ) {
			$uri = substr( $uri, 0, $qs_pos );
		}

		return $uri;		
	}

	public static function Strip_repeating_slashes_from_uri( $uri ) {
		
		return preg_replace('#/{2,}#', '/', $uri);
		
	}

	public static function Route_real_path_exists_by_uri( $uri ) {
		
		try {
			
			if ( self::$Route_base_real ) {
				
				$uri = self::Strip_route_base_from_uri($uri);
				
				$real_path = self::$Route_base_real . DIRECTORY_SEPARATOR . $uri;
				
				if ( is_dir($real_path) ) {
					return true;
				} 
				
				return 0;
				
			}
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}

   public static function Strip_route_base_from_uri( $uri ) {

        if ( $base_uri = self::$Route_base_uri) {

				$relative_uri = self::Base_uri_to_relative_link($base_uri);
				
				if ( substr($uri, 0, strlen($relative_uri)) == $relative_uri ) {
                        $uri = substr($uri, strlen($relative_uri));
                }

        }

        if ( strlen($uri) > 1 && (substr($uri, 0, 1) == '/')) {
                $uri = substr($uri, 1);
        }

        return $uri;

    }

    public static function Base_uri_to_relative_link( $uri ) {

		if ( strpos($uri, '://') !== false ) {
	    	list( , $uri ) = explode('://', $uri, 2);

			if ( strpos($uri, '/') !== false ) {
        		list( , $uri ) = explode('/', $uri, 2);
			}

       	}
		
		if ( substr($uri, 0, 1) != '/' ) {
			$uri = '/' . $uri;
		}

		return $uri;

    }
	
	public static function Prepend_route_base_to_uri( $uri ) {
		
		$join_slash = '/';
		
		if ( self::$Route_base_uri) {
			if ( substr(self::$Route_base_uri, -1) == '/' ) {
				$join_slash = '';
			} 
			if ( substr($uri, 0, 1) == '/') {
				$join_slash = '';
			}
			
			$uri = self::$Route_base_uri . $join_slash . $uri;
			
		}

		return $uri;		
	} 

	private static function _perform_requested_route_action( $route_obj, $options = array() ) {

		try { 
			
			
			$params		  = array();
			$ob_started   = false;
			$static_cache = false;
			
			if ( $headers = $route_obj->headers ) {
				
				if ( !is_array($headers) ) {
					$headers = array($headers);
				}
				
				foreach( $headers as $header ) {
					header( $header );
				}
			}
			
			if ( $redirect = $route_obj->get_redirect() ) {
			
				LL::Require_class('Util/Redirect');
			
				$params = self::_extract_controller_params_from_route_obj($route_obj);

				if ( $params && count($params) > 0 ) {
					foreach( $params as $key => $val ) {
						$redirect = self::_Replace_var_placeholders_in_route($key, $val, $redirect);
					}
				}
				
				if ( isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ) {
					
					LL::Require_class('URI/QueryString');
					
					$qs = $_SERVER['QUERY_STRING'];
					$qs = QueryString::Strip_var( 'uri', $qs );
					$redirect .= '?' . $qs;
					
				}
			
				Redirect::To( $redirect );
				

				exit(0);
			
			}
			

			if ( !($action_key = $route_obj->get_route_action()) ) {
				throw new Exception( __CLASS__ . '-no_route_action_specified' );
			}

			if ( !($action_method = $route_obj->get_route_method()) ) {
				throw new Exception( __CLASS__ . '-no_route_method_available' );
			}

			$controller_class = self::_load_controller_by_route_obj($route_obj);

			if ( !$route_obj->is_default ) {
				$params = self::_extract_controller_params_from_route_obj($route_obj);
			}
			
			if ( isset($options['params']) && is_array($options['params']) ) {
				$params = array_merge($params, $options['params']);
			}

			$controller_obj = new $controller_class();
			$controller_obj->set_requested_action($action_key, array('explicit' => false) );
			$controller_obj->set_requested_method($action_method);
			
			//
			// Params that we set should not be pulled in via GPC
			//
			foreach( $params as $key => $val ) {
				$controller_obj->disallow_external_param( $key );
			}

			
			$controller_obj->merge_params($params);
			
			if ( !$controller_obj->validate_method_access($action_method) ) {
				//
				// Redirection should be handled by ControllerAuth
				// unless auth_skip_redirect is set. 
				//
				trigger_error( 'Invalid Permission.', E_USER_ERROR );
				exit(1);
			}

			if ( $model_name = $route_obj->get_model_name() ) {
				$controller_obj->set_model_name($model_name);
			}
		
			if ( $model_library = $route_obj->get_model_library() ) {
				$controller_obj->set_model_library($model_library);
			}

			//
			// Add any explicitly set controller params
			//
			if ( is_array($route_params = $route_obj->get_controller_parameters()) ) {
				foreach( $route_params as $key => $val ) {
					//echo "{$key} is {$val}<br />";
					//$controller_obj->set_param( $key, $val );
					$controller_obj->disallow_external_param( $key );
					$controller_obj->$key =  $val;
					
				}

			}

			//
			// Add layout
			//
			if ( $route_obj->layout ) {
				$controller_obj->layout( $route_obj->layout );
			}

			if ( !is_callable(array($controller_obj, $action_method)) ) {
				throw new Exception( __CLASS__ . "-no_action_handler %{$action_method}%" );
			}
			else {

				if ( $route_obj->static_cache ) {


					$static_id = null;
					$static_cache = true;

					/*
					if ( !defined('STATIC_CACHE_ENABLED') || constant('STATIC_CACHE_ENABLED') == 0 ) {
						$static_cache = false;						
					} 
					*/
					
					if ( defined('STATIC_CACHE_CHECK_CALLBACK') ) {
						
						if ( !StaticFile::Global_callback_gets_ignored($route_obj->static_cache) ) {
						
							if ( !call_user_func(constant('STATIC_CACHE_CHECK_CALLBACK')) ) {
								$static_cache = false;
							}
						}
					}
			
					if ( !isset($route_obj->static_cache['id']) ) {
						if ( isset($route_params['id']) ) {
							$static_id = $route_params['id'];
						}
						else if ( isset($params['id']) ) {
							$static_id = $params['id'];
						}
					}
					else {
						
						
						//if ( isset($route_params[$route_obj->static_cache['id']]) ) {
						//	$static_id = $route_params[$route_obj->static_cache['id']];
						//}
						$static_id_param = $route_obj->static_cache['id'];
						if ( null === ($static_id = $controller_obj->$static_id_param) ) { 
							trigger_error( 'Parameter ' . $route_obj->static_cache['id'] . ' not found in route, static caching disabled', E_USER_WARNING );
							$static_cache = false;
						} 
						
					}

					if ( $static_cache ) {
												
						LL::Require_class('StaticCache/StaticFile');
					
						$cache = new StaticFile();
						$cache->file_basename = StaticFile::Cache_file_basename_by_controller_method($route_obj->get_controller_name(), $route_obj->get_route_method(), $static_id);
						$cache->key = StaticFile::Cache_key_from_URI();
						
						if ( is_array($route_obj->static_cache) ) {
							foreach( $route_obj->static_cache as $key => $val ) {
								$cache->$key = $val;
							}
						}
						
						$ob_started = true;
						ob_start();
					}
				}
				
				//if ( !$controller_obj->skip_pre_action ) {
					
					

				//
				// Do our before_ callbacks
				//					
				$callback_names= array();
								
				if ( !$controller_obj->skip_pre_action ) {
					$controller_obj->call_mixin_method( 'before_action' );
					$callback_names[] = 'before_before_action';
					$callback_names[] = 'before_action';
					$callback_names[] = '__controller_pre_action'; //deprecated
				}
				
				$callback_names[] = 'before_before_' . $action_method;
				$callback_names[] = 'before_' . $action_method;

				$controller_obj->call_mixin_method( 'before_' . $action_method );
				self::Call_route_callbacks( $controller_obj, $callback_names );				
					
				//}

				try { 
					call_user_func(array($controller_obj, $action_method));
				}
				catch( Exception $e ) {
					throw $e;
				} 
				if ( $static_cache ) {
					
					$ob_contents = ob_get_flush();

					if ( $controller_obj->template ) {
						$cache->template_filepaths = array (
								$controller_obj->template->get_absolute_filepath(),
								$controller_obj->get_header_template()->get_absolute_filepath(),
								$controller_obj->get_footer_template()->get_absolute_filepath()
						);
					}
					
					if ( $controller_obj->static_cache_headers ) {
						$sc_headers = $cache->headers ;
						$sc_headers = array_merge ($sc_headers, $controller_obj->static_cache_headers);
						$cache->headers = $sc_headers;
						
					}
				
					$cache->save($ob_contents);

				}

				$controller_obj->flush_static_cache();
				
				$callback_names= array();
				$callback_names[] = 'after_' . $action_method;
				$callback_names[] = 'after_after_' . $action_method;
				$callback_names[] = 'after_action';
				$callback_names[] = 'after_after_action';
				
				self::Call_route_callbacks( $controller_obj, $callback_names );
				
				$controller_obj->call_mixin_method( 'after_' . $action_method );
				$controller_obj->call_mixin_method( 'after_action' );
				
								
				return true;
			}
		}
		catch( Exception $e ) {
			if ( $ob_started ) {
				ob_end_flush();
			}
			throw $e;
		}

	
	}	

	public static function Call_route_callbacks( $controller_obj, $callback_names ) {
		
	
		if ( !is_array($callback_names) ) {
			$callback_names = array($callback_names);
		}
		
		foreach( $callback_names as $callback_name ) {					

			$callback = array($controller_obj, $callback_name);
	
			try { 

				$skip_var = 'skip_' . $callback_name;
				
				if ( !$controller_obj->$skip_var ) {					
					if (is_callable($callback) ) {
						call_user_func($callback);
					}
				}
			}
			catch( Exception $e ) {
				throw $e;
			} 
		}
		
	}

	private static function _load_controller_by_route_obj( $route_obj ) {

		try {
		
			if ( !($controller_name = $route_obj->get_controller_name()) ) {
				throw new NotFoundException ( __CLASS__ . '-no_controller_specified' );
				
			}

			if ( !($controller_class_name = $route_obj->get_controller_class_name()) ) {
				$controller_class_name = $controller_name . self::$App_controller_suffix;
			}

			if ( !defined('APP_CONTROLLER_BASE_PATH') ) {
				$controller_root = constant('APP_BASE_PATH') . DIRECTORY_SEPARATOR . 'controllers';
			}
			else {
				$controller_root = constant('APP_CONTROLLER_BASE_PATH');
			}

			$controller_path = $controller_root . DIRECTORY_SEPARATOR . $controller_class_name . '.' . self::$App_controller_file_ext;

			if ( !is_readable($controller_path) ) {
				throw new NotFoundException ( __CLASS__ . "-controller_unreadable %{$controller_path}%" );
			}

			if ( require_once($controller_path) ) {
				return $controller_class_name;
			}

			return false;
		}
		catch( Exception $e ) {
			throw $e;
		}

	}

	private static function _extract_controller_params_from_route_obj( $route_obj ) {
		
		try {
	
			$params = array();	
	
			if ( !($matches = $route_obj->get_active_regexp_matches()) ) {
				throw new MissingParameterException( 'no_regexp_matches' );
			}
			else {
				$match_count = count($matches);
				$match_index = 1;
				$route_vars  = $route_obj->get_route_vars();
			
				if ( is_array($route_vars) && (count($route_vars) > 0) ) {
	
					for ( $j = 0; $j < count($route_vars); $j++ ) {
							$param_name = $route_vars[$j];
							$params[$param_name] = $matches[$match_index];
							$match_index ++;
					}
				}
	
			}
			
			return $params;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function get_route_obj_match_string( $route_obj) {
	
		try {
			
			if ( !($route_uri = $route_obj->get_route_uri()) ) {
				throw new Exception( __CLASS__ . '-no_route_uri_specified' );
			}
	
			if ( !($route_setup = $route_obj->get_route_setup()) ) {
				throw new Exception( __CLASS__ . '-no_route_setup_specified' );
			}
	
			if ( substr($route_uri, -1, 1) == '/' ) {
				//
				// Strip trailing slash
				//
				$route_uri = substr($route_uri, 0, -1);
			}
	
			$uri_arr      = explode( '/', $route_uri );
			$uri_count = 0;
			$match_string = '';		
			$var_match_regexp = '([^' . URIRoute::$Link_slash_regexp . ']+)';
	
			if ( $uri_arr && (count($uri_arr) > 0) ) {
				
				foreach( $uri_arr as $cur_uri_part ) {
				
					$parsed_uri_part = self::_Preg_quote_uri_part( $cur_uri_part );
					$parsed_uri_part = preg_replace('/(?<!\\\)\*/', '.*', $parsed_uri_part);
				
					
					if ( self::_Contains_route_var_placeholder($cur_uri_part) ) {
						//
						// This URI part references a variable
						//					
	
						//$replacement_string = $parsed_uri_part;
					
						//echo $cur_uri_part . '<br />';
	
						$var_placeholders = self::_Extract_var_from_uri_part($cur_uri_part);
				
						/*
						if ( $var_name == URIRoute::$Key_id ) {
							if ( isset($route_setup[URIRoute::$Key_id]) ) {
								if ( !isset($route_setup[URIRoute::$Key_route_requirements][URIRoute::$Key_id]) ) {
									$route_setup[URIRoute::$Key_route_requirements][URIRoute::$Key_id] = $route_setup[URIRoute::$Key_id];
								}
							}
						}
						*/
						
						
						foreach( $var_placeholders as $var_name ) {
														
							if ( isset($route_setup[URIRoute::$Key_route_requirements]) && isset($route_setup[URIRoute::$Key_route_requirements][$var_name]) ) {
								
								$cur_requirement_match = $route_setup[URIRoute::$Key_route_requirements][$var_name];
								
								if ( $cur_requirement_match ) {
									if ( !self::_URI_Requirement_match_setup_is_valid($cur_requirement_match) ) {
										throw new Exception( __CLASS__ . '-invalid_uri_match_requirement', $cur_requirement_match);
									}
									else {
										$route_obj->add_route_var( $var_name );
										$regexp = self::_Strip_requirement_match_delimiters($cur_requirement_match);
										
										//$match_string .= "({$regexp})" . URIRoute::$Link_slash_regexp;
										//$replacement_string = self::_Replace_var_placeholders_in_route( $var_name, "({$regexp})", $replacement_string);
										$parsed_uri_part = self::_Replace_var_placeholders_in_route( $var_name, "({$regexp})", $parsed_uri_part);
										
										
									}
								}
							}
							else {
								
								$route_obj->add_route_var( $var_name );
								//$match_string .=  $var_match_regexp . URIRoute::$Link_slash_regexp;
								//$replacement_string = self::_Replace_var_placeholders_in_route( $var_name, $var_match_regexp, $replacement_string);
								$parsed_uri_part = self::_Replace_var_placeholders_in_route( $var_name, $var_match_regexp, $parsed_uri_part);
							}
						}
						
						 					 
					}
					//else if ( $cur_uri_part == '*' ) {
					//	$match_string .= '.*' . URIRoute::$Link_slash_regexp;
					//}
					//else {
						
					//	$match_string .= preg_quote( $cur_uri_part ) . URIRoute::$Link_slash_regexp;
					//}
	
					$match_string .= $parsed_uri_part;
	
					$uri_count ++;
					
					if ( $uri_count < count($uri_arr) ) {
						//
						// Add in a test for a trailing forward slash
						//
						$match_string .= URIRoute::$Link_slash_regexp;
					}
				}
			
				//$match_string = substr( $match_string, 0, -2 ); // strip out the last link slash match so we can make it optional
			
			}
	
			//$match_string .= '(' . URIRoute::$Link_slash_regexp . '?)(([\?#].*)?)?$';
			
			if ( $match_string && ($match_string != URIRoute::$Link_slash_regexp) ) {
				$match_string .= '(' . URIRoute::$Link_slash_regexp . '?)';
			}
			
			if ( substr($match_string, 0, strlen(URIRoute::$Link_slash_regexp)) != URIRoute::$Link_slash_regexp ) {
				$match_string = URIRoute::$Link_slash_regexp . $match_string;
			} 
			
			$match_string = "^{$match_string}$";
			
			//echo $match_string . '<br />';
			
			return $match_string;
		}
		catch( Exception $e ) {
			throw $e;
		} 
	}

	private static function _Preg_quote_uri_part( $part ) {
		
		//
		// We only want to preg_quote stuff that we won't be 
		// replacing with our own regular expressions, 
		// like variable placeholders and *
		//
		//
		
		//
		// Split by stars and both kinds of variable placeholders
		// ( :var_name and {var_name} )
		//
		
		
		/* 
		 * 
		 * BOTH OF THESE METHODS WORK FOR DOING THIS...
		 
		 $quoted = $part;
		 preg_match_all('/((?<!\\\)\:[' . self::VAR_ALLOWED_CHAR_CLASS . ']+|\{[' . self::VAR_ALLOWED_CHAR_CLASS . ']+\}|\*)/', $part, $matches );
		
		for ( $j = 0; $j < count($matches[0]); $j++ ) {
			
			$cur_part = $matches[1][$j];
			
			echo "part: {$cur_part}<br />";
			
			if ( !self::_Contains_route_var_placeholder($cur_part) && $cur_part != '*' ) {
				//$next_part = preg_quote($next_part);
				$quoted = str_replace( $matches[1][$j], preg_quote($cur_part), $quoted );
			}
			
		}
		
		return $quoted;
		*/
		
		$split = preg_split('/((?<!\\\)\:[' . self::VAR_ALLOWED_CHAR_CLASS . ']+|\{[' . self::VAR_ALLOWED_CHAR_CLASS . ']+\}|\*)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE );
		$quoted = '';
		
		foreach( $split as $cur_part ) {
			
			//echo "part: {$cur_part}<br />";
			
			if ( !self::_Contains_route_var_placeholder($cur_part) && $cur_part != '*' ) {
				$quoted .= preg_quote($cur_part);
			}
			else {
				$quoted .= $cur_part;
			}
		}
		
		return $quoted;
		
	}

	private static function _Replace_var_placeholders_in_route( $var_name, $replacement, $replace_in ) {
		
		$replace_in = preg_replace("/(?<!\\\):{$var_name}/", $replacement, $replace_in);
		$replace_in = str_replace( '{' . $var_name . '}', $replacement, $replace_in );
		
		return $replace_in;
	}

	private function _Has_uri_var_prefix( $val ) {

		if ( substr($val, 0, 1) == URIRoute::$Prefix_uri_var_reference ) {
			return true;
		}

		return 0;

	} 

	private function _Contains_route_var_placeholder( $val ) {
		
		$var_char_class = self::VAR_ALLOWED_CHAR_CLASS;
		
		//
		// Rails style :var_name
		//
		if ( preg_match("/\:([{$var_char_class}]+)/", $val) ) {
			return true;
		}

		//
		// New style {var_name}
		//
		if ( preg_match("/\{([{$var_char_class}]+)\}/", $val) ) {
			return true;
		}

			
		return false;
		
	}

	private function _Extract_var_from_uri_part( $part ) {

		$ret = array();
		
		//return substr( $part, 1 );
		//$part = substr( $part, 1 ); //strip leading :
		
		//
		// Rails-style :var_name
		//
		preg_match_all('/(?<!\\\)\:([A-Za-z0-9\_]+)/', $part, $matches);
			
		if ( count($matches[0]) > 0 ) {
			for ( $j = 0; $j < count($matches[0]); $j++ ) {
				$ret[] = $matches[1][$j];
			}
		}

		//
		// New style {var_name}
		//
		preg_match_all('/\{([A-Za-z0-9\_]+)\}/', $part, $matches);
			
		if ( count($matches[0]) > 0 ) {
			for ( $j = 0; $j < count($matches[0]); $j++ ) {
				$ret[] = $matches[1][$j];
			}
		}
			
		return $ret;
		
			//$ret['var_name'] = $matches[1];
			//$ret['extra'] = $matches[2];

	}

	private function _Strip_requirement_match_delimiters( $match ) {

		if ( substr($match, 0, 1) == URIRoute::$Route_match_delimiter ) {
			$match = substr($match, 1);
		}		

		if ( substr($match, -1) == URIRoute::$Route_match_delimiter ) {
			$match = substr($match, 0, -1);
		}		

		return $match;
	}

	private function _URI_Requirement_match_setup_is_valid( $match ) {

		return true;

		/*
		if ( substr($match, 0, 1) == URIRoute::$Route_match_delimiter ) {
			if ( substr($match, -1, 1) == URIRoute::$Route_match_delimiter ) {
				return true;
			}
		}

		return 0;
		*/		

	}

	public static function Route_Not_Found( $options = array() ) {

		try { 
			LL::Require_class('AppControl/ApplicationController');
	
			$controller_file = ApplicationController::Strip_class_name_suffix(self::$Invalid_route_controller); 
				
			if ( self::$Invalid_route_controller && LL::Include_controller($controller_file) ) {
	
				$controller_name = ApplicationController::Append_class_name_suffix(self::$Invalid_route_controller);
				
				$controller = new $controller_name;
				$method_name = self::$Invalid_route_method;
	
				if ( !self::$Invalid_route_method || !is_callable(array($controller, $method_name)) ) {
					$message = 'No Route Recognized for that URL';
					self::Route_display_error( $message, $options);
				} 						
				else {
					$trigger_error = false;
					call_user_func( array($controller, $method_name) );
					exit;
				}			
			}
			else {
				$message = 'No Route Recognized for that URL';
				self::Route_display_error( $message, $options);
			}
	
			exit();
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public static function Route_display_error( $message = null, $options = array() ) {

		LL::Require_class('HTML/MarkupTemplate');
		
		$trigger_error = true;
		$template = new MarkupTemplate( self::$Route_error_template );

		if ( self::$Header_404_on_invalid_route ) {
			header("HTTP/1.0 404 Not Found");
		}

			
		if ( is_readable($template->get_absolute_filepath()) ) {
			$trigger_error = false;
			$template->add_param( 'error_message', $message );
			$template->print_output();
			
		}
		
		if ( $trigger_error ) {
			trigger_error($message, E_USER_ERROR);
		}
	
		exit(1);

	}

	public static function Prepend_uri_slash( $str ) {
		
		if ( substr($str, 0, 1) != '/' ) {
			$str = "/{$str}";
		}
		
		return $str;
		
	}

}

class URIRoute {

	const KEY_VAR_OPTIONS = 'var_options';
	const KEY_ALLOW_SLASHES = 'allow_slashes';

	public $controller_method;
	public $is_default;

	var $_Route_uri;
	var $_Route_action;
	var $_Route_redirect;
	var $_Route_controller_name;
	var $_Route_setup;
	var $_Route_case_sensitive = true;
	var $_Controller_class_name;
	var $_Controller_parameters;
	var $_Model_class_name;
	var $_Model_library;
	var $_Route_vars = array();
	var $_URI_match_string;

	var $_Active_regexp_matches;

	static $Key_route_action           = 'action';
	static $Key_controller_method      = 'method';
	static $Key_action_key    		   = 'action_key';
	static $Key_route_redirect	   	   = 'redirect';
	static $Key_route_controller_name  = 'controller';
	static $Key_route_controller_class = 'controller_class';
	static $Key_route_requirements     = 'requirements';
	static $Key_controller_parameters  = 'parameters';
	static $Key_model_library		   = 'model_library';
	static $Key_model_class_name   	   = 'model_name';
	static $Key_route_case_sensitive   = 'case_sensitive';
	
	static $Key_id = 'id';
	
	static $Prefix_uri_var_reference = ':';
	static $Link_slash_regexp = '\\/';
	static $Route_match_delimiter = '/';

	function apply_route_setup() {
	
		try {
	
			if ( !($route_setup = $this->get_route_setup()) ) {
				throw new Exception( __CLASS__ . '-missing_route_setup');
			}
	
			if ( !$this->is_default ) {
				if ( !($route_uri = $this->get_route_uri()) ) {
					throw new Exception( __CLASS__ . '-missing_route_uri' );
					return false;
				}
			}
	
			if ( isset($route_setup[self::$Key_route_redirect]) ) {
				$this->set_redirect( $route_setup[self::$Key_route_redirect] );		
			}
			else { 
				if ( !isset($route_setup[self::$Key_route_action]) ) {
					throw new Exception( __CLASS__ . '-missing_route_action' );
				}
	
				if ( !isset($route_setup[self::$Key_route_controller_name]) ) {
					throw new Exception( __CLASS__ . '-missing_route_controller_name' );
				}
			}
	
	
			if ( isset($route_setup[self::$Key_route_controller_name]) ) {
				$this->set_route_controller_name( $route_setup[self::$Key_route_controller_name] );
			}
			
			if ( isset($route_setup[self::$Key_route_action]) ) {
				$this->set_route_action( $route_setup[self::$Key_route_action] );
			}
	
			if ( isset($route_setup[self::$Key_route_controller_class]) ) {
				$this->set_controller_class_name( $route_setup[self::$Key_route_controller_class] );
			}
	
			if ( isset($route_setup[self::$Key_controller_parameters]) ) {
				$this->set_controller_parameters( $route_setup[self::$Key_controller_parameters] );
			}
			
			if ( isset($route_setup[self::$Key_model_class_name]) ) {
				$this->set_model_name( $route_setup[self::$Key_model_class_name] );
			}
			
			if ( isset($route_setup[self::$Key_model_library]) ) {
				$this->set_model_library( $route_setup[self::$Key_model_library] );
			}
	
			if ( isset($route_setup[self::$Key_controller_method]) ) {
				$this->controller_method = $route_setup[self::$Key_controller_method];
			}	
	
			if ( isset($route_setup[self::$Key_route_case_sensitive]) ) {
				$this->case_sensitive($route_setup[self::$Key_route_case_sensitive]);
			}
	
			if ( !$this->is_default ) {
				if ( !$this->_URI_match_string = URIRouter::get_route_obj_match_string($this) ) {
					return false;
				}
			}
	
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function __get( $key ) {
		
		if ( isset($this->_Route_setup[$key]) ) {
			return $this->_Route_setup[$key];
		}
		
		return null;
		
	}

	public function route_uri_is_match( $uri ) {

		$match_string = $this->_URI_match_string;

		$flags = '';
		
		if ( !$this->case_sensitive() ) {
			
			$flags .= 'i';
		}
		
		//$flags .= 'U';
		
		if ( preg_match("/{$match_string}/{$flags}", $uri, $matches) ) {
			$this->_Active_regexp_matches = $matches;
			return $matches;
		}

		return 0;

	}
	
	public function add_route_var( $name ) {

		$this->_Route_vars[] = $name;

	}

	public function set_route_setup( $setup ) {

		$this->_Route_setup = $setup;

	}

	public function set_route_uri( $uri ) {

		$this->_Route_uri = $uri;

	}

	public function get_route_uri() {

		return $this->_Route_uri;

	}

	public function get_route_setup() {

		return $this->_Route_setup;
	}

	public function get_uri_match_string() {

		return $this->_URI_match_string;

	}

	public function get_active_regexp_matches() {

		return $this->_Active_regexp_matches;

	}

	public function get_route_vars() {

		return $this->_Route_vars;

	}
		
	function set_route_controller_name( $name ) {

		$this->_Route_controller_name = $name;

	}

	function add_route_variable( $var_name ) {
	
		$this->_Route_variables[] = $var_name;

	}

	function set_route_action( $action ) {

		$this->_Route_action = $action;

	}

	function get_route_action() {

		return $this->_Route_action;

	}

	function get_route_method() {
	
		if ( $this->controller_method ) {
			return $this->controller_method;
		}
		
		if ( $this->get_route_action() == 'list' ) {
			return 'show_list';
		}
		
		return $this->get_route_action();
		
	}

	function get_controller_class_name() {

		return $this->_Controller_class_name;

	}

	function set_controller_class_name( $name ) {

		$this->_Controller_class_name = $name;

	}

	function get_controller_name() {

		return $this->_Route_controller_name;
	}

	function set_controller_parameters( $params ) {

		$this->_Controller_parameters = $params;

	}

	function get_controller_parameters() {

		return $this->_Controller_parameters;

	}

	function set_redirect($redirect) {

		$this->_Route_redirect = $redirect;

	}

	function get_redirect() {

		return $this->_Route_redirect;

	}
	
	function set_model_name( $name ) {
		
		$this->_Model_class_name = $name;
	}
	
	function get_model_name() {
		
		return $this->_Model_class_name;
	}
	
	function set_model_library( $library ) {
		
		$this->_Model_library = $library;
	}
	
	function get_model_library() {
		
		return $this->_Model_library;
	}
	
	public function case_sensitive ( $tf = null ) {
		
		if ( $tf ) {
			$this->_Route_case_sensitive = true;
		}
		else {
			if ( $tf !== null ) {
				$this->_Route_case_sensitive = false;
			}
		}
		
		return $this->_Route_case_sensitive;
	}
}

/* Fuse Compatibility */
class FuseURIRouter extends URIRouter {
	
}
?>
