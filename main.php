<?php namespace core;

//Include functions.
require 'src/functions.php';

//Include the core class.
require 'src/core/Core.php';

//Initialize the core class.
$core = core\Core::getInstance();

//Gather the packages.
$core->gatherPackages();

//Include other packages' main-files.
$core->includeMainFiles();
