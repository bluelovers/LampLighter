<?php

class XMLParser {

	public $xml_version   = '1.0';
	public $xml_encoding  = 'utf-8';
	
	public $generate_xml_header = true;

	var $_Active_dataset_objects;
	var $_Active_dataset_element_obj;

	var $_Parser_elements_by_id;
	var $_Top_level_element_names;
	var $_XML_parser;

	var $_Active_parser_elements;
	var $_Active_parser_element_obj;

	var $_Attrib_key_id = 'ID';

	var $_Suppress_missing_close_tag_errors;
	var $_Newline_between_elements = true;

	var $_Top_level_element_auto_find = true;

	var $_Verbose = false;

	public function __construct() {
		
		$this->_Top_level_element_names = array();
		$this->_Active_dataset_objects  = array();
		$this->_Active_parser_elements  = array();		
		$this->_Parser_elements_by_id	= array();
		$this->_XML_Parser_options	= array();
	}


	function start_top_level_element( $element_name, $attribs = null ) {

		if ( $this->is_xml_case_folding_enabled() ) {
			$element_name = strtoupper($element_name);
		}

		$element_obj = new XMLTopLevelElement();
		$element_obj->set_name( $element_name );
		$element_obj->set_attribs_by_assoc_arr($attribs);

		$this->add_top_level_element_name($element_name);
		$this->add_active_dataset_top_level_obj( $element_obj );
		$this->set_active_dataset_obj( $element_obj );

		return $element_obj;
		
	}

	function add_element_by_name( $element_name, $attribs = null, $character_data = null, $options = null ) {

		$parent_element = null;

		if ( is_object($options) ) {
			//
			// Deprecated call where 4th parameter was $parent_element
			//
			$parent_element = $options;
			$options = array();
			$options[self::KEY_PARENT_ELEMENT] = $options;
		}

		if ( $this->is_xml_case_folding_enabled() ) {
			$element_name = strtoupper($element_name);
		}

		if ( !$parent_element ) {
			if ( !$parent_element = $this->get_active_dataset_obj() ) {
				trigger_error( "Element {$element_name} added with no parent!", E_USER_WARNING );
			}
		}

		$element_obj = new XMLElement();
		$element_obj->set_name( $element_name );
		$element_obj->set_attribs_by_assoc_arr($attribs);
		$element_obj->set_character_data($character_data);

		if ( $parent_element ) {
			$parent_element->add_child_element( $element_obj );
			$element_obj->set_parent_element( $parent_element );
		}

		$this->set_active_dataset_obj( $element_obj );
			
		return $element_obj;

	}

	function close_active_element() {

		$active_obj = $this->get_active_dataset_obj();
		$parent = $active_obj->get_parent_element();

		if ( $parent ) {
			$this->set_active_dataset_obj( $parent );
		}
		else {
			$this->set_active_dataset_obj( $n = NULL );
		}

	}

	function close_active_top_level_element() {

		$this->set_active_dataset_obj( $n = NULL );

	}

	function get_active_dataset_obj() {

		return $this->_Active_dataset_element_obj;

	}


	function set_active_dataset_obj( &$obj ) {

		$this->_Active_dataset_element_obj =& $obj;

	}
	
	function set_top_level_element_name( $element_name ) {

		if ( $this->is_xml_case_folding_enabled() ) {
			$element_name = strtoupper($element_name);
		}

		$this->_Top_level_element_names   = array();
		$this->_Top_level_element_names[] = $element_name;

	}

	function add_top_level_element_name( $element_name ) {


		if ( $this->is_xml_case_folding_enabled() ) {
			$element_name = strtoupper($element_name);
		}

		$this->_Top_level_element_names[] = $element_name;


	}

	function &get_xml_parser() {

		if ( !$this->_XML_parser ) {
			
			$this->_XML_parser = xml_parser_create();
			
			xml_set_element_handler( $this->_XML_parser, array($this, 'element_handler_start'), array($this, 'element_handler_end') );
			xml_set_character_data_handler( $this->_XML_parser, array($this, 'character_data_handler') );
		}

		return $this->_XML_parser;

	}

	function set_xml_parser_option( $option, $value ) {

		return $this->xml_parser_set_option( $option, $value );
	}
		

	function xml_parser_set_option( $option, $value ) {

		$parser = $this->get_xml_parser();

		return xml_parser_set_option( $parser, $option, $value );

	}

	function is_xml_parser_option_set( $option ) {

		if ( $this->get_xml_parser_option($option) ) {
			return true;
		}

	}

	function is_xml_case_folding_enabled() {

		return $this->is_xml_parser_option_set( XML_OPTION_CASE_FOLDING );
	
	}

	function get_xml_parser_option( $option ) {

		$parser = $this->get_xml_parser();

		return xml_parser_get_option( $parser, $option );

	}


	function get_top_level_dataset_obj() {

		if ( count($this->_Active_dataset_objects) > 0 ) {
			
			return $this->_Active_dataset_objects[0];
		}
	}


	function add_parser_element_obj_by_id( $element_name, $id, &$element_obj ) {

		if ( $this->_Verbose ) {
			echo "setting {$element_name} as {$id}\n";
		}

		$this->_Parser_elements_by_id[$element_name][$id] =& $element_obj;

	}

	function get_element_obj_by_name_id( $element_name, $element_id ) {

		if ( isset($this->_Parser_elements_by_id[$element_name]) ) {
			if ( isset($this->_Parser_elements_by_id[$element_name][$element_id]) ) {
				return $this->_Parser_elements_by_id[$element_name][$element_id];
			}
		}

		return null;

	}

	function add_active_parser_element_obj( &$obj ) {

		array_push( $this->_Active_parser_elements, $obj );

	}

	function set_active_parser_element_obj( &$obj ) {

		$this->_Active_parser_element_obj =& $obj;

	}

	function get_active_parser_element_obj() {

		$element_count = count($this->_Active_parser_elements);

		if ( $element_count > 0 ) {
			$element_index = $element_count - 1;
			return $this->_Active_parser_elements[$element_index];
		}

		return null;

		//return $this->_Active_parser_element_obj;

	}

	/*
	function set_active_parser_top_level_obj( &$obj ) {

		$this->_Active_parser_top_level_obj =& $obj;

	}

	function &get_active_parser_top_level_obj() {

		return $this->_Active_parser_top_level_obj;

	}
	*/


	function element_handler_start( $parser, $element_name, $attribs ) {

		if ( $this->_Verbose ) {
			echo "found start for: {$element_name}\n";
		}

		/*
		if ( $this->is_top_level_element_name($element_name) ) {
			echo "{$element_name} is a top level element\n";
		}			
		else {
		}
		*/

		if ( $this->is_top_level_element_name($element_name) 
			|| ($this->_Top_level_element_auto_find && !$this->get_active_parser_element_obj()) ) {

			if ( $this->_Verbose ) {
				echo "starting {$element_name} as top level\n";
			}

			$new_element = new XMLTopLevelElement;
		}			
		else {
			$new_element = new XMLElement;

			if ( $cur_active_element = $this->get_active_parser_element_obj() ) {
				$new_element->set_parent_element($cur_active_element);
				$cur_active_element->add_child_element($new_element);
			}
		}

		$new_element->set_name( $element_name );
		$new_element->set_attribs_by_assoc_arr($attribs);

		if ( $this->is_xml_case_folding_enabled() ) {
			$this->_Attrib_key_id = strtoupper($this->_Attrib_key_id);
		}

		if ( isset($attribs[$this->_Attrib_key_id]) ) {

			$element_id = $attribs[$this->_Attrib_key_id];

			if ( $this->_Verbose ) {
				echo "GOT ID $element_id FOR {$element_name}\n";
			}

			$new_element->set_id( $element_id );
			$this->add_parser_element_obj_by_id( $element_name, $element_id, $new_element );
		}

		$this->add_active_parser_element_obj($new_element);
		
		if ( $this->_Verbose ) {
			echo "attribs: " . print_r( $attribs, 1 ) . "\n";
		}

	}

	function element_handler_end( $parser, $element_name ) {

		if ( $this->_Verbose ) {
			echo "found closing for: {$element_name}\n";
		}

		if ( count($this->_Active_parser_elements) > 0 ) {
			$this->_Active_parser_element_obj = array_pop( $this->_Active_parser_elements );

			if ( $this->_Active_parser_element_obj->is_top_level_element() ) {
			//if ( count($this->_Active_parser_elements) <= 0 ) {
				if ( $this->_Verbose ) {
					echo "Closing top level - given element name: {$element_name}, object element name: " . $this->_Active_parser_element_obj->get_name() . "\n" ;
				}

				if ( count($this->_Active_parser_elements) > 0 ) {
					if ( !$this->_Suppress_missing_close_tag_errors ) {	
						trigger_error( "Top level tag closed without child tag, {$element_name}, being closed first.", E_USER_WARNING );
					}
				}

				$this->add_active_dataset_top_level_obj( $this->_Active_parser_element_obj );
				$this->_Active_parser_elements = array();
			}
			else {
				//
				// Not really sure about this right now...probably don't use it.
				// the XML parser doesn't seem to parse XML data that isn't wrapped entirely
				// in a top level element, 
				// so this may disappear in the future anyway, since 
				// there should *always* be a top level element.
				//

				if ( !$this->_Active_parser_element_obj->get_parent_element() ) {
					if ( $this->_Verbose ) {
						echo "adding {$element_name} to dataset\n";
					}
			
					$this->add_active_dataset_obj( $this->_Active_parser_element_obj );
				}
			}
		}
		else {
			if ( !$this->_Suppress_missing_close_tag_errors ) {	
				trigger_error( "Found closing tag for {$element_name}, but had no open element to close!", E_USER_WARNING );
			}
		}

		
	}

	function add_active_dataset_top_level_obj( &$obj ) {

		$this->_Active_dataset_objects[0] =& $obj;

	}

	function add_active_dataset_obj( &$obj ) {

		$this->_Active_dataset_objects[] =& $obj;

	}
	
	function character_data_handler( $parser, $data ) {

		if ( $active_element_obj = $this->get_active_parser_element_obj() ) {

			$active_element_name = $active_element_obj->get_name();

			if ( $this->_Verbose ) {
				echo "found data:{$data} for {$active_element_name}\n";
			}

			$active_element_obj->set_character_data($data);
		}

	}

	function parse_xml_data( $data ) {

		$parser = $this->get_xml_parser();

		return xml_parse( $parser, $data );

	}

	function is_top_level_element_name( $name ) {

		if ( $this->_Verbose ) {
			echo "checking if {$name} is top level\n";
		}

		if ( is_array($this->_Top_level_element_names) ) {
			if ( in_array($name, $this->_Top_level_element_names) ) {
				return true;
			}
			else { 
				if ( $this->_Verbose ) {
					echo "{$name} is not explicitly marked as top level.\n";
				}
				return 0;
			}
		}
		else {
			trigger_error( 'Warning: _Top_level_element_names is not an array!', E_USER_WARNING );
		}
		

		return false;
	}

	function get_parsed_dataset_objects() {
		
		return $this->_Active_dataset_objects;

	}

	function generate_xml_data_from_dataset( $dataset = null ) {

		return $this->generate( $dataset );
	}

	function generate( $dataset = null ) {

		if ( !$dataset ) {
			$dataset = $this->get_active_dataset_objects();
		}

		$xml_data = '';

		if ( is_array($dataset) && (count($dataset) > 0) ) {

			foreach( $dataset as $cur_element ) {

				$cur_element->set_newline_between_elements( $this->_Newline_between_elements );

				$xml_data .= $cur_element->generate_xml_data();

			}

		}

		return $xml_data;
	}

	function get_active_dataset_objects() {

		return $this->_Active_dataset_objects; 

	}

	function set_xml_option_case_folding( $truefalse ) {

		if ( $truefalse ) {
			$this->xml_parser_set_option( XML_OPTION_CASE_FOLDING, true );
			$this->_XML_Option_case_folding = true;
		}
		else {
			$this->xml_parser_set_option( XML_OPTION_CASE_FOLDING, false );
			$this->_XML_Option_case_folding = false;
		}

	}

	function set_newline_between_elements( $truefalse ) {

		if ( $truefalse ) {
			$this->_Newline_between_elements = true;
		}
		else {
			$this->_Newline_between_elements = false;
		}

	}

	function generate_xml_header() {
		
		return '<?xml version="' . $this->xml_version . '" encoding="' . $this->xml_encoding . '" ?>'; 
		
	}

}

class XMLElement {

	var $_Parent_element;
	var $_Element_name;
	var $_Child_elements;
	var $_Child_elements_by_name;
	var $_Attribs;
	var $_Character_data;
	var $_ID;

	var $_Newline_between_elements = true;
	var $_Newline_before_character_data = false;
	var $_Newline_after_character_data = false;


	function set_name( $name ) {

		$this->_Element_name = $name;
	}

	function get_name() {

		return $this->_Element_name;
	}

	function add_child_element( &$element_obj ) {

		$this->_Child_elements[] = $element_obj;

	}

	function set_attribs_by_assoc_arr( $attribs ) {
	
		$this->_Attribs = $attribs;
	}

	function add_attrib( $key, $value = null ) {

		$this->_Attribs[$key] = $value;

	}
	
	function get_attribs() {
	
		return $this->_Attribs;

	}

	function is_top_level_element() {

		return 0;
	}

	function set_parent_element( &$element_obj ) {

		$this->_Parent_element = $element_obj;

	}

	function get_character_data() {

		return $this->_Character_data;

	}
	

	function set_character_data( $data ) {

		$this->_Character_data = $data;

	}

	function set_id( $id ) {

		$this->_ID = $id;

	}

	function get_id() {

		return $this->_ID;

	}

	function get_child_elements() {

		return $this->_Child_elements;
	}

	function get_parent_element() {

		return $this->_Parent_element;

	}

	function generate_xml_data() {
		
		$xml_data = '';
		$element_name   = $this->get_name();
		$attrib_string  = $this->generate_attrib_string();
		$character_data = $this->get_character_data();

		if ( $attrib_string ) {
			$attrib_string = " {$attrib_string}";
		}

		$xml_data = "<{$element_name}{$attrib_string}>";

		if ( $character_data ) {
			if ( $this->_Newline_before_character_data ) { 
				$xml_data .= "\n";
			}

			$xml_data .= $character_data;

			if ( $this->_Newline_after_character_data ) { 
				$xml_data .= "\n";
			}

		}
		else {
			if ( $this->_Newline_between_elements ) { 
				$xml_data .= "\n";
			}
		}

		$children = $this->get_child_elements();

		if ( is_array($children) && (count($children) > 0) ) {
			foreach ( $children as $cur_child ) {
				$cur_child->set_newline_between_elements( $this->_Newline_between_elements );
				$cur_child->set_newline_before_character_data( $this->_Newline_before_character_data );
				$cur_child->set_newline_after_character_data( $this->_Newline_after_character_data );
				$xml_data .= $cur_child->generate_xml_data();
			}
		}

		$xml_data .= "</{$element_name}>";

		if ( $this->_Newline_between_elements ) { 
			$xml_data .= "\n";
		}

		return $xml_data;
	}

	function generate_attrib_string() {

		$attribs = $this->get_attribs();

		$attrib_string = '';

		if ( is_array($attribs) && (count($attribs) > 0) ) {

			foreach( $attribs as $key => $data ) {

				$attrib_string .= "{$key}=\"{$data}\" ";

			}

			$attrib_string = trim($attrib_string);

		}

		return $attrib_string;

	}

	function get_child_character_data_by_element_name( $name ) {

		$children_by_name = $this->get_child_elements_by_name();

		if ( isset($children_by_name[$name]) ) {
			$cur_obj = $children_by_name[$name];
			return $cur_obj->get_character_data();
		}

		return null;

	}

	function get_child_by_name( $find_name ) {

		try { 
			
			$children = $this->get_child_elements();
			$ret = array(); 
			$indexes = array();
	
			if ( is_array($children) && (count($children) > 0) ) {
	
				foreach ( $children as $cur_child ) {
	
					$cur_name = $cur_child->get_name();
	
					if ( $cur_name ) {
							
						if ( ($find_name == $cur_name) ) {
							
							$ret[] = $cur_child;
							
						}
					}
				}
			}

			if ( count($ret) == 1 ) {

				//
				// return a single object 
				// rather than an array
				// if there's only one instance of this child
				//
				$ret = $ret[0]; 
			}

			return $ret;	
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function set_newline_between_elements( $truefalse ) {

		if ( $truefalse ) {
			$this->_Newline_between_elements = true;
		}
		else {
			$this->_Newline_between_elements = false;
		}

	}

	function set_newline_before_character_data( $truefalse ) {

		if ( $truefalse ) {
			$this->_Newline_before_character_data = true;
		}
		else {
			$this->_Newline_before_character_data = false;
		}

	}

	function set_newline_after_character_data( $truefalse ) {

		if ( $truefalse ) {
			$this->_Newline_after_character_data = true;
		}
		else {
			$this->_Newline_after_character_data = false;
		}

	}

}

class XMLTopLevelElement extends XMLElement {

	
	function is_top_level_element() {

		return true;
	}

}


?>
