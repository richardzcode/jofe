<?php
/**
 * @package		Jofe
 * @subpackage	Application
 * @version		1.0
 *
 * @license MIT
*/

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('jofe.application.application');
jimport('jofe.application.controller');
jimport('jofe.application.model');
jimport('jofe.application.table');
jimport('jofe.application.view');
jimport('jofe.html.html');
jimport('jofe.html.form');

class JofeComponent {
	private $_name = '';
	private $_path = '';

	protected $_default_controller = '';

	private $_controllers = array();
	private $_models = array();

	public function __construct() {
		$this->_name = strtolower(preg_replace('/Component/i', '', get_class($this)));
		$this->_path = JPATH_BASE . DS . 'components' . DS . $this->getFolderName();
	}

	/**
	 * Parse out component name from Controller/Model/Table/View class name
	 *
	 * @param mixed $src String or Object
	 */
	public static function parseComponentName($src) {
		if (is_object($src)) {
			$src = get_class($src);
		}
		if (preg_match("/(\w+)(Controller|Model|Table|View)/i", $src, $matches)) {
			return strtolower($matches[1]);
		}
		return '';
	}

	public function &getController($name) {
		if (!isset($this->_controllers[$name])) {
			require_once $this->getPath() . DS . 'controller.php';

			$class_name = ucwords($this->getName()) . 'Controller';
			if (!empty($name)) {
				$path = $this->getPath() . DS . 'controllers' . DS . $name . '.php';
				if (file_exists($path)) {
					require_once $path;
					$class_name .= ucwords($name);
				}
			}
			$ret = new $class_name(array('name' => $name));
			$this->_controllers[$name] =& $ret;
		}
		
		return $this->_controllers[$name];
	}

	/**
	 * Parse out JModel name and prefix from Controller/Model/Table/View class name.
	 * Then create the model object
	 *
	 * @param mixed $src String or Object
	 */
	public static function &getModel($src) {
		if (is_object($src)) {
			$src = get_class($src);
		}
		if (preg_match("/(\w+)(Controller|Model|Table|View)(\w+)/i", $src, $matches)) {
			$model =& JModel::getInstance($matches[3], ucwords($matches[1]) . 'Model');
			return $model;
		}
		return null;
	}

	/**
	 * Parse out JTable name and prefix from Controller/Model/Table/View class name.
	 * Then create the table object
	 *
	 * @param mixed $src String or Object
	 */
	public static function &getTable($src) {
		if (is_object($src)) {
			$src = get_class($src);
		}
		if (preg_match("/(\w+)(Controller|Model|Table|View)(\w+)/i", $src, $matches)) {
			$tbl =& JTable::getInstance($matches[3], ucwords($matches[1]) . 'Table');
			return $tbl;
		}
		return null;
	}
	
	public function getURI($params = array()) {
		$parts = array('index.php?option=com_' . $this->getName());
		$parts = array_merge($parts, $params);
		return JRoute::_(implode('&', $parts));
	}

	public function getName() {
		return $this->_name;
	}

	public function getFolderName() {
		return 'com_' . strtolower($this->_name);
	}

	public function getPath() {
		return $this->_path;
	}

	public function prepare() {
		// JModel
		$path = JPATH_SITE . DS . 'components' . DS . $this->getFolderName() . DS . 'models';
		if (file_exists($path)) {
			JModel::addIncludePath($path);
		}
		// JTable
		$path = JPATH_SITE . DS . 'components' . DS . $this->getFolderName() . DS . 'tables';
		if (file_exists($path)) {
			JTable::addIncludePath($path);
		} else {
			$path = JPATH_ADMINISTRATOR . DS . 'components' . DS . $this->getFolderName() . DS . 'tables';
			if (file_exists($path)) {
				JTable::addIncludePath($path);
			}
		}

		// Params
		$controller = JRequest::getWord('controller');
		$task = JRequest::getWord('task');
		$view = JRequest::getWord('view');
		$layout = JRequest::getWord('layout');
		if (empty($controller) && !empty($view)) { // Joomla menu system is view based. Set $controller_name by $view in this case.
			JRequest::setVar('controller', $view);
		}
		if (empty($controller) && !empty($this->_default_controller)) {
			$controller = $this->_default_controller;
			JRequest::setVar('controller', $controller);
		}
		if (empty($task)) { // Joomla menu system is layout based. Set $task by $layout in this case.
			JRequest::setVar('task', $layout);
		}
		if (!empty($controller)) {
			JRequest::setVar('view', $controller);
		}
		if (empty($layout) && !empty($task)) {
			JRequest::setVar('layout', $task);
		}
	}

	public function run() {
		$app =& JofeApplication::getInstance();
		$app->setComponent($this);
		
		$this->prepare();
		
		$controller = $this->getController(JRequest::getWord('controller'));
		$task = JRequest::getWord('task');
		$controller->execute($task);
		$controller->redirect();
	}

	/**
	 * Add component level javascript
	 */
	public function addScript($file_name) {
		$doc =& JFactory::getDocument();
		$doc->addScript(JURI::base() . 'components/com_' . $this->getName() . '/js/' . $file_name);
	}

	/**
	 * Add component level CSS
	 */
	public function addStyleSheet($file_name) {
		$doc =& JFactory::getDocument();
		$doc->addStyleSheet(JURI::base() . 'components/com_' . $this->getName() . '/css/' . $file_name);
	}
}
?>
