<?php
/**
 * Database Configuration (Microsoft SQL Server / PDO_SQLSRV)
 */

return [
    'driver'     => getenv('DB_DRIVER') ?: 'sqlsrv',
    'host'       => getenv('DB_HOST') ?: 'localhost\SQLEXPRESS',
    'database'   => getenv('DB_DATABASE') ?: 'ITAssetInventory',
    'auth_mode'  => strtolower(getenv('DB_AUTH_MODE') ?: 'windows'), // 'windows' or 'sql'
    'username'   => getenv('DB_USERNAME') ?: '',
    'password'   => getenv('DB_PASSWORD') ?: '',
    'trust_cert' => filter_var(getenv('DB_TRUST_CERT') ?: true, FILTER_VALIDATE_BOOLEAN),
    'options'    => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
