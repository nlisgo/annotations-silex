<?php

require_once __DIR__.'/bootstrap.php';

$config = include __DIR__.'/../config/prod.php';

(new eLife\Annotations\Kernel($config))->run();
