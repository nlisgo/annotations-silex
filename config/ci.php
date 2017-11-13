<?php

use Monolog\Logger;

return [
    'debug' => false,
    'ttl' => 0,
    'logging_level' => Logger::INFO,
    'api_url' => 'http://localhost:8080/',
    'hypothesis' => [
        'api_url' => 'https://hypothes.is/api',
        'client_id' => '',
        'secret_key' => '',
        'authority' => '',
    ],
    'aws' => [
        'queue_name' => 'annotations--ci',
        'queue_message_default_type' => 'profile',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100',
    ],
];
