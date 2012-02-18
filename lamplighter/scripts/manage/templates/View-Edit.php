<?php

require_once('table_field_func.php');
require('table_field_vars.php');
?>

<{IF message}>
<div style="margin-bottom: 8px; font-weight: bold; text-align:center; padding: 2px;">
  <{message}>
</div>
<{/IF}>

<{form_script_tag}>
<{form_tag}>

<table style="margin-top: 2px;">
<?php foreach( $field_list as $field ) { 
	
	$field_key = field_key_by_field_name($field, $prefix);
	
	if ( $field_key == 'id' ) { 
		continue;
	}
	
?>
  <tr>
  	<td><?php echo field_caption_by_field_name($field, $prefix)?></td>
  	<td><{HTML/FormHelper::text_field('<?php echo $model_name?>', '<?php echo $field_key;?>') }></td>
  </tr>
<?php } ?>
  <tr>
    <td colspan="2" style="text-align:center;">
      <input type="submit" value="Save Changes" />
    </td>
  </tr>
</table>
</form>