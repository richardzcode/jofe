<?php
/**
 * @package    Jofe.Sample
 * @subpackage Blog
*/
 
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('jofe.application.component');

class BlogComponent extends JofeComponent{
	protected $_default_controller = 'post';
	
	public function  prepare() {
		parent::prepare();
		$this->addStyleSheet('blog.css');
		$this->addScript('jquery-1.4.4.min.js');
		$this->addScript('blog.js');
	}
}

$com = new BlogComponent();
$com->run();