<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

require_once __DIR__."/../singleton/SingletonInterface.php";

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
   * Should initialize the class. Is allowed to have calls to external classes unlike __construct.
   * 
   * @return void
   */
  abstract public function init();
  
}
