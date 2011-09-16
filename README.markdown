# Jofe - Joomla 1.5 extension

Some common features I abstracted out when work with Joomla 1.5.

I wasn't aiming for a full, big library. Just something that helps rapid development.

Simplicity is the key.

For example. It does include an inflector library. So instead of what you see in Rails:
<pre>
	post.comments
	post.comments.size
</pre>

In Jofe it is like
<pre>
	post._obj_comment
	post._count_comment
</pre>

Note that although it is a one-to-many relationship, comment is still referred in singular form by post.

It is not very cool by looking at it, but simply works for developers.

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
	* Simple ORM features
		* find function
		* object relationship specified in relates_to_one, and relates_to_many.
		* A couple of helper functions
	* Triggers
* JofeModel - Extends JModel. Handles pagination/sorting/filtering

### Side extensions:

Have dependency on jQuery and need some CSS

* JofeForm - Helps create form.
* JofeGrid - Takes data returned by JofeModel and renders grid.
<pre>
	$grid = new JofeGrid(array(
				array(
					'header' => 'Title',
					'width' => '50%',
					'field' => 'title',
					'callback' => 'post_grid_col_title',
					'sortable' => true
				),
				array(
					'header' => 'Created',
					'width' => '20%',
					'field' => 'created_on',
					'sortable' => true
				)
			),
			$this->griddata
		);
	$grid->render();
</pre>

## Sample component:

/components/com_blog