<?php


if ( !defined('FORM_MAILER') ) {

define('FORM_MAILER', 1);

LL::Require_class('Form/InputFormValidator');

class InputFormMailer extends InputFormValidator {

        var $mail_prepend;
        var $mail_append;
        var $mail_values;
        var $mail_recipient;
        var $mail_sender;
        var $mail_headers;
        var $mail_reply_to;
        var $mail_subject;
        var $mail_subject_default;
		
        var $ignore_mail_inputs;
		var $mail_inputs = array();
		
		var $friendly_name_ucfirst = true;
		

	function InputFormMailer( $form_name = '' ) {

		$this->init_InputForm($form_name);
		$this->init_InputFormMailer();		
		return true;

	}

	function init_InputFormMailer() {

		$this->mail_subject_default    = "The following was submitted through {$_SERVER['SCRIPT_NAME']}:\n";

		$this->mail_append	       = ( defined('FORM_MAIL_APPEND') ) ? constant('FORM_MAIL_APPEND') : $this->mail_append;
		$this->mail_prepend	       = ( defined('FORM_MAIL_PREPEND') ) ? constant('FORM_MAIL_PREPEND') : $this->mail_prepend;
                $this->mail_subject_default    = ( defined('FORM_MAIL_SUBJECT_DEFAULT') ) ? constant('FORM_MAIL_SUBJECT_DEFAULT') : $this->mail_subject_default;

		$this->mail_inputs = array();
	        $this->ignore_mail_inputs = array();
        	$this->mail_values = array();
	}



	function add_mail_input( $input_name ) {

		$this->mail_inputs[] = array( $input_name => $this->get_value($input_name) );

	}

	function add_mail_field( $input_name ) {
	
		//Alias to add_mail_input

		return $this->add_mail_input( $input_name );
	}

	function set_mail_prepend( $value ) {

		$this->mail_prepend = $value;
		return true;

	}

	function set_mail_append( $value ) {
	
		$this->mail_append = $value;
		return true;
	}

	function mail_append( $value ) {

		return $this->set_mail_append($value);
	}

	function mail_prepend( $value ) {
	
		return $this->set_mail_prepend($value);
	}
	
	function set_mail_ignore( $input_name ) {

		$this->ignore_mail_inputs[] = $input_name;
		return true;
	}

	function unset_mail_ignore( $input_name ) {

		if ( count($this->ignore_mail_inputs) ) {
			$count = 0;
			for ( $j=0; $j <$count; $j++ ) {
				if ( $this->ignore_mail_inputs[$j] == $input_name ) {
					$this->ignore_mail_inputs[$j] = '';
				}
			}
		}

		return true;	
				
	}

	function ignore_mail_input( $input_name ) {

		return $this->set_mail_ignore($input_name);
	}

	function unignore_mail_input( $input_name ) {
		return $this->unset_mail_ignore( $input_name );
	}

	function set_global_mail_ignore() {

		if ( defined('FORM_IGNORE_MAIL_INPUT') ) {
			
			$ignore_value = constant('FORM_IGNORE_MAIL_INPUT');
			$ignore_value = preg_replace('/\s/', '', $ignore_value);

			$ignore_array = explode( ',', $ignore_value );
			if ( count($ignore_array) ) {
				foreach( $ignore_array as $cur_input_name ) {
					$this->ignore_mail_input($cur_input_name);
				}
			}
		}

		return true;

	}
	

	function set_mail_input( $input_name, $mail_value ) {

		return $this->set_mail_value($input_name, $mail_value);

	}

	function set_mail_value( $input_name, $mail_value ) {

		$this->mail_values[$input_name] = $mail_value;
	
		return true;

	}

	public function generate_mail_message( $options = array() ) {
		
		
        $mail_prepend = ( isset($options['mail_prepend']) ) ? $options['mail_prepend'] : $this->mail_prepend;
        $mail_append  = ( isset($options['mail_append']) )  ? $options['mail_append']  : $this->mail_append;
			

		$this->set_global_mail_ignore();
	
		$input_array = $this->get_dataset();
		$mail_message = $mail_prepend;
	
		if ( count($input_array) ) {
			foreach( $input_array as $input_key => $input_val ) {

				if ( in_array($input_key, $this->ignore_mail_inputs) ) {
					continue;
				}

				if ( count($this->mail_inputs) > 0 ) {
					if ( !in_array($input_key, $this->mail_inputs) ) {
						continue;
					}
				}

				if ( isset($this->mail_values[$input_key]) && $this->mail_values[$input_key] ) {
					$input_val = $this->mail_values[$input_key];
				}
				else {
					$input_val = $this->get_unparsed($input_key);
					//$input_val = $this->unparse_value($input_val);
					$input_val = $this->parse_mail_value($input_val);
				}

				$friendly_name = $this->get_friendly_name($input_key);
				
				if ( $this->friendly_name_ucfirst ) {
					$friendly_name = ucfirst($friendly_name);
				}
				
				if ( is_array($input_val) ) {
					$input_string = '';

					foreach( $input_val as $cur_val ) {
						$input_string .= $cur_val . ', ';
					}

					$input_string = preg_replace('/,\s*$/', '', $input_string);

					$mail_message .= "{$friendly_name}: {$input_string}\n";
				}
				else {

					$mail_message .= "{$friendly_name}: {$input_val}\n";
				}
			}
		}

		$mail_message .= $mail_append;
		
		return $mail_message;
	}

	function send_mail_message( $recipient = '', $subject = '', $extra_headers = '', $mail_prepend = '', $mail_append = '') {
			
			$mail_subject  = ( $subject ) ? $subject : $this->mail_subject;
			$mail_subject  = ( $mail_subject ) ? $mail_subject : $this->mail_subject_default; 			
		
			$recipient    = ( $recipient ) ? $recipient : $this->mail_recipient;
			$extra_headers = ( $extra_headers ) ? $extra_headers : $this->mail_headers;
	
			if ( !$recipient ) {
				LL::Raise_error( 'send_mail_message called with no recipient set, aborting mail', '', $_SERVER['PHP_SELF'], $this->_Error_level_warn );
				return false;
			}
			else {
			
				if ( $mail_prepend ) {
					$msg_options['mail_prepend'] = $mail_prepend;
				}
				if ( $mail_append ) {
					$msg_options['mail_append'] = $mail_append;
				}
			
				$mail_message = $this->generate_mail_message( $msg_options );
				
				if ( $this->mail_sender ) {
					$extra_headers .= "From: {$this->mail_sender}\r\n";
				}
	
				if ( $this->mail_reply_to ) {
					$extra_headers .= "Reply-to: {$this->mail_reply_to}\r\n";
				}
	
	
				if ( mail($recipient, $mail_subject, $mail_message, $extra_headers) ) {
					return true;
				}
			}
		

		return false;
	}	


	function parse_mail_value( $value ) {

		if ( function_exists('form_parse_mail_value') AND $this->use_hook_functions ) {
			$value = form_parse_mail_value($value);
		}

		return $value;
	}

	function is_valid_email_address( $address ) {

		if ( function_exists('form_is_valid_email_address') AND $this->use_hook_functions ) {
			return form_is_valid_email_address($address);
		}
		else {
        		if ( !preg_match('/[A-Za-z0-9\-\._]+@[A-Za-z0-9\-\._]+/', $address) ) {
		                return false;
			}
			else {
				return true;
			}
	        }

		return false;


	}


        function prepare_mail_message( $message ) {

                $message = ( get_magic_quotes_gpc() ) ? stripslashes($message) : $message;

                return $message;

        }

	
} //end class

} //if defined FORM_MAILER


?>
