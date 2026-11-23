<?php
if (!defined('DB_TZ')) {
    define('DB_TZ', 'UTC');
}
if (!defined('VIEW_TZ')) {
    define('VIEW_TZ', 'Africa/Cairo');
}
date_default_timezone_set(VIEW_TZ);

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'wander';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';
$DB_PORT = getenv('DB_PORT') ?: '8889';

if (!function_exists('project_base_url')) {
    function project_base_url() {
        static $base = null;
        if ($base !== null) {
            return $base;
        }
        if (isset($_SERVER['DOCUMENT_ROOT']) && realpath($_SERVER['DOCUMENT_ROOT'])) {
            $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
            $projectDir = str_replace('\\', '/', realpath(dirname(__DIR__)));
            if (str_starts_with($projectDir, $docRoot)) {
                $base = rtrim(substr($projectDir, strlen($docRoot)), '/');
                return $base;
            }
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        foreach (['/dashboard', '/pages', '/auth', '/actions', '/includes'] as $marker) {
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

if (!function_exists('auth_url')) {
    function auth_url($path = '') {
        return base_url('auth/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('dashboard_url')) {
    function dashboard_url($path = '') {
        return base_url('dashboard/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return dashboard_url($path);
    }
}

if (!function_exists('page_url')) {
    function page_url($path = '') {
        return base_url('pages/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('action_url')) {
    function action_url($path = '') {
        return base_url('actions/' . ltrim((string)$path, '/'));
    }
}

if (!function_exists('user_url')) {
    function user_url($username = '') {
        return base_url('u/' . rawurlencode(ltrim((string)$username, '/')));
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
        return base_url(ltrim((string)$path, '/'));
    }
}
