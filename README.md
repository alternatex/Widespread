Widespread
=============

Common utilities packed together

Prerequisites
-------------
PHP 5.3

Installation 
-------------

Install through composer `composer require 'alternatex/widespread:*'`

See: https://packagist.org/packages/alternatex/widespread

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

Use-Case: Preprocessor 
----------------------

... see sledgehammer *

Metadata-Extraction
-------------------

Extract metadata from file header (defaults to first 4096 bytes)

```php
<?php 

// ...
$data = Widespread::FetchMetadata(

  // path to entity
  'test/examples/members/', 

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
      array('IN', array('Include_1', 'Include_2')),
      array('EX', array('Exclude_1'))
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

```php
<?php 

// ...
foreach(array('buckets', 'options', 'widgets') as $context) $$context = array(); // $buckets = $options = $widgets = array();

// fetch partials
Widespread::FetchPartials(
  $buckets, 
  $options, 
  $widgets, 
  'index.html', 
  '{{>/templates/body}}'
);

// create mustache rendering engine helper
$m = new \Mustache_Engine(array('partials' => $buckets));

// process
print $m->render($buckets['index.html'], $data);
?>
```

Convenience
-------------

Gather templates w/partials and merge w/data

```php
<?php 

// ...
$data = array(
  '...',
  '...',
  '...'
);

// default access
$segment_a = &Widespread::AccessSegment($data, 'path.to.prop.where.ever');

// custom delimiter
$segment_b = &Widespread::AccessSegment($data, 'path/to/prop/where/ever', null, null, null, '/');

// ...
$segment_a = 'test';

// ...
echo $data['path']->to['prop']['where']->ever;

// ...
$segment_b = 'test2';

// ...
echo $data['path']->to['prop']['where']->ever;

?>
```

License
-------------
Released under two licenses: new BSD, and MIT. You may pick the
license that best suits your development needs.

https://github.com/alternatex/widespread/blob/master/LICENSE
