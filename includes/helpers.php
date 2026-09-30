<?php
/**
 * Helper Functions
 */

if (!function_exists('e')) {
    function e($string): string {
        return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        global $appConfig;
        $base = rtrim($appConfig['url'] ?? '', '/');
        $path = ltrim($path, '/');
        return $path ? "{$base}/{$path}" : $base;
    }
}

if (!function_exists('setFlash')) {
    function setFlash(string $type, string $message): void {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('getFlashes')) {
    function getFlashes(): array {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }
}
