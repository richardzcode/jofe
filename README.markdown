# Jofe - Joomla 1.5 extension

Some common features I abstracted out when work with Joomla 1.5.

I wasn't aiming for a full, big library. Just something that helps rapid development.

Simplicity is the key.


## Location: /libraries/jofe/

## Extensions:

### Major extensions:

* JofeComponent - Simplify the component creation.
<pre>
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
</pre>
* JofeController - Extends JController. Wraps RESTful actions.
* JofeView - Extends JView. Very small extention just created some default RESTful responses.
* JofeTable - Extends JTable.
** Simple ORM features
*** find function
*** object relationship specified in relates_to_one, and relates_to_many.
*** A couple of helper functions
** Triggers
* JofeModel - Extends JModel. Handles pagination/sorting/filtering

### Side extensions:

Have dependency on jQuery and need some CSS

* JofeForm - Helps create form.
* JofeGrid - Takes data returned by JofeModel and renders grid.

## Sample component:

/components/com_blog