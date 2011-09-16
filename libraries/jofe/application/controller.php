<?php
/**
 * @package		Jofe
 * @subpackage	Application
 * @version		1.0
 *
 * @license MIT
*/

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.application.component.controller');

class JofeController extends JController{
	public function __construct($config = array()) {
		if (empty($config['default_task'])) {
			$config['default_task'] = 'index';
		}
		
		parent::__construct($config);
	}
	
	public function getComponent() {
		return JofeApplication::getComponent(JofeComponent::parseComponentName($this));
	}
	
	/**
	 * By default get related JTable object for actions.
	 * Override this function if actions are performed on other resources.
	 */
	public function getResource() {
		return $this->getComponent()->getTable($this);
	}

	/**
	 * Override JController 1) Remove creation of JModel; 2) Give prefix as %ComponentName%View.
	 */
	public function display($cachable=false)
	{
		$document =& JFactory::getDocument();

		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd( 'view', $this->getName() );
		$viewLayout	= JRequest::getCmd( 'layout', null );
		if ($viewLayout === null) {
			$viewLayout = 'index';
			JRequest::setVar('layout', 'index');
		}

		$component_name = JofeComponent::parseComponentName($this);
		$prefix = ucwords($component_name) . 'View';

		$view = & $this->getView( $viewName, $viewType, $prefix, array( 'base_path'=>$this->_basePath));

		// Set the layout
		$view->setLayout($viewLayout);

		// Display the view
		if ($cachable && $viewType != 'feed') {
			global $option;
			$cache =& JFactory::getCache($option, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}
	}

	public function  execute($task) {
		$this->_task = $task; // Set _task before beforeExecute
		
		if ($this->beforeExecute($task)) {
			parent::execute($task);
		} else {
			if (empty($this->_redirect)) {
				JofeApplication::enqueueMessage('Error happened in processing ' . $task . '.', 'error');
				$this->setRedirect(JRoute::_('/'));
			}
		}
	}

	/**
	 * @return false to stop execute
	 */
	public function beforeExecute($task) {
		$post = JRequest::get('POST');
		return $this->_process($post);
	}

	/**
	 * Process HTTP POST. Adjust request parameters before executing task.
	 */
	public function _process($post) {
		if (!empty($this->_task)) {
			$method = '_process' . ucwords($this->_task);
			if (method_exists($this, $method)) {
				return call_user_func_array(array($this, $method), array(&$post));
			}
		}
		return true;
	}
	
	public function index() {
		$this->display();
	}
	
	public function show() {
		$this->display();
	}
	
	/**
	 * For both new and create. If POST is empty then just show the new form.
	 */
	public function create() {
		$this->display();
	}
	
	/**
	 * For both edit and update. If POST is empty then just show the edit form.
	 */
	public function update() {
		$this->display();
	}
	
	public function destory() {
		$uri = $this->getComponent()->getURI(array('controller=' . $this->getName()));
		$this->setRedirect($uri);
	}
	
	public function _processCreate($post) {
		if (empty($post)) {
			return true;
		}
		$tbl = $this->getResource();
		if ($tbl == null) {
			return false;
		}
		if ($tbl->bind($post) && $tbl->store()) {
			JofeApplication::enqueueMessage('Created.');
			$tbl_key = $tbl->getKeyName();
			$uri = $this->getComponent()->getURI(
				array(
					'controller=' . $this->getName(),
					'task=show',
					$tbl_key . '=' . $tbl->$tbl_key
				)
			);
			$this->setRedirect($uri);
			return false;
		}
		
		JofeApplication::enqueueMessage('Error happened in creation.', 'error');
		return true;
	}
	
	public function _processUpdate($post) {
		if (empty($post)) {
			return true;
		}
		$tbl = $this->getResource();
		if ($tbl != null) {
			if ($tbl->bind($post) && $tbl->store()) {
				JofeApplication::enqueueMessage('Updated.');
			}
		}
		return true;
	}
	
	public function _processDestory($post) {
		$tbl = $this->getResource();
		if (($tbl != null) && ($tbl->loadFromRequest())) {
			return $tbl->delete();
		}
		return false;
	}
}
?>
