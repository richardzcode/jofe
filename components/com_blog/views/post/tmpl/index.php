<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

$form = new JofeForm('blog', 'post', array(
	'task' => 'index',
	'form_title' => 'Post List'
));

$base_uri = $this->getComponent()->getURI(array(
	'controller=' . $this->getName(),
	'task=show'
));
?>
<?php echo $form->start(); ?>
<?php echo $form->title(array('create')); ?>
<?php
	$grid = new JofeGrid(array(
				array(
					'header' => 'Title',
					'width' => '50%',
					'field' => 'title',
					'callback' => 'post_grid_col_title',
					'sortable' => true
				),
				array(
					'header' => 'Created',
					'width' => '20%',
					'field' => 'created_on',
					'sortable' => true
				)
			),
			$this->griddata
		);
	$grid->render();
?>

<?php echo $form->end(); ?>
<script type="text/javascript">
	function post_grid_col_title(row) {
		return '<a href=\"<?php echo $base_uri; ?>&id=' + row.id + '\">' + row.title + '</a>';
	}
</script>