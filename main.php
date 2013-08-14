<?php namespace forall\core;

//Set response code to 200.
http_response_code(200);

//Include autoloader.
$loader = require_once __DIR__."/../../autoload.php";

//Run the system start up sequence.
forall('core')->setLoader($loader)->includeMainFiles();
