<?php

/**
 * @package core
 * @version 0.1
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace core\core;

require_once __DIR__."/../singleton/SingletonInterface.php";
require_once "CoreException.php";

use \core\singleton\SingletonInterface;

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
   * The name of this package.
   * @var string
   */
  protected $name;
  
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
   * @param array $propertyValues An array like `[$propertyName => $propertyValue, ..]`.
   */
  public function __construct(array $propertyValues = [])
  {
    
    //Iterate over the given values and call the setProperty method with them.
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
    
    return $this->name;
    
  }
  
  /**
   * Return the root folder of this package.
   *
   * @return string
   */
  public function getRoot()
  {
    
    return "{$this->dir}/{$this->name}";
    
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
