<?php

/**
 * @package core
 * @version 0.1
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace core
{
  
  /**
   * Loads the instance stored in the Core class under the given key.
   * 
   * Calling this function is short for `core\Core::getInstance()->loadInstance($key);`.
   *
   * @param  string        $key The key of the instance to look for.
   *
   * @return AbstractCore      The instance.
   */
  function fa($key)
  {
    
    return core\Core::getInstance()->loadInstance($key);
    
  }
  
}

//Include global name space for exports.
namespace
{
  
  //Export the "fa" function to the global name space.
  if(!function_exists("fa")){
    function fa($key){
      return \core\fa($key);
    }
  }
  
}

