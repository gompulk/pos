<?php

declare(strict_types=1);

return [
    'app_name' => 'POS Flex',
    'base_url' => '/workspace/pos/public',
    'timezone' => 'Asia/Jakarta',
    'currency' => 'IDR',
    'ai' => [
        'enabled' => true,
        'default_provider' => 'generic',
        'webhook_secret' => 'change-this-secret',
    ],
];
