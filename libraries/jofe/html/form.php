<?php
/**
 * @package		Jofe
 * @subpackage	Html
 * @version		1.0
 *
 * @license MIT
 */

class JofeForm {
	private $_component_name = '';
	private $_resource_name = '';
	private $_table = null;
	
	private $_options = array(
		'readonly' => false,
		'form_action' => null,
		'form_method' => 'POST',
		'form_title' => '',
		'task' => 'update',
		'field_wrapper_tag' => 'div',
		'field_wrapper_class' => 'jofeform-field',
		'field_class_suffix' => '',
		'field_label_separator' => ''
	);

	/**
	 * @param mixed $options Array or JofeTable
	 */
	public function  __construct($component_name, $resource_name, $options = array()) {
		$this->_component_name = $component_name;
		$this->_resource_name = $resource_name;

		jimport('joomla.filesystem.path');
		if($path = JPath::find(JTable::addIncludePath(), strtolower($resource_name).'.php')) {
			$this->_table = JTable::getInstance($resource_name, ucwords($component_name) . 'Table');
		}
		$this->_options['form_title'] = ucfirst($component_name) . ' ' . ucfirst($resource_name);

		$this->_options = array_merge($this->_options, $options);

		if (empty($this->_options['form_action'])) {
			$this->_options['form_action'] = JRoute::_('index.php?option=com_' . $this->_component_name . '&controller=' . $this->_resource_name . '&task=' . $this->_options['task']);
		}
	}

	public function setTable($table) {
		if ($table == null) {
			return;
		}
		$this->_table = $table;
		$title = $this->_table->getTitle();
		if (!empty($title)) {
			$this->_options['form_title'] = $title;
		}
	}

	/**
	 * @param mixed $set If null then nothing, else should be a boolean that set to readonly flag of the form.
	 * @return boolean If the form is readonly.
	 */
	public function readonly($set = null) {
		if ($set !== null) {
			$this->_options['readonly'] = $set;
		}
		return $this->_options['readonly'];
	}

	/**
	 * Returns start tag of form HTML.
	 *
	 * @param array $options Options array(
	 *             'action' => '', // The url that form submit to. default value base on component_name and resource_name.
	 *             'method' => 'POST|GET', // Default POST
	 *             'additional' => '' // Additional attributes of the element.
	 *       )
	 */
	public function start($options = array()) {
		if (isset($options['action'])) {
			$action = $options['action'];
		} else {
			$action = $this->_options['form_action'];
		}
		if (isset($options['method'])) {
			$method = $options['method'];
		} else {
			$method = $this->_options['form_method'];
		}
		$ret = '<form action="' . $action . '" method="' . $method . '" class="' . $options['class'] . '"' . (isset($options['additional'])? ' ' . $options['additional'] : '') . '>';
		if (!isset($options['no_message']) || !$options['no_message']) {
			$ret .= $this->message();
		}
		if (!isset($options['no_error_message']) || !$options['no_error_message']) {
			$ret .= $this->errorMessage();
		}
		return $ret;
	}

	/**
	 * Returns end tag of form HTML.
	 */
	public function end() {
		return '</form>';
	}

	public function message($message = null) {
		if ($message != null) {
			return '<div class="jofeform-message">' . $message . '</div>';
		} else {
			return '<div class="jofeform-message" style="display: none;"></div>';
		}
	}

	public function errorMessage() {
		return '<div class=jofeform-error-message" style="display: none;"></div>';
	}

	/**
	 * Return form title HTML, include title and action
	 *
	 * @param array $actions array (
	 *                             'task',
	 *                             array(
	 *                                   'label' => '',
	 *                                   'task' => '',
	 *                                   'url' => '', // URL of the action
	 *                                   'target' => ''
	 *                             )
	 *                       )
	 *                       Default action of list is create
	 */
	public function title($actions = null) {
		if ($actions === null) {
			$actions = array();
			switch($this->_options['task']) {
				case 'list':
					$actions = array('create');
					break;
			}
		}
		$title_actions = '';
		foreach ($actions as $action) {
			if (is_string($action)) {
				$url = JRoute::_('index.php?option=com_' . $this->_component_name . '&controller=' . $this->_resource_name . '&task=' . $action);
				$title_actions .= ' <a class="anchor-button" href="' . $url . '">' . ucfirst($action) . '</a>';
				continue;
			}
			if (!is_array($action)) {
				continue;
			}
			if (empty($action['label']) && !empty($action['task'])) {
				$action['label'] = ucwords($action['task']);
			}
			if (empty($action['label'])) {
				continue;
			}
			$url = $action['url'];
			if (!empty($action['task'])) {
				if (!empty($this->_table) && in_array($action['task'], array('show', 'update', 'destory'))) {
					$tbl_key = $this->_table->getKeyName();
					$params = '&' . $tbl_key . '=' . $this->_table->$tbl_key;
				}
				$url = JRoute::_('index.php?option=com_' . $this->_component_name . '&controller=' . $this->_resource_name . '&task=' . $action['task'] . $params);
			}
			if (empty($url)) {
				$title_actions .= ' ' . $action['label'];
			} else {
				$title_actions .= ' <a class="anchor-button" href="' . $url . '"' . (empty($action['target'])? '' : ' target="' . $action['target'] . '"') . '>' . $action['label'] . '</a>';
			}
		}
		$ret = '<div class="form-title"><div class="form-title-action">' . $title_actions
			. '</div><div class="form-title-title">' . $this->_options['form_title'] . '</div></div>';
		if (!empty($actions)) {
			$ret .= '<div class="clear"></div>';
		}
		return $ret;
	}

	/**
	 * Merge field information into form element options. $options override $field
	 *
	 * @param array $options Options array(
	 *             'id' => '',
	 *             'name' => '',
	 *             'label' => '',
	 *             'value' => '',
	 *             'class' => '',
	 *             'additional' => '', // Additional attributes of the element.
	 *             'wrapper_tag' => 'span',
	 *             'wrapper_class' => '',
	 *             'label_separator' => '',
	 *             'lookup' => array()
	 *       )
	 */
	private function mergeOptions($field, &$options = array()) {
		if (is_string($options)) {
			$options = array('value' => $options);
		}
		if (!isset($options['readonly'])) {
			$options['readonly'] = $this->readonly();
		}

		if (is_string($field)) {
			if (!isset($options['id'])) {
				$options['id'] = $this->_component_name . '_' . $this->_resource_name . '_' . $field;
			}
			if (!isset($options['name'])) {
				$options['name'] = $field;
			}
			if (!isset($options['label'])) {
				$options['label'] = ucwords(preg_replace('/_/', ' ', $field));
			}
			if (($this->_table != null) && isset($this->_table->$field)) {
				if (!isset($options['value'])) {
					$options['value'] = $this->_table->$field;
				}
				if ($options['value'] == null) {
					$options['value'] = '';
				}
			}
		}

		if (!isset($options['wrapper_tag'])) {
			$options['wrapper_tag'] = $this->_options['field_wrapper_tag'];
		}
		if (!isset($options['wrapper_class'])) {
			$options['wrapper_class'] = '';
		}
		if (!isset($options['label_separator'])) {
			$options['label_separator'] = $this->_options['field_label_separator'];
		}

		return $options;
	}

	public function wrap($wrapper_tag, $label, $separator, $field, $wrapper_class = '') {
		$wrapper_class .= (empty($wrapper_class)? '' : ' ') . $this->_options['field_wrapper_class'];
		return '<' . $wrapper_tag . ' class="' . $wrapper_class . '">' . $label . $separator . $field . '</' . $wrapper_tag . '>';
	}

	private function toHtmlAttr($value) {
		return preg_replace('/\n/', '\n', htmlspecialchars($value));
	}

	private function _hidden_plain($options) {
		return sprintf('<input type="hidden" id="%s" name="%s" value="%s"/>', $options['id'], $options['name'], $this->toHtmlAttr($options['value']));
	}

	public function hidden($field, $options = array()) {
		if (!isset($options['wrapper_tag'])) {
			$options['wrapper_tag'] = 'span';
		}
		$options['label_separator'] = '';
		$this->mergeOptions($field, &$options);
		$value = preg_replace('/\n/', '\n', htmlspecialchars($options['value']));
		$field = sprintf('<input type="hidden" id="%s" name="%s" class="%s" value="%s" %s/>',
					$options['id'], $options['name'], $options['class'], $this->toHtmlAttr($options['value']),
					$options['additional']
				);
		return $this->wrap($options['wrapper_tag'], '', $options['label_separator'], $field, 'jofeform-field jofeform-field-hidden');
	}

	/**
	 * Returns text input HTML
	 */
	public function text($field, $options = array()) {
		$this->mergeOptions($field, &$options);
		$label = '<label>' . $options['label'] . '</label>';
		if ($options['readonly']) {
			$field = htmlspecialchars($options['value']);
			if (!$this->readonly()) {
				$field .= $this->_hidden_plain($options);
			}
		} else {
			$field = sprintf('<input type="text" id="%s" name="%s" class="%s" value="%s" %s/>',
					$options['id'], $options['name'], $options['class'], $this->toHtmlAttr($options['value']),
					$options['additional']
				);
		}
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $field, $options['wrapper_class']);
	}

	/**
	 * Returns textarea input HTML
	 */
	public function textArea($field, $options = array()) {
		$this->mergeOptions($field, $options);
		$label = '<label>' . $options['label'] . '</label>';
		if ($options['readonly']) {
			$field = nl2br(htmlspecialchars($options['value']));
			if (!$this->readonly()) {
				$field .= $this->_hidden_plain($options);
			}
		} else {
			$field = sprintf('<textarea type="text" id="%s" name="%s" class="%s" %s/>%s</textarea>',
					$options['id'], $options['name'], $options['class'], $options['additional'],
					htmlspecialchars($options['value'])
				);
		}
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $field);
	}

	public function submit($label = 'Submit', $options = array()) {
		$this->mergeOptions($field, $options);
		$format = '<' . $options['wrapper_tag'] . ' class="jofeform-field"><input type="submit" id="%s" name="%s" value="%s" class="%s" %s/></' . $options['wrapper_tag'] . '>';
		$ret = sprintf($format, $options['id'], $options['name'], $label, $options['class'], $options['additional']);
		return $ret;
	}

	public function button($label = 'button', $options = array()) {
		$this->mergeOptions($field, $options);
		$format = '<' . $options['wrapper_tag'] . ' class="jofeform-field"><button id="%s" name="%s" class="%s" %s>%s</button></' . $options['wrapper_tag'] . '>';
		$ret = sprintf($format, $options['id'], $options['name'], $options['class'], $options['additional'], $label);
		return $ret;
	}

	/**
	 * Returns checkbox HTML
	 */
	public function checkbox($field, $options = array()) {
		if (!isset($options['checkbox_value'])) {
			$options['checkbox_value'] = 1;
		}
		$this->mergeOptions($field, $options);
		if ($options['checked'] || ($options['checkbox_value'] == $options['value'])) {
			$options['additional'] .= ' checked';
		}
		if ($options['readonly']) {
			$options['additional'] .= ' readonly';
		}
		$label = '<label>' . $options['label'] . '</label>';
		$field = sprintf('<input type="checkbox" id="%s" name="%s" class="%s" value="%s" %s/>',
					$options['id'], $options['name'], $options['class'], $this->toHtmlAttr($options['checkbox_value']),
					$options['additional']
				);
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $field, $options['wrapper_class']);
	}

	/**
	 * Returns radio button HTML
	 */
	public function radio($field, $options = array()) {
		$field = $this->mergeOptions($field, &$options);
		$label = '<label>' . $options['label'] . '</label>';
		$values = $options['lookup'];
		if ($options['readonly']) {
			foreach ($values as $value) {
				if ($options['value'] == $value['value']) {
					$radios = htmlspecialchars($value['label']);
					if (!$this->readonly()) {
						$radios .= $this->_hidden_plain($options);
					}
					break;
				}
			}
		} else {
			$radios = '';
			$class = empty($options['class'])? '' : $options['class'];
			foreach ($values as $value) {
				$radios .= sprintf(' <input type="radio" class="%s" name="%s" value="%s" %s %s/> %s', $class, $options['name'], addslashes($value['value']),
						($options['value'] == $value['value'])? 'checked' : '',
						$options['additional'], htmlspecialchars($value['label'])
					);
			}
		}
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $radios);
	}

	/**
	 * Returns drop-down box HTML
	 */
	public function select($field, $options = array()) {
		$field = $this->mergeOptions($field, &$options);
		$label = '<label>' . $options['label'] . '</label>';
		$values = $options['lookup'];
		$class = empty($options['class'])? '' : $options['class'];
		if ($options['readonly']) {
			foreach ($values as $value) {
				if ($options['value'] == $value['value']) {
					$select = htmlspecialchars($value['label']);
					if (!$this->readonly()) {
						$select .= $this->_hidden_plain($options);
					}
					break;
				}
			}
		} else {
			$select = sprintf('<select id="%s" name="%s" class="%s" %s>', $options['id'], $options['name'], $options['class'], $options['additional']);
			if (!empty($options['empty'])) {
				$select .= ' <option>' . $options['empty'] . '</option>';
			}
			foreach ($values as $value) {
				if (is_a($value, 'JTable')) {
					$val = array();
					$tbl_key = $options['tbl_key'];
					if (empty($tbl_key)) {
						$tbl_key = $value->getKeyName();
					}
					$val['value'] = $value->$tbl_key;

					$tbl_title = $options['tbl_title'];
					if (!empty($tbl_title)) {
						$val['label'] = $value->$tbl_title;
					} elseif (method_exists($value, 'getTitle')) {
						$val['label'] = $value->getTitle();
					} else {
						$val['label'] = $value->$tbl_key;
					}
				} else {
					$val = $value;
				}
				$select .= sprintf(' <option value="%s" %s>%s</option>', addslashes($val['value']),
					($options['value'] == $val['value'])? 'selected' : '',
					htmlspecialchars($val['label'])
				);
			}
			$select .= '</select>';
		}
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $select);
	}

	/**
	 * Returns text input HTML
	 */
	public function file($field, $options = array()) {
		$this->mergeOptions($field, &$options);
		$label = '<label>' . $options['label'] . '</label>';
		if ($options['readonly']) {
			$field = htmlspecialchars($options['value']);
			if (!$this->readonly()) {
				$field .= $this->_hidden_plain($options);
			}
		} else {
			$field = sprintf('<input type="file" id="%s" name="%s" class="%s" value="%s" %s/>',
					$options['id'], $options['name'], $options['class'], $this->toHtmlAttr($options['value']),
					$options['additional']
				);
		}
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $field, $options['wrapper_class']);
	}

	/**
	 * Returns date picker input HTML
	 */
	public function date($field, $options = array()) {
		$this->mergeOptions($field, &$options);
		$label = '<label>' . $options['label'] . '</label>';
		if ($options['readonly']) {
			$field = $options['value'];
			if (!$this->readonly()) {
				$field .= $this->_hidden_plain($options);
			}
		} else {
			$field = sprintf('<input type="text" id="%s" name="%s" class="jofeinput-datepicker %s" value="%s" %s/>',
					$options['id'], $options['name'], $options['class'], empty($options['value'])? '' : $options['value'],
					$options['additional']
				);
		}
		return $this->wrap($options['wrapper_tag'], $label, $options['label_separator'], $field, $options['wrapper_class']);
	}
}
?>
