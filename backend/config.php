<?php
/**
 * Smart Library Management System - Database Configuration
 */

return [
    // Database Driver: 'mysql' or 'sqlite'
    'driver'   => 'mysql',

    // MySQL / MariaDB Settings
    'mysql'    => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'library_db',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset'  => 'utf8mb4',
    ],

    // SQLite Fallback (creates a local library.sqlite database if MySQL is not active)
    'sqlite'   => [
        'path' => __DIR__ . '/library.sqlite',
    ]
];
