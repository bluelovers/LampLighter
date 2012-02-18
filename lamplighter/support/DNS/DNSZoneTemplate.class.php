<?php

LL::Require_class('Data/DataModel');

class DNSZoneTemplate extends DataModel {

	const KEY_DEFAULT_TEMPLATE = 'default';

	protected $_Table_name = 'dns_zone_templates';
	protected $_Prefix_db_field_name = 'zone_template_';

	protected $_String_format_obj;

	public function _Init() {
		
		$this->has_unique_key('key');
		$this->has_one('DNS/DNSZone');
	}

	public function set_string_format_obj( $obj ) {
		
		$this->_String_format_obj = $obj;
		
	}
	
	public function get_string_format_obj() {
		
		return $this->_String_format_obj;
		
	} 

	public function get_zone_data() {
		
		try {
			
			$zone_data = '';
			
			if ( !$this->dns_zone->id ) {
				throw new Exception ( 'general-missing_id', "\$this->dns_zone->id");
			}
			
			$record_iterator = $this->dns_zone->dns_records->fetch_all( array( 'include' => 'dns_record_types',
																			   'order_by' => 'dns_record_types.dns_record_type_order'));
			
			$this->dns_zone->soa_record->set_string_format_obj($this->get_string_format_obj());
			$zone_data .= $this->dns_zone->soa_record->format_record();
			$zone_data .= "\n";
			
			while ( $record = $record_iterator->next() ) {
				$record->set_string_format_obj($this->get_string_format_obj());
				$zone_data .= $record->format_record_line() . "\n";
			}

			return $zone_data;
						
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	
}
?>