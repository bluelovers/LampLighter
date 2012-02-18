<?php

require_once('table_field_func.php');
require('table_field_vars.php');
?>

<{IF message}>
<div style="text-align: center; margin-bottom: 10px; background-color: #F2F2F2;">
<{print(htmlspecialchars(message))}>
</div>
<{/IF}>

<div>
<a href="<{SITE_BASE_URI}>/<?php echo strtolower($options['controller_name']);?>/add">Add</a>
</div>
<table border="1">
  <tr>
    <th>Options</th>
  <?php foreach( $field_list as $field ) { ?>
  	<th colspan=""><?php echo field_caption_by_field_name($field, $prefix)?></th>
  <?php } ?>
  </tr>
  <{ITERATOR <?php echo $table_name ?>}>
  <tr>
  	<td>
  		<a href="<{$_SITE_BASE_URI}>/<?php echo strtolower($options['controller_name']);?>/edit/<{id}>">Edit</a>
  		&nbsp;
  		<a href="#" onclick="<{HTML/ListHelper::Delete_confirm('<?php echo strtolower($options['controller_name']);?>', id )}>">Delete</a>
    </td>  		

  <?php foreach( $field_list as $field ) { 
  	
  	$field_key = field_key_by_field_name($field, $prefix);
  ?>
  	<td><?php echo '<{' . $field_key . '}>'; ?></td>
  <?php } ?>

  </tr>
  <{/ITERATOR}>
</table>