<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

use \Closure;

/**
 * File including class.
 */
class FileIncluder
{
  
  //Flags.
  const STRICT = 0b01;
  const ONCE   = 0b10;
  
  /**
   * The closure that does the including.
   *
   * @var Closure
   */
  private $includer;
  
  /**
   * The default environment variables for the include.
   *
   * @var array
   */
  private $env = [];
  
  /**
   * Constructs the including closure.
   *
   * @param integer      $flags   Bitmap of options.
   * @param object|null  $context An initial context may be passed.
   * 
   * @see FileIncluder::setContext() for details about what the `$context` parameter is for.
   * 
   */ 
  public function __construct($FLAGS = 0, $context = null)
  {
    
    //Create a reference to the default environment for the closure to use.
    $DEFAULTENV =& $this->env;
    
    //Create the including closure.
    $this->includer = function($INCLUDEFILE, $USERENV=[])use(&$DEFAULTENV, $FLAGS){
      
      //Unset all reserved variables from both environments.
      unset(
        $USERENV['INCLUDEFILE'],
        $USERENV['USERENV'],
        $USERENV['DEFAULTENV'],
        $USERENV['FLAGS'],
        $DEFAULTENV['INCLUDEFILE'],
        $DEFAULTENV['USERENV'],
        $DEFAULTENV['DEFAULTENV'],
        $DEFAULTENV['FLAGS']
      );
      
      //Extract environments.
      extract($DEFAULTENV);
      extract($USERENV);
      
      //Unset original environments.
      unset($USERENV, $DEFAULTENV);
      
      //Perform a "strict" require?
      if($FLAGS & self::STRICT == self::STRICT)
      {
        
        //Do it once?
        if($FLAGS & self::ONCE == self::ONCE){
          return require_once($INCLUDEFILE);
        }
        
        //Do it as often as you want.
        return require($INCLUDEFILE);
        
      }
      
      //Include once?
      if($FLAGS & self::ONCE == self::ONCE){
        return include_once($INCLUDEFILE);
      }
      
      //Include as often as you want.
      return include($INCLUDEFILE);
      
    };
    
    //Set the initial context.
    $this->setContext($context);
    
  }
  
  /**
   * Call the including closure with the given file and environment.
   *
   * @param  string $file The path to the file that needs to be included.
   * @param  array  $env  A call-time override of the default environment.
   * 
   * @see FileIncluder::setEnv() for details about what the `$env` parameter is for.
   *
   * @return mixed        Whatever the included file returns will be returned.
   */
  public function __invoke($file, array $env=[])
  {
    
    return call_user_func($this->includer, $file, $env);
    
  }
  
  /**
   * Set the context object that the file will be included with.
   * 
   * This determines the value of the `$this`-variable within the included file.
   * If null is given, `setContext` will create an empty StdClass object to use as context.
   *
   * @param object|null $context
   * 
   * @return self Chaining enabled.
   */
  public function setContext($context)
  {
    
    //Create an empty object?
    if(!is_object($context)){
      $context = (object) [];
    }
    
    //Replace the existing closure with a rebound one.
    $this->includer = $this->includer->bindTo($context);
    
    //Enable chaining
    return $this;
    
  }
  
  /**
   * Sets a default environment for the include.
   * 
   * The environment is an associative array of variables that will be available inside
   * the included file. For example, if the array would have a `'foo' => 'bar'` pair, the
   * code in the included file may access it through `$foo`.
   * 
   * Unfortunately there are some reserved variable names (note that the casing is
   * intentional, the lower-case equivalents of these words are __not__ reserved):
   * 
   * * INCLUDEFILE
   * * USERENV
   * * DEFAULTENV
   * * FLAGS
   *
   * @param array $env The environment.
   * 
   * @return self      Chaining enabled.
   * 
   */
  public function setEnv(array $env)
  {
    
    //Set the property.
    $this->env = $env;
    
    //Enable chaining.
    return $this;
    
  }
  
}
