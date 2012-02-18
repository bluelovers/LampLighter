<?php

LL::Require_class('Data/DataModel');

class SOARecord extends DataModel {

	protected $_Table_name = 'dns_soa_records';
	protected $_Prefix_db_field_name = 'soa_record_';
	protected $_String_format_obj;

	public function _Init() {
		
		$this->belongs_to('DNS/DNSZone');	
	}

	public function set_string_format_obj( $obj ) {
		
		$this->_String_format_obj = $obj;
		
	}
	
	public function get_string_format_obj() {
		
		return $this->_String_format_obj;
		
	} 
	
	public function format_record() {
		
		LL::require_class('DNS/RDataHelper');
		
		$this->mname = RDataHelper::append_trailing_dot($this->mname);
		$this->rname = RDataHelper::append_trailing_dot($this->rname);
		
		$soa_data = '';
		
		$soa_data .= "IN\tSOA\t{$this->mname}\t{$this->rname} (\n";
		$soa_data .= "\t\t{$this->serial}\n";
		$soa_data .= "\t\t{$this->refresh}\n";
		$soa_data .= "\t\t{$this->retry}\n";
		$soa_data .= "\t\t{$this->expire}\n";
		$soa_data .= "\t\t{$this->minimum}\n";
		$soa_data .= "\t\t)\n";
		
		if ( $formatter = $this->get_string_format_obj() ) {
			$soa_data = $formatter->parse($soa_data);
		}
		
		return $soa_data;
		
	}
	
	
}


?>
