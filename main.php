<?php namespace forall\core;

//Include autoloader.
$loader = require_once __DIR__."/../../autoload.php";

//Set response code to 200.
http_response_code(200);

//Run the system start up sequence.
forall('core')->setLoader($loader)->includeMainFiles();
