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

MAKE IT WORK FOR PACKAGEJSON ASWELL
MAKE IT WORK FOR PACKAGEJSON ASWELL
MAKE IT WORK FOR PACKAGEJSON ASWELL
MAKE IT WORK FOR PACKAGEJSON ASWELL
MAKE IT WORK FOR PACKAGEJSON ASWELL

> try to parse as json -> results in an non-empty array => ok 
else default? hmmm - cannot just read x bytes then

> just hardcode it to filename package.json
> format detection > by filename > returns handler > json-decoder, etc...

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

Syntax:

```php

<?php namespace Widespread;

// fetch main layout (TODO: define in settings - routes > fetch )
$layout = file_get_contents($template, FILE_USE_INCLUDE_PATH);

// extract partials
Widespread\Widespread::FetchPartials($partials, $options, $widgets, 'main', $layout, true, false, "<!-- ", '-->');		

// process widgets
foreach($options as $widget_path => $widget_data) {

	// first check whether the target template that shall be applied exists
	if(isset($widget_data['template']) && FileExists($widget_data['template'])) {

		// post-inject
		$widgets[$widget_path] = $widget_data;

		// gather template we're about to render
		$_template = file_get_contents($widget_data['template'], FILE_USE_INCLUDE_PATH);	

		// gather data
		$dataclass = $widget_data['query']['class'];
		$datafunction = $widget_data['query']['function'];
		$datapath = $remote = $widget_data['remote']['protocol'].'://'.$widget_data['remote']['host'].':'.$widget_data['remote']['port'].$widget_data['remote']['uri'];
		$datainstance = new $dataclass($datapath);
		
    	// process data
		$data = $datainstance->$datafunction($widget_data, $routes, $partials, $contents, $options, $widgets, $externals);		
		
    	// transform if intermediate-layer aka specialized model class specified
		if(isset($widget_data['model']['class'])) {
			
			// wrap it! > instantiate model
			$class = $widget_data['model']['class'];				
			// pass object data						
			$model = new $class($data);
		} else {
			// direct pass through json object 
			$model = $data;
		}

		// inject model data <> 4 widgets actually > so...... SOLVE!!!
		$contents[(isset($widget_data['model']['contents']['context']) ? $widget_data['model']['contents']['context'] : 'model')] = $model;
        $contents['searchurl'] = $datainstance->BuildQuery();
        // custom hook "post widget data fetch"
        //CustomizeContentPostWidgetDataFetch($datainstance, $contents);   
	}

}

// inject messages into contents for later replacement
$contents = array_merge($contents, $messages);

// initialize output
$output = '';

// initialize helper object for templating
$m = new Mustache();

// bind template w/ data
$output = $m->render($partials['main'] , $contents, $partials);
?>
```

License
-------------
Released under two licenses: new BSD, and MIT. You may pick the
license that best suits your development needs.

https://github.com/alternatex/widespread/blob/master/LICENSE
