<?php

if ( !class_exists('DNSRecord') ) {

LL::Require_class('Data/DataModel');

class DNSRecord extends DataModel {

	var $_Library = 'DNS';
	var $_Table_name = 'dns_records';
	var $_Form_input_key = 'dns_record';

	var $_Prefix_db_field_name='dns_record_';
	
	var $_String_format_obj;
	
	function _Init() {

		$this->validates_presence_of( 'type_key', array('input_type' => constant('FORM_INPUT_TYPE_LISTBOX'), 
								'friendly_name' => 'a record type') 
					    );

		$this->validates_presence_of( 'rdata' );

		$this->validates_format_of( 'rdata', array( 
							'with' => '/^[A-Za-z0-9\.\-_]+$/'
						     )
					  );
		
		
		$this->belongs_to('DNS/DNSZone', array('foreign_key' => 'zone_id') );
		
		$this->belongs_to('DNS/DNSRecordType', array('foreign_key' => 'type_key') );

		
		//$this->has_one('SOARecord', array('foreign_key' => ) );
		$this->set_friendly_name( 'rdata', 'the DNS record data' );
		
		
	}

	public function set_string_format_obj( $obj ) {
		
		$this->_String_format_obj = $obj;
		
	}
	
	public function get_string_format_obj() {
		
		return $this->_String_format_obj;
		
	} 

	public static function Append_trailing_dot_if_required( $val ) {
		
		if ( substr($val, -1) != '.' ) {
			$val .= '.';
		}

		return $val;
		
	}

	public static function Strip_trailing_dot( $val ) {
		
		if ( substr($val, -1) == '.' ) {
			$val = substr($val, 0, -1);
		}

		return $val;
		
	}

	public function format_record_line() {

		try {
			
			if ( !$this->validate_record() ) {
				throw new Exception ( __CLASS__ . '-record_validation_failed' );
			}
			
			$domain_name = self::Append_trailing_dot_if_required($this->domain_name);
			$rdata 		 = self::Append_trailing_dot_if_required($this->rdata);

			$line = "{$domain_name} {$this->ttl} {$this->class_type_key} {$this->type_key} {$rdata}";		
		
			$line = $this->expand_format_string($line);
			
			return $line;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function expand_format_string( $str ) {
		
		if ( $formatter = $this->get_string_format_obj() ) {
			$str = $formatter->parse($str);
		}
		
		return $str;
		
	}

	public function validate_record() {
		
		try {
			
			LL::require_class('Inet/InetValidate');
			
			$valid	     = true;
			$domain_name = $this->expand_format_string(self::Strip_trailing_dot($this->domain_name));
			$rdata		 = $this->expand_format_string($this->rdata);
			
			if ( !$domain_name || !InetValidate::Domain_name_is_sane($domain_name) ) {
				LL::raise_user_error( "InetValidate-invalid_domain_name %{$domain_name}%" );
				$valid = false;
			}

			if ( !$rdata || !self::Rdata_is_valid($rdata) ) {
				LL::raise_user_error( __CLASS__ . "-invalid_rdata %{$domain_name}%");
				$valid = false;
			}
			
			if ( !$this->type_key || !is_valid_index_key($this->type_key) ) {
				LL::raise_user_error( __CLASS__ . "-invalid_type_key %{$this->type_key}%");
				$valid = false;
			}

			if ( !$this->class_type_key || !is_valid_index_key($this->class_type_key) ) {
				LL::raise_user_error( __CLASS__ . "-invalid_class_type_key %{$this->class_type_key}%");
				$valid = false;
			}
			
			//
			// TTL can be blank, "&&" is correct below.
			//
			if ( $this->ttl && !self::TTL_is_valid($this->ttl) ) {
				LL::raise_user_error( "InetValidate-invalid_domain_name %{$this->domain_name}%");
				$valid = false;
			}
			
			if ( $valid ) {
				return true;
			}
			
			return 0;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public static function TTL_is_valid( $ttl ) {
		
		return is_numeric($ttl);
		
	} 
	
	public static function Rdata_is_valid( $rdata ) {
		
		try { 
			
			LL::require_class('Inet/InetValidate');
			
			$rdata = self::Strip_trailing_dot($rdata);
			
			if ( !$rdata || !InetValidate::Domain_name_is_sane($rdata) ) {
				return 0;
			}
			
			return true;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	

}

}

?>
