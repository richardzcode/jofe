<?php
/**
 * @package		Jofe
 * @subpackage	Application
 * @version		1.0
 *
 * @license MIT
 *
 * Simple ORM
 * Triggers
 * Compare
 *
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.database.table');

class JofeTable extends JTable
{
	const BOOL_TRUE		= 1;
	const BOOL_FALSE	= 0;

	/**
	 * Default order and dir used in find()
	 * array(array('class_name' => '', 'order' => '', 'order_dir' => ''))
	 *
	 * This is the place to hold default order definition of all children tables. This is a binding issue in PHP.
	 * @see http://www.php.net/manual/en/language.oop5.static.php#96402
	 */
	private static $_default_orders = array();
	
	/**
	 * Only cares one-to-one and one-to-many. Many-to-... doesn't make any difference at this stage.
	 *
	 * @var array (
	 *            'name' => '', // Only needed if has more than one relation to a same table.
	 *            'foreign_table' => '',
	 *            'key' => '',
	 *            'foreign_key' => '',
	 *            'order' => ''
	 *      )
	 */
	protected $_relates_to_one = array();
	protected $_relates_to_many = array();

	/**
	 * Calculated field list
	 *
	 * array(array('field' => '', 'value' => '', 'callback' => ''))
	 * If there is a value then return value, otherwise look for the callback.
	 */
	protected $_calc_fields = array();

	/**
	 * An array of objects. In most cases an array of JTables.
	 * Before insert/update/delete actions developer may prepare some data.
	 * Only save them when current object is saved/deleted successfully.
	 *
	 * Call addAfterActionStore() to add objects to this array.
	 *
	 * JofeTable defined afterInsert/afterUpdate/afterDelete functions will loop thrugh this array, call store() on each object.
	 */
	protected $_after_action_stores = array();

	/**
	 * An array of objects. In most cases an array of JTables.
	 * Before insert/update/delete actions developer may prepare some data.
	 * Only delete them when current object is saved/deleted successfully.
	 *
	 * Call addAfterActionDelete() to add objects to this array.
	 *
	 * JofeTable defined afterInsert/afterUpdate/afterDelete functions will loop thrugh this array, call delete() on each object.
	 */
	protected $_after_action_deletes = array();

	public function getTablePrefix() {
		return ucwords(JofeComponent::parseComponentName($this)) . 'Table';
	}

	public static function toObjectList($list, $map = null) {
		$obj_list = array();
		foreach ($list as &$item) {
			$obj_list[] = $item->toObject($map);
		}
		return $obj_list;
	}

	/**
	 * Calculate summary result on the provided list.
	 *
	 * @param string $type summary type: sum/avg/min/max
	 * @param array $list List of objects
	 * @param mixed $field Field to be calculated. For sum and avg, $field can be an array of fields
	 */
	public static function sumList($type, &$list, $field, $default = null) {
		if (empty($list)) {
			return $default;
		}
		
		switch ($type) {
		case 'sum':
			$sum = 0;
			foreach ($list as &$item) {
				if (is_array($field)) {
					foreach($field as $f) {
						$sum += $item->$f;
					}
				} else {
					$sum += $item->$field;
				}
			}
		case 'avg':
			if ($type == 'sum') {
				return $sum;
			}
			return $sum / count($list);
		case 'min':
			$min = $list[0]->$field;
			foreach ($list as &$item) {
				if ($min > $item->$field) {
					$min = $item->$field;
				}
			}
			return $min;
		case 'max':
			$max = $list[0]->$field;
			foreach ($list as &$item) {
				if ($max < $item->$field) {
					$max = $item->$field;
				}
			}
			return $max;
		}

		return $default;
	}

	/**
	 * Update list of rows.
	 *  - Delete rows not in new list;
	 *  - Add rows not in old list;
	 *  - Update rows both in new and old lists.
	 *
	 * @param array $keys Compare lists base on keys.
	 * @param boolean $force_update Update without comparing
	 */
	public static function updateList($old, $new, $keys, $force_update = false) {
		if (!empty($old)) {
			$tbl_key = $old[0]->getKeyName();
		} elseif (!empty($new)) {
			$tbl_key = $new[0]->getKeyName();
		}
		if (empty($tbl_key)) {
			return;
		}
		foreach($old as $old_item) {
			$update_item = null;
			foreach($new as $new_item) {
				$found = true;
				foreach ($keys as $key) {
					if ($old_item->$key != $new_item->$key) {
						$found = false;
						break;
					}
				}
				if ($found) {
					$update_item =& $new_item;
					break;
				}
			}

			if ($update_item == null) {
				$old_item->delete();
				continue;
			}

			if (!$force_update && $update_item->compare($old_item, array($tbl_key))) {
				$update_item->$tbl_key = $old_item->$tbl_key;
				continue;
			}

			$conditions = array();
			foreach($keys as $key) {
				$conditions[] = $key . '=' . $update_item->$key;
			}
			$save_item = $update_item->find('first', $conditions);
			if ($save_item) {
				$update_item->$tbl_key = $save_item->$tbl_key;
				$update_item->store();
			}
		}

		foreach ($new as $new_item) {
			if (empty($new_item->$tbl_key)) {
				$new_item->store();
			}
		}
	}

	/**
	 * Call this function in constructor to set the default order of the table.
	 *
	 * @order mixed One order is represented as array('order' => field_name, 'order_dir' => direction)
	 *              If there are multiple orders then put them into array
	 */
	public function setDefaultOrder($order) {
		if (!is_array($order[0])) {
			$order = array($order);
		}
		$class_name = get_class($this);
		foreach (self::$_default_orders as $o) {
			if ($o['class_name'] == $class_name) {
				$o['orders'] = $order;
				return;
			}
		}
		self::$_default_orders[] = array(
			'class_name' => $class_name,
			'order' => $order
		);
	}

	public function getDefaultOrderBy() {
		$ret = array();
		$class_name = get_class($this);
		foreach (self::$_default_orders as $o) {
			if ($o['class_name'] == $class_name) {
				foreach ($o['orders'] as $order) {
					$ret[] = $order['order'] . ' ' . $order['order_dir'];
				}
			}
		}
		return implode(', ', $ret);
	}

	/**
	 * Get visual identifier of the resource.
	 * Look for 'title', 'name', 'label', 'subject', 'caption' fields.
	 * If can not find then return id.
	 */
	public function getTitle() {
		$checks = array('name', 'title', 'label', 'subject', 'caption');
		foreach ($checks as $field) {
			if (isset($this->$field)) {
				return $this->$field;
			}
		}
		$tbl_key = $this->getKeyName();
		return $this->$tbl_key;
	}
	
	/**
	 * Load by id param in HTTP request. Look for tbl_key first, if not found then 'id'
	 */
	public function loadFromRequest() {
		$id = JRequest::getVar($this->getKeyName(), null);
		if ($id === null) {
			$id = JRequest::getVar('id', null);
		}
		if ($id === null) {
			return false;
		}
		return $this->load($id);
	}

	public function load($oid = null) {
		if (parent::load($oid)) {
			JofeTableCache::addToPool(clone $this);
			return true;
		}
		return false;
	}

	public function store($updateNulls=false) {
		if ($this->isNew()) {
			$this->beforeInsert();
		} else {
			$this->beforeUpdate();
		}
		if (parent::store($updateNulls)) {
			if ($this->isNew()) {
				$this->afterInsert();
			} else {
				$this->afterUpdate();
			}
			return true;
		}
		return false;
	}

	public function delete($oid = null) {
		$this->beforeDelete();
		if (parent::delete($oid)) {
			$this->afterDelete();
			return true;
		}
		return false;
	}

	/**
	 * Callback before store() for update;
	 */
	protected function beforeUpdate() {
		// Nothing
	}

	/**
	 * Callback before store() for insert;
	 */
	protected function beforeInsert() {
		// Nothing
	}

	/**
	 * Callback before delete().
	 */
	protected function beforeDelete() {
		// Nothing
	}

	/**
	 * Callback after store() for update; Triggers only when store success.
	 */
	protected function afterUpdate() {
		$this->afterAction();
	}

	/**
	 * Callback after store() for insert; Triggers only when store success.
	 */
	protected function afterInsert() {
		$this->afterAction();
	}

	/**
	 * Callback after delete().
	 */
	protected function afterDelete() {
		$this->afterAction();
	}

	protected function addAfterActionStore($obj) {
		$this->_after_action_stores[] = $obj;
	}

	protected function addAfterActionDelete($obj) {
		$this->_after_action_deletes[] = $obj;
	}

	protected function afterAction() {
		foreach ($this->_after_action_stores as $obj) {
			$obj->store();
		}

		foreach ($this->_after_action_deletes as $obj) {
			$obj->delete();
		}
	}

	/**
	 * Search the database table.
	 *
	 * @param string $type - list: array of rows as JTable objects;
	 *                       first: one row;
	 *                       count: count number;
	 *                       objects: array of rows as std objects, returned from JDatabase
	 * @param mixed $conditions
	 * @param array $fields
	 * @param string $order
	 * @return mixed - null if gets nothing. return one object if type is 'first', return an array of object if type is 'list', return int if type is 'count'
	 */
	public function find($type = 'list', $conditions = array(), $fields = array(), $order = null, $limit = 0, $limit_start = 0) {
		if ($type == 'count') {
			$fields = 'COUNT(*)';
		} elseif (empty($fields)) {
			$fields = '*';
		} else {
			$fields = implode(', ', $fields);
		}
		$query = 'SELECT ' . $fields . ' FROM ' . $this->getTableName();
		if (!empty($conditions)) {
			if (is_string($conditions)) {
				$query .= ' WHERE ' . $conditions;
			} else {
				$query .= ' WHERE ' . implode (' AND ', $conditions);
			}
		}
		if (empty($order)) {
			$order = $this->getDefaultOrderBy();
		}
		if (!empty($order)) {
			$query .= ' ORDER BY ' . $order;
		}
		if (!empty($limit)) {
			$query .= ' LIMIT ' . $limit_start . ', ' . $limit;
		}

		$db =& $this->getDBO();
		$db->setQuery($query);
		$class_name = get_class($this);
		switch ($type) {
		case 'list':
			$rows = $db->loadObjectList();
			$objs = array();
			if ($rows) {
				foreach ($rows as $row) {
					$obj = new $class_name($db);
					$obj->bind($row);
					$objs[] = $obj;
				}
			}
			return $objs;
		case 'first':
			$row = $db->loadObject();
			if ($row) {
				$obj = new $class_name($db);
				$obj->bind($row);
			}
			return $obj;
		case 'count':
			return $db->loadResult();
		case 'objects':
			return $db->loadObjectList();
		}
	}

	public function isNew() {
		$k = $this->_tbl_key;
		return empty($this->$k);
	}

	/**
	 * Compare to a named array/hash with this object.
	 *
	 * @param mixed $from
	 * @param array $ignore
	 * @return boolean
	 */
	public function compare($from, $ignore = array()) {
		$fromArray    = is_array( $from );
		$fromObject    = is_object( $from );

		if (!$fromArray && !$fromObject) {
			return false;
		}
		if (!is_array($ignore)) {
			$ignore = explode(' ', $ignore);
		}
		foreach ($this->getProperties() as $k => $v) {
			if (in_array($k, $ignore)) {
				continue;
			}
			if ($fromArray && isset($from[$k])) {
				if ($this->$k != $from[$k]) {
					return false;
				}
			} else if ($fromObject && isset( $from->$k )) {
				if ($this->$k != $from->$k) {
					return false;
				}
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Create a plain object represent the record.
	 *
	 * @param array $map Get related objects through relationship.
	 * @return object
	 */
	public function toObject($map = null) {
		$obj = new stdClass();
		foreach ($this->getProperties() as $k => $v) {
			$obj->$k = $this->$k;
		}

		// Calculated fields
		foreach ($this->_calc_fields as $field) {
			if (isset($field['value'])) {
				$obj->$field['field'] = $field['value'];
			}
			if (!empty($field['callback'])) {
				$obj->$field['field'] = call_user_func(array(get_class($this), $field['callback']), $this);
			}
		}

		if ($map == null) {
			return $obj;
		}

		if (is_string($map)) {
			$map = array($map);
		}
		foreach($map as $key => $val) {
			if (is_array($val)) {
				$t = $key;
				$child_map = $val;
			} else {
				$t = $val;
			}
			$child = $this->$t;
			if (is_array($child)) {
				$obj->$t = array();
				foreach($child as &$item) {
					$o = $item->toObject($child_map);
					array_push($obj->$t, $o);
				}
			} elseif(is_numeric($child)) {
				$obj->$t = $child;
			} elseif($child != null) {
				$obj->$t =& $child->toObject($child_map);
			}
		}

		return $obj;
	}

	public function toFields() {
		$fields = $this->getFields();
		$ret = array();
		foreach ($fields as $field) {
			$name = $field->name;
			$field->value = $this->$name;
			$ret[$name] = $field;
		}
		return $ret;
	}

	public function  __toString() {
		return json_encode($this->toObject());
	}

	public function __get($name) {
		if (strpos($name, '_obj_') === 0) {
			return $this->getRelatesObj(substr($name, 5));
		}

		if (strpos($name, '_count_') === 0) {
			return $this->getRelatesCount(substr($name, 7));
		}

		return $this->getCalcValue($name);
	}

	/**
	 * Get calculated field.
	 *
	 * @param string $name Calculated field name
	 * @return mixed Calculated field value if exists.
	 */
	public function getCalcValue($name) {
		foreach ($this->_calc_fields as $field) {
			if ($field['field'] == $name) {
				if (isset($field['value'])) {
					return $field['value'];
				}

				$method = $field['callback'];
				if (empty($method)) {
					$method = '_calc_field_' . $field['field'];
				}
				if (method_exists($this, $method)) {
					return call_user_func_array(array($this, $method), array($this));
				}
				break;
			}
		}
		return;
	}

	public function getRelatesObj($name) {
		foreach ($this->_relates_to_one as $relation) {
			if (($relation['foreign_table'] != $name) && (!isset($relation['name']) || ($relation['name'] != $name))) {
				continue;
			}

			if (isset($relation['object'])) {
				return $relation['object'];
			}

			$obj =& JTable::getInstance($relation['foreign_table'], $this->getTablePrefix());
			if ($obj == null) {
				return;
			}
			if (!isset($relation['key'])) {
				$relation['key'] = $this->getKeyName();
			}
			$value = $this->$relation['key'];
			if ($value == null) {
				return null;
			}
			if (isset($relation['foreign_key']) && ($relation['foreign_key'] != $obj->getKeyName())) { // Default foreign key of one-to-one relationship is relates object's key.
				$obj = $obj->find('first', array($relation['foreign_key'] . '=' . $this->_db->Quote($value)), $relation['order']);
			} else {
				$cache =& JofeTableCache::getFromPool(get_class($obj), $value);
				if ($cache == null) {
					if (!$obj->load($value)) {
						return null;
					}
					JofeTableCache::addToPool($obj);
				} else {
					$obj = $cache;
				}
			}
			$relation['object'] = $obj;
			return $relation['object'];
		}

		foreach ($this->_relates_to_many as $relation) {
			if (($relation['foreign_table'] != $name) && (!isset($relation['name']) || ($relation['name'] != $name))) {
				continue;
			}

			if (isset($relation['object'])) {
				return $relation['object'];
			}

			$obj =& JTable::getInstance($relation['foreign_table'], $this->getTablePrefix());
			if ($obj == null) {
				return;
			}
			if (!isset($relation['key'])) {
				$relation['key'] = $this->getKeyName();
			}
			$value = $this->$relation['key'];
			if ($value == null) {
				return array();
			}
			if (!isset($relation['foreign_key'])) {
				$relation['foreign_key'] = $this->getKeyName(); // Default foreign key of one-to-many relationship is this object's key.
			}
			if ($relation['foreign_key'] != $obj->getKeyName()) { // Cound be datatypes other than integer if it is not a primary key.
				$value = $this->_db->Quote($value);
			}
			$relation['object'] = $obj->find('list', array($relation['foreign_key'] . '=' . $value));
			return $relation['object'];
		}
	}

	public function getRelatesCount($name) {
		foreach ($this->_relates_to_one as $relation) {
			if ($relation['foreign_table'] != $name) {
				continue;
			}

			if (isset($relation['object'])) {
				return ($relation['object'] == null)? 0 : 1;
			}

			$obj =& JTable::getInstance($name, $this->getTablePrefix());
			if ($obj == null) {
				return;
			}
			if (!isset($relation['key'])) {
				$relation['key'] = $this->getKeyName();
			}
			$value = $this->$relation['key'];
			if ($value == null) {
				return 0;
			}
			if (isset($relation['foreign_key']) && ($relation['foreign_key'] != $obj->getKeyName())) {
				return $obj->find('count', array($relation['foreign_key'] . '=' . $value));
			} else {
				$cache =& JofeTableCache::getFromPool(get_class($obj), $value);
				return ($cache == null)? 0 : 1;
			}
		}

		foreach ($this->_relates_to_many as $relation) {
			if ($relation['foreign_table'] != $name) {
				continue;
			}

			if (isset($relation['object'])) {
				return count($relation['object']);
			}

			$obj =& JTable::getInstance($name, $this->getTablePrefix());
			if ($obj == null) {
				return;
			}
			if (!isset($relation['key'])) {
				$relation['key'] = $this->getKeyName();
			}
			$value = $this->$relation['key'];
			if ($value == null) {
				return 0;
			}
			if (!isset($relation['foreign_key'])) {
				$relation['foreign_key'] = $this->getKeyName();
			}
			return $obj->find('count', array($relation['foreign_key'] . '=' . $value));
		}
	}
}

/**
 * Creates a cache pool for table records.
 *
 * Currently is used for * to one relationship.
 */
class JofeTableCache {
	static $_cache_size = 100;
	static $_cache_pool = array();

	static function setPoolSize($size) {
		self::$_cache_size = $size;
	}

	static function getFromPool ($class_name, $id) {
		if (array_key_exists($key, self::$_cache_pool)) {
			return self::$_cache_pool[$key];
		}
		return null;
	}

	static function addToPool ($row) {
		if (count(self::$_cache_pool) >= self::$_cache_size) {
			return;
		}

		$id = $row->get($row->getKeyName());
		if (empty($id)) {
			return;
		}

		$key = get_class($row) . '-' . $id;
		self::$_cache_pool[$key] = $row;
	}
}
?>
