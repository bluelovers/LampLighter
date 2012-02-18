<?php

class Pagination {

	const JS_object_var_name_default = 'pagination'; 
	
	const KEY_ITEMS_PER_PAGE = 'items_per_page';
	const KEY_QUERY_STRING	 = 'query_string';
	
	public $name;
	public $suffix_js_object_var = '_pagination';
	public $js_object_var_name   = null;
	public $qs_key_page			 = 'page';
	public $qs_key_offset		 = 'page_offset';
	public $query_string_maintain = 1;
	
	protected $_Result_object;
	protected $_JS_class_properties;
	protected $_Num_items_total = null;
	protected $_Num_pages = null;
	
	public function __construct( $num_items_total = null, $options = null ) {
		
		
		if ( is_array($options) ) {
			foreach( $options as $key=>$val ) {
				$this->$key = $val;
			}
		}
		
		if ( $num_items_total ) {
			$this->_Num_items_total = $num_items_total;
		}
		
		
		$this->links_per_offset = 5;
		$this->pages_linked_through_uri = true;
		$this->next_offset_caption = ' More &gt;&gt;';
		$this->prev_offset_caption = '&lt;&lt; Back';
		$this->prev_page_caption = '&laquo; ';
		$this->next_page_caption = ' &raquo;';
		$this->offset_link_class = 'page_link';
		$this->link_class = 'page_link';
		$this->active_page_class = 'page_active';
		$this->link_base = QueryString::Remove_from_uri($_SERVER['REQUEST_URI']);
		
		
	}
	
	public function get_reflector() {
		
		
		
	}
	
	function __set( $key, $val ) {
		
		if ( $key == 'num_items_total' ) {
			$this->_Num_items_total = $val;
		} 
		
		if ( $key == 'num_pages' ) {
			$this->_Num_pages = $val;
		} 
		
		$this->_JS_class_properties[$key] = $val;
		
	}

	function __get( $key ) {
		
		if ( $key == 'num_items_total' ) {
			return $this->get_num_items_total();
		}
		else if ( $key == 'num_pages' ) {
			return $this->get_num_pages();
		}
		
		if ( isset($this->_JS_class_properties[$key]) ) {
			return $this->_JS_class_properties[$key];
		}
	}

	function set_result_object( $obj ) {
		
		$this->_Result_object = $obj;
		
	}
	
	function get_result_object() {
		
		return $this->_Result_object;
		
	}

	function get_js_object_var_name() {
		
		if ( $this->js_object_var_name ) {
			return $this->js_object_var_name;
		}
		else {
			if ( $this->name ) {
				return $this->name . $this->suffix_js_object_var;
			}
			else {
				return self::JS_object_var_name_default;
			}
		}
		
	}
	
	function is_object_iterator($obj) {
		
		return ( is_a($obj, 'ObjectIterator') || is_subclass_of($obj, 'ObjectIterator') );
		
	}

	function get_num_items_total() {
		
		try {
			
			return $this->_Num_items_total;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function initialize_query_string() {
		
		LL::require_class('URI/QueryString');
		
		if ( !$this->query_string ) {
			if ( $this->query_string_maintain ) {
				$this->query_string = $_SERVER['QUERY_STRING'];
			}
		}
			
		if ( $this->query_string ) {
			
			$this->query_string = QueryString::Strip_var($this->qs_key_page, $this->query_string );
			$this->query_string = QueryString::Strip_var($this->qs_key_offset, $this->query_string );
		}
		
	}

	function generate_setup_script($options = null) {
		
		try {
			
			$script = '';
			$qs 	= null;
			
			$js_object_var_name = $this->get_js_object_var_name();
			$num_items_total 	= $this->get_num_items_total();
			
			if ( !is_numeric($num_items_total) ) $num_items_total = 0;
			
			$script = "{$js_object_var_name} = new Pagination();\n";
			$script .= "{$js_object_var_name}.reference_var_name = '{$js_object_var_name}';\n";
			$script .= "{$js_object_var_name}.num_items_total    = " . intval($num_items_total) . ";\n";

			$this->initialize_query_string();
			
			if ( count($this->_JS_class_properties) > 0 ) {
			
				LL::require_class('HTML/JavascriptHelper');
			
				foreach( $this->_JS_class_properties as $key => $val ) {
				
					if ( $key == 'num_items_total' ) {
						continue;
					}
					
					$val = JavascriptHelper::Literal($val, array('return_output' => true));			
					$script .= "{$js_object_var_name}.{$key} = {$val};\n";
				}
				
			}
			
			return $script;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	public function generate_html() {
		
		LL::Require_class('URI/QueryString');
		
		$html        = '';
		$next_offset = '';
		$prev_offset = '';
		$offset_page_start = 0;
		$qs_delimiter = '';
				
		if ( $this->get_num_items_total() > 0 ) {

			$this->initialize_query_string();
			$num_pages   = $this->get_num_pages();

			if ( $num_pages <= 1 && $this->single_page_caption ) {
				$html .= '<span class="' . $this->text_class . '">' . $this->single_page_caption . '</span>' . "\n";
			}
			else {
				
				if ( $this->page_offset && is_numeric($this->page_offset) ) {
					$current_offset = $this->page_offset;
				}
				else {
					if ( $this->current_page > $this->links_per_offset ) {	
						$current_offset = ceil($this->current_page / $this->links_per_offset);
					}
					else {
						$current_offset = 1;	
					}
				}
				
				$next_offset = $current_offset + 1;
				$prev_offset = ( $current_offset > 1 ) ? $current_offset - 1 : 1;	

				$offset_page_start = ( $current_offset <= 1 ) ? 1 : (($current_offset - 1) * $this->links_per_offset) + 1;

				if ( $offset_page_start > $num_pages ) {
					$offset_page_start = ceil( $num_pages / $this->links_per_offset );
				}

				if ( $offset_page_start > $this->links_per_offset ) {

					//
					// We're on a page higher than 1
					//
					$offset_link = $this->get_link_by_page_number($this->current_page);
					$offset_link .= QueryString::Get_leader($offset_link) . $this->qs_key_offset . '=' . $prev_offset;
					
					$html .= ' <a class="' . $this->link_class . ' page_offset_prev" href="' . $offset_link . '">' . $this->prev_offset_caption . '</a>';

				}
				else {
					if ( $this->page_links_caption ) {
						$html .= '<span class="' . $this->text_class . '">' . $this->page_links_caption . '</span>';
					}

				} 
			
				if ( $this->current_page > 1 && $this->prev_page_caption ) {
					$html .= '<a class="' . $this->link_class . ' prev_link" href="' . $this->get_link_by_page_number( $this->current_page - 1 ) . '">' . $this->prev_page_caption . '</a>';
				}
			
				if ( $this->link_bookend_l ) { 
					$html .= '<span class="' . $this->bookend_class . '">' . $this->link_bookend_l . '</span>';
				}
						
				for ( $j = $offset_page_start; $j < ($offset_page_start + $this->links_per_offset) && $j <= $num_pages; $j++ ) {
					if ( $j == $this->current_page ) {
						$html .= '<span style="' . $this->active_page_style . '"';

						if ( $this->active_page_class != '' ) {
							$html .= ' class="' . $this->active_page_class . '"';
						}
						else {
							$html .= ' class="' . $this->text_class . '"';
						}
					
						$html .=  '>';
						$html .= $j . ' ';
						$html .= '</span>';
					}
					else {
						$html .= '<a '; 
						$html .= ' onclick="' . $this->link_onclick . '"';
						$html .= ' class="' . $this->link_class . '"';
								
						if ( $this->link_href === null ) {
							
							$html .= ' href="' . $this->get_link_by_page_number($j) . '"';
								
						}
						else {
							$html .= ' href="' . $this->link_href . '"';
						}
								
						$html .= '>' . $j . '</a> ';
					}
				}

				if ( $this->link_bookend_r ) { 
					$html .=  '<span class="' . $this->bookend_class . '">' . $this->link_bookend_r . '</span>';
				}

				if ( $this->current_page < $num_pages && $this->next_page_caption ) {
					$html .= '<a class="' . $this->link_class . ' next_page" href="' . $this->get_link_by_page_number( $this->current_page + 1 ) . '">' . $this->next_page_caption . '</a>';
				}

				if ( ($offset_page_start + ($this->links_per_offset -1)) < $num_pages ) {
					
					$offset_link = $this->get_link_by_page_number($this->current_page);
					$offset_link .= QueryString::Get_leader($offset_link) . $this->qs_key_offset . '=' . $next_offset;
					
					$html .= ' <a class="' . $this->link_class . ' page_offset_next" href="' . $offset_link . '">' . $this->next_offset_caption . '</a>';
				}
			}
		}
				
		return $html;
		
	}
	
	public function get_link_by_page_number( $page_number ) {
		
		$link = $this->link_base;
								
		if ( $this->pages_linked_through_uri ) {
								
			$link_base_trailer = substr( $link, -1 );

			if ( $link_base_trailer != '/' && $link_base_trailer != '#') {
				$link .= '/';
			}
								
			$link .= $page_number;
		}
		else {
			if ( $link == '' ) {
				$link = '?';
				$qs_delimiter = '';
			}
			else {
				$qs_delimiter = QueryString::Get_leader($link);
			}
			
			$link .= $qs_delimiter . $this->qs_key_page . '=' . $page_number;
		}

		//
		// Yes, find qs_delimiter again. 
		//
		$qs_delimiter = ( $this->pages_linked_through_uri && (strpos($link, '?') === false) ) ? '?' : '&';

		if ( $this->query_string ) {
			$link .= $qs_delimiter . $this->query_string;
		}

		return htmlspecialchars($link);
		
	}
	
	public function get_num_pages() {
		
		$num_pages = 0;
		
		if ( $this->_Num_pages !== null ) {
			return $this->_Num_pages;
		}
		else {
			$num_pages = ( $this->items_per_page > 0 ) ? ceil ( $this->get_num_items_total() / $this->items_per_page ) : 1;
			$num_pages = ( $this->max_pages && $num_pages > $this->max_pages ) ? $this->max_pages : $num_pages;
			return $num_pages;
		}
		
	}

}
?>