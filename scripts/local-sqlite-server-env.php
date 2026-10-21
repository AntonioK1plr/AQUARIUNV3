<?php

// Runtime overrides for a local demo when PostgreSQL/Docker is unavailable.
// Load with PHP's auto_prepend_file; this does not change the developer's .env.
foreach ([
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => 'database/aquarium-local.sqlite',
    'CACHE_STORE' => 'file',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'file',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
