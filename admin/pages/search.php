<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['users' => [], 'links' => []]);
    exit;
}

$currentUser = current_user();
$uid = (int)$currentUser['id'];
$isAdmin = is_admin();

$term = '%' . $q . '%';
$users = [];
$links = [];

if ($isAdmin) {
    $stUsers = $pdo->prepare('SELECT id, name, username, email, role, status FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? OR handle LIKE ? LIMIT 10');
    $stUsers->execute([$term, $term, $term, $term]);
    $rawUsers = $stUsers->fetchAll();
    foreach ($rawUsers as $ru) {
        $ru['profile_url'] = user_url($ru['username']);
        $users[] = $ru;
    }

    $stLinks = $pdo->prepare('SELECT l.id, l.user_id, l.name, l.url, l.clicks, u.username FROM links l JOIN users u ON u.id = l.user_id WHERE l.name LIKE ? OR l.url LIKE ? LIMIT 10');
    $stLinks->execute([$term, $term]);
    $links = $stLinks->fetchAll();
} else {
    $users = [];
    $stLinks = $pdo->prepare('SELECT id, user_id, name, url, clicks FROM links WHERE user_id = ? AND (name LIKE ? OR url LIKE ?) LIMIT 10');
    $stLinks->execute([$uid, $term, $term]);
    $links = $stLinks->fetchAll();
}

echo json_encode([
    'ok' => true,
    'users' => $users,
    'links' => $links
]);
exit;
