<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$currentUser = current_user();
$adminId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['id'] ?? 0);

    if ($targetId > 0 && $targetId !== $adminId) {
        if ($action === 'toggle_status') {
            $stmtGet = $pdo->prepare('SELECT username, status FROM users WHERE id = ?');
            $stmtGet->execute([$targetId]);
            $target = $stmtGet->fetch();
            if ($target) {
                $newStatus = ($target['status'] === 'active') ? 'suspended' : 'active';
                $logged = log_audit('toggle_user_status', 'user', $targetId, 'Status changed to ' . $newStatus . ' for @' . $target['username']);
                if ($logged) {
                    $stmtUpdate = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
                    $stmtUpdate->execute([$newStatus, $targetId]);
                    create_notification($targetId, 'Account Status Notice', 'Your account status has been updated to: ' . $newStatus, $newStatus === 'active' ? 'success' : 'warning');
                    flash('User @' . $target['username'] . ' is now ' . $newStatus . '.');
                } else {
                    flash('Operation aborted: failed to record mandatory audit log.', 'err');
                }
            }
        } elseif ($action === 'delete_user') {
            $stmtGet = $pdo->prepare('SELECT username FROM users WHERE id = ?');
            $stmtGet->execute([$targetId]);
            $target = $stmtGet->fetch();
            if ($target) {
                $logged = log_audit('delete_user', 'user', $targetId, 'Deleted user @' . $target['username'] . ' and all data');
                if ($logged) {
                    $stmtDel = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmtDel->execute([$targetId]);
                    flash('User @' . $target['username'] . ' and all data deleted.');
                } else {
                    flash('Operation aborted: failed to record mandatory audit log.', 'err');
                }
            }
        }
    }

    $retInspect = !empty($_POST['inspect_id']) ? '?inspect=' . (int)$_POST['inspect_id'] : '';
    header('Location: ' . page_url('users.php' . $retInspect));
    exit;
}

$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$inspectId = (int)($_GET['inspect'] ?? 0);

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();
$suspendedUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "suspended"')->fetchColumn();

$sql = 'SELECT u.*, 
        (SELECT COUNT(*) FROM links l WHERE l.user_id = u.id) AS links_count,
        (SELECT COALESCE(SUM(clicks), 0) FROM links l WHERE l.user_id = u.id) AS total_clicks,
        (SELECT COUNT(*) FROM visits v WHERE v.user_id = u.id) AS visits_count
        FROM users u WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.handle LIKE ?)';
    $term = '%' . $q . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter === 'active' || $statusFilter === 'suspended') {
    $sql .= ' AND u.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY u.id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$inspectedUser = null;
$inspectedLinks = [];
$inspectedVisitsTotal = 0;
$inspectedTopLink = null;
if ($inspectId > 0) {
    $stmtIns = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmtIns->execute([$inspectId]);
    $inspectedUser = $stmtIns->fetch();
    if ($inspectedUser) {
        $stmtInsLinks = $pdo->prepare('SELECT * FROM links WHERE user_id = ? ORDER BY sort_order, id');
        $stmtInsLinks->execute([$inspectId]);
        $inspectedLinks = $stmtInsLinks->fetchAll();

        $stmtVis = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE user_id = ?');
        $stmtVis->execute([$inspectId]);
        $inspectedVisitsTotal = (int)$stmtVis->fetchColumn();

        $stmtTop = $pdo->prepare('SELECT name, clicks, url FROM links WHERE user_id = ? ORDER BY clicks DESC, sort_order ASC LIMIT 1');
        $stmtTop->execute([$inspectId]);
        $inspectedTopLink = $stmtTop->fetch();
    }
}

$auditAction = trim($_GET['audit_action'] ?? '');
$auditQ = trim($_GET['audit_q'] ?? '');

$auditSql = 'SELECT a.*, u.name AS actor_name, u.username AS actor_username
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.actor_id
             WHERE 1=1';
$auditParams = [];

if ($auditAction === 'user_ops') {
    $auditSql .= ' AND a.action IN ("toggle_user_status", "delete_user")';
} elseif ($auditAction === 'features') {
    $auditSql .= ' AND a.action IN ("update_feature_status", "edit_feature", "create_feature")';
} elseif ($auditAction === 'exports') {
    $auditSql .= ' AND a.action = "export_csv"';
} elseif ($auditAction === 'broadcasts') {
    $auditSql .= ' AND a.action IN ("broadcast_announcement", "send_notification")';
} elseif ($auditAction === 'links') {
    $auditSql .= ' AND a.action IN ("reorder_links", "add_links", "update_link", "delete_link", "reset_clicks")';
}

if ($auditQ !== '') {
    $auditSql .= ' AND (a.details LIKE ? OR a.action LIKE ? OR u.username LIKE ?)';
    $term = '%' . $auditQ . '%';
    $auditParams[] = $term;
    $auditParams[] = $term;
    $auditParams[] = $term;
}

$auditSql .= ' ORDER BY a.created_at DESC LIMIT 30';
$auditStmt = $pdo->prepare($auditSql);
$auditStmt->execute($auditParams);
$auditLogs = $auditStmt->fetchAll();

$pageTitle  = 'Wander — Users Directory & Intelligence';
$activePage = 'users';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>Users Directory</h1>
        <p>Registered accounts, profile intelligence, administrative controls, and system activity logs.</p>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <div class="seg">
          <a href="?status=&q=<?= urlencode($q) ?>" class="<?= $statusFilter === '' ? 'on' : '' ?>">All (<?= $totalUsers ?>)</a>
          <a href="?status=active&q=<?= urlencode($q) ?>" class="<?= $statusFilter === 'active' ? 'on' : '' ?>">Active (<?= $activeUsers ?>)</a>
          <a href="?status=suspended&q=<?= urlencode($q) ?>" class="<?= $statusFilter === 'suspended' ? 'on' : '' ?>">Suspended (<?= $suspendedUsers ?>)</a>
        </div>
        <div style="display:inline-flex;gap:6px">
          <form method="post" action="<?= page_url('export.php') ?>" style="display:inline;margin:0">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="type" value="users">
            <button type="submit" class="btn s g" title="Download users as CSV" style="cursor:pointer">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
              Export Users
            </button>
          </form>
          <form method="post" action="<?= page_url('export.php') ?>" style="display:inline;margin:0">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="type" value="links">
            <button type="submit" class="btn s g" title="Download destination links as CSV" style="cursor:pointer">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
              Export Links
            </button>
          </form>
        </div>
      </div>
    </div>

    <?php if ($inspectedUser):
      $inspUrl = full_user_url($inspectedUser['username']);
    ?>
      <div class="card mb" style="border-color:var(--a1)">
        <h2>
          <span>Account Intelligence: @<?= e($inspectedUser['username']) ?></span>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?= e(user_url($inspectedUser['username'])) ?>" target="_blank" rel="noopener" class="btn s g">Open Profile</a>
            <button type="button" class="btn s g copy-btn" data-url="<?= e($inspUrl) ?>"><span class="btn-text">Copy Profile Link</span></button>
            <form method="post" action="<?= page_url('export.php') ?>" style="display:inline;margin:0">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="type" value="links">
              <input type="hidden" name="user_id" value="<?= (int)$inspectedUser['id'] ?>">
              <button type="submit" class="btn s g" title="Export this user destinations as CSV" style="cursor:pointer">Export Links CSV</button>
            </form>
            <a href="<?= page_url('links.php?user_id=' . (int)$inspectedUser['id']) ?>" class="btn s">Manage destinations</a>
            <a href="<?= page_url('users.php') ?>" class="btn s g">Close inspector</a>
          </div>
        </h2>
        <div class="grid g2 mb">
          <div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Full Name</span>
              <span class="bar-v"><?= e($inspectedUser['name']) ?></span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Username</span>
              <span class="bar-v">@<?= e($inspectedUser['username']) ?></span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Public Profile URL</span>
              <span class="bar-v"><a href="<?= e(user_url($inspectedUser['username'])) ?>" target="_blank" rel="noopener" style="color:var(--a3);font-family:monospace;font-size:12px;text-decoration:underline"><?= e($inspUrl) ?></a></span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Email Address</span>
              <span class="bar-v"><?= e($inspectedUser['email']) ?></span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Role &amp; Status</span>
              <span class="bar-v">
                <span class="pill <?= $inspectedUser['role'] === 'admin' ? 'up' : 'flat' ?>"><?= e(ucfirst($inspectedUser['role'])) ?></span>
                <span class="pill <?= $inspectedUser['status'] === 'active' ? 'up' : 'down' ?>" style="margin-left:4px"><?= e(ucfirst($inspectedUser['status'])) ?></span>
              </span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Profile Availability</span>
              <span class="bar-v">
                <span class="pill <?= $inspectedUser['status'] === 'active' ? 'up' : 'down' ?>"><?= $inspectedUser['status'] === 'active' ? 'Public & Accessible' : 'Suspended / Inaccessible' ?></span>
              </span>
            </div>
          </div>
          <div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Profile Visits</span>
              <span class="bar-v"><b><?= nf($inspectedVisitsTotal) ?></b> visits recorded</span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Destinations Configured</span>
              <span class="bar-v"><b><?= count($inspectedLinks) ?></b> links</span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Top Destination</span>
              <span class="bar-v"><?= $inspectedTopLink ? '<b>' . e($inspectedTopLink['name']) . '</b> (' . nf($inspectedTopLink['clicks']) . ' clicks)' : 'None' ?></span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Account Registration</span>
              <span class="bar-v"><?= e(date('M j, Y H:i', strtotime($inspectedUser['created_at']))) ?> (<?= e(ago(ts_of($inspectedUser['created_at']))) ?>)</span>
            </div>
            <div class="card" style="padding:10px 12px;margin:0">
              <div style="font-size:11.5px;color:var(--mut);margin-bottom:2px">Bio Description</div>
              <div style="font-size:13px;color:#fff"><?= nl2br(e($inspectedUser['bio'] ?: 'No biography set.')) ?></div>
            </div>
          </div>
        </div>

        <h2 style="margin-top:16px">Configured Social Destinations (<?= count($inspectedLinks) ?>)</h2>
        <?php if ($inspectedLinks): ?>
          <div class="scroll">
            <table class="tbl">
              <thead><tr><th>Order</th><th>Destination Title</th><th>Target URL</th><th>Clicks</th></tr></thead>
              <tbody>
                <?php foreach ($inspectedLinks as $il): ?>
                  <tr>
                    <td>#<?= (int)$il['sort_order'] ?></td>
                    <td><b><?= e($il['name']) ?></b></td>
                    <td><a href="<?= e($il['url']) ?>" target="_blank" rel="noopener" style="color:var(--a3);text-decoration:underline"><?= e($il['url']) ?></a></td>
                    <td><b><?= nf($il['clicks']) ?></b></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="empty">This user has not configured any social destinations yet.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card mb">
      <form method="get" action="<?= page_url('users.php') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <div style="flex:1;min-width:240px" class="search-box">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input name="q" value="<?= e($q) ?>" placeholder="Search users by name, username, email, or handle..." autocomplete="off">
        </div>
        <button class="btn s">Search</button>
        <?php if ($q || $statusFilter): ?>
          <a href="<?= page_url('users.php') ?>" class="btn s g">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card mb">
      <h2>Registered Members <span><?= count($users) ?> matching</span></h2>
      <div class="scroll">
        <table class="tbl">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Public Profile URL</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Availability</th>
              <th>Links</th>
              <th>Clicks</th>
              <th>Joined</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="11" class="empty">No registered members found matching criteria.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u):
              $uUrl = full_user_url($u['username']);
            ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td>
                  <b><?= e($u['name']) ?></b><br>
                  <small style="color:var(--mut)">@<?= e($u['username']) ?></small>
                </td>
                <td>
                  <a href="<?= e(user_url($u['username'])) ?>" target="_blank" rel="noopener" style="color:var(--a3);font-family:monospace;font-size:12px;text-decoration:underline">
                    <?= e($uUrl) ?>
                  </a>
                </td>
                <td><?= e($u['email']) ?></td>
                <td><span class="pill <?= $u['role'] === 'admin' ? 'up' : 'flat' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                <td><span class="pill <?= $u['status'] === 'active' ? 'up' : 'down' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                <td><span class="pill <?= $u['status'] === 'active' ? 'up' : 'down' ?>"><?= $u['status'] === 'active' ? 'Public' : 'Hidden' ?></span></td>
                <td><?= (int)$u['links_count'] ?></td>
                <td><b><?= nf($u['total_clicks']) ?></b></td>
                <td><small style="color:var(--mut)"><?= e(ago(ts_of($u['created_at']))) ?></small></td>
                <td style="text-align:right">
                  <div style="display:inline-flex;gap:6px;align-items:center">
                    <a href="<?= e(user_url($u['username'])) ?>" target="_blank" rel="noopener" class="btn s g" title="Open profile in new tab">Open</a>
                    <button type="button" class="btn s g copy-btn" data-url="<?= e($uUrl) ?>" title="Copy profile link" style="cursor:pointer">
                      <span class="btn-text">Copy</span>
                    </button>
                    <a href="?inspect=<?= (int)$u['id'] ?>&status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($q) ?>" class="btn s g">Inspect</a>
                    <?php if ((int)$u['id'] !== $adminId): ?>
                      <form method="post" action="<?= page_url('users.php') ?>" style="display:inline" onsubmit="return confirm('Change status for this user?');">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button class="btn s <?= $u['status'] === 'active' ? 'd' : 'g' ?>">
                          <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                        </button>
                      </form>
                      <form method="post" action="<?= page_url('users.php') ?>" style="display:inline" onsubmit="return confirm('Permanently delete @<?= e($u['username']) ?> and all data?');">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button class="btn s d">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
        <h2>System Activity &amp; Audit Log</h2>
        <span style="font-size:12px;color:var(--mut)">Recent administrative operations &amp; security events</span>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px">
        <div class="filter-bar" style="margin:0">
          <a href="<?= page_url('users.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null, 'inspect' => $inspectId ?: null]))) ?>" class="filter-pill <?= $auditAction === '' ? 'on' : '' ?>">All Events</a>
          <a href="<?= page_url('users.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null, 'inspect' => $inspectId ?: null, 'audit_action' => 'user_ops']))) ?>" class="filter-pill <?= $auditAction === 'user_ops' ? 'on' : '' ?>">User Status &amp; Deletions</a>
          <a href="<?= page_url('users.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null, 'inspect' => $inspectId ?: null, 'audit_action' => 'features']))) ?>" class="filter-pill <?= $auditAction === 'features' ? 'on' : '' ?>">Roadmap Updates</a>
          <a href="<?= page_url('users.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null, 'inspect' => $inspectId ?: null, 'audit_action' => 'exports']))) ?>" class="filter-pill <?= $auditAction === 'exports' ? 'on' : '' ?>">CSV Exports</a>
          <a href="<?= page_url('users.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null, 'inspect' => $inspectId ?: null, 'audit_action' => 'broadcasts']))) ?>" class="filter-pill <?= $auditAction === 'broadcasts' ? 'on' : '' ?>">Announcements</a>
          <a href="<?= page_url('users.php?' . http_build_query(array_filter(['status' => $statusFilter ?: null, 'q' => $q ?: null, 'inspect' => $inspectId ?: null, 'audit_action' => 'links']))) ?>" class="filter-pill <?= $auditAction === 'links' ? 'on' : '' ?>">Destinations</a>
        </div>
        <form method="get" action="<?= page_url('users.php') ?>" style="display:flex;gap:6px">
          <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <input type="hidden" name="q" value="<?= e($q) ?>">
          <?php if ($inspectId): ?><input type="hidden" name="inspect" value="<?= (int)$inspectId ?>"><?php endif; ?>
          <input type="hidden" name="audit_action" value="<?= e($auditAction) ?>">
          <input type="text" name="audit_q" value="<?= e($auditQ) ?>" placeholder="Filter audit logs..." style="background:rgba(255,255,255,.05);border:1px solid var(--line);border-radius:10px;padding:6px 10px;font-size:12px;color:#fff">
          <button type="submit" class="btn s g" style="padding:6px 12px;font-size:12px">Filter</button>
        </form>
      </div>
      <?php if (empty($auditLogs)): ?>
        <p class="empty">No system audit events recorded yet.</p>
      <?php else: ?>
        <div class="scroll">
          <table class="audit-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Actor</th>
                <th>Operation</th>
                <th>Target Type</th>
                <th>Details</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($auditLogs as $log): ?>
                <tr>
                  <td style="white-space:nowrap;color:var(--mut)"><?= e(date('M j, H:i', strtotime($log['created_at']))) ?> (<?= e(ago(ts_of($log['created_at']))) ?>)</td>
                  <td><strong>@<?= e($log['actor_username'] ?: 'system') ?></strong></td>
                  <td><span class="tag tag-sugg"><?= e($log['action']) ?></span></td>
                  <td><span style="font-size:12px;color:var(--mut)"><?= e($log['target_type']) ?><?= $log['target_id'] ? ' #' . (int)$log['target_id'] : '' ?></span></td>
                  <td><?= e($log['details']) ?></td>
                  <td style="font-family:monospace;font-size:11.5px;color:var(--mut)"><?= e($log['ip_address'] ?: '127.0.0.1') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
