<?php
/**
 * @package    Jofe.Sample
 * @subpackage Blog
*/

defined('_JEXEC') or die( 'Restricted access' );
require_once JPATH_COMPONENT . DS . 'controller.php';

class BlogControllerComment extends BlogController
{
	public function _processCreate($post) {
		parent::_processCreate($post);
		$uri = $this->getComponent()->getURI(array(
			'controller=post',
			'task=show',
			'id=' . $post['post_id']
		));
		$this->setRedirect($uri);
		return false;
	}
}
