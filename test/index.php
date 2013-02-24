<?php 

// load dependencies through composer
require_once('vendor/autoload.php');

// include core
require_once('../src/Widespread/widespread.php');

// ...
$metas = Widespread::FetchMetadata("examples/format/text/", array(
'Name' => 'Plugin Name',
'Repository' => 'Repository',
'Version' => 'Version'
));

// ...
print_r($metas);

?>