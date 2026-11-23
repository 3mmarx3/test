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
}

$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$inspectId = (int)($_GET['inspect'] ?? 0);

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();
$suspendedUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "suspended"')->fetchColumn();

$sql = 'SELECT u.*, 
        (SELECT COUNT(*) FROM links l WHERE l.user_id = u.id) AS links_count,
        (SELECT COUNT(*) FROM visits v WHERE v.user_id = u.id) AS visits_count
        FROM users u WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $term = '%' . $q . '%';
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
if ($inspectId > 0) {
    $stmtIns = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmtIns->execute([$inspectId]);
    $inspectedUser = $stmtIns->fetch();
    if ($inspectedUser) {
        $stmtInsLinks = $pdo->prepare('SELECT * FROM links WHERE user_id = ? ORDER BY sort_order, id');
        $stmtInsLinks->execute([$inspectId]);
        $inspectedLinks = $stmtInsLinks->fetchAll();
    }
}

$pageTitle  = 'Wander — Users Directory';
$activePage = 'users';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>Users Directory</h1>
        <p>Registered accounts, profile information, and member social destinations.</p>
      </div>
      <div class="seg">
        <a href="?status=&q=<?= urlencode($q) ?>" class="<?= $statusFilter === '' ? 'on' : '' ?>">All (<?= $totalUsers ?>)</a>
        <a href="?status=active&q=<?= urlencode($q) ?>" class="<?= $statusFilter === 'active' ? 'on' : '' ?>">Active (<?= $activeUsers ?>)</a>
        <a href="?status=suspended&q=<?= urlencode($q) ?>" class="<?= $statusFilter === 'suspended' ? 'on' : '' ?>">Suspended (<?= $suspendedUsers ?>)</a>
      </div>
    </div>

    <?php if ($inspectedUser): ?>
      <div class="card mb" style="border-color:var(--a1)">
        <h2>
          <span>Inspecting @<?= e($inspectedUser['username']) ?></span>
          <div style="display:flex;gap:8px">
            <a href="<?= page_url('links.php?user_id=' . (int)$inspectedUser['id']) ?>" class="btn s">Manage links</a>
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
              <span class="bar-l">Username &amp; Public Link</span>
              <span class="bar-v">
                <a href="<?= user_url($inspectedUser['username']) ?>" target="_blank" rel="noopener" style="text-decoration:underline;color:var(--a3)">
                  /u/<?= e($inspectedUser['username']) ?>
                </a>
              </span>
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
          </div>
          <div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Handle / Tagline</span>
              <span class="bar-v"><?= e($inspectedUser['handle'] ?: 'None') ?></span>
            </div>
            <div class="bar" style="margin-bottom:10px">
              <span class="bar-l">Joined Platform</span>
              <span class="bar-v"><?= e(date('M j, Y H:i', strtotime($inspectedUser['created_at']))) ?></span>
            </div>
            <div class="card" style="padding:12px;margin:0">
              <div style="font-size:12px;color:var(--mut);margin-bottom:4px">Biography</div>
              <div style="font-size:13.5px;color:#fff"><?= nl2br(e($inspectedUser['bio'] ?: 'No biography provided.')) ?></div>
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
        <div style="flex:1;min-width:200px">
          <input name="q" value="<?= e($q) ?>" placeholder="Search by name, username or email..." autocomplete="off">
        </div>
        <button class="btn s">Search</button>
        <?php if ($q || $statusFilter): ?>
          <a href="<?= page_url('users.php') ?>" class="btn s g">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card">
      <h2>Registered Members <span><?= count($users) ?> matching</span></h2>
      <div class="scroll">
        <table class="tbl">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Links</th>
              <th>Visits</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="8" class="empty">No registered members found matching criteria.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td>
                  <b><?= e($u['name']) ?></b><br>
                  <small style="color:var(--mut)">@<?= e($u['username']) ?></small>
                </td>
                <td><?= e($u['email']) ?></td>
                <td><span class="pill <?= $u['role'] === 'admin' ? 'up' : 'flat' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                <td><span class="pill <?= $u['status'] === 'active' ? 'up' : 'down' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                <td><?= (int)$u['links_count'] ?></td>
                <td><?= (int)$u['visits_count'] ?></td>
                <td style="text-align:right">
                  <div style="display:inline-flex;gap:6px;align-items:center">
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
  </main>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
