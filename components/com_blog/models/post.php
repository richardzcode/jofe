<?php
/**
 * @package    Jofe.Sample
 * @subpackage Blog
*/

defined('_JEXEC') or die( 'Restricted access' );

class BlogModelPost extends JofeModel
{
	public $_default_order = 'created_on';
	public $_default_order_dir = 'DESC';
	
	public $_table_name = 'post';
}
