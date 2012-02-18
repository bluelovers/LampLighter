<?php

require_once('table_field_func.php');
require('table_field_vars.php');
?>

<?php foreach( $field_list as $field ) { ?>
<div>
<?php echo field_caption_by_field_name($field, $prefix) . ': ' . '<{' . depluralize($table_name) . '->' . field_key_by_field_name($field, $prefix) . '}>'; ?> 
</div>
<?php } ?>
