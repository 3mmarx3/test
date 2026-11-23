<?php
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmtLink = $pdo->prepare('SELECT id, user_id FROM links WHERE id = ?');
        $stmtLink->execute([$id]);
        $link = $stmtLink->fetch();
        if ($link) {
            $pdo->prepare('UPDATE links SET clicks = clicks + 1 WHERE id = ?')->execute([$id]);

            $visitor = $_COOKIE['wv'] ?? '';
            if (!preg_match('/^[a-f0-9]{32}$/', $visitor)) {
                $visitor = bin2hex(random_bytes(16));
            }
            try {
                $pdo->prepare('INSERT INTO click_log (user_id, link_id, visitor) VALUES (?, ?, ?)')
                    ->execute([(int)$link['user_id'], $id, $visitor]);
            } catch (Throwable $e) {}
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
exit;
