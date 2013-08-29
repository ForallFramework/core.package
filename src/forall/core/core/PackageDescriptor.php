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
   * An array of PackageDescriptors.
   * @var self[]
   */
  private static $instances = [];
  
  /**
   * Factory: Get or create an instance of PackageDescriptor.
   *
   * @param string $package_directory The path to the package.
   *
   * @return self
   */
  public static function describe($packageDirectory)
  {
    
    //Look in the cache.
    if(array_key_exists($packageDirectory, self::$instances)){
      return self::$instances[$packageDirectory];
    }
    
    //Create a new instance.
    self::$instances[$packageDirectory] = $r = new self($packageDirectory);
    
    //Return the new instance.
    return $r;
    
  }
  
  /**
   * The absolute directory in which this package stands.
   * @var string
   */
  protected $dir;
  
  /**
   * Cache of settings.
   * @var array
   */
  protected $settings;
  
  /**
   * Construct a PackageDescriptor by giving it the package directory.
   *
   * @param string $dir The directory of the package.
   * 
   * @see self::describe() - Factory for PackageDescriptor.
   */
  public function __construct($dir)
  {
    
    $this->dir = $dir;
    
  }
  
  /**
   * An alias of self::getSettings()[$key].
   *
   * @param string $key
   *
   * @return mixed
   *
   * @see self::getSettings() For more documentation.
   */
  public function __get($key)
  {
    
    return $this->getSettings()[$key];
    
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
   * Get the folder name this package is in.
   *
   * @return string
   */
  public function getName()
  {
    
    return basename($this->dir);
    
  }
  
  /**
   * Returns true if the package is physically present.
   * @return boolean
   */
  public function exists()
  {
    
    return file_exists($this->getDir().'/composer.json');
    
  }
  
  /**
   * Return true if this is a Forall package.
   * @return boolean
   */
  public function isForallPackage()
  {
    
    return basename(dirname($this->getDir())) === 'forall';
    
  }
  
  /**
   * Get the merged default settings and user settings.
   * 
   * @throws CoreException If This package is not a Forall package.
   *
   * @return array
   */
  public function getSettings($forceRead=false)
  {
    
    //Only works for Forall packages.
    if(!$this->isForallPackage()){
      throw new CoreException('Can only get settings of Forall packages.');
    }
    
    //Return from cache?
    if(!$forceRead && isset($this->settings)){
      return $this->settings;
    }
    
    //Parse the default settings.
    $default = Utils::parseJsonFromFile($this->getDir().'/settings.json');
    
    //Parse user settings.
    $user = Utils::parseJsonFromFile(realpath(__DIR__.'/../../../../../.settings/'.$this->getName().'.json'));
    
    //Merge and cache the settings.
    $this->settings = $r = array_merge($default, $user);
    
    //Return the result.
    return $r;
    
  }
  
}
