<?php

if ( !class_exists('DNSRecordType') ) {

LL::Require_class('Data/DataModel');

class DNSRecordType extends DataModel {
	
	var $_Library = 'DNS';
	var $_Prefix_db_field_name='dns_record_type_';
	var $_Table_name = 'dns_record_types';
	
	var $_DB_field_type_key = 'dns_record_type_key';

	function _Init() {

		$this->order_by_default( 'dns_record_type_order ASC' );

	}
}

}

?>
