Widespread
=============

Common utilities packed together

Prerequisites
-------------
-

Installation 
-------------

**Fetch sources** [https://github.com/alternatex/widespread/archive/master.zip](https://github.com/alternatex/widespread/archive/master.zip)

**Extract to** <your-webroot-here>

**Update environment variables**

Metadata Extraction
-------------

Extract metadata from file "header" (read first 4096 bytes of each file)

Metadata-Syntax:

```php

<?php namespace Widespread;

require_once('widespread.php');

$metas = Widespread::FetchMetadata("examples/meta/", array(
'Name' => 'Plugin Name',
'PluginURI' => 'Plugin URI',
'Version' => 'Version',
'Description' => 'Description',
'Author' => 'Author',
'AuthorURI' => 'Author URI',
'TextDomain' => 'Text Domain',
'DomainPath' => 'Domain Path'
));

print_r($metas);

```

```js

```

```css

```

```html

```

License
-------------
Released under two licenses: new BSD, and MIT. You may pick the
license that best suits your development needs.

https://github.com/alternatex/widespread/blob/master/LICENSE
