<?php

$prefix = null;

if ( isset($options['table_name']) ) {
	
	LL::Require_class('AppManage/ModelGenerator' );
	LL::Require_class('PDO/PDOFactory');
	
	$db = PDOFactory::Instantiate();
	
	
	$table_name = $options['table_name'];
	$mg = new ModelGenerator();
	
	if ( isset($options['model_name']) ) {
		$model_name = $options['model_name'];
	}
	
	try {
		$field_list = $db->get_column_names($table_name);	
		$prefix = $mg->model_prefix_by_field_list($field_list);
		
	}
	catch( Exception $e ) {
		
	} 
}



if ( !isset($table_name) || !$table_name ) {
	$table_name = 'your_table_name_here';

	if ( !$model_name ) {
		$model_name = 'YourModelNameHere';
	}
}

if ( !isset($model_name) || !$model_name ) {
	$model_name = ucfirst(singularize(underscore_to_camel_case($table_name)));
}

if ( !isset($field_list) || !$field_list) {
	$field_list = array( 'field1', 'field2', 'field3' );
}


?>
