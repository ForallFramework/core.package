<?php

/**
 * @package forall\core
 * @version 0.1
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

require_once __DIR__."/AbstractCore.php";
require_once __DIR__."/CoreException.php";
require_once __DIR__."/PackageDescriptor.php";
require_once __DIR__."/FileIncluder.php";
require_once __DIR__."/../singleton/SingletonTraits.php";
require_once __DIR__."/../singleton/SingletonInterface.php";

use \forall\core\singleton\SingletonTraits;
use \forall\core\singleton\SingletonInterface;
use \Closure;

/**
 * Core class.
 * 
 * Searches through folders to find all packages and maps them so their names and paths
 * may be requested and calls their main.php files. Also allows packages that define
 * "Core"-classes to pass their instance and a key name for others to request them through
 * Core.
 */
class Core implements SingletonInterface
{
  
  //Use standard singleton code.
  use SingletonTraits{
    __construct as private singletonConstructor;
  }
  
  /**
   * The descriptor for the core package.
   * @var PackageDescriptor
   */
  private $descriptor;
  
  /**
   * Stores information about found packages.
   * @var PackageDescriptor[]
   */
  private $packages = [];
  
  /**
   * Stores all AbstractCore classes.
   * @var AbstractCore[]
   */
  private $instances = [];
  
  /**
   * Stores AbstractCore loader functions for lazy-loading.
   * @var Closure[]
   */
  private $instanceLoaders = [];
  
  /**
   * A container for the onMainFilesIncluded event callbacks.
   * @var Closure[]
   */
  private $includeCallbacks = [];
  
  /**
   * Holds the results of any JSON file that was parsed via the parseJsonFromFile method.
   * @see Core::parseJsonFromFile() for information about what this property is for.
   * @var array
   */
  private $cachedJSON = [];
  
  /**
   * Creates the PackageDescriptor for the core package and loads the settings.
   */
  public function __construct()
  {
    
    //Call the singleton constructor first.
    $this->singletonConstructor();
    
    //Create a PackageDescriptor for ourselves.
    $descriptor = new PackageDescriptor([
      'dir' => realpath(__DIR__."/../../../"),
      'root' => 'core',
      'hasMainFile' => true,
      'hasSettingsFile' => true
    ]);
    
    //Store the descriptor.
    $this->packages[] = $this->descriptor = $descriptor;
    
  }
  
  /**
   * Register a Core class under the given key.
   *
   * @param  string        $key      The key that the instance is registered under.
   * @param  AbstractCore $instance The Core-class.
   * 
   * @throws CoreException If the key is already in use.
   * @throws CoreException If the instance has already been registered.
   *
   * @return self                    Chaining enabled.
   */
  public function registerInstance($key, AbstractCore $instance)
  {
    
    //The key must not be in use.
    if($this->isInstanceKeyUsed($key)){
      throw new CoreException(sprintf("Can not register any more Core instances with key %s.", $key));
    }
    
    //The instance may only be registered once.
    if($this->isInstanceRegistered($instance)){
      throw new CoreException(sprintf(
        "The given object(%s) has already been registered.", get_class($instance)
      ));
    }
    
    //Store the instance.
    $this->instances[$key] = $instance;
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Register a loader for a Core instance.
   * 
   * Register a closure that will be called and expected to return an AbstractCore
   * instance when an instance of that key is first requested.
   * 
   * @param  string   $key    The key the instance should be stored under.
   * @param  Closure  $loader An anonymous function that should return an instance of AbstractCore.
   * 
   * @throws CoreException If the key is already in use.
   * 
   * @return self             Chaining enabled.
   */
  public function registerInstanceLoader($key, Closure $loader)
  {
    
    //The key can not already be in use.
    if($this->isInstanceKeyUsed($key)){
      throw new CoreException(sprintf("Can not register any more Core instances with key %s.", $key));
    }
    
    //Store the loader.
    $this->instanceLoaders[$key] = $loader;
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Load the instance that has been registered under the given key.
   *
   * @param  string $key
   * 
   * @throws CoreException If no instances with the given key are registered.
   * @throws CoreException If the used instanceLoader does not return an AbstractCore object.
   *
   * @return AbstractCore
   */
  public function loadInstance($key)
  {
    
    //Instance key must exist.
    if(!$this->isInstanceKeyUsed($key)){
      throw new CoreException(sprintf("No instances with key '%s' are registered.", $key));
    }
    
    //Return an already loaded instance?
    if(array_key_exists($key, $this->instances)){
      return $this->instances[$key];
    }
    
    //Load the instance.
    $instance = $this->instanceLoaders[$key]();
    
    //Validate the instance.
    if(!($instance instanceof AbstractCore)){
      throw new CoreException(sprintf(
        "The instance loader with key %s does not return a Core instance.", $key
      ));
    }
    
    //Store the instance.
    $this->instances[$key] = $instance;
    
    //Call the "init" method.
    $this->initializeInstance($instance);
    
    //Return the instance.
    return $instance;
    
  }
  
  /**
   * Calls the `init`-method of an AbstractCore instance.
   * 
   * It also stores within the instance that it has been initialized and skips all of
   * these steps if the instance had been initialized before.
   *
   * @param  AbstractCore $instance The instance to call init on.
   *
   * @return bool                   Whether init was called.
   */
  public function initializeInstance(AbstractCore $instance)
  {
    
    //Return false when the initialized property has already been set to true.
    if($instance->_initialized !== false){
      return false;
    }
    
    //Initialize.
    $instance->init();
    $instance->_initialized = true;
    
    //Return true.
    return true;
    
  }
  
  /**
   * Returns true if the given instance is a registered AbstractCore object.
   *
   * @param  AbstractCore $instance
   *
   * @return boolean
   */
  public function isInstanceRegistered(AbstractCore $instance)
  {
    
    return (array_search($instance, $this->instances) !== false);
    
  }
  
  /**
   * Returns true when the given key has been registered as an instance or instanceLoader.
   * 
   * @param  string  $key
   * 
   * @return boolean
   */
  public function isInstanceKeyUsed($key)
  {
    
    return (array_key_exists($key, $this->instances) || array_key_exists($key, $this->instanceLoaders));
    
  }
  
  /**
   * Finds all packages in the package directories provided by the settings file.
   *
   * @return self Chaining enabled.
   */
  public function gatherPackages()
  {
    
    //Reference the directories to look in from the settings.
    $directories = $this->getPackageDirectories();
    
    //Create a result array.
    $result = [];
    
    //Iterate over an array of all possible package folders.
    foreach(glob('{'.implode(',', $directories).'}/*/', GLOB_NOSORT|GLOB_BRACE|GLOB_ONLYDIR) as $directory)
    {
      
      //If this is not a .package, skip it.
      if(!is_file("$directory/forall.json")){
        continue;
      }
      
      //Extract the package name.
      $name = basename($directory);
      
      //Check if we already have a package by this name, if we do, skip the rest.
      if($this->getPackageByName($name) !== false){
        continue;
      }
      
      //Create a descriptor for the discovered package.
      $descriptor = new PackageDescriptor([
        'dir' => dirname($directory),
        'root' => $name,
        'hasMainFile' => is_file("$directory/main.php"),
        'hasSettingsFile' => is_file("$directory/settings.json")
      ]);
      
      //Add the new PackageDescriptor to our result.
      $result[] = $descriptor;
      
    }
    
    //Merge the result with the already existing packages.
    $this->packages = array_merge($this->packages, $result);
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Returns the array of all currently discovered packages.
   * @return PackageDescriptor[] @see Core::$packages
   */
  public function getPackages()
  {
    
    return $this->packages;
    
  }
  
  /**
   * Returns the package of the given name or false when not found.
   *
   * @param  string $name The name to look for.
   *
   * @return PackageDescriptor|false
   */
  public function getPackageByName($name)
  {
    
    //Add the Forall vendor name to the name, if it's lacking one.
    if(strpbrk('.\\/_', $name)===false){
      $name = "forall.$name";
    }
    
    //Iterate over the packages to find one with the given name.
    foreach($this->packages as $package){
      if($package->getName() === $name){
        return $package;
      }
    }
    
    //All packages have been iterated without result. Return false.
    return false;
    
  }
  
  /**
   * Get the package directories as defined in settings.json, converted to absolute paths.
   *
   * @return array
   */
  public function getPackageDirectories()
  {
    
    //Reference the directories to look in from the settings.
    $directories = $this->descriptor->getSettings()["packageDirectories"];
    
    //Convert them to absolute paths.
    foreach($directories as $i => $directory){
      if($directory{0} !== '/'){
        $directories[$i] = realpath($this->descriptor->getFullPath()."/$directory");
      }
    }
    
    //Return them.
    return $directories;
    
  }
  
  /**
   * Loads the contents from a given file and parses it as JSON. Returns the result.
   * 
   * This method also caches the result so that future requests to the same JSON file can
   * be returned from the cache. You can always get a freshly parsed result by settings
   * the `$recache` argument to true.
   *
   * @param  string  $file    The absolute path to the file.
   * @param  boolean $recache Whether the previously cached result should be recreated.
   * 
   * @throws CoreException If the file does not exist.
   * @throws CoreException If the JSON was invalid.
   *
   * @return array            The parsed JSON.
   */
  public function parseJsonFromFile($file, $recache=false)
  {
    
    
    //Check the cache if it's wanted and present.
    if($recache === false && array_key_exists($file, $this->cachedJSON)){
      return $this->cachedJSON[$file];
    }
    
    //Check if the requested file exists.
    if(!is_file($file)){
      throw new CoreException(sprintf('The file given for JSON parsing at "%s" does not exists.', $file));
    }
    
    //Attempt to parse the JSON.
    if(($result = @json_decode(file_get_contents($file), true)) === null){
      throw new CoreException(sprintf(
        'The file given for JSON parsing at "%s" could not be parsed.', $file
      ));
    }
    
    //Cache the result.
    $this->cachedJSON[$file] = $result;
    
    //And return it.
    return $result;
    
  }
  
  /**
   * Calls the "main.php" file on all currently found packages that have one that hasn't been included yet.
   *
   * @return self Chaining enabled.
   */
  public function includeMainFiles()
  {
    
    //Reset the event callbacks.
    $this->includeCallbacks = [];
    
    //Use a FileIncluder to prevent giving away our private scope.
    $includer = new FileIncluder(FileIncluder::STRICT|FileIncluder::ONCE);
    
    //Give our public scope as $core.
    $includer->setEnv([
      'core' => $this
    ]);
    
    //Iterate over the packages.
    foreach($this->packages as $descriptor)
    {
      
      //Skip this package if it hasn't got a main-file.
      if(!$descriptor->hasMainFile()){
        continue;
      }
      
      //Include the file.
      $includer($descriptor->getFullPath()."/main.php");
      
    }
    
    //Call the callbacks.
    foreach($this->includeCallbacks as $callback){
      $callback($this);
    }
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Register a callback to call after all "main.php" files have been included.
   * 
   * @param  Closure $callback The callback. Receives the instance of Core as first argument.
   *
   * @return self              Chaining enabled.
   */
  public function onMainFilesIncluded(Closure $callback)
  {
    
    $this->includeCallbacks[] = $callback;
    
  }
  
}
