<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

$form = new JofeForm('blog', 'post', array(
	'task' => 'index',
	'form_title' => 'Front Page'
));

$posts_uri = $this->getComponent()->getURI(array(
	'controller=post'
));
?>
<section>
	<?php echo $form->start(); ?>
	<?php echo $form->title(array('create')); ?>

	<?php foreach ($this->posts as $post): ?>
	<article>
	<header><?php echo $post->title; ?></header>
	<div><?php echo $post->content; ?></div>
	<footer>
	Comments(<?php echo $post->_count_comment; ?>)
	| <a href="<?php echo $posts_uri . '&task=show&id=' . $post->id; ?>">Read more ...</a>
	</footer>
	</article>
	<?php endforeach; ?>

	<footer>
		<a href="<?php echo $posts_uri; ?>">More...</a>
	</footer>
</section>

<?php echo $form->end(); ?>