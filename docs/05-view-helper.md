View helper
===========

The module comes with the `uploads()` view helper that makes it really easy to render an uploaded object's public url.

~~~php
<?php /** @var Zend\View\Renderer\PhpRenderer $this */ ?>

<?php if ($this->uploads()->has($id)): ?>
	<img src="<?= $this->uploads()->get($id) ?>"/>
	<img src="<?= $this->uploads()->getPublicFile($id, array('resize' =>array('width' => 500,'height' => 100))) ?>"/>
<?php endif ?>
~~~

You have access to the following methods from this helper:

* `get($id)`
* `has($id)`
* `getContainer()` - Returns the upload container.
