<?php

require('../vendor/autoload.php');
$config = require('../config/settings.inc.php');

use Rocket\Models\Kernel;

$kernel = new Kernel($config);
$response = $kernel->parseRequest();

echo $response;
