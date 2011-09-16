<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

$form = new JofeForm('blog', 'post', array(
		'field_wrapper_tag' => 'td',
		'field_label_separator' => '</td><td>',
		'form_title' => 'Create Post',
		'task' => 'create'
	)
);
$form->setTable($this->resource);

include '_form.php';

?>