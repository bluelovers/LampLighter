<?php

if ( !class_exists('DNSZone') ) {

LL::Require_class('Data/DataModel');

class DNSZone extends DataModel {
     
    var $_Prefix_db_field_name='dns_zone_';

	function _Init() {
		
		
		$this->has_one( 'DNS/SOARecord', array('table' => 'dns_soa_records') );
		$this->has_many( 'DNS/DNSRecord' );
		
		$this->belongs_to('DNS/DNSZoneTemplate');
		
		$this->add_table_reference_alias('soa_record', 'dns_soa_records');
	}

	
}

}

?>
