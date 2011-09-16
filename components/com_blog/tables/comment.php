<?php
/**
 * @package    Jofe.Sample
 * @subpackage Blog
*/

defined('_JEXEC') or die( 'Restricted access' );

class BlogTableComment extends JofeTable
{
	public $id			= null;
	public $post_id		= null;
	public $name		= null;
	public $content		= null;
	public $created_on	= null;

	public function __construct(&$db)
	{
		parent::__construct('#__blog_comments', 'id', $db);
	}
}
