<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core
{
  
  /**
   * Loads the instance stored in the Core class under the given key.
   * 
   * Calling this function is short for `core\Core::getInstance()->loadInstance($key)`.
   *
   * @param  string       $key The key of the instance to look for.
   *
   * @return AbstractCore      The instance.
   */
  function forall($key)
  {
    
    return core\Core::getInstance()->loadInstance($key);
    
  }
  
}

//Include global name space for exports.
namespace
{
  
  //Export the "forall" function to the global name space.
  if(!function_exists("forall")){
    function forall($key){
      return \forall\core\forall($key);
    }
  }
  
}
