<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed. Use POST.');
}

verify_csrf();

$type = trim($_POST['type'] ?? 'users');
$allowedTypes = ['users', 'links', 'analytics'];
if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    exit('Invalid export type requested.');
}

$targetUid = (int)($_POST['user_id'] ?? 0);
$auditLogged = log_audit('export_csv', 'dataset', $targetUid ?: null, 'Exported ' . $type . ' CSV dataset');
if (!$auditLogged) {
    http_response_code(500);
    exit('Export aborted: could not record mandatory audit log.');
}

if (ob_get_level()) {
    ob_end_clean();
}

$filename = 'wander_' . $type . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF");

if ($type === 'users') {
    fputcsv($out, ['User ID', 'Name', 'Username', 'Email', 'Role', 'Status', 'Handle', 'Destinations Count', 'Total Clicks', 'Created At']);
    
    $sql = 'SELECT u.id, u.name, u.username, u.email, u.role, u.status, u.handle, 
                   COUNT(DISTINCT l.id) AS total_links,
                   COALESCE(SUM(l.clicks), 0) AS total_clicks,
                   u.created_at
            FROM users u
            LEFT JOIN links l ON l.user_id = u.id
            GROUP BY u.id
            ORDER BY u.id ASC';
    
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['id'],
            safe_csv_val($row['name']),
            safe_csv_val($row['username']),
            safe_csv_val($row['email']),
            $row['role'],
            $row['status'],
            safe_csv_val($row['handle']),
            (int)$row['total_links'],
            (int)$row['total_clicks'],
            $row['created_at']
        ]);
    }
} elseif ($type === 'links') {
    fputcsv($out, ['Link ID', 'Owner ID', 'Owner Username', 'Title', 'Destination URL', 'Sort Order', 'Clicks', 'Created At']);
    
    if ($targetUid > 0) {
        $sql = 'SELECT l.id, l.user_id, u.username, l.name, l.url, l.sort_order, l.clicks, l.created_at
                FROM links l
                JOIN users u ON u.id = l.user_id
                WHERE l.user_id = ?
                ORDER BY l.sort_order ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$targetUid]);
    } else {
        $sql = 'SELECT l.id, l.user_id, u.username, l.name, l.url, l.sort_order, l.clicks, l.created_at
                FROM links l
                JOIN users u ON u.id = l.user_id
                ORDER BY l.user_id ASC, l.sort_order ASC';
        $stmt = $pdo->query($sql);
    }
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['id'],
            $row['user_id'],
            safe_csv_val($row['username']),
            safe_csv_val($row['name']),
            safe_csv_val($row['url']),
            $row['sort_order'],
            (int)$row['clicks'],
            $row['created_at']
        ]);
    }
} elseif ($type === 'analytics') {
    fputcsv($out, ['Visit ID', 'User ID', 'Owner Username', 'Visitor Hash', 'Country', 'Referrer', 'User Agent', 'Visited At']);
    
    $sql = 'SELECT v.id, v.user_id, u.username, v.visitor, v.country, v.referrer, v.ua, v.created_at
            FROM visits v
            JOIN users u ON u.id = v.user_id
            ORDER BY v.created_at DESC
            LIMIT 5000';
    
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['id'],
            $row['user_id'],
            safe_csv_val($row['username']),
            $row['visitor'],
            $row['country'] ?: '??',
            safe_csv_val($row['referrer']),
            safe_csv_val($row['ua']),
            $row['created_at']
        ]);
    }
}

fclose($out);
exit;
