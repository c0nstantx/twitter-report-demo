<?php

require('../vendor/autoload.php');
$config = require('../config/settings.inc.php');

use Rocket\Models\Kernel;

$loader = new Twig_Loader_Filesystem('../templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false,
));
// $twig = new Twig_Environment($loader, array(
//     'cache' => '../cache',
// ));

$kernel = new Kernel($config, $twig);
$response = $kernel->parseRequest();

echo $response;
