<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

require_once __DIR__."/../singleton/SingletonInterface.php";
require_once __DIR__."/CoreException.php";

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
   * The name of the root folder of this package.
   * @var string
   */
  protected $root;
  
  /**
   * If this package has a "main.php" file in its root.
   * @var bool
   */
  protected $hasMainFile;
  
  /**
   * If this package has a "settings.json" file in its root.
   * @var bool
   */
  protected $hasSettingsFile;
  
  /**
   * Set some initial values.
   *
   * @param array $propertyValues An array like `[$propertyName => $propertyValue, ..].
   */
  public function __construct(array $propertyValues = [])
  {
    
    //Iterate the given values and call the setProperty method with them.
    foreach($propertyValues as $name => $value){
      $this->_setProperty($name, $value);
    }
    
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
   * Return the name.
   *
   * @return string
   */
  public function getName()
  {
    
    return (array_key_exists('name', $this->getMeta()) ? $this->getMeta()['name'] : "forall.{$this->root}");
    
  }
  
  /**
   * Return the root folder of this package.
   *
   * @return string
   */
  public function getRoot()
  {
    
    return $this->root;
    
  }
  
  /**
   * Returns the full path to the package directory.
   *
   * @return string
   */
  public function getFullPath()
  {
    
    return "{$this->dir}/{$this->root}";
    
  }
  
  /**
   * Returns the settings parsed from the settings.json file, or false when the file is not present.
   *
   * @return array|false
   */
  public function getSettings()
  {
    
    if(!$this->hasSettingsFile()){
      return false;
    }
    
    return Core::getInstance()->parseJsonFromFile($this->getFullPath()."/settings.json");
    
  }
  
  /**
   * Returns the package' meta data obtained from the parsed package.json file.
   *
   * @return array
   */
  public function getMeta()
  {
    
    return Core::getInstance()->parseJsonFromFile($this->getFullPath()."/forall.json");
    
  }
  
  /**
   * Returns true if the package has a "main.php" file.
   *
   * @return boolean
   */
  public function hasMainFile()
  {
    
    return !! $this->hasMainFile;
    
  }
  
  /**
   * Returns true if the packages has a "settings.json" file.
   *
   * @return boolean
   */
  public function hasSettingsFile()
  {
    
    return !! $this->hasSettingsFile;
    
  }
  
  /**
   * Set one of the descriptive properties.
   *
   * @param string $name  The name of the property to set.
   * @param [type] $value [description]
   * 
   * @throws coreException If the property does not exist.
   * @throws coreException If the property has already been set.
   * 
   * @return self Chaining enabled.
   */
  public function _setProperty($name, $value)
  {
    
    //The property must exists.
    if(!property_exists($this, $name)){
      throw new coreException(sprintf("Property %s does not exist.", $name));
    }
    
    //The property must not yet have been set.
    if(isset($this->{$name})){
      throw new coreException(sprintf("Property %s has already been set.", $name));
    }
    
    //Set the property.
    $this->{$name} = $value;
    
    //Enable chaining.
    return $this;
    
  }
  
}
