<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

class Utils
{
  
  /**
   * Loads the contents from a given file and parses it as JSON. Returns the result.
   *
   * This method also caches the result so that future requests to the same JSON file can
   * be returned from the cache. You can always get a freshly parsed result by settings
   * the `$recache` argument to true.
   *
   * @param string $file The absolute path to the file.
   * @param boolean $noMinify Whether to skip minifying the input.
   * 
   * @throws CoreException If the JSON was invalid.
   *
   * @return array The parsed JSON.
   */
  public static function parseJsonFromFile($file, $noMinify=false)
  {
    
    //Check if the requested file exists. If it doesn't, return an empty array.
    if(!is_file($file)){
      return [];
    }
    
    //Attempt to parse the JSON.
    $result = @json_decode(($noMinify
      ? file_get_contents($file)
      : json_minify(file_get_contents($file))
    ), true);
    
    //If JSON failed to parse.
    if($result === null){
      throw new CoreException(sprintf(
        'The file given for JSON parsing at "%s" could not be parsed. Error: %s', $file, json_last_error_msg()
      ));
    }
    
    //Return the result.
    return $result;
    
  }
  
}
