<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

$post = $this->resource;

$form = new JofeForm('blog', 'post', array(
		'field_wrapper_tag' => 'td',
		'field_label_separator' => '</td><td>',
		'form_title' => $post->getTitle(),
		'readonly' => true
	)
);
$form->setTable($post);
$form_command = array(
	array(
		'task' => 'index',
		'label' => 'Posts'
	),
	array(
		'label' => '|'
	),
	'update',
	array(
		'task' => 'destory',
		'label' => 'Delete'
	)
);

include '_form.php';

?>

<br/>
<div class="form-subtitle"><?php $post->_count_comment; ?> Comments:</div>

<dl class="comments">
	<?php foreach($post->_obj_comment as $comment): ?>
		<dt><?php echo $comment->name; ?></dt>
		<dd><?php echo $comment->content; ?></dd>
	<?php endforeach; ?>
</dl>

<?php
$form_comment = new JofeForm('blog', 'comment', array(
		'field_wrapper_tag' => 'div',
		'field_label_separator' => '</div><div>',
		'task' => 'create'
	)
);

echo $form_comment->start();
echo $form_comment->hidden('post_id', array('value' => $post->id));
echo $form_comment->text('name');
echo $form_comment->textArea('content');
echo $form_comment->submit('Submit');
echo $form_comment->end();
?>