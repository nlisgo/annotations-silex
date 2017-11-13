<?php

use Monolog\Logger;

return [
    'debug' => true,
    'ttl' => 0,
    'logging_level' => Logger::DEBUG,
    'api_url' => 'http://0.0.0.0:1234/',
    'hypothesis' => [
        'api_url' => 'https://hypothes.is/api',
        'client_id' => '',
        'secret_key' => '',
        'authority' => '',
    ],
    'aws' => [
        'queue_name' => 'annotations--local',
        'queue_message_default_type' => 'profile',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100',
    ],
];
