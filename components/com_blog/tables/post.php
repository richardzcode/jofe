<?php
/**
 * @package    Jofe.Sample
 * @subpackage Blog
*/

defined('_JEXEC') or die( 'Restricted access' );

class BlogTablePost extends JofeTable
{
	public $id			= null;
	public $author_id	= null;
	public $title		= null;
	public $content		= null;
	public $created_on	= null;

	public $_relates_to_many = array(
		array(
			'foreign_table' => 'comment',
			'foreign_key' => 'post_id'
		)
	);

	public function __construct(&$db)
	{
		parent::__construct('#__blog_posts', 'id', $db);
	}
	
	protected function beforeDelete() {
		foreach($this->_obj_comment as $comment) {
			$this->addAfterActionDelete($comment);
		}
	}
}
