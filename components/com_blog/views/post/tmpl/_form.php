<article>
	<?php echo $form->start(array('class' => 'blog-form', 'no_message' => true)); ?>
		<?php echo $form->hidden('id'); ?>
		<?php echo $form->title($form_command); ?>
		<br/>
		<table width="100%">
			<colgroup>
				<col style="width: 20%;"/>
				<col style="width: 80%;"/>
			</colgroup>
			<tr><?php echo $form->text('title'); ?>
			</tr>
			<tr><?php echo $form->textArea('content', array('additional' => 'style="width: 95%; height: 10em;"', 'label' => 'Content')); ?></tr>
			<?php if (!$form->readonly()): ?>
				<tr>
					<td></td>
					<td><?php echo $form->submit('Submit', array('wrapper_tag' => 'span')); ?></td>
				</tr>
			<?php endif; ?>
		</table>
	<?php echo $form->end(); ?>
</article>