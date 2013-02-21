<?php namespace Widespread;

/**
* Widespread
*
* Common utilities packed together
*
* @author Gianni Furger
* @version 1.2.1
* @copyright 2012-2013 Gianni Furger <gianni.furger@gmail.com>
* @license Released under two licenses: new BSD, and MIT. (see LICENSE)
* @example see README.md
*/

abstract class Widespread {

  /**
  * @constant
  * @type {String}
  */  
  const VERSION = '1.2.1'; 
 
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
  * file mime type «application/json»
  * @constant
  * @type {String} 
  */
  const META_FORMAT_JSON = 'json';

  /**
  * gather partial references
  * @constant
  * @type {String} | regex
  */  
  const PARTIAL_REF = '/{{(>)(.+?)\\1?}}+/s';

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
  *     array('UUID', 'Name', 'Repository', 'Version', 'Sort', 'Status'),
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
  *       'Name' => array(array('IN', array('XXX2')),array('EX', array('XXX'))), 
  * 
  *       // restrict by age
  *       'Sort'  => array(array('LT', 1000), array('GT', 0))
  *     )
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

  public static function FetchMetadata($meta_dir = '', $meta_attributes = array(self::META_MANDATORY), $sortby=self::META_MANDATORY, $sortasc=true, $filters=array(), $docache=true, $force=false, $meta_mandatory=self::META_MANDATORY, $meta_bytes=self::META_BYTES, $meta_format=self::META_FORMAT_TEXT) {
    
    // ...
    static $cache=array();

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

        // build full path
        if(substr($metas_dir, -1, 1)=='/') $metas_dir=substr($metas_dir, 0, strlen($metas_dir)-1);
        $meta_file_path = "$metas_dir/$meta_file";
          
        // check if accessible
        if (!is_readable($meta_file_path)) continue;    

        // TODO: $meta_format == 'JSON' VS. $meta_format == 'TEXT'

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
        
        // ...
        $data['Path'] = $meta_file_path;

        // maybe there will be no mandatory field in future: || sizeof($data)==0...
        if(empty($data[$meta_mandatory])) continue;

        // determine meta path
        $path = trim(preg_replace('|/+|','/', str_replace('\\','/',$meta_file))); 

        // add meta w/data to list
        $metas[$path] = $data;
      }

      // sort list items alphabetically > - case-insensitive
      ($sort=array_keys($metas)) && sort($sort);
      
      // context storage
      $ctxs=array();

      // contextualize
      foreach($sort as $sortkey){

        // get context
        $ctx=array_pop(array_slice(explode('/', $meta_dir), 1, 1));
        
        // create context if not exists
        if(!array_key_exists($ctx, $ctxs)) $ctxs[$ctx]=array(); 
        
        // store within context
        $ctxs[$ctx][]=$metas[$sortkey];
      }

    } else {

      // retrieve from cache
      $ctxs=$cache[$meta_dir];
    }

    // free mem
    unset($metas);

    // do cache if requested
    if($docache) $cache[$meta_dir]=$ctxs;

    // create sort fnc 
    $sortfunc = create_function('$a,$b', 'return strnatcasecmp($a["'.$sortby.'"], $b["'.$sortby.'"]);');
    
    // iterate contexts
    foreach($ctxs as $ctxid => $ctx) {

      // temp helper holding entities matching filters
      $filtered=array();

      // ...
      foreach($ctx as $item) {
          
        // flag as match by default
        $ismatch=true;

        // process filters
        foreach($filters as $filter => $rules) {
      
          // existance check
          if(!isset($item[$filter])) { $ismatch=false; break; }
          
          // extract
          $candidate = $item[$filter];

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

      // apply filtered
      if(sizeof($filters)>0) $ctx=$filtered;        

      // sort context by value
      uasort($ctx, $sortfunc);

      // apply sort direction (defaults to ascending)
      if(!$sortasc) $ctx=array_reverse($ctx);

      // (re-)attach
      $ctxs[$ctxid]=$ctx;
    }

    // return extracted *
    return $ctxs;
  }

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
}