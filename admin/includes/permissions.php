<?php
require_once __DIR__ . '/auth.php';

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return current_user() !== null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        $u = current_user();
        return $u !== null && ($u['role'] ?? '') === 'admin';
    }
}

if (!function_exists('is_user')) {
    function is_user() {
        $u = current_user();
        return $u !== null && ($u['role'] ?? '') === 'user';
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            header('Location: ' . auth_url('login.php'));
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        if (!is_logged_in()) {
            header('Location: ' . auth_url('login.php'));
            exit;
        }
        if (!is_admin()) {
            http_response_code(403);
            exit('Access forbidden. Administrator privileges required.');
        }
    }
}

if (!function_exists('can_access_user')) {
    function can_access_user($target_user_id) {
        $u = current_user();
        if (!$u) {
            return false;
        }
        if ($u['role'] === 'admin') {
            return true;
        }
        return (int)$u['id'] === (int)$target_user_id;
    }
}

if (!function_exists('can_manage_user')) {
    function can_manage_user($target_user_id) {
        return is_admin();
    }
}
