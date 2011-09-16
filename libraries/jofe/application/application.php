<?php
/**
 * @package		Jofe
 * @subpackage	Application
 * @version		1.0
 *
 * @license MIT
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

class JofeApplication {
	private static $_instance = null;

	private static $_components = array();

	private function __construct() {
		//
	}

	public static function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new JofeApplication();
		}
		return self::$_instance;
	}

	public function setComponent($component) {
		$this->_components[$component->getName()] = $component;
	}

	public static function &getComponent($name) {
		$name_lower = strtolower($name);
		if (!isset(self::$_components[$name])) {
			$class_name = ucwords($name) . 'Component';
			if (!class_exists($class_name)) {
				$path = JPATH_BASE . DS . 'components' . DS . 'com_' . $name_lower . DS . $name_lower . '.php';
				if (file_exists($path)) {
					include $path;
				}
			}

			if (class_exists($class_name)) {
				self::$_components[$name] = new $class_name();
			}
		}
		return self::$_components[$name];
	}

	/**
	 * Enqueue a message. Just a wrapper of JApplication function for now.
	 */
	public static function enqueueMessage($msg, $type = 'message') {
		global $mainframe;
		$mainframe->enqueueMessage($msg, $type);
	}
}
?>
