Widespread
=============

Common utilities packed together

Prerequisites
-------------
PHP 5.3

Installation 
-------------

Install through composer:

`composer install`

or fetch tarball from: 

[https://github.com/alternatex/widespread/archive/master.zip](https://github.com/alternatex/widespread/archive/master.zip)

General
-------------------

Load dependencies through composer autoloader:

```php
<?php
// ...
require_once('vendor/autoload.php');

// ...
use Widespread\Widespread as Widespread;

?>
```

Metadata-Extraction
-------------------

Extract metadata from file "header" (read first 4096 bytes)

Metadata-Syntax:

```php
<?php 

// ...
$data = Widespread::FetchMetadata(

  // path to entity
  'contents/members/', 

  // properties to extract
  array('UUID', 'Name', 'Repository', 'Version', 'Sort', 'Status', 'Type'),

  // sort by field
  'Sort', 

  // sort ascending
  true,

  // filters to apply
  array(

    // published only
    'Status' => array(array('EQ', 'Published')),

    // restrict by name
    'Name' => array(array('IN', array('XXX2')),array('EX', array('XXX'))),  

    // restrict by age
    'Sort'  => array(array('LT', 1250), array('LT', 1050) , array('GT', 0), array('GT', 750))
  )
);

?>
```

Templating
-------------

Gather templates w/partials and merge w/data

Partials-Syntax:

```php
<?php 

// ...
$buckets = $options = $widgets = array();

// ...
$template = '
	{{>/templates/body}}
';

// fetch partials
Widespread::FetchPartials($buckets, $options, $widgets, 'index.html', $template);

?>
```

License
-------------
Released under two licenses: new BSD, and MIT. You may pick the
license that best suits your development needs.

https://github.com/alternatex/widespread/blob/master/LICENSE
