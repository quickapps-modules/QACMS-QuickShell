<?php
	$actual_value = isset($data['field']['FieldData']['data']) ? $data['field']['FieldData']['data'] : '';

	// Storage ID
	echo $this->Form->hidden("FieldData.FieldName.{$data['field']['id']}.id", array('value' => $data['field']['FieldData']['id']));

	// Storage DATA
	echo $this->Form->input("FieldData.FieldName.{$data['field']['id']}.data", array('label' => $data['field']['label'], 'value' => $actual_value));
?>

<?php
	// Field help
	if (!empty($data['field']['description'])):
?>
	<?php echo $this->Form->helpBlock($data['field']['description']); ?>
<?php endif; ?>