Widespread
=============

Common utilities packed together

Prerequisites
-------------
PHP 5.3

Installation 
-------------

**Fetch sources** [https://github.com/alternatex/widespread/archive/master.zip](https://github.com/alternatex/widespread/archive/master.zip)

**Extract to** &lt;your-webroot-here&gt;

Metadata Extraction
-------------

Extract metadata from file "header" (read first 4096 bytes)

Metadata-Syntax:

```php

<?php namespace Widespread;

require_once('widespread.php');

$metas = Widespread::FetchMetadata("examples/meta/", array(
'Name' => 'Plugin Name',
'Repository' => 'Repository',
'Version' => 'Version'
));

print_r($metas);

?>
```

Templating
-------------

Gather templates w/partials and merge w/data

License
-------------
Released under two licenses: new BSD, and MIT. You may pick the
license that best suits your development needs.

https://github.com/alternatex/widespread/blob/master/LICENSE
