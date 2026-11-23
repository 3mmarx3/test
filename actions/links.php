<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . page_url('links.php'));
    exit;
}

verify_csrf();
$currentUser = current_user();
$currentUserId = (int)$currentUser['id'];
$isAdmin = is_admin();

$targetUserId = ($isAdmin && !empty($_POST['target_user_id'])) ? (int)$_POST['target_user_id'] : $currentUserId;
if (!$isAdmin) {
    $targetUserId = $currentUserId;
}

$a = $_POST['action'] ?? '';

if ($a === 'reorder_links') {
    $orderedIds = (array)($_POST['order'] ?? []);
    $orderedIds = array_map('intval', $orderedIds);
    if ($orderedIds) {
        $st = $pdo->prepare('UPDATE links SET sort_order = ? WHERE id = ? AND user_id = ?');
        foreach ($orderedIds as $pos => $lid) {
            if ($lid > 0) {
                $st->execute([$pos + 1, $lid, $targetUserId]);
            }
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
} elseif ($a === 'add_links') {
    $names = array_slice((array)($_POST['names'] ?? []), 0, 20);
    $urls  = (array)($_POST['urls'] ?? []);
    $stmtMax = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM links WHERE user_id = ?');
    $stmtMax->execute([$targetUserId]);
    $max = (int)$stmtMax->fetchColumn();
    $ins = $pdo->prepare('INSERT INTO links (user_id, name, url, sort_order) VALUES (?, ?, ?, ?)');
    $ok = 0; $bad = 0;
    foreach ($names as $i => $n) {
        $n = trim((string)$n);
        $raw = trim((string)($urls[$i] ?? ''));
        $u = safe_url($raw);
        if ($n === '' && $raw === '') continue;
        if ($n !== '' && $u) {
            $ins->execute([$targetUserId, $n, $u, ++$max]);
            $ok++;
        } else {
            $bad++;
        }
    }
    if ($ok) flash($ok . ' destination' . ($ok > 1 ? 's' : '') . ' added.');
    else flash('Enter a name and valid link.', 'err');
} elseif ($a === 'update_link') {
    $n = trim($_POST['name'] ?? '');
    $u = safe_url($_POST['url'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($n && $u && $id > 0) {
        $pdo->prepare('UPDATE links SET name = ?, url = ? WHERE id = ? AND user_id = ?')->execute([$n, $u, $id, $targetUserId]);
        flash('Destination updated.');
    }
} elseif ($a === 'delete_link') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM links WHERE id = ? AND user_id = ?')->execute([$id, $targetUserId]);
        flash('Destination deleted.');
    }
} elseif ($a === 'reset_clicks') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('UPDATE links SET clicks = 0 WHERE id = ? AND user_id = ?')->execute([$id, $targetUserId]);
        flash('Clicks reset.');
    }
}

$retQuery = ($isAdmin && $targetUserId !== $currentUserId) ? '?user_id=' . $targetUserId : '';
header('Location: ' . page_url('links.php' . $retQuery));
exit;
