<?php

abstract class PayPalAPIController {

	const KEY_GET_AMOUNT = 'amount';
    const KEY_GET_API_DIRECTOR = 'api_director';
	const KEY_GET_PAYMENT_DETAILS = 'payment_details';
    const KEY_GET_CC_DETAILS = 'cc_details';
    const KEY_GET_ADDRESS_MANAGER = 'addresses';
	const KEY_GET_PAYER = 'payer';

	const KEY_SET_AMOUNT = 'amount';
    
    public $response_obj;
    
	public $charset = 'iso-8859-1';
	public $currency_id = 'USD';
	
	protected $_API_director;
	protected $_Request_obj;
	protected $_Payment_details_obj;
	protected $_CC_details_obj;
	protected $_Address_manager_obj;
	protected $_Amount_obj;
	protected $_Payer_wrapper;

	protected $_Amount;

	public function __construct() {
		
		$this->initialize_api_director();
		
	}

	public function get_api_director() {
		
		try { 
			return $this->initialize_api_director();
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
    

    public function initialize_api_director() {
       
       try { 
    		if ( !$this->_API_director ) {
    		
    			LL::Require_class('PaymentProcessing/PayPal/PayPalAPIDirector');
    		
    			$this->_API_director = new PayPalAPIDirector();
    		
    		}
    	
    		return $this->_API_director;
       }
       catch( Exception $e ) {
       		throw $e;
       }
    	
    }
    
    public function __set( $property, $val ) {
    	
    	if ( $property == self::KEY_SET_AMOUNT ) {
    		$this->set_amount( $val );
    	}
		else {
			trigger_error( "Invalid property: {$property}", E_USER_ERROR );
			exit;
		}

    	
    }
    
    public function __get( $property ) {
    	
    	switch( $property ) {
    		
    		case self::KEY_GET_API_DIRECTOR:
    			return $this->get_api_director();
    			break;
    		case self::KEY_GET_AMOUNT:
    			return $this->_Amount;
    			break;
    		case self::KEY_GET_PAYMENT_DETAILS:
    			return $this->get_payment_details_obj();
    			break;
    		case self::KEY_GET_CC_DETAILS:
    			return $this->get_cc_details_obj();
    			break;
    		case self::KEY_GET_ADDRESS_MANAGER:
    			return $this->get_address_manager_obj();
    			break;
			case self::KEY_GET_PAYER:
    			return $this->get_payer_wrapper();
    			break;    			
    	}
    	
    	trigger_error( 'Invalid property name: ' . $property, E_USER_ERROR );
    	exit;
    }
    
    public function set_api_director( $director ) {
    	
    	$this->_API_director = $director;
    }
    
   public function get_request_obj() {
    	
    	if ( !$this->_Request_obj ) {
    		
    		$director = $this->get_api_director();
    		
    		$this->_Request_obj = PayPal::getType($this->request_type_name);
    		
    		if ( PayPal::isError($this->_Request_obj) ) {
   				throw new Exception( 'Couldn\'t create request object', $this->_Request_obj->getMessage() );
    		}
    	}
    	
    	return $this->_Request_obj;
    	
    }

	public function set_order_total( $total ) {
		
		try { 
			return $this->set_amount( $total );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function set_amount( $amount ) {
		
		try { 
			$this->_Amount = $amount;
        
    	    $amount_obj = $this->get_amount_obj();
        	$amount_obj->setval($this->_Amount, $this->charset);

		}
		catch( Exception $e ) {
			throw $e;
		}

		
	}

    public function get_amount_obj() {
    	
    	if ( !$this->_Amount_obj ) {
    		$this->_Amount_obj =& PayPal::getType('BasicAmountType');
	       	$this->_Amount_obj->setattr('currencyID', $this->currency_id);
    	   	$this->_Amount_obj->setval($this->_Amount, $this->charset);

    	}


    	
        return $this->_Amount_obj;
    }
    
    public function get_payment_details_obj() {
    	
    	if ( !$this->_Payment_details_obj ) {
    		
    		$this->initialize_api_director();
    		$this->_Payment_details_obj =& PayPal::getType('PaymentDetailsType');
	   		$this->_Payment_details_obj->setOrderTotal($this->get_amount_obj());
   			$this->_Payment_details_obj->setShipToAddress($this->addresses->ship_to->get_real_address_obj());
    	}


    	
    	return $this->_Payment_details_obj;
    	
    }

    public function get_cc_details_obj() {
    	
    	
    	if ( !$this->_CC_details_obj ) {
    		$this->initialize_api_director();
    	
    		$this->_CC_details_obj =& PayPal::getType('CreditCardDetailsType');
    		$this->_CC_details_obj->setCardOwner($this->get_payer_wrapper()->get_real_payer_obj());
    	
    	}
    	
    	return $this->_CC_details_obj;
    	
    }
    
    public function get_address_manager_obj() {
    	
    	try { 
    		if ( !$this->_Address_manager_obj ) {
    			
    			$this->_Address_manager_obj = new PayPalPaymentAddressManager();
    			
    		}
	
			return $this->_Address_manager_obj;    		
    	}
    	catch( Exception $e) {
    		throw $e;
    	}
    }

    public function get_payer_wrapper() {
    	
    	try { 
    		if ( !$this->_Payer_wrapper ) {
    			
    			$this->_Payer_wrapper = new PayPalPayerInfoWrapper();
  		  		$real_payer = $this->_Payer_wrapper->get_real_payer_obj();
    			$real_payer->setAddress($this->addresses->bill_to->get_real_address_obj());
    			
    		}

  
			return $this->_Payer_wrapper;    		
    	}
    	catch( Exception $e) {
    		throw $e;
    	}
    }

        
}

class PayPalPaymentAddressManager {
	
	const KEY_GET_ADDRESS_SHIP_TO = 'ship_to';
	const KEY_GET_ADDRESS_BILL_TO = 'bill_to';
	
	protected $_Address_obj_ship_to;
	protected $_Address_obj_bill_to;

	public function __get( $property ) {
		
		switch( $property ) {
			case self::KEY_GET_ADDRESS_SHIP_TO:
				return $this->get_address_obj_ship_to();
				break;
			case self::KEY_GET_ADDRESS_BILL_TO:
				return $this->get_address_obj_bill_to();
				break;
		}

		return null;		
	}
	
	public function get_address_obj_ship_to() {
		
		try {
			if ( !$this->_Address_obj_ship_to ) {
				$this->_Address_obj_ship_to = new PayPalPayerAddressWrapper;
			}
			
			return $this->_Address_obj_ship_to;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	public function get_address_obj_bill_to() {
		
		try {
			if ( !$this->_Address_obj_bill_to ) {
				$this->_Address_obj_bill_to = new PayPalPayerAddressWrapper;
			}
			
			return $this->_Address_obj_bill_to;
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	

	
}

class PayPalPayerAddressWrapper {
	
	const KEY_MAP_ARRAY = 'map';
	
	protected $_Real_address_obj;
	protected $_Real_address_reflector;

	public $address_setter_keys = array( 
				'Name', 
				'Street1', 
				'Street2', 
				'CityName', 
				'StateOrProvince', 
				'Country', 
				'PostalCode' );
	
	public function __call( $method, $params ) {
		
		$reflector = $this->get_real_address_reflector();
		$real_address_obj = $this->get_real_address_obj();
		
		if ( $reflector->hasMethod($method) ) {
			return call_user_func_array( array($real_address_obj, $method), $params );
		} 
			
		
	}

	public function get_real_address_reflector() {
		
		try { 
			if ( !$this->_Real_address_reflector ) {
			
				$this->_Real_address_reflector = new ReflectionObject($this->get_real_address_obj());
			
			}
			
			return $this->_Real_address_reflector;
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}	

	
	public function get_real_address_obj() {
		
		if ( !$this->_Real_address_obj ) {
			$this->_Real_address_obj = PayPal::getType('AddressType');
		}
		
		return $this->_Real_address_obj;
		
	}	
	
	public function apply_associative_array( $arr, $options = null ) {

		try { 
			
			$map_array = array();
			$reflector = $this->get_real_address_reflector();
			$real_address_obj = $this->get_real_address_obj();					
					
			if ( isset($options[self::KEY_MAP_ARRAY]) ) {
				$map_array = $options[self::KEY_MAP_ARRAY];
			}
		
			foreach( $this->address_setter_keys as $cur_key ) {

				if( isset($map_array[$cur_key]) ) {
					$real_key = $cur_key;
					$arr_key  = $map_array[$cur_key];
				}
				else {
					$real_key = $arr_key = $cur_key;
				}			
			
				if ( isset($arr[$arr_key]) ) {
					$method_name = 'set' . $real_key;
				
					if ( !$reflector->hasMethod($method_name) ) {
						throw new Exception( __CLASS__ . "-invalid_address_key: %{$cur_key}%" );
					}
					
					$real_address_obj->$method_name( $arr[$arr_key] );
				}
			
			}
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
	
	
}

class PayPalPayerInfoWrapper {
	
	const KEY_GET_NAME_OBJ = 'name';
	
	protected $_Real_payer_obj;
	protected $_Real_payer_reflector;
	
	protected $_Name_wrapper;
	
	public function __construct() {
		
		$this->setPayerName($this->get_name_wrapper()->get_real_name_obj());
		
	}
	
	public function __call( $method, $params ) {
		
		$reflector = $this->get_real_payer_reflector();
		$real_payer_obj = $this->get_real_payer_obj();
		
		if ( $reflector->hasMethod($method) ) {
			return call_user_func_array( array($real_payer_obj, $method), $params );
		} 
			
		
	}
	
	public function __get( $property ) {
		
		if ( $property == self::KEY_GET_NAME_OBJ ) {
			return $this->get_name_wrapper();
		}

		trigger_error( 'Invalid property name: ' . $property, E_USER_ERROR );
    	exit;		
		
	}

	public function get_real_payer_reflector() {
		
		try { 
			if ( !$this->_Real_payer_reflector ) {
			
				$this->_Real_payer_reflector = new ReflectionObject($this->get_real_payer_obj());
			
			}
			
			return $this->_Real_payer_reflector;
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}	
	
	public function get_real_payer_obj() {

		if ( !$this->_Real_payer_obj ) {
			
			$this->_Real_payer_obj = PayPal::getType('PayerInfoType');
			
		}
	
		return $this->_Real_payer_obj;
		
		
	}
	
	public function get_name_wrapper() {
		
		try {

			if ( !$this->_Name_wrapper ) {
				$this->_Name_wrapper = new PayPalPayerNameWrapper;
				$this->get_real_payer_obj()->setPayerName($this->_Name_wrapper->get_real_name_obj());
			}
			
			return $this->_Name_wrapper;
			
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	

	
}

class PayPalPayerNameWrapper {

	protected $_Real_name_obj;
	protected $_Real_name_reflector;

	public function __call( $method, $params ) {
		
		$reflector = $this->get_real_name_reflector();
		$real_name_obj = $this->get_real_name_obj();
		
		if ( $reflector->hasMethod($method) ) {
			return call_user_func_array( array($real_name_obj, $method), $params );
		} 
		else {
			trigger_error( 'Invalid method: ' . $method, E_USER_ERROR );
			exit;
		}
		
	}

	public function get_real_name_reflector() {
		
		try { 
			if ( !$this->_Real_name_reflector ) {
			
				$this->_Real_name_reflector = new ReflectionObject($this->get_real_name_obj());
			
			}
			
			return $this->_Real_name_reflector;
		}
		catch( Exception $e ) {
			throw $e;
		}		
	}	

	public function get_real_name_obj() {
		
		if ( !$this->_Real_name_obj ) {
			
			$this->_Real_name_obj = PayPal::getType('PersonNameType');
			
		}
	
		return $this->_Real_name_obj;
		
	}	

	
	
}

?>
