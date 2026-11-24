<?php
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

if (!function_exists('verify_csrf')) {
    function verify_csrf() {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token');
        }
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        static $user = false;
        global $pdo;
        if ($user !== false) {
            return $user;
        }
        if (empty($_SESSION['uid'])) {
            $user = null;
            return null;
        }
        $stmt = $pdo->prepare('SELECT id, name, email, username, role, status, handle, bio, photo, created_at FROM users WHERE id = ?');
        $stmt->execute([(int)$_SESSION['uid']]);
        $row = $stmt->fetch();
        if ($row && $row['status'] === 'active') {
            $user = $row;
        } else {
            unset($_SESSION['uid']);
            $user = null;
        }
        return $user;
    }
}

if (!function_exists('flash')) {
    function flash($m, $t = 'ok') {
        $_SESSION['msg']  = $m;
        $_SESSION['msgt'] = $t;
    }
}

if (!function_exists('pull_flash')) {
    function pull_flash() {
        $m = $_SESSION['msg'] ?? '';
        $t = $_SESSION['msgt'] ?? 'ok';
        unset($_SESSION['msg'], $_SESSION['msgt']);
        return [$m, $t];
    }
}
