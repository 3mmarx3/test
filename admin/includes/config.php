<?php
if (!defined('DB_TZ')) {
    define('DB_TZ', 'UTC');
}
if (!defined('VIEW_TZ')) {
    define('VIEW_TZ', 'Africa/Cairo');
}
date_default_timezone_set(VIEW_TZ);

$isLocalEnv = (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === 'development' ||
    (isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $_SERVER['HTTP_HOST'])) ||
    (strpos(str_replace('\\', '/', __DIR__), '/Applications/MAMP/') !== false));

if ($isLocalEnv && getenv('APP_ENV') !== 'production') {
    $DB_HOST = getenv('DB_HOST') ?: 'localhost';
    $DB_NAME = getenv('DB_NAME') ?: 'wander';
    $DB_USER = getenv('DB_USER') ?: 'root';
    $DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';
    $DB_PORT = getenv('DB_PORT') ?: '8889';
} else {
    $DB_HOST = getenv('DB_HOST') ?: 'localhost';
    $DB_NAME = getenv('DB_NAME') ?: 'u596103429_my_tap';
    $DB_USER = getenv('DB_USER') ?: 'u596103429_my_tap';
    $DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'n8ZY/MV+7';
    $DB_PORT = getenv('DB_PORT') ?: '3306';
}

if (!function_exists('project_base_url')) {
    function project_base_url() {
        static $base = null;
        if ($base !== null) {
            return $base;
        }
        if (isset($_SERVER['DOCUMENT_ROOT']) && realpath($_SERVER['DOCUMENT_ROOT'])) {
            $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
            $projectDir = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
            if (str_starts_with($projectDir, $docRoot)) {
                $base = rtrim(substr($projectDir, strlen($docRoot)), '/');
                return $base;
            }
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        foreach (['/admin', '/dashboard', '/pages', '/auth', '/actions', '/includes'] as $marker) {
            $pos = strpos($scriptName, $marker);
            if ($pos !== false) {
                $base = rtrim(substr($scriptName, 0, $pos), '/');
                return $base;
            }
        }
        $base = '';
        return $base;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $base = project_base_url();
        $p = ltrim((string)$path, '/');
        return ($base === '' ? '' : $base) . ($p === '' ? '/' : '/' . $p);
    }
}

if (!function_exists('admin_base_url')) {
    function admin_base_url($path = '') {
        return base_url('admin/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('auth_url')) {
    function auth_url($path = '') {
        return admin_base_url('auth/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('dashboard_url')) {
    function dashboard_url($path = '') {
        $p = ltrim((string)$path, '/');
        return admin_base_url($p === '' ? 'index.php' : $p);
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return dashboard_url($path);
    }
}

if (!function_exists('page_url')) {
    function page_url($path = '') {
        return admin_base_url('pages/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('action_url')) {
    function action_url($path = '') {
        return admin_base_url('actions/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('app_origin')) {
    function app_origin() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                  ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}

if (!function_exists('user_url')) {
    function user_url($username = '') {
        $u = ltrim((string)$username, '@/');
        return base_url($u);
    }
}

if (!function_exists('full_user_url')) {
    function full_user_url($username = '') {
        $u = ltrim((string)$username, '@/');
        return app_origin() . user_url($u);
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path = '') {
        return base_url('assets/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url($path = '') {
        if (!$path) {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $p = ltrim((string)$path, '/');
        if (str_starts_with($p, 'uploads/')) {
            $p = substr($p, 8);
        }
        return admin_base_url('uploads/' . $p);
    }
}

if (!function_exists('login_url')) {
    function login_url() {
        return base_url('login');
    }
}

if (!function_exists('register_url')) {
    function register_url() {
        return base_url('register');
    }
}
