<?php

class FormInputType {

	const INPUT_TYPE_TEXT = 1;
	const INPUT_TYPE_TEXTBOX = 1;
	const INPUT_TYPE_HIDDEN = 1;
	const INPUT_TYPE_RADIO = 2;
	const INPUT_TYPE_CHECKBOX = 3;
	const INPUT_TYPE_LISTBOX = 4;
	const INPUT_TYPE_SELECT = 4;
	const INPUT_TYPE_DROPDOWN = 4;
	const INPUT_TYPE_FILE = 5;
	const INPUT_TYPE_TEXTBOX_ARRAY = 6;
	const INPUT_TYPE_LISTBOX_ARRAY = 7;

	public static function Constant_by_name( $name ) {
	
		
		switch( strtolower($name) ) {
			case 'checkbox':
				return self::INPUT_TYPE_CHECKBOX;
				break;
			case 'radio':
			case 'radiobutton':
			case 'radio_button':
				return self::INPUT_TYPE_RADIO;
				break;
			case 'dropdown':
			case 'select':
			case 'listbox':
				return self::INPUT_TYPE_DROPDOWN;
				break;
			case 'text':
			case 'textbox':
			case 'textarea':
			default:
				return self::INPUT_TYPE_TEXT;
				break;
		}
		
		return self::INPUT_TYPE_TEXT;
				
		
	}
	
}
?>