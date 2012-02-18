<?php

class USPSWebTools {

	var $_TL_Element_name_city_state_lookup_request  = 'CityStateLookupRequest';
	var $_TL_Element_name_city_state_lookup_response = 'CityStateLookupResponse';

	var $_Use_test_settings = false;

	var $_Request_host_production;
	var $_Request_uri_production;

	var $_Request_host_test = 'testing.shippingapis.com';
	var $_Request_uri_test  = '/ShippingAPITest.dll';
	
	var $_Element_name_zip5     = 'Zip5';
	var $_Element_name_zip_code = 'ZipCode';

	var $_Attribe_key_id      = 'ID';
	var $_Attrib_key_user_id  = 'USERID';
	var $_Attrib_key_password = 'PASSWORD';

	var $_API_key_city_state_lookup = 'CityStateLookup';

	var $_Userid;
	var $_User_password;


	var $_XML_obj;
	var $_Connector_obj;

	function set_xml_obj( &$obj ) {
		
		$this->_XML_obj =& $obj;
	}

	function &get_connector_obj() {

		if ( !$this->_Connector_obj ) {

			LL::Require_class('WWW/WebConnector');

			$this->_Connector_obj =& new WebConnector();
		}

		return $this->_Connector_obj;

	}

	function &get_active_xml_obj() {

		return $this->get_xml_obj( false );

	}

	function &get_new_xml_obj() {

		return $this->get_xml_obj( true );

	}

	function &get_xml_obj( $force_new = false ) {

		try { 
			static $was_called;
	
			if ( !$this->_XML_obj || $force_new ) {
	
				if ( !$was_called ) {
					LL::Require_class('XML/XMLParser');
					$was_called = true;
				}
	
				$this->_XML_obj =& new XMLParser();
				$this->_XML_obj->set_xml_parser_option( XML_OPTION_CASE_FOLDING, false );
			}
	
			return $this->_XML_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	function set_userid( $id ) {

		$this->_Userid = $id;
	}

	function get_userid() {

		return $this->_Userid;
	}

	function set_user_password( $password ) {

		$this->_User_password = $password;
	}

	function get_user_password() {

		return $this->_User_password;
	}

	function city_state_lookup_by_5_digit_zip_code( $zip_code ){ 

		try {
			$xml_obj = $this->get_new_xml_obj();
	
			$tle = $xml_obj->start_top_level_element( $this->_TL_Element_name_city_state_lookup_request );
	
			$userid   = $this->get_userid();
			$password = $this->get_user_password();
	
			if ( $userid ) {
				$tle->add_attrib( $this->_Attrib_key_user_id, $userid );
			}
	
			if ( $password ) {
				$tle->add_attrib( $this->_Attrib_key_password, $password );
			}
	
			$xml_obj->add_element_by_name( $this->_Element_name_zip_code );
			$xml_obj->add_element_by_name( $this->_Element_name_zip5, null, $zip_code );
			$xml_obj->close_active_top_level_element();
	
			$request_data = $xml_obj->generate_xml_data_from_dataset();
	
			$xml_response = $this->send_xml_request($this->_API_key_city_state_lookup, $request_data);
	
			if ( !$xml_response ) {
				return false;
			}
	
			$xml_obj = $this->get_new_xml_obj();
			//$xml_obj->set_top_level_element_name($this->_TL_Element_name_city_state_lookup_response);
			$xml_obj->parse_xml_data( $xml_response );
		
			$top_level_element = $xml_obj->get_top_level_dataset_obj();
			$zip_code_element  = $top_level_element->get_child_element_by_name('ZipCode');
			$city_element	   = $zip_code_element->get_child_element_by_name('City');
			$state_element	   = $zip_code_element->get_child_element_by_name('State');
			
			/*
			echo 'CITY ELEMENT NAME: ' . $city_element->get_name(); echo "\n";
			echo 'CITY ELEMENT DATA: ' . $city_element->get_character_data(); echo "\n";
	
			echo 'STATE ELEMENT NAME: ' . $state_element->get_name(); echo "\n";
			echo 'STATE ELEMENT DATA: ' . $state_element->get_character_data(); echo "\n";
			*/
	
			$state_abbr = $state_element->get_character_data();
			$city_name  = $city_element->get_character_data();
	
			return array( $city_name, $state_abbr );
		}
		catch( Exception $e ) {
			throw $e;
		}
	

	}

	function send_xml_request( $request_api, $request_data ) {

		try { 
			$request_data = str_replace("\n", '', $request_data);
			$request_data = str_replace("\r", '', $request_data);
	
			if ( !$request_data ) {
				throw new MissingParameterException ( $request_data );
			}
	
			$connector = $this->get_connector_obj();
	
			$host = $this->get_request_host();
			$uri  = $this->get_request_uri();
	
			$qs_lead = get_query_string_leader($uri);
	
			$url_request_data = urlencode($request_data);
			$url_request_api  = urlencode($request_api);
	
			$uri .= $qs_lead . "API={$url_request_api}&XML={$url_request_data}";
	
			//echo $host . $uri; echo "\n";
	
			$connector->set_remote_host( $host );
			
			$request_retval = $connector->send_http_get_request( $uri );
			$request_retval = $connector->strip_http_header($request_retval);
	
			return $request_retval;
		}
		catch( Exception $e ) {
			throw $e;
		} 
		
		
	}

	function get_request_host() {

		if ( $this->get_use_test_settings() ) {
			return $this->_Request_host_test;
		}
		else {
			return $this->_Request_host_production;
		}
	}

	function get_request_uri() {

		if ( $this->get_use_test_settings() ) {
			return $this->_Request_uri_test;
		}
		else {
			return $this->_Request_uri_production;
		}
	}

	function get_use_test_settings() {

		return $this->_Use_test_settings;

	}

	function set_use_test_settings( $truefalse ) {

		if ( $truefalse ) {
			$this->_Use_test_settings = true;
		}
		else {
			$this->_Use_test_settings = false;
		}
	}
	
}

?>
