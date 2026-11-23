<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . page_url('settings.php'));
    exit;
}

verify_csrf();
$currentUser = current_user();
$userId = (int)$currentUser['id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_email') {
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmtCheck->execute([$email, $userId]);
        if ($stmtCheck->fetch()) {
            flash('This email address is already in use by another account.', 'err');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
            $stmt->execute([$email, $userId]);
            flash('Email updated.');
        }
    } else {
        flash('Enter a valid email address.', 'err');
    }
    header('Location: ' . page_url('settings.php'));
    exit;
} elseif ($action === 'change_password') {
    $currentPass = (string)($_POST['current_password'] ?? '');
    $newPass = (string)($_POST['new_password'] ?? '');
    $confirmPass = (string)($_POST['confirm_password'] ?? '');

    $stmtPass = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmtPass->execute([$userId]);
    $hash = (string)$stmtPass->fetchColumn();

    if (!password_verify($currentPass, $hash)) {
        flash('Current password is incorrect.', 'err');
    } elseif (strlen($newPass) < 8) {
        flash('New password must be at least 8 characters long.', 'err');
    } elseif ($newPass !== $confirmPass) {
        flash('New password and confirmation do not match.', 'err');
    } else {
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmtUpdate->execute([$newHash, $userId]);
        flash('Password updated.');
    }
    header('Location: ' . page_url('settings.php'));
    exit;
} elseif ($action === 'delete_account') {
    if ($currentUser['role'] === 'admin') {
        flash('Administrator account cannot be deleted.', 'err');
        header('Location: ' . page_url('settings.php'));
        exit;
    }

    $password = (string)($_POST['delete_password'] ?? '');
    $confirm = trim($_POST['confirm_delete'] ?? '');

    $stmtPass = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmtPass->execute([$userId]);
    $hash = (string)$stmtPass->fetchColumn();

    if (!password_verify($password, $hash)) {
        flash('Password verification failed.', 'err');
        header('Location: ' . page_url('settings.php'));
        exit;
    }

    if ($confirm !== 'DELETE') {
        flash('Type DELETE to confirm deletion.', 'err');
        header('Location: ' . page_url('settings.php'));
        exit;
    }

    $stmtDel = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmtDel->execute([$userId]);

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    header('Location: ' . auth_url('signup.php'));
    exit;
}

header('Location: ' . page_url('settings.php'));
exit;
