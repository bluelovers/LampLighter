<?php

LL::Require_class('PaymentProcessing/PayPal/PayPalAPIController');

class PayPalDirectPayment extends PayPalAPIController {

	const KEY_GET_REQUEST_DETAILS_OBJ = 'request_details';
	
	const KEY_SET_ORDER_TOTAL = 'order_total';
	const KEY_PAYMENT_ACTION = 'payment_action';
	
	public $request_type_name = 'DoDirectPaymentRequestType';
	
	protected $_Txn_successful;
	protected $_Request_details_obj;
	
	protected $_Payment_action;
	
	public function __get( $property ) {
		
		if ( $property == self::KEY_GET_REQUEST_DETAILS_OBJ ) {
			return $this->get_request_details_obj();
		}
		else if ( $property == self::KEY_PAYMENT_ACTION ) {
			return $this->_Payment_action;
		}
		
		return parent::__get($property);
		
	}

	public function __set( $property, $val ) {
		
		if ( $property == self::KEY_SET_ORDER_TOTAL ) {
			$this->set_order_total( $val );
		}
		else if ( $property == self::KEY_PAYMENT_ACTION ) {
			$this->set_payment_action( $val );
		}
		else {		
			return parent::__set( $property, $val );
		}
		
	}	
	
	public function set_payment_action( $action ) {
		
		try { 
			$this->_Payment_action = $action;
			$this->request_details->setPaymentAction($action);
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}

	public function get_request_details_obj() {
	
		try {
			if ( !$this->_Request_details_obj ) {
				
				$this->initialize_api_director();
    			$this->_Request_details_obj = PayPal::getType('DoDirectPaymentRequestDetailsType');
				$this->_Request_details_obj->setCreditCard($this->cc_details );
				$this->_Request_details_obj->setIPAddress($_SERVER['SERVER_ADDR']);
				$this->_Request_details_obj->setPaymentDetails($this->payment_details);
			
			}
			
			
			
			return $this->_Request_details_obj;
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
		

   	public function process_payment() {
    	
    	try {
	    	
	    	$this->response_obj 	  = null;
    		$this->_Txn_successful = false;

			if ( !$this->payment_action ) {
    			throw new Exception( __CLASS__ . '-no_payment_action_specified' );
    		}

			$request  = $this->get_request_obj();
    		$request->setDoDirectPaymentRequestDetails($this->request_details);
    		
    		$director = $this->get_api_director();
    		$caller   = $director->get_caller();
    		
    		$this->response_obj = $caller->DoDirectPayment($request);
    		
    		if ( is_a($this->response_obj, 'PEAR_Error') || is_subclass_of($this->response_obj, 'PEAR_Error') ) {
    			throw new Exception( $this->response_obj->message );
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

    public function txn_successful() {
    	
    	return $this->_Txn_successful;
    }    
	
}
?>