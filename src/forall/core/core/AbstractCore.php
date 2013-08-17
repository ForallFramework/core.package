<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

use \forall\core\singleton\SingletonInterface;

/**
 * Core abstraction.
 */
abstract class AbstractCore implements SingletonInterface
{
  
  /**
   * Holds whether the class has been initialized.
   * @var boolean
   */
  public $_initialized = false;
  
  /**
   * The PackageDescriptor of the package that the class is in.
   * @var PackageDescriptor
   */
  private $descriptor;
  
  /**
   * Should initialize the class. Is allowed to have calls to external classes unlike __construct.
   *
   * @return void
   */
  abstract public function init();
  
  /**
   * Return the PackageDescriptor of the package that the class is in.
   *
   * @return PackageDescriptor
   */
  public function getDescriptor()
  {
    
    return $this->descriptor;
    
  }
  
  /**
   * Return the PackageDescriptor of the package that the class is in.
   *
   * @return self  Chaining enabled.
   */
  public function setDescriptor(PackageDescriptor $descriptor)
  {
    
    //Set the property.
    $this->descriptor = $descriptor;
    
    //Enable chaining.
    return $this;
    
  }
  
}
