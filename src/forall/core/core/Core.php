<?php

/**
 * @package forall.core
 * @author Avaq <aldwin.vlasblom@gmail.com>
 */
namespace forall\core\core;

use \forall\core\singleton\SingletonTraits;
use \forall\core\singleton\SingletonInterface;
use \Composer\Autoload\ClassLoader;
use \Closure;

/**
 * Core class.
 */
class Core implements SingletonInterface
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
   * A container for the onIncluded event callbacks.
   * @var Closure[]
   */
  private $initCallbacks = [];
  
  /**
   * This will hold the composer loader that was initiated by main.php.
   * @var ClassLoader
   */
  private $loader;
  
  /**
   * Contains a FileIncluder instance with $core in its environment.
   * @var FileIncluder
   */
  private $includer;
  
  /**
   * The names of the packages currently being initialized.
   * @var string|null
   */
  private $initializing = [];
  
  /**
   * An array of package names that have been initialized.
   * @var string[]
   */
  private $initialized = [];
  
  /**
   * The PackageDescriptor instance for the core package.
   * @var PackageDescriptor
   */
  private $descriptor;
  
  /**
   * Creates the PackageDescriptor for the core package and loads the settings.
   */
  public function __construct()
  {
    
    //Call the singleton constructor first.
    $this->singletonConstructor();
    
    //Create a PackageDescriptor for ourselves.
    $this->descriptor = $descriptor = PackageDescriptor::describe(realpath(__DIR__."/../../../../"));
    
    //Set the default time-zone if necessary.
    if($descriptor->overrideServerTimezone || empty(ini_get('date.timezone'))){
      if(!date_default_timezone_set($descriptor->defaultTimezone)){
        throw new CoreException(sprintf(
          'Failed to set the default timezone to: %s.',
          $descriptor->defaultTimezone
        ));
      }
    }
    
    //Create a FileIncluder to prevent giving away our private scope.
    $includer = new FileIncluder(FileIncluder::STRICT|FileIncluder::ONCE);
    
    //Give our public scope as $core.
    $includer->setEnv([
      'core' => $this
    ]);
    
    //Store it for common use.
    $this->includer = $includer;
    
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
   * Return the full absolute path to the forall packages directory.
   * @return string
   */
  public function getForallDirectory()
  {
    
    return realpath(__DIR__."/../../../../../");
    
  }
  
  /**
   * Register a Core class under the given key.
   *
   * @param  string $key The key that the instance is registered under.
   * @param  object $instance The Core-class.
   *
   * @throws CoreException If an instance is being registered outside of a package initialization.
   * @throws CoreException If the key is already in use.
   *
   * @return self Chaining enabled.
   */
  public function register($key, $instance)
  {
    
    //Get the initializing package name.
    $packageName = end($this->initializing);
    
    //We have to be initializing a package.
    if(!$packageName){
      throw new CoreException("Can not register instances (not even $key) outside init.php.");
    }
    
    //Create the full name-spaced key.
    $key = "$packageName.$key";
    
    //The key may not be taken.
    if($this->isInstanceKeyUsed($key)){
      throw new CoreException(sprintf("Can not register any more instances with key %s.", $key));
    }
    
    //Set.
    $this->instances[$key] = $instance;
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Returns true if the given instance is a registered AbstractCore object.
   *
   * @param  AbstractCore $instance
   *
   * @return boolean
   */
  public function isInstanceRegistered($instance)
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
    
    return array_key_exists(strtolower($key), $this->instances);
    
  }
  
  /**
   * Acquire a previously registered instance.
   *
   * @param string $key The full key under which the instance was registered.
   * 
   * @throws CoreException If the argument is not properly name-spaced.
   * @throws CoreException If the package is not installed.
   *
   * @return object The instance.
   */
  public function findInstance($key)
  {
    
    //Make sure the key was properly name-spaced.
    if(substr_count($key, '.') !== 1){
      throw new CoreException(
        'Invalid Argument: Please provide a package name and instance name, separated by a dot.'
      );
    }
    
    //If the key doesn't exist, we will try to load its package before trying again.
    if(!$this->isInstanceKeyUsed($key))
    {
      
      //Extract the package name.
      $packageName = explode('.', $key)[0];
      
      //Check if the package exists, if it doesn't there is a problem.
      if(!$this->getPackageDescriptorByName($packageName)->exists()){
        throw new CoreException(sprintf(
          'Could not find the %s instance. The %s package is not installed.',
          $key,
          $this->normalizePackageName($packageName)
        ));
      }
      
      //Check if the package is already initialized. In that case the key is wrong.
      if($this->isInitialized($packageName)){
        throw new CoreException(sprintf(
          'The %s package never registered an instance under the %s key.',
          $this->normalizePackageName($packageName),
          $key
        ));
      }
      
      //Initialize the package and try again.
      $this->_initializePackage($packageName);
      return $this->findInstance($key);
      
    }
    
    //Return the result.
    return $this->instances[$key];
    
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
    
    //Iterate an array of all possible package folders.
    foreach(glob($this->getVendorDirectory().'/*/*/', GLOB_NOSORT|GLOB_ONLYDIR) as $directory)
    {
      
      //Get the vendor name.
      $vendor = basename(dirname($directory));
      
      //Get the base package name.
      $name = basename($directory);
      
      //Create the full package name.
      $fullname = "$vendor/$name";
      
      //Call the iterator. If it returns false, stop the iteration.
      if($iterator($fullname, $directory, $vendor, $name) === false){
        break;
      }
      
    }
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Return the PackageDescriptor for the package of the given name.
   *
   * @param string $name
   *
   * @return PackageDescriptor
   */
  public function getPackageDescriptorByName($name)
  {
    
    $name = $this->normalizePackageName($name);
    $path = $this->getVendorDirectory().'/'.$this->convertPackageNameToDir($name);
    return PackageDescriptor::describe($path);
    
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
   * Get the package directory as defined in settings.json, converted to an absolute path.
   * @return string
   */
  public function getVendorDirectory()
  {
    
    return realpath($this->descriptor->projectRootDirectory.'/'.$this->descriptor->vendorDirectory);
    
  }
  
  /**
   * Initialize all packages by calling their "init.php" files.
   *
   * @return self Chaining enabled.
   */
  public function initialize()
  {
    
    //Reset the event callbacks.
    $this->initCallbacks = [];
    
    //Iterate the explicit load order packages.
    foreach($this->descriptor->packageLoadOrder as $name){
      $this->_initializePackage($name);
    }
    
    //Iterate the packages by folder structure.
    $this->iteratePackages(function($fullname, $dir, $vendor, $name){
      
      //Skip this package if it isn't a Forall package.
      if($vendor !== 'forall'){
        return true;
      }
      
      //Initialize the package.
      $this->_initializePackage($name);
      
    });
    
    //Call the callbacks.
    foreach($this->initCallbacks as $callback){
      $callback($this);
    }
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Initialize a single package.
   *
   * @param string $name The name of the package.
   *
   * @return self Chaining enabled.
   */
  private function _initializePackage($name)
  {
    
    //Skip the package if it has already been initialized.
    if($this->isInitialized($name)){
      return $this;
    }
    
    //If this package is already initializing, we have a problem.
    if(in_array($name, $this->initializing)){
      throw new CoreException(sprintf(
        'Failed to initialize "%s". It was dependent on "%s".',
        $name,
        implode(
          '" which in turn was dependent on "', 
          array_reverse(array_slice($this->initializing, array_search($name, $this->initializing)))
        )
      ));
    }
    
    //Set this package to initializing.
    array_push($this->initializing, $name);
    
    //No init file found yet.
    $init = false;
    
    //Get the vendor directory.
    $vendors = $this->getVendorDirectory();
    
    //Check if the package has its own init-file.
    if(file_exists("$vendors/forall/$name/init.php")){
      $init = "$vendors/forall/$name/init.php";
    }
    
    //Find out if there is an override init-file available.
    if(file_exists("$vendors/forall/.initializers/$name.php")){
      $init = "$vendors/forall/.initializers/$name.php";
    }
    
    //Include the file.
    if($init){
      $includer = $this->includer;
      $includer($init);
    }
    
    //Move from initializing to initialized.
    array_push($this->initialized, array_pop($this->initializing));
    
    //Enable chaining.
    return $this;
    
  }
  
  /**
   * Returns true if the given package is initialized.
   *
   * @param string $packageName The name of the package to check.
   *
   * @return boolean
   */
  public function isInitialized($packageName)
  {
    
    return in_array($packageName, $this->initialized);
    
  }
  
  /**
   * Register a callback to call after all "init.php" files have been included.
   *
   * @param  Closure $callback The callback. Receives the instance of Core as first argument.
   *
   * @return self Chaining enabled.
   */
  public function onInitialized(Closure $callback)
  {
    
    $this->initCallbacks[] = $callback;
    
  }
  
}
