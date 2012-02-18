<?php

FUSE::Require_class('HTML/FormCommon');

class FormLayoutHelper extends FormCommon {

	static $Label_container_class = 'form_label_container';
	static $Label_class = 'form_label';
	static $Sub_label_class = 'sub_label';
	static $Data_container_class = 'form_data_container';
	static $Data_class = 'form_data';
	static $Data_label_class = 'data_label';
	static $Container_class = 'form_input_container';
	static $Clearer_class = 'clearer';

    static function Label( $text, $options = array() ) {
    	
    	//
    	// Container
    	//
    	$html = '<div class="' . self::$Label_container_class . '">';
        	
        //
        // Label
        //
    	$html .= '<div class="' . self::$Label_class . '">';
    	$html .= htmlspecialchars($text);
    	$html .= '</div>' . "\n";
    	
    	//
    	// Sub Label
    	//
    	if ( isset($options['sub_label']) && $options['sub_label'] ) {
    		$html .= '<div class="' . self::$Sub_label_class . '">';
    		$html .= $options['sub_label'];
    		$html .= '</div>' . "\n";
    	}
    	
    	//
    	// Close Container
    	//
	    $html .= '</div>' . "\n";
	        	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Container_start( $options = array() ) {
    	
    	static $odd_even;
    	
    	if ( isset($options['parity']) ) {
    		if ( $options['parity'] == 'none' ) {
    			$odd_even = '';
    		}
    		else {
    			$odd_even = $options['parity'];
    		} 
    	}
    	else {
	    	if ( !$odd_even || $odd_even == 'even' ) {
	    		$odd_even = 'odd';
	    	}
	    	else {
	    		$odd_even = 'even';
	    	}
    	}
    	
    	$style = isset($options['container_style']) ? $options['container_style'] : null;
    	
    	$class_attr = self::$Container_class;
    	
    	if ( $odd_even ) {
    		$class_attr .= ' ' . self::$Container_class . '_' . $odd_even;
    	} 
    
    	if ( isset($options['container_class']) && $options['container_class'] ) {
			$class_attr .= ' ' . $options['container_class'];    		
    	}
    	
    	$html = '<div style="' . $style . '" class="' . $class_attr . '">' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Container_end( $options = array() ) {
    	
    	$html = '<div class="' . self::$Clearer_class . '"></div>' . "\n";
    	$html .= '</div>' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Data_container_start( $options = array() ) {
    	
    	$html = '<div class="' . self::$Data_container_class . '">' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Data_container_end( $options = array() ) {
    	
    	$html = '</div>' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }


    static function Data_start( $options = array() ) {
    	
    	$html = '<div class="' . self::$Data_class . '">' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Data_end( $options = array() ) {
    	
    	$html = '</div>' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Data_label_start( $options = array() ) {
    	
    	$html = '<div class="' . self::$Data_label_class . '">' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

    static function Data_label_end( $options = array() ) {
    	
    	$html = '</div>' . "\n";
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }


   public static function Dropdown( $options = array() ) {

		FUSE::Require_class('HTML/FormInput');
    	
		$sub_options = $options;
    	$sub_options['return_output'] = true;
    	$sub_options['input_type'] = 'dropdown';
    	
		if ( !isset($options['field_tag']) ) {
			$options['field_tag'] = self::Field_tag_by_options( $sub_options );
		}
		
		return self::Form_component($options);
    	
    }

   public static function Text_field( $options = array() ) {

		FUSE::Require_class('HTML/FormInput');
    	
		$sub_options = $options;
    	$sub_options['return_output'] = true;
    	
		if ( !isset($options['field_tag']) ) {
			$options['field_tag'] = FormInput::Text_field( $sub_options );
		}
		
		return self::Form_component($options);
    	
    }

   public static function Text_area( $options = array() ) {

		FUSE::Require_class('HTML/FormInput');
    	
		$sub_options = $options;
    	$sub_options['return_output'] = true;
    	
		if ( !isset($options['field_tag']) ) {
			$options['field_tag'] = FormInput::Text_area( $sub_options );
		}
		
		return self::Form_component($options);
    	
    }

   public static function Password_field( $options = array() ) {

		FUSE::Require_class('HTML/FormInput');
    	
		$sub_options = $options;
    	$sub_options['return_output'] = true;
    	
		if ( !isset($options['field_tag']) ) {
			$options['field_tag'] = FormInput::Password_field( $sub_options );
		}
		
		return self::Form_component($options);
    	
    }
    
    public static function Form_component( $options = array() ) {
    	
    	$html = '';
    	
    	$sub_options = $options;
    	$sub_options['return_output'] = true;
    	
    	$label = isset($options['label']) ? $options['label'] : null;
    	
    	$html .= self::Container_start( $sub_options );
    	$html .= self::Label($label, $sub_options);
    	$html .= self::Data_container_start($sub_options);
    	$html .= self::Data_start($sub_options);
    	
    	if ( isset($options['field_tag']) && $options['field_tag'] ) {
    		$html .= $options['field_tag'];
    	}
    	else {
    		$html .= self::Field_tag_by_options($sub_options);
    	}
    	
    	$html .= self::Data_end($sub_options);
    	
    	$html .= self::Data_label_start($sub_options);
    	
    	if ( isset($options['data_label']) ) {
    		$html .= $options['data_label'];
    	}
    	
    	$html .= self::Data_label_end($sub_options);
    	
    	
    	$html .= self::Data_container_end($sub_options);
    	$html .= self::Container_end( $sub_options );
    	
    	
    	if ( isset($options['return_output']) && $options['return_output'] ) {
    		return $html;
    	} 
    	
    	print( $html );
    	
    }

	public static function Field_tag_by_options( $options ) {
		
		try {
			
			LL::Require_class('Form/FormInputType');
			
			$output = '';
			
			if ( !isset($options['input_type']) || !$options['input_type'] ) {
				throw new MissingParameterException('input_type');
			}

			if ( !isset($options['input_name']) || !$options['input_name'] ) {
				if ( !isset($options['name']) || !$options['name']) {
					throw new MissingParameterException('input_name');
				}
				else {
					$options['input_name'] = $options['name'];
				}
			}
			
			switch( $options['input_type'] ) {
				
				case 'dropdown':
					
					LL::Require_class('HTML/FormOptionsHelper');
					
					$selections = isset($options['options']) ? $options['options'] : array();
					$sub_options = $options;

					if ( !($sub_options['id'] = self::Input_id_specified($sub_options)) ) {
						$sub_options['id']= $sub_options['input_name']; 
					} 

					$sub_options['return_output'] = true;
					
					$output = FormOptionsHelper::Select_tag_open( $options['input_name'], $sub_options );
					$output .= FormOptionsHelper::Options_from_array_for_select($selections, $sub_options);
					$output .= FormOptionsHelper::Select_tag_close($sub_options);
					
					break;
				case 'radio':
					
					LL::Require_class('HTML/FormInput');
					
					$count = 0;
					
					foreach( $options['options'] as $option_data ) {
					
						$sub_options = $options;
						unset($sub_options['options']);
						$sub_options['return_output'] = true;
					
					
						$option_text = $option_data[0];
						$option_val = $option_data[1];
						
						if ( !($input_id = self::Input_id_specified($sub_options)) ) {
							$input_id =$sub_options['input_name'] . '-' . $option_val; 
						} 

						//
						// If we have an array of selected values, 
						// check to see if this option is in the array.
						// if it is, make sure it gets selected.
						//
						if ( is_array($options['selected_value']) ) {
							
							if ( in_array($option_val, $options['selected_value']) ) {
								$sub_options['selected_value'] = $option_val;
							}
							else {
								unset($sub_options['selected_value']);
							}
						}

						$sub_options['id'] = $input_id;
					
						$sub_options['value'] = $option_val;
						
						$output .= "<div class=\"form-radio_container {$input_id} index-{$count}\">\n";
						$output .= FormInput::Radio_button($sub_options);
						$output .= '<label for="' . $input_id . '" class="form-radio">' . $option_text . '</label>';
						$output .= "\n" . '</div>';
						
						$count++;
						
					}							
			
					break;
				case 'checkbox':
					
					LL::Require_class('HTML/FormInput');
					
					$count = 0;
					if ( isset($options['options']) && is_array($options['options']) ) {
						foreach( $options['options'] as $option_data ) {
	
							$sub_options = $options;
							unset($sub_options['options']);
							$sub_options['return_output'] = true;
						
							if ( count($options['options']) > 1 ) {
								if ( substr($sub_options['input_name'], -2) != '[]' ) {
									$sub_options['input_name'] .= '[]';
								}
							}
							
							$option_text = $option_data[0];
							$option_val = $option_data[1];
	
							if ( !($input_id = self::Input_id_specified($options)) ) {
								$input_id = rtrim($sub_options['input_name'], '[]') . '_' . $option_val; 
							} 
	
							//
							// If we have an array of selected values, 
							// check to see if this option is in the array.
							// if it is, make sure it gets selected.
							//
							if ( is_array($options['selected_value']) ) {
								if ( in_array($option_val, $options['selected_value']) ) {
									$sub_options['selected_value'] = $option_val;
								}
								else {
									unset($sub_options['selected_value']);
								}
							}
		
							/*
							// label IDs need to be per option
							
							if ( isset($options['label_id']) ) {
								$label_id = $options['label_id'];
							}
							else {
								$label_id = 'label_' . $input_id;
							}
							*/
	
							$sub_options['id'] = $input_id;
							$sub_options['value'] = $option_val;
							
							$output .= "<div class=\"form-checkbox_container {$input_id} index-{$count}\">\n";
							$output .= FormInput::Checkbox($sub_options);
							$output .= '<label for="' . $input_id . '" class="form-checkbox">' . $option_text . '</label>';
							$output .= "\n" . '</div>';
							
							$count++;
						}							
					}
					break;
				case 'textbox':
				
					LL::Require_class('HTML/FormInput');
					
					$sub_options = $options;
					$sub_options['return_output'] = true;
					
					$output = FormInput::Text_field( $sub_options );
				
					break;
				
				case 'hidden':
				
					LL::Require_class('HTML/FormInput');
					
					$sub_options = $options;
					$sub_options['return_output'] = true;
					
					$output = FormInput::Hidden_field( $sub_options );
				
					break;

				case 'file':
				
					LL::Require_class('HTML/FormInput');
					
					$sub_options = $options;
					$sub_options['return_output'] = true;
					
					$output = FormInput::File_field( $sub_options );
				
					break;					

			}

			if ( isset($options['return_output']) && $options['return_output'] ) {
			 	return $output;
			}
			else {
				print $output;
			}
			
		}
		catch( Exception $e ) {
			throw $e;
		}
		
	}
    
}
?>