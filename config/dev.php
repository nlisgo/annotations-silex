<?php

use Monolog\Logger;

return [
    'debug' => true,
    'ttl' => 0,
    'logging_level' => Logger::DEBUG,
    'aws' => [
        'queue_name' => 'annotations--dev',
        'queue_message_default_type' => 'profile',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100',
    ],
];
