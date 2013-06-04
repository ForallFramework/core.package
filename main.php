<?php namespace forall\core;

//Include autoloader.
require_once __DIR__."/../../autoload.php";

//Set response code to 200.
http_response_code(200);

//Initialize the core class.
$core = core\Core::getInstance();

//Gather the packages.
$core->gatherPackages();

//Include other packages' main-files.
$core->includeMainFiles();
