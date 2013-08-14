<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

class Utils
{
  
  /**
   * Holds the results of any JSON file that was parsed via the parseJsonFromFile method.
   * @see self::parseJsonFromFile() for information about what this property is for.
   * @var array
   */
  private static $cachedJSON = [];
  
  /**
   * Loads the contents from a given file and parses it as JSON. Returns the result.
   * 
   * This method also caches the result so that future requests to the same JSON file can
   * be returned from the cache. You can always get a freshly parsed result by settings
   * the `$recache` argument to true.
   *
   * @param  string  $file    The absolute path to the file.
   * @param  boolean $recache Whether the previously cached result should be recreated.
   * 
   * @throws CoreException If the file does not exist.
   * @throws CoreException If the JSON was invalid.
   *
   * @return array            The parsed JSON.
   */
  public static function parseJsonFromFile($file, $recache=false)
  {
    
    //Check the cache if it's wanted and present.
    if($recache === false && array_key_exists($file, self::$cachedJSON)){
      return self::$cachedJSON[$file];
    }
    
    //Check if the requested file exists.
    if(!is_file($file)){
      $result = [];
    }
    
    //Attempt to parse the JSON.
    elseif(($result = @json_decode(file_get_contents($file), true)) === null){
      throw new CoreException(sprintf(
        'The file given for JSON parsing at "%s" could not be parsed.', $file
      ));
    }
    
    //Cache the result.
    self::$cachedJSON[$file] = $result;
    
    //And return it.
    return $result;
    
  }
  
}
