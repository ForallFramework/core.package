<?php namespace forall\core;

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


#TEMP: Set response code to 200.
http_response_code(200);
