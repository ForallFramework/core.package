<?php

/**
 * @package forall\core
 * @version 0.1
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\singleton;

/**
 * Singleton traits.
 */
trait SingletonTraits
{
  
  /**
   * The singleton instance, or null before it's been requested.
   * @var null|self
   */
  protected static $instance;
  
  /**
   * If needed instantiates and, returns the instance stored in self::$instance.
   * 
   * @return self The singleton instance.
   */
  public static function getInstance()
  {
    
    //Create a new instance if that hasn't been done yet.
    if(!(self::$instance instanceof self)){
      self::$instance = new self;
    }
    
    //Return the instance.
    return self::$instance;
    
  }
  
  /**
   * Throws an exception if an instance of self already exists in self::$instance.
   * 
   * @throws SingletonException If self::$instance has been set.
   */
  private function __construct()
  {
    
    //Throw an exception if the instance already exists.
    if(self::$instance instanceof self){
      throw new SingletonException(sprintf(
        "Cannot create multiple instances of %s. Please use `%s::getInstance()`.",
        get_class(), get_class()
      ));
    }
    
  }
  
}
