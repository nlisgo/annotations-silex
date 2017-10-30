<?php

require_once __DIR__.'/bootstrap.php';

use eLife\Annotations\Kernel;

$app = Kernel::create();
$app['debug'] = true;

$app->run();
