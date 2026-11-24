<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$currentUser = current_user();
$currentUserId = (int)$currentUser['id'];
$isAdmin = is_admin();

$targetUserId = $currentUserId;
if ($isAdmin && !empty($_GET['user_id'])) {
    $reqUid = (int)$_GET['user_id'];
    $stCheck = $pdo->prepare('SELECT id, name, username FROM users WHERE id = ?');
    $stCheck->execute([$reqUid]);
    $targetUser = $stCheck->fetch();
    if ($targetUser) {
        $targetUserId = (int)$targetUser['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $a = $_POST['action'] ?? '';
    $effectiveUserId = ($isAdmin && !empty($_POST['target_user_id'])) ? (int)$_POST['target_user_id'] : $targetUserId;
    if (!$isAdmin && $effectiveUserId !== $currentUserId) {
        $effectiveUserId = $currentUserId;
    }

    if ($a === 'reorder_links') {
        $orderedIds = (array)($_POST['order'] ?? []);
        $orderedIds = array_map('intval', $orderedIds);
        if ($orderedIds) {
            $st = $pdo->prepare('UPDATE links SET sort_order = ? WHERE id = ? AND user_id = ?');
            foreach ($orderedIds as $pos => $lid) {
                if ($lid > 0) {
                    $st->execute([$pos + 1, $lid, $effectiveUserId]);
                }
            }
            log_audit('reorder_links', 'link', null, 'Reordered ' . count($orderedIds) . ' links for user ID #' . $effectiveUserId);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }
    elseif ($a === 'add_links') {
        $names = array_slice((array)($_POST['names'] ?? []), 0, 20);
        $urls  = (array)($_POST['urls'] ?? []);
        $stmtMax = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM links WHERE user_id = ?');
        $stmtMax->execute([$effectiveUserId]);
        $max = (int)$stmtMax->fetchColumn();
        $ins = $pdo->prepare('INSERT INTO links (user_id, name, url, sort_order) VALUES (?, ?, ?, ?)');
        $ok = 0; $bad = 0;
        foreach ($names as $i => $n) {
            $n = trim((string)$n);
            $raw = trim((string)($urls[$i] ?? ''));
            $u = safe_url($raw);
            if ($n === '' && $raw === '') continue;
            if ($n !== '' && $u) { $ins->execute([$effectiveUserId, $n, $u, ++$max]); $ok++; }
            else $bad++;
        }
        if ($ok) {
            log_audit('add_links', 'link', null, 'Added ' . $ok . ' destination links for user ID #' . $effectiveUserId);
            flash($ok . ' destination' . ($ok > 1 ? 's' : '') . ' added' . ($bad ? ' · ' . $bad . ' skipped (name or link missing)' : '.'));
        }
        else flash($bad ? 'Enter a name and a valid destination link.' : 'Nothing to add yet — fill in at least one link.', 'err');
    }
    elseif ($a === 'update_link') {
        $n = trim($_POST['name'] ?? '');
        $u = safe_url($_POST['url'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($n && $u && $id > 0) {
            $pdo->prepare('UPDATE links SET name = ?, url = ? WHERE id = ? AND user_id = ?')->execute([$n, $u, $id, $effectiveUserId]);
            log_audit('update_link', 'link', $id, 'Updated link #' . $id . ' to ' . $n);
            flash('Destination updated.');
        } else {
            flash('Name and a valid link are required.', 'err');
        }
    }
    elseif ($a === 'move_up' || $a === 'move_down') {
        $stmtIds = $pdo->prepare('SELECT id FROM links WHERE user_id = ? ORDER BY sort_order, id');
        $stmtIds->execute([$effectiveUserId]);
        $ids = array_map('intval', $stmtIds->fetchAll(PDO::FETCH_COLUMN));
        $i = array_search((int)$_POST['id'], $ids, true);
        $j = $a === 'move_up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            $st = $pdo->prepare('UPDATE links SET sort_order = ? WHERE id = ? AND user_id = ?');
            foreach ($ids as $k => $id) $st->execute([$k + 1, $id, $effectiveUserId]);
        }
    }
    elseif ($a === 'delete_link') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM links WHERE id = ? AND user_id = ?')->execute([$id, $effectiveUserId]);
            log_audit('delete_link', 'link', $id, 'Deleted link #' . $id . ' for user ID #' . $effectiveUserId);
            flash('Destination deleted.');
        }
    }
    elseif ($a === 'reset_clicks') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('UPDATE links SET clicks = 0 WHERE id = ? AND user_id = ?')->execute([$id, $effectiveUserId]);
            log_audit('reset_clicks', 'link', $id, 'Reset clicks for link #' . $id);
            flash('Clicks reset.');
        }
    }

    $redirectUrl = page_url('links.php' . ($isAdmin && $effectiveUserId !== $currentUserId ? '?user_id=' . $effectiveUserId : ''));
    header('Location: ' . $redirectUrl);
    exit;
}

$stmtLinks = $pdo->prepare('SELECT * FROM links WHERE user_id = ? ORDER BY sort_order, id');
$stmtLinks->execute([$targetUserId]);
$links       = $stmtLinks->fetchAll();
$totalClicks = array_sum(array_column($links, 'clicks'));
$maxClicks   = max(1, ...array_map('intval', array_column($links, 'clicks') ?: [0]));

$targetUserData = null;
if ($targetUserId !== $currentUserId) {
    $stT = $pdo->prepare('SELECT id, name, username FROM users WHERE id = ?');
    $stT->execute([$targetUserId]);
    $targetUserData = $stT->fetch();
}

$pageTitle  = 'Wander — ' . ($isAdmin ? 'Pages' : 'My Page');
$activePage = 'pages';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1><?= $targetUserData ? 'Page for @' . e($targetUserData['username']) : ($isAdmin ? 'Pages' : 'My Page') ?></h1>
        <p><?= $targetUserData ? 'Managing destinations for user ' . e($targetUserData['name']) : 'Add, edit and reorder the destinations shown on your public page.' ?></p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <?php $pageOwnerUsername = $targetUserData ? $targetUserData['username'] : $currentUser['username']; ?>
        <a href="<?= e(user_url($pageOwnerUsername)) ?>" target="_blank" rel="noopener" class="btn s g">View Public Page</a>
        <?php if ($targetUserData): ?>
          <a href="<?= page_url('users.php') ?>" class="btn s g">Back to Users Directory</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb">
      <h2>Add destinations <span>add as many as you like at once</span></h2>
      <form method="post" id="addForm" action="<?= page_url('links.php' . ($targetUserData ? '?user_id=' . $targetUserId : '')) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="add_links">
        <?php if ($targetUserData): ?>
          <input type="hidden" name="target_user_id" value="<?= (int)$targetUserId ?>">
        <?php endif; ?>
        <div id="addRows"></div>
        <div class="add-bar">
          <button type="button" class="btn g" id="addRow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Add another destination</button>
          <button class="btn">Save destinations</button>
        </div>
      </form>
      <div class="lbl" style="margin-top:20px">Quick presets</div>
      <div class="chips" id="presets">
        <?php foreach ([['Instagram','https://instagram.com/'],['TikTok','https://tiktok.com/@'],['X','https://x.com/'],['YouTube','https://youtube.com/@'],['Facebook','https://facebook.com/'],['LinkedIn','https://linkedin.com/in/'],['GitHub','https://github.com/'],['Snapchat','https://snapchat.com/add/'],['Telegram','https://t.me/'],['WhatsApp','https://wa.me/'],['Behance','https://behance.net/'],['Spotify','https://open.spotify.com/']] as $p): ?>
          <button type="button" class="chip" data-name="<?= e($p[0]) ?>" data-url="<?= e($p[1]) ?>">+ <?= e($p[0]) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Configured destinations <span><?= count($links) ?> total · <?= nf($totalClicks) ?> clicks</span></h2>
      <?php if (!$links): ?><p class="empty">No destinations added yet. Pick a preset above or add any custom URL.</p><?php endif; ?>
      <div id="linksList" class="links-list" data-reorder-url="<?= page_url('links.php' . ($targetUserData ? '?user_id=' . $targetUserId : '')) ?>">
        <?php foreach ($links as $i => $l): $w = round($l['clicks'] / $maxClicks * 100); ?>
          <div class="lk-item" data-id="<?= (int)$l['id'] ?>">
            <form method="post" class="lk" action="<?= page_url('links.php' . ($targetUserData ? '?user_id=' . $targetUserId : '')) ?>">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <?php if ($targetUserData): ?>
                <input type="hidden" name="target_user_id" value="<?= (int)$targetUserId ?>">
              <?php endif; ?>
              <div class="mv">
                <div class="drag-handle" title="Drag to reorder" aria-label="Drag to reorder" role="button" tabindex="0">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                </div>
                <div class="mv-btns">
                  <button class="ib" name="action" value="move_up" aria-label="Move up" <?= $i === 0 ? 'disabled style="opacity:.3;cursor:default"' : '' ?>><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button>
                  <button class="ib" name="action" value="move_down" aria-label="Move down" <?= $i === count($links) - 1 ? 'disabled style="opacity:.3;cursor:default"' : '' ?>><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></button>
                </div>
              </div>
              <input name="name" value="<?= e($l['name']) ?>" required aria-label="Name">
              <input name="url" value="<?= e($l['url']) ?>" dir="ltr" required aria-label="URL">
              <div class="acts">
                <button class="btn s" name="action" value="update_link">Save</button>
                <button class="btn s g" name="action" value="reset_clicks" onclick="return confirm('Reset clicks for this link?')">Reset</button>
                <button class="btn s d" name="action" value="delete_link" onclick="return confirm('Delete this link?')">Delete</button>
              </div>
              <div class="cl"><b><?= nf($l['clicks']) ?> clicks</b><div class="cb"><div style="width:<?= $w ?>%"></div></div></div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
