<?php
/**
 * @package		Jofe
 * @subpackage	Html
 * @version		1.0
 *
 * @license MIT
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.html.pagination');

class JofePagination extends JPagination {
	var $_input_prefix = '';
	
	// Customized form submit function.
	private $_submit_function = '';

	function setInputPrefix($prefix) {
		$this->_input_prefix = $prefix;
	}

	function _list_footer($list)
	{
		// Initialize variables
		$html = "<div class=\"list-footer\">\n";

		$html .= "\n<div class=\"limit\">".JText::_('Display Num ').$list['limitfield']."</div>";
		$html .= $list['pageslinks'];
		$html .= "\n<div class=\"counter\">".$list['pagescounter']."</div>";

		$html .= "\n<input type=\"hidden\" name=\"" . $this->_input_prefix . "limitstart\" value=\"".$list['limitstart']."\" />";
		$html .= "\n</div>";

		return $html;
	}

	function getLimitBox()
	{
		global $mainframe;

		// Initialize variables
		$limits = array ();

		// Make the option list
		for ($i = 5; $i <= 30; $i += 5) {
			$limits[] = JHTML::_('select.option', "$i");
		}
		$limits[] = JHTML::_('select.option', '50');
		$limits[] = JHTML::_('select.option', '100');
		$limits[] = JHTML::_('select.option', '0', JText::_('all'));

		$selected = $this->_viewall ? 0 : $this->limit;

		// Build the select list
		if ($mainframe->isAdmin()) {
			$html = JHTML::_('select.genericlist',  $limits, $this->_input_prefix . 'limit', 'class="inputbox limitBox" size="1" onchange="submitform();"', 'value', 'text', $selected);
		} else {
			$html = JHTML::_('select.genericlist',  $limits, $this->_input_prefix . 'limit', 'class="inputbox limitBox" size="1" onchange="var frm = jQuery(this).parents(\'form\'); ' . $this->getSubmitFunction() . '"', 'value', 'text', $selected);
		}
		return $html;
	}

	function _item_active(&$item)
	{
		global $mainframe;
		if ($mainframe->isAdmin())
		{
			if($item->base>0)
				return "<a title=\"".$item->text."\" onclick=\"javascript: document.adminForm.limitstart.value=".$item->base."; submitform();return false;\">".$item->text."</a>";
			else
				return "<a title=\"".$item->text."\" onclick=\"javascript: document.adminForm.limitstart.value=0; submitform();return false;\">".$item->text."</a>";
		} else {
			$script = "javascript: var frm = jQuery(this).parents('form'); frm.find('input[name=" . $this->_input_prefix . "limitstart]').val('" . ($item->base > 0 ? $item->base : '0') . "'); " . $this->getSubmitFunction() . ";return false;";
			return "<a href=\"#\" class=\"pagenav\" title=\"".$item->text."\" onclick=\"" . $script . "\">".$item->text."</a>";
		}
	}

	function setSubmitFunction($function) {
		$this->_submit_function = $function;
	}

	function getSubmitFunction() {
		if (empty($this->_submit_function)) {
			return 'frm.submit()';
		} else {
			return $this->_submit_function;
		}
	}
}

class JofeHtmlGrid {
	static $_sort_dir_images = array('sort_asc.png', 'sort_desc.png');
	/**
	 * @param	string	The link title
	 * @param	string	The order field for the column
	 * @param	string	The current direction
	 * @param	string	The selected ordering
	 * @param	string	An optional task override
	 */
	public function sort($title, $order, $direction = 'asc', $selected = 0, $input_prefix = '', $submit_func = '')
	{
		$direction	= strtolower( $direction );
		$index		= intval( $direction == 'desc' );
		$direction	= ($direction == 'desc') ? 'asc' : 'desc';

		$script = "var frm = jQuery(this).parents('form'); frm.find('input[name=" . $input_prefix . "filter_order]').val('" . addslashes($order) . "'); frm.find('input[name=" . $input_prefix . "filter_order_Dir]').val('" . $direction . "'); " . (empty($submit_func)? "frm.submit()" : $submit_func) . "; return false;\"";
		$html = '<a href="" onclick="javascript:' . $script . '" title="'.JText::_( 'Click to sort this column' ).'">';
		$html .= JText::_( $title );
		if ($order == $selected ) {
			$html .= JHTML::_('image.site',  self::$_sort_dir_images[$index], '/images/', NULL, NULL);
		}
		$html .= '</a>';
		return $html;
	}
}

class JofeGrid {
	static $GRID_ID_PREFIX = 'grid_';
	static $SHOW_COMMANDS = false;

	private $_grid_id = '';
	private $_input_prefix = '';
	private $_script_path = '';
	private $_tmpl_path = '';
	private $_cols = array();
	private $_data = array();

	private $_order = '';
	private $_order_dir = 'ASC';

	private $_pagination = null;

	// Default form submit function for ajax paging.
	private $_ajax_submit = false;
	private $_ajax_submit_action = '';
	// Customized form submit function.
	private $_submit_function = '';
	private $_submit_success_callback = '';
	private $_after_load_page = '';

	private $_row_class_callback = '';

	/**
	 * Construct the grid object.
	 * @param array $cols Defination of columns.
	 * array(
	 *   array(
	 *     'sortable' => true|false,
	 *     'header' => '',
	 *     'width' => '',
	 *     'field' => '',
	 *     'sort_field' => '',
	 *     'callback' => '',
	 *     'header_cell_class' => '',
	 *     'cell_class' => ''
	 *   )
	 * )
	 * @param mixed $data Data of current page.
	 *                    If it is an array than data only. If it is an object then it contains more information for grid.
	 * @param string $input_prefix The input prefix for filter_order and filter_order_Dir.
	 * @param string $tmpl_path Template path, if null then use the default template at /tmpl/obgrid.php.
	 */
	function __construct($cols, $data = array(), $input_prefix = '', $tmpl_path = null) {
		$this->_grid_id = md5(microtime());
		$this->_input_prefix = $input_prefix;
		$this->_script_path = dirname(__FILE__) . DS . 'tmpl' . DS . 'jofegrid_script.php';
		$this->_tmpl_path = empty($tmpl_path)? dirname(__FILE__) . DS . 'tmpl' . DS . 'jofegrid.php' : $tmpl_path;
		
		$this->_cols = $cols;
		foreach ($this->_cols as &$col) {
			if (empty ($col['sortable'])) {
				$col['sortable'] = false;
			}
			if ($col['sortable'] && empty($col['input_name'])) {
				$col['input_name'] = $col['field'];
			}
			if ($col['sortable'] && empty($col['sort_field'])) {
				$col['sort_field'] = $col['field'];
			}
		}

		if (is_object($data) && isset ($data->data)) {
			$this->_data = $data->data;
			if (isset($data->input_prefix)) {
				$this->_input_prefix = $data->input_prefix;
			}
			if (isset($data->order)) {
				$this->_order = $data->order;
			}
			if (isset($data->order_dir)) {
				$this->_order_dir = $data->order_dir;
			}
			if (isset($data->total) && isset($data->limit) && isset($data->limitstart)) {
				$this->_pagination = new JofePagination($data->total, $data->limitstart, $data->limit);
			}
		} else {
			$this->_data = $data;
		}
	}

	function getGridId() {
		return self::$GRID_ID_PREFIX . $this->_grid_id;
	}

	function setGridId($grid_id) {
		$this->_grid_id = $grid_id;
	}
	
	function setInputPrefix($prefix) {
		$this->_input_prefix = $prefix;
	}

	/**
	 * Turn ajax submit on/off for sorting and paging.
	 * 
	 * @param boolean $on Turn ajax submit on/off
	 * @param string $action ajax submit action. If empty then submit to the form action.
	 */
	function setAjaxSubmit($on = true, $action = null) {
		$this->_ajax_submit = $on;
		$this->_ajax_submit_action = $action;
	}

	function getAjaxSubmitAction() {
		return $this->_ajax_submit_action;
	}

	function setSubmitFunction($function) {
		$this->_submit_function = $function;
	}

	function getSubmitFunction() {
		if (!empty($this->_submit_function)) {
			return $this->_submit_function;
		}

		if ($this->_ajax_submit) {
			return 'jofegrid_ajax_submit(frm, ' . $this->getGridId() . ')';
		}
		
		return 'frm.submit()';
	}

	function setSubmitSuccessCallback($function) {
		$this->_submit_success_callback = $function;
	}

	function getSubmitSuccessCallback() {
		return $this->_submit_success_callback;
	}

	/**
	 * Set the js callback on after load page event.
	 */
	function setAfterLoadPage($callback) {
		$this->_after_load_page = $callback;
	}

	function getAfterLoadPage() {
		return $this->_after_load_page;
	}

	/**
	 * Set the js callback on adding css class to the row.
	 */
	function setRowClassCallback($callback) {
		$this->_row_class_callback = $callback;
	}

	function getRowClassCallback() {
		return $this->_row_class_callback;
	}

	function setOrder($order, $order_dir) {
		$this->_order = $order;
		$this->_order_dir = $order_dir;
	}

	function setData($data) {
		$this->_data = $data;
	}

	function getData() {
		return $this->_data;
	}

	function setPagination($pagination) {
		$pagination->setSubmitFunction($this->getSubmitFunction());
		$this->_pagination = &$pagination;
	}

	function getPagination() {
		return $this->_pagination;
	}

	function isEmpty() {
		return (count($this->_data) == 0);
	}

	function render($data = null) {
		if (!empty($data)) {
			$this->setData($data);
		}
		include $this->_script_path;
		include $this->_tmpl_path;
	}
}
?>
