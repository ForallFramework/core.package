<?php namespace forall\core;

//Include autoloader.
$loader = require_once __DIR__."/../../autoload.php";

//Run the system start up sequence.
forall('core')->setLoader($loader)->includeMainFiles();
