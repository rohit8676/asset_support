<?php
/**
 * Application Configuration
 */

return [
    'name' => getenv('APP_NAME') ?: 'IT Asset & Support Management System',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    'url' => getenv('APP_URL') ?: 'http://localhost/asset-management',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Kolkata',
    'setup_key' => getenv('APP_SETUP_KEY') ?: 'IT_ASSET_SETUP_SECURE_KEY_2026',
];
