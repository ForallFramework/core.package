<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\singleton;

/**
 * Singleton interface.
 */
interface SingletonInterface
{
  
  /**
   * Should, if needed instantiate and, return the instance stored in self::$instance.
   * 
   * @return SingletonInterface The singleton instance.
   */
  public static function getInstance();
  
  /**
   * Should throw an exception if an instance of self already exists in self::$instance.
   * 
   * @throws SingletonException If self::$instance has been set.
   */
  function __construct();
  
}
