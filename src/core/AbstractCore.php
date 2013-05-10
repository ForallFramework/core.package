<?php

/**
 * @package core
 * @version 0.1
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace core\core;

require_once __DIR__."/../singleton/SingletonInterface.php";

use \core\singleton\SingletonInterface;

/**
 * Core abstraction.
 */
abstract class AbstractCore implements SingletonInterface
{
  
  /**
   * Should initialize the class. Is allowed to have calls to external classes unlike __construct.
   * @return void
   */
  abstract public function init(array $settings);
  
}
