<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$activePage = 'notifications';
$currentUser = current_user();
$userId = (int)$currentUser['id'];
$isAdmin = is_admin();
$pageTitle = 'Wander — Notifications';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'mark_all_read') {
        $st = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $st->execute([$userId]);
        flash('All notifications marked as read.');
    } elseif ($action === 'mark_read') {
        $nid = (int)($_POST['id'] ?? 0);
        if ($nid > 0) {
            $st = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $st->execute([$nid, $userId]);
        }
    } elseif ($action === 'delete') {
        $nid = (int)($_POST['id'] ?? 0);
        if ($nid > 0) {
            $st = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
            $st->execute([$nid, $userId]);
            flash('Notification deleted.');
        }
    } elseif ($action === 'broadcast') {
        if (!$isAdmin) {
            http_response_code(403);
            exit('Access forbidden. Administrator privileges required.');
        }
        $title = trim($_POST['title'] ?? '');
        $msg = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'info');
        $target = trim($_POST['target'] ?? 'all');
        $targetUid = (int)($_POST['target_uid'] ?? 0);
        $rawLink = trim($_POST['link_url'] ?? '');
        $linkUrl = safe_url($rawLink);

        $allowedTypes = ['info', 'success', 'warning', 'system'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'info';
        }

        if ($title !== '' && $msg !== '') {
            if ($target === 'single' && $targetUid > 0) {
                $stTarget = $pdo->prepare('SELECT id, status FROM users WHERE id = ?');
                $stTarget->execute([$targetUid]);
                $targetUser = $stTarget->fetch();
                if ($targetUser && $targetUser['status'] === 'active') {
                    create_notification($targetUid, $title, $msg, $type, $linkUrl);
                    log_audit('send_notification', 'user', $targetUid, 'Sent direct notification: ' . $title);
                    flash('Notification sent to user ID #' . $targetUid . '.');
                } else {
                    flash('Target user is inactive, suspended, or does not exist.', 'err');
                }
            } else {
                try {
                    $pdo->beginTransaction();
                    $users = $pdo->query('SELECT id FROM users WHERE status = "active"')->fetchAll(PDO::FETCH_COLUMN);
                    $ins = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, ?, ?, 0)');
                    foreach ($users as $recipientId) {
                        $ins->execute([(int)$recipientId, $title, $msg, $type, $linkUrl]);
                    }
                    log_audit('broadcast_announcement', 'platform', null, 'Broadcasted to ' . count($users) . ' active users: ' . $title);
                    $pdo->commit();
                    flash('Platform announcement dispatched to ' . count($users) . ' active users.');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    flash('Broadcast delivery failed: transaction safely rolled back.', 'err');
                }
            }
        } else {
            flash('Please provide both an announcement title and message.', 'err');
        }
    }

    header('Location: ' . page_url('notifications.php'));
    exit;
}

$filter = trim($_GET['filter'] ?? 'all');
$sql = 'SELECT * FROM notifications WHERE user_id = ?';
$params = [$userId];

if ($filter === 'unread') {
    $sql .= ' AND is_read = 0';
}

$sql .= ' ORDER BY created_at DESC LIMIT 50';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifs = $stmt->fetchAll();

$unreadTotal = unread_notifications_count($userId);
$allUsersList = $isAdmin ? $pdo->query('SELECT id, name, username, email FROM users ORDER BY name ASC')->fetchAll() : [];

include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>Notifications</h1>
        <p>Stay updated on account events, system notices, and platform updates.</p>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <div class="seg">
          <a href="<?= page_url('notifications.php') ?>" class="<?= $filter === 'all' ? 'on' : '' ?>">All</a>
          <a href="<?= page_url('notifications.php?filter=unread') ?>" class="<?= $filter === 'unread' ? 'on' : '' ?>">Unread (<?= $unreadTotal ?>)</a>
        </div>
        <?php if ($unreadTotal > 0): ?>
          <form method="post" action="<?= page_url('notifications.php') ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn s g">Mark all as read</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($isAdmin): ?>
      <div class="card mb">
        <h2>Broadcast System Announcement</h2>
        <form method="post" action="<?= page_url('notifications.php') ?>" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="broadcast">
          
          <div style="grid-column:1/-1">
            <label style="display:block;font-size:12px;color:var(--mut);margin-bottom:6px">Announcement Title</label>
            <input type="text" name="title" placeholder="e.g. Scheduled System Upgrade" required style="width:100%;background:rgba(255,255,255,.05);border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:#fff">
          </div>

          <div style="grid-column:1/-1">
            <label style="display:block;font-size:12px;color:var(--mut);margin-bottom:6px">Message Content</label>
            <textarea name="message" rows="3" placeholder="Describe the announcement or update details..." required style="width:100%;background:rgba(255,255,255,.05);border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:#fff"></textarea>
          </div>

          <div>
            <label style="display:block;font-size:12px;color:var(--mut);margin-bottom:6px">Type</label>
            <select name="type" style="width:100%;background:#1b1f26;border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:#fff">
              <option value="info">Information (Blue)</option>
              <option value="success">Success / Milestone (Green)</option>
              <option value="warning">Important Alert (Yellow)</option>
              <option value="system">Critical Notice (Red)</option>
            </select>
          </div>

          <div>
            <label style="display:block;font-size:12px;color:var(--mut);margin-bottom:6px">Target Recipients</label>
            <select name="target" id="targetSelect" style="width:100%;background:#1b1f26;border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:#fff" onchange="document.getElementById('singleUserRow').style.display = this.value === 'single' ? 'block' : 'none'">
              <option value="all">Broadcast to All Users</option>
              <option value="single">Send to Specific User</option>
            </select>
          </div>

          <div id="singleUserRow" style="display:none;grid-column:1/-1">
            <label style="display:block;font-size:12px;color:var(--mut);margin-bottom:6px">Select Recipient</label>
            <select name="target_uid" style="width:100%;background:#1b1f26;border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:#fff">
              <?php foreach ($allUsersList as $uItem): ?>
                <option value="<?= (int)$uItem['id'] ?>"><?= e($uItem['name']) ?> (@<?= e($uItem['username']) ?> - <?= e($uItem['email']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="grid-column:1/-1">
            <label style="display:block;font-size:12px;color:var(--mut);margin-bottom:6px">Action Link (Optional URL)</label>
            <input type="text" name="link_url" placeholder="https://example.com/update or internal path" style="width:100%;background:rgba(255,255,255,.05);border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:#fff">
          </div>

          <div style="grid-column:1/-1;margin-top:6px">
            <button type="submit" class="btn s">Send announcement</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <?php if (empty($notifs)): ?>
      <div class="card" style="text-align:center;padding:48px 20px">
        <h3 style="font-size:18px;margin-bottom:8px">No notifications</h3>
        <p style="color:var(--mut);font-size:14px">You are completely caught up! We will notify you when new account or system events occur.</p>
      </div>
    <?php else: ?>
      <div>
        <?php foreach ($notifs as $n): ?>
          <?php
            $bgIcon = 'var(--a1)';
            if ($n['type'] === 'success') $bgIcon = 'var(--ok)';
            elseif ($n['type'] === 'warning') $bgIcon = '#ffd166';
            elseif ($n['type'] === 'system') $bgIcon = 'var(--bad)';
          ?>
          <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
            <div class="icon" style="background:rgba(255,255,255,.08);color:<?= $bgIcon ?>">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div class="info">
              <div class="title"><?= e($n['title']) ?></div>
              <div class="msg"><?= nl2br(e($n['message'])) ?></div>
              <div class="time"><?= e(ago(ts_of($n['created_at']))) ?></div>
              <?php if (!empty($n['link_url']) && safe_url($n['link_url'])): ?>
                <div style="margin-top:6px"><a href="<?= e(safe_url($n['link_url'])) ?>" class="btn s g" style="padding:2px 8px;font-size:11px" target="_blank" rel="noopener noreferrer">View details &rarr;</a></div>
              <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
              <?php if (!$n['is_read']): ?>
                <form method="post" action="<?= page_url('notifications.php') ?>">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <button type="submit" class="btn s g" title="Mark as read" style="padding:4px 8px;font-size:11px">Mark read</button>
                </form>
              <?php endif; ?>
              <form method="post" action="<?= page_url('notifications.php') ?>" onsubmit="return confirm('Delete this notification?')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <button type="submit" class="btn s d" style="padding:4px 8px;font-size:11px" title="Delete">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
