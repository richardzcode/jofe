<?php
/**
 * @package    Jofe.Sample
 * @subpackage Blog
*/

defined('_JEXEC') or die( 'Restricted access' );
require_once JPATH_COMPONENT . DS . 'view.php';

class BlogViewPost extends BlogView
{
	public function _prepareFront() {
		$post =& JTable::getInstance('post', 'BlogTable');
		$posts = $post->find('list', array(), array(), 'created_on DESC', 5);
		$this->assignRef('posts', $posts);
		return true;
	}
}