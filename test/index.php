<?php namespace Widespread;

// helpers *
$disable_log=true;

// load dependencies through composer
//require_once('vendor/autoload.php');

// include core
require_once('../src/Widespread/widespread.php');

// disable inner log statements 
if($disable_log) ob_start();

// ...
$items = Widespread::FetchMetadata("examples/format/text/", array(
'Name' => 'Plugin Name',
'Repository' => 'Repository',
'Version' => 'Version'
));

// disable inner log statements 
if($disable_log) ob_end_clean();

// ...
echo "*** METADATA-ITEMS ***\n";
print_r($items);

$items = array_pop($items);

// ...
echo "*** METADATA-SORTBY-ATTR (ASC)***\n";
$items_inv = Widespread::FilterData($items, "Version"); 
print_r($items_inv);

// ...
echo "*** METADATA-SORTBY-ATTR (DESC)***\n";
$items_inv = Widespread::FilterData($items, "Version", false); 
print_r($items_inv);
