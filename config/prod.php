<?php

return [
    'api_url' => 'http://prod--gateway.elife.internal/',
    'hypothesis' => [
        'api_url' => 'https://hypothes.is/api',
        'client_id' => '',
        'secret_key' => '',
        'authority' => '',
    ],
    'aws' => [
        'queue_name' => 'annotations--prod',
        'queue_message_default_type' => 'profile',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
];
