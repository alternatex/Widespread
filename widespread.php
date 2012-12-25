<?php namespace Widespread;

/**
* Widespread
*
* @author Gianni Furger
* @version 1.0.1
* @copyright 2012 Gianni Furger <gianni.furger@gmail.com>
* @license Released under two licenses: new BSD, and MIT. (see LICENSE)
* @example see README.md
* 
* Just a bunch o common utilities packed together
*
*/

abstract class Widespread {

  /**
  * this file's version
  * @constant
  * @type {String}
  */  
  const VERSION = '1.0.1'; 
 
  /**
  * num o bytes to be read for metadata analysis
  * @constant
  * @type {Integer} 
  */  
  const META_BYTES = 4096;

  /**
  * mandatory field for file inclusion in result set TODO: should be kinda more flexible ^^
  * @constant
  * @type {Integer} 
  */
  const META_MANDATORY = 'Name';

  /**
  * meta field/value replacement
  * @constant
  * @type {String} | regex
  */  
  const META_FIELD = '/\s*(?:\*\/|\?>).*/';

  /**
  * gather partial references
  * @constant
  * @type {String} | regex
  */  
  const PARTIAL_REF = '/{{(>)(.+?)\\1?}}+/s';

  /**
  * TODO: solve properly - comma separated list of filenames to be matched to a format
  * @constant
  * @type {String} | regex
  */  
  const META_FORMAT_JSON_FILENAME_MAP = 'package.json';

  /**
  * scan directory and extract it's content's metadata 
  *
  * @static 
  * @param string $meta_dir root-directory to scan from
  * @param array $meta_attributes attributes to be extracted
  * @return array $metas
  */
  public static function FetchMetadata($meta_dir = '', $meta_attributes = array('Name')) {

    // return value
    $metas = array ();

    // helpers/files
    $metas_resources = array();

    // heplers/directory    
    $metas_dir = $meta_dir;
    $metas_dir_handle = opendir( $metas_dir); 

    // TODO: define stack for unlimited crawling? > wich then should be limitable to a certain level back again ;)

    // gather top-level / 1st-level
    while (($file = @ readdir( $metas_dir_handle ) ) !== false && $file!='') { 

      // self-skip       
      if($file=='.') continue;

        // process directory
      if ( is_dir( $metas_dir.'/'.$file ) ) {    

          // get handle
             $metas_subdir_handle = @ opendir( $metas_dir.'/'.$file );
          
             // process sub-directory
             while ($metas_subdir_handle && ($subfile = readdir( $metas_subdir_handle ) ) !== false ) {

              // push all 4 now
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
    foreach ( $metas_resources as $meta_file ) {

    // build full path
    $meta_file_path = "$metas_dir/$meta_file";
      
    // check if accessible
    if ( !is_readable( $meta_file_path ) ) continue;    

        // gather partial for metadata inspection
        $fp = fopen( $meta_file_path, 'r' );
        $data = fread( $fp, self::META_BYTES); 
        fclose( $fp );
      
        // extract meta attributes
        foreach ( $meta_attributes as $field => $regex ) {
          preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $data, ${$field}); // backref
          ${$field} = !empty( ${$field}) ? trim(preg_replace(self::META_FIELD, '', ${$field}[1])) : '';
        }

        // flatten
        $data = compact(array_keys($meta_attributes));
        
        // maybe there will be no mandatory field in future: || sizeof($data)==0...
        if(empty($data[self::META_MANDATORY])) continue;

        // determine meta path
        $path = trim(preg_replace('|/+|','/', str_replace('\\','/',$meta_file))); 

        // add meta w/data to list
        $metas[$path] = $data;
    }

    // sort list items alphabetically > - case-insensitive
    uasort($metas, 'self::SortKeyIC');

    // return extracted metas
    return $metas;
  }

  // helpers
  private static function SortKeyIC($a, $b) { return strnatcasecmp($a[self::META_MANDATORY], $b[self::META_MANDATORY]); }

  /**
  * extract references and gather file contents > return as array filename <> contents - TODO: > remove those suppressor's when gathering contents and/or handle w/some kind of feedback > lalalog.
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
    $partials = $bucket[$filename] = $template; //str_replace(array("\n", "\t"), "", $template);       

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

  public static function ReplacePartials($bucket, &$widgets, $trace=false, $trace_prefix='/* ', $trace_suffix=' */'){  

    // extract bucket identifiers
    $buckets = array_keys($bucket);
    
    // iterate buckets and replace references - TODO: find a better way than that q&d-impl.
    foreach($buckets as $key) { foreach($buckets as $key2) { $bucket[$key] = str_replace("{{>".$key2."}}", ($trace?$trace_prefix.'[START] '.$key2.$trace_suffix."\n":'').$bucket[$key2].($trace?$trace_prefix.'[END] '.$key2.$trace_suffix."\n":''), $bucket[$key]); $bucket[$key] = str_replace(array_values($widgets), array_keys($widgets), $bucket[$key]); } }

    // ...
    return $bucket;
  }

  /**
  * TODO: define supported objects and friends - check if there is a way to not loose pre-fetched stuff *only* affecting references to primitive datatypes, so ..... > $xxx = &$data['yyy']; first, then changing reference name???
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

  public static function AccessSegment(&$data, $path, $set=null, $rename=null, $type=null){    

    // bypass silly inputs
    if(!($path!='' && (is_object($data) || is_array($data) && sizeof($data)>0))) 
      return ;
    
    // extract segments
    $segments = explode('.', $path);
    
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
      } else {
        trigger_error("Unknown Datatype OR Property/Key Not Found", E_USER_WARNING);
      }      

    }

    // enforce datatype?
    if($type!=null) {
      // check and process ^^
    }

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
      } else{
        trigger_error("Unknown Datatype OR Property/Key Not Found", E_USER_WARNING);
      }   
    }

    return $current;
  }  

}
?>
