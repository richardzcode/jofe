<?php
	defined('_JEXEC') or die('Restricted access');

	if (!defined('_JOFEGRID_')) :
		define('_JOFEGRID_', true);
?>
	<script type="text/javascript">
		var jofegrid_ids = new Array();
		function jofegrid_ajax_submit(frm, grid_obj) {
			var grid = jQuery('#' + grid_obj.id);
			jQuery.ajax({
				url: (grid_obj.ajax_submit_action.length == 0)? frm.attr('action') : grid_obj.ajax_submit_action,
				data: frm.serialize(),
				type: 'POST',
				dataType: 'json',
				contentType: 'application/x-www-form-urlencoded',
				context: grid_obj,
				success: function (ret) {
					if (this.submit_success_callback) {
						eval(this.submit_success_callback);
					} else {
						grid_el = jQuery('#' + grid_obj.id);
						if (ret.data) {
							this.data = ret.data;
							jofegrid_load_page(grid_el);
						}
						grid_el.parents('form').find('.list-footer').replaceWith(ret.pagination_footer);

						if (ret.pagination_footer) {
							grid_el.parent().find('.pager').html(ret.pagination_footer).show();
						}
					}
				}
			});
		}

		jQuery(document).ready(function() {
			for (var i = 0; i < jofegrid_ids.length; i ++) {
				var grid = jQuery('#' + jofegrid_ids[i]);
				jofegrid_load_page(grid);
			}
		});

		function jofegrid_load_page(grid) {
			if (grid.length == 0) {
				return;
			}
			
			var obj = eval(grid.attr('id'));
			var rowbase = grid.find('.jofegrid-row-base');
			grid.find('.jofegrid-row').remove();
			if (obj.data.length == 0) {
				rowbase.clone(true).removeClass('jofegrid-row-base').addClass('jofegrid-row jofegrid-row-empty').html('<span>Result list is Empty</span>').appendTo(grid).show();
			}
			for (var j = 0; j < obj.data.length; j ++) {
				var row_class = '';
				if (obj.row_class_callback) {
					row_class = ' ' + eval(obj.row_class_callback + '(obj.data[j])');
				}
				var row = rowbase.clone(true).removeClass('jofegrid-row-base').addClass('jofegrid-row' + row_class).appendTo(grid);
				for (var k = 0; k < obj.cols.length; k ++) {
					var col = obj.cols[k];
					var val = (col.field.length > 0)? eval('obj.data[j].' + col.field) : '';
					if (col.callback.length > 0) {
						val = eval(col.callback + '(obj.data[j])');
					}
					row.find('span:eq(' + k + ')').html(val);
					//row.find('td:eq(' + k + ')').html('h');
				}
				row.show();
			}

			if (obj.after_load_page) {
				eval(obj.after_load_page);
			}
		}

		function jofegrid_create_row_el(grid_id, data) {
			var obj = eval('<?php echo self::$GRID_ID_PREFIX ?>' + grid_id);
			var grid = jQuery('#' + obj.id);

			var rowbase = grid.find('.jofegrid-row-base');
			var row = rowbase.clone(true).removeClass('jofegrid-row-base').addClass('jofegrid-row').appendTo(grid);
			for (var k = 0; k < obj.cols.length; k ++) {
				var col = obj.cols[k];
				var val = (col.field.length > 0)? eval('data.' + col.field) : '';
				if (col.callback.length > 0) {
					val = eval(col.callback + '(data)');
				}
				row.find('span:eq(' + k + ')').html(val);
				//row.find('td:eq(' + k + ')').html('h');
			}
			return row;
		}

		function jofegrid_add_row(grid_id, data) {
			var obj = eval('<?php echo self::$GRID_ID_PREFIX ?>' + grid_id);
			var grid = jQuery('#' + obj.id);

			grid.find('.jofegrid-row-empty').hide();
			jofegrid_create_row_el(grid_id, data).show();
		}

		function jofegrid_refresh(grid_id) {
			var grid_obj = eval('<?php echo self::$GRID_ID_PREFIX ?>' + grid_id);
			var frm = jQuery('#' + '<?php echo self::$GRID_ID_PREFIX ?>' + grid_id).parents('form');
			eval(grid_obj.submit_function);
		}
	</script>
<?php endif; ?>
<script type="text/javascript">
	jofegrid_ids.push('<?php echo $this->getGridId(); ?>');
	var <?php echo $this->getGridId(); ?> = {
		id: '<?php echo $this->getGridId(); ?>',
		ajax_submit_action: '<?php echo $this->getAjaxSubmitAction(); ?>',
		submit_function: '<?php echo $this->getSubmitFunction(); ?>',
		submit_success_callback: '<?php echo $this->getSubmitSuccessCallback(); ?>',
		after_load_page: '<?php echo $this->getAfterLoadPage(); ?>',
		row_class_callback: '<?php echo $this->getRowClassCallback(); ?>',
		cols: [
			<?php
				for ($i = 0; $i < count($this->_cols); $i ++):
					if ($i > 0) { echo ','; }
			?>
				{field: '<?php echo $this->_cols[$i]['field']; ?>',
				callback: '<?php echo $this->_cols[$i]['callback']; ?>'}
			<?php endfor; ?>
		],
		data: <?php echo ($this->_data)? json_encode($this->_data) : '[]'; ?>
	};
</script>