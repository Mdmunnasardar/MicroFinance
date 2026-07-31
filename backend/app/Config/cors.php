<?php

declare(strict_types=1);

return [
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173')
    ))),
    'allowed_methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    'allowed_headers' => 'Content-Type, X-Requested-With',
    'allow_credentials' => true,
    'max_age' => 86400,
];
