<?php

use Monolog\Logger;

return [
    'debug' => true,
    'ttl' => 0,
    'logging_level' => Logger::DEBUG,
    'aws' => [
        'mock_queue' => false,
        'queue_name' => 'annotations-local',
        'queue_message_default_type' => 'profile',
        'key' => '-----------------------',
        'secret' => '-------------------------------',
        'region' => '---------',
    ],
];
