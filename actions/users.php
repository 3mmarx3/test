<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . page_url('users.php'));
    exit;
}

verify_csrf();
$currentUser = current_user();
$adminId = (int)$currentUser['id'];

$action = $_POST['action'] ?? '';
$targetId = (int)($_POST['id'] ?? 0);

if ($targetId > 0 && $targetId !== $adminId) {
    if ($action === 'toggle_status') {
        $stmtGet = $pdo->prepare('SELECT username, status FROM users WHERE id = ?');
        $stmtGet->execute([$targetId]);
        $target = $stmtGet->fetch();
        if ($target) {
            $newStatus = ($target['status'] === 'active') ? 'suspended' : 'active';
            $stmtUpdate = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmtUpdate->execute([$newStatus, $targetId]);
            flash('User @' . $target['username'] . ' is now ' . $newStatus . '.');
        }
    } elseif ($action === 'delete_user') {
        $stmtGet = $pdo->prepare('SELECT username FROM users WHERE id = ?');
        $stmtGet->execute([$targetId]);
        $target = $stmtGet->fetch();
        if ($target) {
            $stmtDel = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmtDel->execute([$targetId]);
            flash('User @' . $target['username'] . ' and all data deleted.');
        }
    }
}

$retInspect = !empty($_POST['inspect_id']) ? '?inspect=' . (int)$_POST['inspect_id'] : '';
header('Location: ' . page_url('users.php' . $retInspect));
exit;
