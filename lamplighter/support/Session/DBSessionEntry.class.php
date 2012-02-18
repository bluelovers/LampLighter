<?php

LL::Require_class('Data/DataModel');

class DBSessionEntry extends DataModel {

    protected function _Init() {
    	
    	$this->belongs_to( 'Session/DBSession', array('table' => 'sessions') );
    	
    }
}
?>