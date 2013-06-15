<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

use \forall\core\singleton\SingletonInterface;

/**
 * Package descriptor class.
 */
class PackageDescriptor
{
  
  /**
   * The absolute directory in which this package stands.
   * @var string
   */
  protected $dir;
  
  /**
   * Construct a PackageDescriptor by giving it the package directory.
   *
   * @param string $dir The directory of the package.
   */
  public function __construct($dir)
  {
    
    $this->dir = $dir;
    
  }
  
  /**
   * An alias of self::getJson($key).
   *
   * @see self::getJson()
   *
   * @param  string $key
   *
   * @return array
   */
  public function __get($key)
  {
    
    return $this->getJson($key);
    
  }
  
  /**
   * Returns the decoded JSON file of the given name.
   *
   * @param  string $fileName The name of the JSON file without extension.
   *
   * @return array            The decoded JSON data.
   */
  public function getJson($fileName)
  {
    
    return forall('core')->parseJsonFromFile($this->getDir().DIRECTORY_SEPARATOR.$fileName.'.json');
    
  }
  
  /**
   * Return the directory.
   *
   * @return string
   */
  public function getDir()
  {
    
    return $this->dir;
    
  }
  
  /**
   * Returns true if the packages has a "settings.json" file.
   *
   * @return boolean
   */
  public function hasSettingsFile()
  {
    
    return file_exists($this->getDir().'/settings.json');
    
  }
  
}
