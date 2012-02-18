<?php

LL::require_class('PaymentProcessing/PayPal/PayPalAPIDirector');

class PayPalRefund {

	const REFUND_PARTIAL = 'Partial';
	const REFUND_FULL    = 'Full';

	public $txn_id;
	public $amount;
	public $type;
	public $charset = 'iso-8859-1';
	public $currency_id = 'USD';
	
	public $response_obj;
	
	protected $_API_director;
	protected $_Txn_successful = false;

    function __construct() {
    
    	
    	
    }
    
    public function __get( $property ) {
    	
    	if ( $property == 'api_director' ) {
    		return $this->get_api_director();
    	}	

    }
    
    public function get_request_obj() {
    	
    	if ( !$this->_Request_obj ) {
    		
    		$director = $this->get_api_director();
    		
    		$director->require_request_base();
    				
    		$this->_Request_obj = PayPal::getType('RefundTransactionRequestType');
    		
    		if ( PayPal::isError($this->_Request_obj) ) {
   				throw new Exception( 'Couldn\'t create request object', $this->_Request_obj->getMessage() );
    		}
    	}
    	
    	return $this->_Request_obj;
    	
    }
    
    public function set_api_director( $director ) {
    	
    	$this->_API_director = $director;
    }
    
    public function get_api_director() {
    
    	if ( !$this->_API_director ) {
    		
    		$this->_API_director = new PayPalAPIDirector();
    		
    	}
    	
    	return $this->_API_director;
    	
    }
    
    public function process_refund() {
    	
    	$this->response_obj 	  = null;
    	$this->_Txn_successful = false;
    	
    	if ( !$this->type ) {
    		throw new Exception( __CLASS__ . '-no_refund_type_specified' );
    	}
    	
    	if ( $this->type == self::REFUND_PARTIAL ) {
    		if ( $this->amount === null ) {
    			throw new Exception (__CLASS__ . 'missing_refund_amount');
    		}	
    	}
    	    	
    	if ( !$this->txn_id ) {
    		throw new Exception (__CLASS__ . 'missing_txn_id');
    	}	
    	
    	try {
    		$request = $this->get_request_obj();
    		$request->setTransactionId( $this->txn_id, $this->charset );
    		$request->setRefundType($this->type);
    		
    		if ( $this->type == self::REFUND_PARTIAL && $this->amount !== null ) {
    			$amount_obj = $this->get_amount_obj();
           		$request->setAmount($amount_obj);
    		}
    		
    		$director = $this->get_api_director();
    		$caller   = $director->get_caller();
    		
    		$this->response_obj = $caller->RefundTransaction($request);
    		
    		if ( is_a($this->response_obj, 'PEAR_Error') || is_subclass_of($this->response_obj, 'PEAR_Error') ) {
    			throw new Exception( __CLASS__ . 'txn_error', $this->response_obj->message );
    		}
    		
    		$ack = $this->response_obj->getAck();
    		
    		if ( $ack == PayPalAPIDirector::ACK_SUCCESS || $ack == PayPalAPIDirector::ACK_SUCCESS_WITH_WARNING ) {
				$this->_Txn_successful = true;    			
    		}
    		else {
	    		$this->_Txn_successful = false;
    		}
    		
    		return $this->response_obj;
    		
    		
    	}
    	catch( Exception $e ) {
    		throw $e;
    	}
    	
    
    }
    
    public function handle_response_obj() {
    
    	if ( $this->response_obj ) {
    		
    	}
    	
    }
    
    public function txn_successful() {
    	
    	return $this->_Txn_successful;
    }
    
    public function get_amount_obj() {
    	
    	$amount_obj =& PayPal::getType('BasicAmountType');
        $amount_obj->setattr('currencyID', $this->currency_id);
        $amount_obj->setval($this->amount, $this->charset);
         
        return $amount_obj;
    }
    
}
?>