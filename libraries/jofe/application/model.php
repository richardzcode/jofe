<?php
/**
 * @package		Jofe
 * @subpackage	Application
 * @version		1.0
 *
 * @license MIT
 *
 * Filtering, Sorting, Pagination
 *
 * Filter and pagination parameters are stored in session state, under namespace $this->user_state_context.
 * It can be get and set during runtime by calling getUserStateContext() and setUserStateContext()
 *
 * When having multiple grid in one page, set $this->_input_prefix to distinguish contain parameters into a namespace.
 *
 * Filtering:
 * This class build WHERE statement base on filters specified in $this->_filters.
 * Override _listWhere function to build customized WHERE statement.
 *
 * Pagination:
 * - Call $this->getList() to get the current page of data.
 * - Call $this->getPagination() to get pagination object.
 * - Call $this->getTotal() to get total number of current search.
 * - Call $this->getOrder() and $this->getOrderDir() to get sorting info.
*/

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.application.component.model');

class JofeModel extends JModel{
	protected $_user_state_context = null;
	// For order and pagination
	protected $_input_prefix = '';
	protected $_list = null;
	protected $_total = null;
	protected $_limit = 10;
	protected $_limitstart = 0;
	protected $_pagination = null;
	protected $_order = '';
	protected $_order_dir = '';

	protected $_default_order = '';
	protected $_default_order_dir = 'ASC';
	protected $_default_limit = 5;

	/**
	 * Filters that affect WHERE statement.
	 * array of array('filter_name' => '', value => '', 'type' => 'string|number', 'field_name' => '', 'operator' => '=|LIKE|...')
	 * type 'customize' is a placeholder for customized filters. Base class will not process the 'customize' filter.
	 */
	protected $_filters = array();
	protected $_filters_loaded = false;

	/**
	 * Calculated field list
	 *
	 * array(array('field' => '', 'value' => '', 'callback' => ''))
	 * If there is a value then return value, otherwise look for the callback.
	 */
	protected $_calc_fields = array();

	/**
	 * List of fields to be shown in return list. If empty then all fields are shown.
	 */
	protected $_mask = array();

	// Table name
	protected $_table_name = '';

	public function getTableName() {
		if (empty($this->_table_name)) {
			$this->_table_name = $this->getName();
		}
		return $this->_table_name;
	}
	
	private function _get_filter_value_from_request($filter_name, $default = null, $type = 'none') {
		global $mainframe;
		$context = $this->getUserStateContext();
		return $mainframe->getUserStateFromRequest($context . $this->_input_prefix . $filter_name, $this->_input_prefix . $filter_name, $default, $type);
	}

	protected function _loadFilters() {
		if ($this->_filters_loaded) {
			return;
		}

		foreach ($this->_filters as &$filter) {
			if (empty($filter['field_name']) && ($filter['type'] != 'customize')) {
				$filter['field_name'] = $filter['filter_name'];
			}
			$filter['value'] = $this->_get_filter_value_from_request($filter['filter_name']);
		}
		$this->_filters_loaded = true;
	}

	protected function _loadPagingParam() {
		global $mainframe;
		if (empty($this->_default_limit)) {
			$this->_default_limit = $mainframe->getCfg('list_limit');
		}

		$context = $this->getUserStateContext();
		$this->_order		= $this->_get_filter_value_from_request('filter_order', $this->_default_order, 'string');
		$this->_order_dir	= $this->_get_filter_value_from_request('filter_order_Dir', $this->_default_order_dir, 'word');
		$this->_limit		= $this->_get_filter_value_from_request('limit', $this->_default_limit, 'int');
		$this->_limitstart	= $this->_get_filter_value_from_request('limitstart', 0, 'int');
		if (empty($this->_order)) {
			$this->_order = $this->_default_order;
		}
		if (empty($this->_order_dir)) {
			$this->_order_dir = $this->_default_order_dir;
		}

		// In case limit has been changed, adjust it
        $this->_limitstart = ($this->_limit != 0 ? (floor($this->_limitstart / $this->_limit) * $this->_limit) : 0);
		$this->setState($context . $this->_input_prefix.'limitstart', $this->_limitstart);
	}

	function _loadList() {
		$db =& JFactory::getDBO();
		$query = $this->_listQuery();
		$db->setQuery($query, $this->_limitstart, $this->_limit);
		$this->_list = $db->loadObjectList();
		if (is_null($this->_list)) {
			$this->_list = array();
		} else {
			$db->setQuery('SELECT FOUND_ROWS()');
			$this->_total = $db->loadResult();
			if ($this->_total < $this->_limitstart) {
				$this->_limitstart = ($this->_limit != 0 ? (floor($this->_total / $this->_limit) * $this->_limit) : 0);
				$query = $this->_listQuery();
				$db->setQuery($query, $this->_limitstart, $this->_limit);
				$this->_list = $db->loadObjectList();
			}
			$this->_pagination = new JofePagination($this->_total, $this->_limitstart, $this->_limit);
			$this->_pagination->setInputPrefix($this->_input_prefix);
		}

		$has_mask = !empty($this->_mask);
		if ($has_mask) {
			$ary = array();
		}
		foreach ($this->_list as &$item) {
			$this->_calculateFields(&$item);

			if (!$has_mask) {
				continue;
			}
			$lite = new stdClass();
			foreach ($this->_mask as $key) {
				$lite->$key = $item->$key;
			}
			$ary[] = $lite;
		}
		if ($has_mask) {
			$this->_list =& $ary;
		}
	}

	/**
	 * Add more fields to the result list.
	 * To be overrided.
	 *
	 * @param object $item One item in the list.
	 */
	function _calculateFields(&$item) {
		// Calculated fields
		foreach ($this->_calc_fields as $field) {
			if (isset($field['value'])) {
				$item->$field['field'] = $field['value'];
			}
			if (!empty($field['callback'])) {
				$item->$field['field'] = call_user_func_array(array($this, $field['callback']), array($item));
			}
		}
	}

	function _user_state_context() {
		return 'jofemodel.' . (empty($this->_table_name)? get_class($this) : $this->_table_name) . '.list.';
	}

	function _listSelectExpr() {
		return '*';
	}

	/**
	* Override this function if join tables.
	*
	* By default it finds the JTable that maps to the same resource as the model.
	* Assume class name of JTable is in format $ComponentName$Table$TableName$
	*/
	function _listFrom() {
		// To be overrided if join tables
		$tbl =& JTable::getInstance($this->_table_name, ucwords(JofeComponent::parseComponentName($this)) . 'Table');
		if ($tbl) {
			return 'FROM ' . $tbl->getTableName();
		} else {
			JError::raiseError('500', '_listFrom function is not overrided and can not get JTable associated with the model ' . get_class($this));
		}
	}

	function _listWhere() {
		$where = $this->_listWhere_customizedFilters();
		foreach ($this->_filters as $filter) {
			if (($filter['type'] == 'customize') || ($filter['value'] === null)) {
				continue;
			}

			$value = $filter['value'];
			switch($filter['type']) {
			case 'number':
				break;
			case 'string':
			case 'date':
			default:
				if ($filter['operator'] == 'LIKE') {
					$value = $this->_help_searchQuote($value);
				} else {
					$value = $this->_db->Quote($value);
				}
			}
			$where[] = $filter['field_name'] . ' ' . $filter['operator'] . ' ' . $value;
		}

		return empty($where)? '' : 'WHERE ' . implode(' AND ', $where);
	}

	/**
	 * To be overrided if there is groupping in place
	 */
	function _listGroupBy() {
		return '';
	}

	function _listOrderBy() {
		return empty($this->_order)? '' : 'ORDER BY ' . $this->_order . ' ' . $this->_order_dir;
	}

	function _listQuery($calc_round_rows = true) {
		$this->_loadFilters();
		$this->_loadPagingParam();

		return 'SELECT ' . ($calc_round_rows? 'SQL_CALC_FOUND_ROWS ' : '') . $this->_listSelectExpr()
			. ' ' . $this->_listFrom()
			. ' ' . $this->_listWhere()
			. ' ' . $this->_listGroupBy()
			. ' ' . $this->_listOrderBy();
	}

	/**
	 * Override this function if there is customized filter.
	 */
	function _listWhere_customizedFilters() {
		return array();
	}

	function _help_searchQuote($str) {
		return $this->_db->Quote('%' . $this->_db->getEscaped($str, true).'%', false);
	}

	function getUserStateContext() {
		if ($this->_user_state_context == null) {
			$this->_user_state_context = $this->_user_state_context();
		}
		return $this->_user_state_context;
	}

	function setUserStateContext($new_value) {
		if ($new_value != null) {
			$this->_user_state_context = $new_value;
		}
	}

	/**
	 * Set prefix for ordering and pagination input parameters.
	 *
	 * @param string $prefix
	 */
	function setInputPrefix($prefix) {
		$this->_input_prefix = $prefix;
	}

	function getFilters() {
		$this->_loadFilters();
		return $this->_filters;
	}

	function getFiltersObject() {
		$obj = new stdClass();
		foreach ($this->getFilters() as $filter) {
			$obj->$filter['filter_name'] = $filter['value'];
		}
		return $obj;
	}

	function getFilter($filter_name) {
		foreach ($this->getFilters() as $filter) {
			if ($filter['filter_name'] == $filter_name) {
				return $filter;
			}
		}
		return null;
	}

	function getFilterValue($filter_name) {
		$filter = $this->getFilter($filter_name);
		if ($filter == null) {
			return null;
		}

		return $filter['value'];
	}

	/**
	 * Set filter value to session state so it can be used later.
	 */
	function setFilter($filter_name, $filter_value) {
		if (empty($filter_name)) {
			return;
		}

		global $mainframe;
		$mainframe->setUserState($this->getUserStateContext() . $this->_input_prefix . $filter_name, $filter_value);
	}

	/**
	 * Clear filter values from session state.
	 */
	function clearFilters() {
		global $mainframe;

		$context = $this->getUserStateContext();
		foreach ($this->_filters as &$filter) {
			$mainframe->setUserState($context . $this->_input_prefix . $filter['filter_name'], null);
			$filter['value'] = null;
		}
	}

	/**
	 * @param array fields
	 */
	function setMask($mask) {
		$this->_mask = $mask;
	}

	function getList($return_row_offset = false) {
		if ($this->_list === null) {
			$this->_loadList();
		}

		if (!$return_row_offset) {
			return $this->_list;
		}

		// Calculates row offsets
		$list = $this->getList();
		$offset = $this->_limitstart;
		foreach ($list as &$item) {
			$item->_grid_row_offset = $offset ++;
		}
		return $list;
	}

	function getTotal() {
		if ($this->_list === null) {
			$this->_loadList();
		}
		return $this->_total;
	}

	function getPagination() {
		if ($this->_list === null) {
			$this->_loadList();
		}
		return $this->_pagination;
	}

	function getOrder() {
		return $this->_order;
	}

	function getOrderDir() {
		return $this->_order_dir;
	}

	/**
	 * Get an object with data enough for a grid.
	 * @return stdClass The object that contains grid information.
	 *         obj->data
	 *		      ->order
	 *            ->order_dir
	 *            ->total
	 *            ->limit
	 *            ->limitstart
	 *            ->input_prefix
	 *            ->filters
	 */
	function getGridData() {
		$list = $this->getList();
		$offset = $this->_limitstart;
		foreach ($list as &$item) {
			$item->_grid_row_offset = $offset ++;
		}

		$obj = new stdClass();
		$obj->data = $this->getList();
		$obj->order = $this->_order;
		$obj->order_dir = $this->_order_dir;
		$obj->total = $this->_total;
		$obj->limit = $this->_limit;
		$obj->limitstart = $this->_limitstart;
		$obj->input_prefix = $this->_input_prefix;
		$obj->filters =& $this->getFiltersObject();
		return $obj;
	}
}
?>
