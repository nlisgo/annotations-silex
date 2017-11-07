<?php

use Monolog\Logger;

return [
    'debug' => true,
    'ttl' => 0,
    'logging_level' => Logger::DEBUG,
    'api_url' => 'http://localhost:8080/',
    'hypothesis' => [
        'api_url' => 'https://hypothes.is/',
        'client_id' => '',
        'secret_key' => '',
        'authority' => '',
    ],
    'aws' => [
        'queue_name' => 'annotations--travis',
        'queue_message_default_type' => 'profile',
        'key' => '-----------------------',
        'secret' => '-------------------------------',
        'region' => '---------',
        'endpoint' => 'http://localhost:4100',
    ],
];
