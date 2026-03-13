<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration Temps Réel SGEE
    |--------------------------------------------------------------------------
    |
    | Configuration pour la communication temps réel avec la base de données
    | XAMPP et les services de synchronisation.
    |
    */

    'enabled' => env('REALTIME_UPDATES', true),

    'refresh_interval' => env('REALTIME_REFRESH_INTERVAL', 5000), // millisecondes

    'cache' => [
        'enabled' => true,
        'ttl' => 60, // secondes
        'prefix' => 'sgee_realtime_',
    ],

    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'enrolcm'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],

    'performance' => [
        'max_execution_time' => 30,
        'memory_limit' => '256M',
        'query_timeout' => 10,
    ],

    'sync' => [
        'auto_sync' => true,
        'batch_size' => 100,
        'max_retries' => 3,
        'retry_delay' => 1000, // millisecondes
    ],

    'notifications' => [
        'enabled' => true,
        'max_notifications' => 10,
        'priority_levels' => ['low', 'normal', 'high', 'critical'],
    ],

    'health_check' => [
        'enabled' => true,
        'interval' => 30, // secondes
        'timeout' => 5, // secondes
    ],

    'api' => [
        'rate_limit' => 60, // requêtes par minute
        'timeout' => 30, // secondes
        'max_response_size' => '10MB',
    ],

    'logging' => [
        'enabled' => true,
        'level' => env('LOG_LEVEL', 'info'),
        'channels' => ['single', 'daily'],
    ],

    'security' => [
        'require_auth' => true,
        'admin_only_endpoints' => [
            '/api/realtime/sync',
        ],
        'public_endpoints' => [
            '/api/realtime/ping',
            '/api/realtime/health',
            '/api/realtime/metrics',
        ],
    ],

    'xampp' => [
        'mysql_port' => 3306,
        'apache_port' => 80,
        'phpmyadmin_port' => 80,
        'check_services' => true,
    ],

    'fallback' => [
        'use_cache_on_error' => true,
        'use_demo_data' => true,
        'demo_data' => [
            'total_students' => 1247,
            'total_schools' => 8,
            'total_payments' => 1834,
            'active_exams' => 12,
        ],
    ],
];