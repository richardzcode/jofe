<?php
/**
 * @package		Jofe
 * @subpackage	Application
 * @version		1.0
 *
 * @license MIT
*/

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.application.component.view');

class JofeView extends JView {
	public function getViewType() {
		$document =& JFactory::getDocument();
		return $document->getType();
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
	 * Prepare for display
	 *
	 * @return mixed HTML - bool Continue or stop display.
	 *               JSON - data
	 */
	public function prepare() {
		$layout = JRequest::getWord('layout', '');
		if (empty($layout)) {
			return true;
		}
		if (!empty($layout)) {
			$method = '_prepare' . ucwords($layout);
			if (method_exists($this, $method)) {
				return call_user_func_array(array($this, $method), array());
			}
		}
		
		return true;
	}

	public function  display($tpl = null) {
		switch($this->getViewType()) {
			case 'json':
				$data = $this->prepare();
				echo json_encode($data);
				return;
			default:
				if ($this->prepare()) {
					parent::display($tpl);
				}
				return;
		}
	}
	
	public function _prepareIndex() {
		$model =& $this->getComponent()->getModel($this);
		if ($model != null) {
			$this->assignRef('griddata', $model->getGridData());
		}
		return true;
	}
	
	public function _prepareCreate() {
		$this->assignRef('resource', $this->getResource());
		return true;
	}
	
	public function _prepareShow() {
		$tbl =& $this->getResource();
		if ($tbl != null) {
			$tbl->loadFromRequest();
			$this->assignRef('resource', $tbl);
		}
		return true;
	}
}
?>
