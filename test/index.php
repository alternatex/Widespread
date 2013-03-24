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

// ...
$itemstest = array_pop($items);

// ...
echo "*** METADATA-SORTBY-ATTR (ASC)***\n";
$itemstest_sortasc = Widespread::FilterData($itemstest, "Version"); 
print_r($itemstest_sortasc);

// ...
echo "*** METADATA-SORTBY-ATTR (DESC)***\n";
$itemstest_sortdesc = Widespread::FilterData($itemstest, "Version", false); 
print_r($itemstest_sortdesc);
