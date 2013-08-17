<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

use \forall\core\singleton\SingletonTraits;
use \forall\core\singleton\SingletonInterface;
use \Composer\Autoload\ClassLoader;
use \Monolog\Logger;
use \Monolog\Handler\RotatingFileHandler;
use \Monolog\Handler\StreamHandler;
use \Closure;

/**
 * Core class.
 *
 * Searches through folders to find all packages and maps them so their names and paths
 * may be requested and calls their main.php files. Also allows packages that define
 * "Core"-classes to pass their instance and a key name for others to request them through
 * Core.
 */
class Core extends AbstractCore
{
  
  //Use standard singleton code.
  use SingletonTraits{
    __construct as private singletonConstructor;
  }
  
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
   * This will hold the composer loader that was initiated by main.php.
   * @var ClassLoader
   */
  private $loader;
  
  /**
   * The system logger.
   * @var Logger
   */
  private $logger;
  
  /**
   * Creates the PackageDescriptor for the core package and loads the settings.
   */
  public function __construct()
  {
    
    //Call the singleton constructor first.
    $this->singletonConstructor();
    
    //Then call our own initialize method.
    $this->registerInstance('core', $this)->initializeInstance($this);
    
  }
  
  /**
   * Set up read-only properties.
   */
  public function init()
  {
    
    //Create a PackageDescriptor for ourselves.
    $descriptor = new PackageDescriptor(realpath(__DIR__."/../../../../"));
    
    //Store the descriptor.
    $this->setDescriptor($descriptor);
    
    //Set the default time-zone if necessary.
    if($descriptor->settings['overrideServerTimezone'] || empty(ini_get('date.timezone'))){
      if(!date_default_timezone_set($descriptor->settings['defaultTimezone'])){
        throw new CoreException(sprintf(
          'Failed to set the default timezone to: %s.',
          $descriptor->settings['defaultTimezone']
        ));
      }
    }
    
    //Create the system logger.
    $logger = new Logger('system_log');
    
    //Push a file handler?
    if($descriptor->settings['logFile'] !== false){
      if($descriptor->settings['useSingleLogFile'] === true){
        $logger->pushHandler(new StreamHandler($descriptor->settings['logFile']));
      }else{
        $logger->pushHandler(new RotatingFileHandler($descriptor->settings['logFile']));
      }
    }
    
    //The logger has been set up.
    $logger->info('System logger has been set up.');
    
    //Store the logger.
    $this->logger = $logger;
    
  }
  
  /**
   * Return the system logger object.
   *
   * @return Logger
   */
  public function getSystemLogger()
  {
    
    return $this->logger;
    
  }
  
  /**
   * Set the composer class loader instance.
   *
   * @param ClassLoader $loader The composer class loader.
   *
   * @return self Chaining enabled.
   */
  public function setLoader(ClassLoader $loader)
  {
    
    //Set the property.
    $this->loader = $loader;
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Get the composer class loader instance.
   *
   * @return ClassLoader The composer class loader.
   */
  public function getLoader()
  {
    
    return $this->loader;
    
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
    
    //All keys are stored in lower case.
    $key = strtolower($key);
    
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
    
    //All keys are stored in lower case.
    $key = strtolower($key);
    
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
    
    //All keys are stored in lower case.
    $key = strtolower($key);
    
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
    
    //Log this for debug.
    if($this->logger){
      $this->logger->debug(sprintf('Initializing `%s`.', get_class($instance)));
    }
    
    //Initialize.
    $instance->_initialized = true;
    $instance->init();
    
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
    
    //All keys are stored in lower case.
    $key = strtolower($key);
    
    //Do it.
    return (array_key_exists($key, $this->instances) || array_key_exists($key, $this->instanceLoaders));
    
  }
  
  /**
   * Iterate each directory that passes as a package and call the iterator.
   *
   * @param  Closure $iterator The callback to call for every package.
   *                           Receives 3 arguments. The package name, the vendor
   *                           directory, and the full directory.
   *
   * @return self              Chaining enabled.
   */
  public function iteratePackages(Closure $iterator)
  {
    
    //Reference the directories to look in from the settings.
    $directories = $this->getVendorDirectories();
    
    //Iterate an array of all possible package folders.
    foreach(glob('{'.implode(',', $directories).'}/*/*/', GLOB_NOSORT|GLOB_BRACE|GLOB_ONLYDIR) as $directory)
    {
      
      //Extract the package name.
      $name = basename(dirname($directory)).'/'.basename($directory);
      
      //Extract the vendor directory.
      $vendorDir = dirname(dirname($directory));
      
      //Call the iterator. If it returns false, stop the iteration.
      if($iterator($name, $vendorDir, $directory) === false){
        break;
      }
      
    }
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Create a new PackageDescriptor for the package of the given name.
   *
   * @param  string $packageName The name of the package.
   *
   * @return PackageDescriptor   The descriptor instance.
   */
  public function createPackageDescriptor($packageName)
  {
    
    //Normalize the package name.
    $packageName = $this->normalizePackageName($packageName);
    
    //Create a string for glob.
    $glob = ''
      . ('{')
      . (implode(',', $this->getVendorDirectories()))
      . ('}/')
      . ($this->convertPackageNameToDir($packageName));
    
    //Get the possible matches.
    $directories = glob($glob, GLOB_NOSORT|GLOB_BRACE|GLOB_ONLYDIR);
    
    //No matches? Package not found.
    if(empty($directories)){
      throw new CoreException(sprintf('Package "%s" not found.', $packageName));
    }
    
    //Too many matches? Package installed multiple times.
    if(count($directories) > 1){
      throw new CoreException(sprintf('Package "%s" found %s times.', $packageName), count($directories));
    }
    
    //Create and return the descriptor.
    return new PackageDescriptor($directories[0]);
    
  }
  
  /**
   * Automatically prepends our vendor name to the package name if it's lacking one.
   *
   * @param  string $name The given name of the package.
   *
   * @return string       The normalize package name.
   */
  public function normalizePackageName($name)
  {
    
    //Add the Forall vendor name to the name, if it's lacking one.
    if(strpbrk('.\\/_', $name)===false){
      $name = "forall/$name";
    }
    
    //Return the normalized name.
    return $name;
    
  }
  
  /**
   * Converts a package name to a name space.
   *
   * @param  string $name
   *
   * @return string       The name space.
   */
  public function convertPackageNameToNs($name)
  {
    
    return str_replace(['.', '\\', '/', '_'], '\\', $name);
    
  }
  
  /**
   * Converts a package name to a directory.
   *
   * @param  string $name
   *
   * @return string       The directory.
   */
  public function convertPackageNameToDir($name)
  {
    
    return str_replace(['.', '\\', '/', '_'], DIRECTORY_SEPARATOR, $name);
    
  }
  
  /**
   * Get the package directories as defined in settings.json, converted to absolute paths.
   *
   * @return array
   */
  public function getVendorDirectories()
  {
    
    //Reference the directories to look in from the settings.
    $directories = $this->getDescriptor()->settings['vendorDirectories'];
    
    //Convert them to absolute paths.
    foreach($directories as $i => $directory){
      if($directory{0} !== '/'){
        $directories[$i] = realpath($this->getDescriptor()->getDir()."/$directory");
      }
    }
    
    //Return them.
    return $directories;
    
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
    
    //Iterate the packages.
    $this->iteratePackages(function($name, $vendor, $dir)use($includer){
      
      //Skip this package if it hasn't got a main-file.
      if(!file_exists("$dir/main.php")){
        return true;
      }
      
      //Log.
      $this->logger->debug(sprintf('Including "%s" main file.', $name));
      
      //Include the file.
      $includer("$dir/main.php");
      
    });
    
    //Log.
    $this->logger->debug('All main files are executed. Now calling callbacks.');
    
    //Call the callbacks.
    foreach($this->includeCallbacks as $callback){
      $callback($this);
    }
    
    //Log.
    $this->logger->info('Completed includeMainFiles.');
    
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
