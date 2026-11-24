<?php
require_once __DIR__ . '/admin/includes/permissions.php';
require_once __DIR__ . '/admin/includes/functions.php';
require_login();

if (is_admin()) {
    header('Location: ' . admin_base_url('index.php'));
    exit;
}

define('ALLOW_USER_WORKSPACE', true);
require __DIR__ . '/admin/index.php';
