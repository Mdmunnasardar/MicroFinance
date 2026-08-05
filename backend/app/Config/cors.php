<?php

declare(strict_types=1);

$configured = array_values(array_filter(array_map(
    'trim',
    explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '')
)));

$defaults = [
    'http://localhost',
    'http://localhost:80',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1',
    'http://127.0.0.1:80',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];

return [
    'allowed_origins' => array_values(array_unique(array_merge($defaults, $configured))),
    'allowed_methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    'allowed_headers' => 'Content-Type, X-Requested-With',
    'allow_credentials' => true,
    'max_age' => 86400,
];