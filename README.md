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
  array('UUID', 'Name', 'Repository', 'Version', 'Sort', 'Status'),

  // sort by field
  'Sort', 

  // sort ascending
  false,

  // filters to apply
  array(

    // published only
    'Status' => array(
      array('NOT', 'Published')
    ),

    // restrict by name
    'Name' => array(
      array('IN', array('XXX', 'XXX4')),
      array('EX', array('XXX2'))
    ),  

    // restrict by age
    'Sort'  => array(
      array('LT', 1000), 
      array('GT', 200)
    )
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
