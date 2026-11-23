<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . page_url('profile.php'));
    exit;
}

verify_csrf();
$currentUser = current_user();
$userId = (int)$currentUser['id'];

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $stCheck->execute([$email, $userId]);
    if ($stCheck->fetch()) {
        flash('This email address is already in use by another account.', 'err');
    } else {
        $pdo->prepare('UPDATE users SET name = ?, email = ?, handle = ?, bio = ? WHERE id = ?')
            ->execute([$name, $email, trim($_POST['handle'] ?? ''), trim($_POST['bio'] ?? ''), $userId]);

        if (strlen($_POST['password'] ?? '') >= 8) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $userId]);
        }

        if (!empty($_FILES['photo']['name'])) {
            $p = save_photo($_FILES['photo'], 'avatars');
            if ($p) {
                $pdo->prepare('UPDATE users SET photo = ? WHERE id = ?')->execute([$p, $userId]);
            } else {
                flash('Photo must be JPG, PNG, WEBP or GIF and under 3MB.', 'err');
            }
        }

        if (empty($_SESSION['msg'])) {
            flash('Profile updated successfully.');
        }
    }
} else {
    flash('Full name and a valid email are required.', 'err');
}

header('Location: ' . page_url('profile.php'));
exit;
