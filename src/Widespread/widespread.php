<?php namespace Widespread;

// ... TODO: fix *
date_default_timezone_set('Europe/Berlin');

/**
* Widespread
*
* Common utilities packed together
*
* @author Gianni Furger
* @version 2.1.2
* @copyright 2012-2013 Gianni Furger <gianni.furger@gmail.com>
* @license Released under two licenses: new BSD, and MIT. (see LICENSE)
* @example see README.md OR test/index.php
*/

class Item {
  public $data = array();
  public function __construct($data){
    $this->data = $data;
  }  
}

abstract class Widespread {

  /**
  * @constant
  * @type {String}
  */  
  const VERSION = '2.1.2'; 
 
  /**
  * default cache lifetime in seconds 
  * @constant
  * @type {Integer} 
  */  
  const META_CACHE_LIFETIME = 10;

  /**
  * number of bytes to be read for metadata analysis
  * @constant
  * @type {Integer} 
  */  
  const META_BYTES = 4096;

  /**
  * default mandatory field for file inclusion in result set
  * @constant
  * @type {Integer} 
  */
  const META_MANDATORY = 'Name';

  /**
  * TODO: enhance » multiline-strings
  *
  * meta field/value replacement
  * @constant
  * @type {String} | regex
  */  
  const META_FIELD = '/\s*(?:\*\/|\?>).*/';

  /**
  * file mime type «text/plain»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_TEXT = 'text';
  
  /**
  * file mime type «text/plain»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_TEXT_MIME = 'text/plain';  
  
  /**
  * file mime type «text/plain»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_TEXT_PATTERN = '*.txt';

  /**
  * file mime type «text/html»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_HTML = 'html';
  
  /**
  * file mime type «text/html»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_HTML_MIME = 'text/html';  
  
  /**
  * file mime type «text/html»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_HTML_PATTERN = '*.html';
  
  /**
  * file mime type «application/json»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_JSON = 'json';  
  
  /**
  * file mime type «application/json»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_JSON_MIME = 'application/json';  
  
  /**
  * file mime type «application/json»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_JSON_PATTERN = '*.json';

  /**
  * gather partial references
  * @constant
  * @type {String} | regex
  */  
  const PARTIAL_REF = '/{{(>)(.+?)\\1?}}+/s';

  /**
  * html comments pattern
  * @constant
  * @type {String} | regex
  */  
  const MATCH_HTML_COMMENTS = '/<!--.*-->/sU';

  /**
  * default access path delimiter
  * @constant
  * @type {String}
  */  
  const ACCESS_PATH_DELIM = '.';

  /**
  * scan directory and extract it's content's metadata  
  *
  * <code> 
  * <?php
  *
  *   // ...
  *   $data = Widespread::FetchMetadata(
  *
  *     // path to entity
  *     'contents/members/', 
  *
  *     // properties to extract
  *     array('UUID', 'Name', 'Repository', 'Version', 'Sort', 'Status')
  *   );
  * ?> 
  * </code> 
  *  
  * @static 
  * @param string $meta_dir root-directory to scan from
  * @param array $meta_attributes attributes to be extracted
  * @return array $metas 
  * @example ./ 
  */  

  public static function FetchMetadata($meta_dir = '', $meta_attributes = array(self::META_MANDATORY), $sortby=self::META_MANDATORY, $sortasc=true, $filters=array(), $docache=true, $force=false, $meta_mandatory=self::META_MANDATORY, $meta_bytes=self::META_BYTES, $meta_format=self::META_FORMAT_TEXT, &$meta_data=null) {
    
    // ...
    static $cache=null;

    // TODO: $lifetime if not forced - force 
    // ...

    // initialize cache from fs *
    if($cache==null) {
      
      // ...
      $cache = array();

      // try to fetch cache data (php-serialized)
      try {
        
        // ...        
        if(time() - self::GetFileTime_LastModified($meta_dir.'/cache.dat') > META_CACHE_LIFETIME) {
        
          // ...          
          unlink($meta_dir.'/cache.dat');
        } else {
        
          // ...
          $cache = unserialize(file_get_contents($meta_dir.'/cache.dat'));
        }
      
      // ...
      } catch(Exception $e) {

        // TODO: implement ... *
        die(print_r($e, true));
      }
    }

    // scan directory if not cached or forced
    if($force || !array_key_exists($meta_dir, $cache)) {

      // return value
      $metas = array ();

      // helpers/files
      $metas_resources = array();

      // heplers/directory    
      $metas_dir = $meta_dir;
      $metas_dir_handle = opendir( $metas_dir); 

      // apply defaults
      if(array_key_exists(0, $meta_attributes)) {
        
        // attributes helper
        $meta_attributesx = array();
        
        // assign default key = value
        foreach($meta_attributes as $meta_attribute) $meta_attributesx[$meta_attribute]=$meta_attribute;
        
        // assign & free
        $meta_attributes=$meta_attributesx; unset($meta_attributesx);
      }    

      // gather top-level / 1st-level
      while (($file = @ readdir( $metas_dir_handle ) ) !== false && $file!='') { 

        // self-skip       
        if($file=='.' || $file=='..') continue;

          // process directory
        if ( is_dir( $metas_dir.'/'.$file ) ) {    

          // get handle
          $metas_subdir_handle = @ opendir( $metas_dir.'/'.$file );

          // process sub-directory
          while ($metas_subdir_handle && ($subfile = readdir( $metas_subdir_handle ) ) !== false ) {

            // push all (4 now)
            $metas_resources[] = "$file/$subfile";
          }    
          
          // release handle
          closedir( $metas_subdir_handle );
          
        // process file   
        } else { 
         
          // push all 4 now
          $metas_resources[] = $file; 
        }
      }

      // release handle
      closedir( $metas_dir_handle );

      // process matched files
      foreach($metas_resources as $meta_file) {
        
        // helper *
        $meta_file_path = "$metas_dir/$meta_file";

        // handle "default" - TODO: what about performance?
        if(!is_array($data = json_decode(file_get_contents("$metas_dir/$meta_file"), true))) {
          
          // build full path
          if(substr($metas_dir, -1, 1)=='/') $metas_dir=substr($metas_dir, 0, strlen($metas_dir)-1);
                     
          // check if accessible
          if (!is_readable($meta_file_path)) continue;            

          // gather partial for metadata inspection
          $fp = fopen( $meta_file_path, 'r' );
          $data = fread( $fp, $meta_bytes); 
          fclose( $fp );
        
          // extract meta attributes
          foreach($meta_attributes as $field => $regex) {
            preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $data, ${$field}); // backref
            ${$field} = !empty( ${$field}) ? trim(preg_replace(self::META_FIELD, '', ${$field}[1])) : '';
          }

          // flatten
          $data = compact(array_keys($meta_attributes));

          // maybe there will be no mandatory field in future: || sizeof($data)==0...
          if(empty($data[$meta_mandatory])) continue;

        } 

        // determine meta path
        $path = trim(preg_replace('|/+|','/', str_replace( array('\\', '//'), array('//', '/'), '/'.$meta_file))); 

        // ...
        $data['path'] = $meta_file_path;
        
        // add meta w/data to list
        $object = new Item($data);
        $metas[$path] = $object;
      }

      // sort list items alphabetically > - case-insensitive
      ($sort=array_keys($metas)) && sort($sort);
      
      // context storage
      $ctxs=array();

      // contextualize
      foreach($sort as $sortkey){

        // get context
        if(false) $ctx=array_pop(array_slice(explode('/', $meta_dir), 1, 1));
        else $ctx=$meta_dir;

        // create context if not exists
        if(!array_key_exists($ctx, $ctxs)) $ctxs[$ctx]=array(); 
        
        // store within context
        $ctxs[$ctx][]=$metas[$sortkey];
      }

    } else {

      // retrieve from cache
      $ctxs=&$cache[$meta_dir];
    }

    // do cache if requested
    if($docache) $cache[$meta_dir]=$ctxs;

    if($docache) {
      try {
        file_put_contents($meta_dir.'/cache.dat', serialize($cache));
      } catch(Exception $e) {
        die(print_r($e, true));
      }
    }

    // return extracted *
    return $ctxs;
  }

  /**
  * array filtering & sorting helper *
  *
  * <code> 
  * <?php
  *
  *   // ...
  *   $data = Widespread::FilterData(
  *
  *     // data reference
  *     $data, 
  *
  *     // sort by field
  *     'Sort', 
  *
  *     // sort ascending
  *     false,
  *
  *     // filters to apply
  *     array(
  *
  *       // published only
  *       'Status' => array(array('EQ', 'Published')),
  *
  *       // restrict by name
  *       'Name' => array(array('IN', array('Included')),array('EX', array('Excluded'))), 
  * 
  *       // restrict by age
  *       'Sort'  => array(array('LT', 1000), array('GT', 0))
  *     )
  *   );
  * ?> 
  * </code> 
  *  
  * Roadmap: 2.1.0 add support for custom source to aggregate data from * return injected into $items
  *          2.2.0 add support for custom filters rules
  *          2.3.0 add support for 2nd, 3rd sort -> 2nd, last sort if path if not used already * (check by same attribute position on asc/desc sort - ml * ...)
  *
  * @static 
  * @param array $items collection of items to filter
  * @param string $sortby item attribute to sort by
  * @param boolean $sortasc ascendig/descending sort flag
  * @param array $filters filters to apply
  * @param boolean $docache cache *
  * @param boolean $force force execution (ignore cache)
  * @param String $source custom datasource for usage with self::FetchMetadata();
  * @return array $items filtered data
  * @example ./ 
  */     

  public static function &FilterData(&$items = null, $sortby=self::META_MANDATORY, $sortasc=true, $filters=array(), $docache=true, $force=false, $source=null, $attributes=array(self::META_MANDATORY)) {

    // custom source to fetch data from?
    if($source!=null) {

      // fetch metadata * - TODO: ensure cached?! - NOTE: must be pop-able *
      $items = array_pop(self::FetchMetadata($source, $attributes));
    }

    // create sort fnc 
    $sortfunc = create_function('$a,$b', 'return strnatcasecmp($a->data["'.$sortby.'"], $b->data["'.$sortby.'"]);');

    // temp helper holding entities matching filters
    $filtered=array();    

    // iterate contexts
    foreach($items as $item) {
          
      // flag as match by default
      $ismatch=true;

      // process filters
      foreach($filters as $filter => $rules) {
    
        // existance check
        if(!isset($item->data[$filter])) { $ismatch=false; break; }
        
        // extract
        $candidate = $item->data[$filter];

        // process rules
        foreach($rules as $rule) {
          
          // extract operand
          $operand = $rule[0];

          // extract against
          $against = $rule[1];

          // validate rule
          switch($operand) {
            case 'EQ':
              $ismatch=($candidate===$against);
              break;
            case 'NOT': 
              $ismatch=($candidate!==$against);
              break;
            case 'CI':
              $ismatch=(stripos($candidate, $against)!==false); 
              break;
            case 'CS':
              $ismatch=(strpos($candidate, $against)!==false); 
              break;                    
            case 'GT':
              $ismatch=(intval($candidate)>intval($against));
              break;
            case 'LT':
              $ismatch=(intval($candidate)<intval($against));
              break;
            case 'GE':
              $ismatch=(intval($candidate)>=intval($against));
              break;
            case 'LE':
              $ismatch=(intval($candidate)<=intval($against));
              break;
            case 'IN':
              $ismatch=in_array($candidate, $against);
              break;
            case 'EX':
              $ismatch=!in_array($candidate, $against);
              break;
            default:
              trigger_error("Unknown operand: \"$operand\"", E_USER_WARNING);
              break;
          }
          
          // skip item on mismatch (AND-selector)
          if(!$ismatch) break;          
        }          

        // skip item on mismatch (AND-selector)
        if(!$ismatch) break;  
      }
      
      // store match
      if($ismatch) $filtered[]=$item;
  }

  // ...
  if(sizeof($filters)>0) $items = (array) $filtered;

  // sort context by value
  uasort($items, $sortfunc);

  // apply sort direction (defaults to ascending)
  if(!$sortasc) $items=array_reverse($items);
  else $items=array_merge(array(), $items);

  // ...
  return $items;
}

  /**
  * extract references and gather file contents > return as array filename <> contents - TODO: > remove those suppressor's when gathering contents and/or handle w/some kind of feedback > lalalog.
  *
  * @example 
  *
  *  {{>partials/list.html :[{           
  *      "sortby": "firstname",
  *      "sortasc": "true",
  *      "filter": {
  *        "function": [
  *          ["EQ", "CTO"]
  *        ], 
  *        "firstname": [
  *          ["CI", "Bill"]
  *        ]
  *      }
  *    }] 
  *  }}
  *
  * @static 
  * @param {Array} $bucket array holding references and their contents
  * @param {Array} $options array holding options 
  * @param {Array} $widgets array holding special buckets
  * @param {String} $filename file to assign template to  
  * @param {String} $template string template content (optional - file will be read if empty string encountered)
  * @param {Boolean} $process replace references in bucket list
  * @param {Boolean} $trace injection o information when replacing partials 
  * @param {String} $trace_prefix 
  * @param {String} $trace_suffix 
  * @return {String} partials
  */

  public static function FetchPartials(&$bucket, &$options, &$widgets, $filename, $template='', $process=false, $trace=false, $trace_prefix='/* ', $trace_suffix=' */') {

    // init 
    $partials = '';
 
    // diversity matters
    if(isset($bucket[$filename])) 
      return $bucket[$filename];    

    // helper > hold regex matches for referenced partials
    $matches = array();   

    // helper > convenience
    $template = ($template==='') ? (@file_get_contents($filename, FILE_USE_INCLUDE_PATH)) : $template;

    // store partials content
    $partials = $bucket[$filename] = $template;

    // look out for partials
    while (preg_match(self::PARTIAL_REF, $template, $matches, PREG_OFFSET_CAPTURE)) {

      // matches matches matches      
      if(($pos=strpos($matches[2][0], ":[{"))!==false) {

        // keep copy o original match
        $original = $matches[2][0];

        // extract metadata from filename
        $metadata = substr($original, ($pos+3), -3);

        // extract filename
        $partial_filename = trim(substr($original, 0, $pos));   

        // store options
        $options[$partial_filename] = json_decode(" { ".substr(trim(str_replace(array("\n", "\t"), "", $metadata)), 0, -3)." } ", true);       
      
        // replace reference 
        $template = str_replace($matches[2][0], $partial_filename, $template);

        // replace match w correct filename
        $matches[0][0] = $matches[2][0] = $partial_filename;
        
        // store original contents to replace em' laterz 
        $widgets[$partial_filename] = $original;          
      }

      // process found partials {{>partial}} >> partial || set empty 
      (($partials .= self::FetchPartials($bucket, $options, $widgets, $matches[2][0], '')) || ($bucket[$matches[2][0]] = ''));

      // prep next partial (offset + match.length)
      if ((substr($template, ($next_offset = $matches[0][1] + strlen($matches[0][0])), 1) == "\n")) { $next_offset++; }

      // fetch next
      $template = substr($template, $next_offset);
    }

    // direct process?
    if($process) { $bucket = self::ReplacePartials($bucket, $widgets, $trace, $trace_prefix, $trace_suffix); }

    // over and out
    return $partials;
  }

  /**
  * replace partial references 
  * @static 
  * @param {Array} $bucket array holding references and their contents
  * @return {Array} bucket's content w/partial references replaced
  */

  public static function ReplacePartials($bucket, &$widgets, $trace=false, $trace_prefix='/* ', $trace_suffix=' */', $process_widgets=true){  

    // extract bucket identifiers
    $buckets = array_keys($bucket);
    
    // iterate buckets and replace references - TODO: find a better way than that q&d-impl.
    foreach($buckets as $key) { foreach($buckets as $key2) { $bucket[$key] = str_replace("{{>".$key2."}}", ($trace?$trace_prefix.'[START] '.$key2.$trace_suffix."\n":'').$bucket[$key2].($trace?$trace_prefix.'[END] '.$key2.$trace_suffix."\n":''), $bucket[$key]); if($process_widgets) { $bucket[$key] = str_replace(array_values($widgets), array_keys($widgets), $bucket[$key]); } } }

    // ...
    return $bucket;
  }

  /**
  *
  * wrapper for getting or setting, renaming and enforcing type of object/array segments 
  *
  * @static 
  * @param {Array} $data structured array
  * @param {String} $paths structured array path/object type definition
  * @param {Object} $set optional 
  * @param {String} $rename optionally change the name of the last path segment
  * @param {String} $type datatype for path segment (must be 'array' if not null for now)
  * @return {Boolean} success or failure? TODO: ensure +++ add user_error => handle globally where appropriate? hmm.
  */  

  public static function &AccessSegment(&$data, $path, &$set=null, $rename=null, $type=null, $separator=self::ACCESS_PATH_DELIM){    

    // ...
    static $MESSAGE_ERROR_ACCESS = "Unknown Datatype | Property/Key Not Found";

    // bypass silly inputs
    if(!($path!='' && (is_object($data) || is_array($data) && sizeof($data)>0))) 
      return ;
    
    // extract segments
    $segments = explode($separator, $path);
    
    // helpers
    $previous = null;    

    // assign base reference to "root node"
    $current = &$data;
    
    // gather requested path segment
    foreach($segments as $segmentIndex => &$segmentData){

      // store parent if structured
      if(is_object($current) || is_array($current)) $previous = $current;

      // extract from object
      if(is_object($current) && property_exists($current, $segmentData)) {        
        $current = &$current->$segmentData;

      // extract from array
      } elseif(is_array($current) && array_key_exists($segmentData, $current)) {
        $current = &$current[$segmentData];

      // handle the unwanted - tbi *
      } else {
        trigger_error($MESSAGE_ERROR_ACCESS, E_USER_WARNING);
      }      
    }

    // enforce datatype?
    if($type!=null) { /* check and process ... type cast ? think. ... */ }

    // update path segment value
    if($set!=null) $current = &$set;

    // create new reference and remove current (after value set!)
    if($rename!=null) {

      // assignment/removal for objects
      if(is_object($previous)) {        
        $previous->$rename = &$current;
        unset($previous->$segmentData);    
      
      // assignment/removal for arrays    
      } elseif(is_array($previous)) {
        $previous[$rename] = &$current;
        unset($previous[$segmentData]);
      
      // handle the unwanted - tbi *
      } else{
        trigger_error($MESSAGE_ERROR_ACCESS, E_USER_WARNING);
      }   
    }

    return $current;
  }  

  /**
  * glob* extension
  *
  * @static 
  * @param {String} $pattern string ~ '\/**\/*'
  * @param {Integer} $flags see http://php.net/manual/en/function.glob.php
  * @param {Boolean} $recurse recurse into subdirectories - defaults to true
  * @return {Array} matched files path *
  */

  public static function Glob($pattern, $flags = 0, $recurse=true){

    // ...
    $files = glob($pattern, $flags);        

    // ...
    if($recurse) {
      foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, self::Glob($dir.'/'.basename($pattern), $flags));
      }        
    }
    return $files;
  }  

  /**
  * helper: extract constants by prefix
  *
  * @static 
  * @param {String} $prefix constant prefix eg. constant: MY_NAMESPACE_ACTION - prefix: MY_NAMESPACE_ - turns into array('action' => MY_NAMESPACE_ACTION)
  * @return void
  */

  public static function GetConstants($prefix, &$settings=array()){
    foreach(get_defined_constants() as $key => $value){ 
        $prefixc=strlen($prefix);
        if(($substr=substr($key, 0, $prefixc))==$prefix) {     
            $settings[strtolower(substr($key, $prefixc))] = $value;
        }
    }
    return $settings;
  }  

  /**
  * helper: check if is json data structure - read contents to array » is_array()? return false if not; else the array retrieved *
  *
  * @static 
  * @param {String} $filename path to file to check / retrieve
  * @return void
  */

  public static function FileIsJSON($filepath, &$data=null){
    // ...    
    try{
      $data=@json_decode(file_get_contents($filepath));
      return $data;
    } catch(Exception $ex){
      return false;
    }
  }


  /**
  * helper: filemtime wrapper * (borrowed from: http://www.php.net/manual/en/function.filemtime.php#100692)
  *
  * @static 
  * @param {String} $filename path to file to check / retrieve
  * @return void
  */

  public static function GetFileTime_LastModified($filePath) { 

    $time = filemtime($filePath); 

    $isDST = (date('I', $time) == 1); 
    $systemDST = (date('I') == 1); 

    $adjustment = 0; 

    if($isDST == false && $systemDST == true) 
      $adjustment = 3600; 
    
    else if($isDST == true && $systemDST == false) 
      $adjustment = -3600; 

    else 
      $adjustment = 0; 

    return ($time + $adjustment); 
  } 

}