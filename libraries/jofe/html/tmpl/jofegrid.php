<?php
	defined('_JEXEC') or die('Restricted access');
?>
<ul class="jofegrid" id="<?php echo $this->getGridId(); ?>">
	<li class="jofegrid-header">
		<?php if (self::$SHOW_COMMANDS): ?>
			<div class="jofegrid-command">
				<a onclick="var frm = jQuery(this).parents('form'); <?php echo $this->getSubmitFunction(); ?>"><?php echo JHTML::_('image.site', 'refresh.png', JURI::base(true) . '/images/openbox/', null, null);?></a>
			</div>
		<?php endif; ?>
		<?php foreach ($this->_cols as $col): ?>
		<span<?php if (!empty($col['width'])) { echo ' style="width:' . $col['width'] . ';"'; } ?> class="jofegrid-cell<?php if (!empty($col['header_cell_class'])) { echo ' ' . $col['header_cell_class']; } ?>">
			<?php
				if ($col['sortable']) {
					echo JofeHtmlGrid::sort($col['header'], $col['sort_field'], $this->_order_dir, $this->_order, $this->_input_prefix, $this->getSubmitFunction());
				} else {
					echo $col['header'];
				}
			?>
		</span>
		<?php endforeach; ?>
	</li>
	<li class="jofegrid-row-base" style="display:none">
		<?php foreach ($this->_cols as $col): ?>
		<span<?php if (!empty($col['width'])) { echo ' style="width:' . $col['width'] . ';"'; } ?> class="jofegrid-cell<?php if (!empty($col['cell_class'])) { echo ' ' . $col['cell_class']; } ?>">
		</span>
		<?php endforeach; ?>
	</li>
</ul>
<br/>
<?php $pagination = $this->getPagination(); ?>
<div class="pager"<?php if ($pagination == null) { echo ' style="display:none;"'; } ?>>
	<?php if ($pagination) { echo $pagination->getListFooter(); } ?>
</div>
<input type="hidden" name="<?php echo $this->_input_prefix; ?>filter_order" value="<?php echo $this->_order; ?>"/>
<input type="hidden" name="<?php echo $this->_input_prefix; ?>filter_order_Dir" value="<?php echo $this->_order_dir; ?>"/>