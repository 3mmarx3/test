<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$activePage = 'features';
$pageTitle  = 'Wander — System Insights & Feature Recommendations';

$categories = [
    'User Experience',
    'User Management',
    'Profile Customization',
    'Link Management',
    'Analytics',
    'Security',
    'Administration',
    'Notifications',
    'Performance',
    'Future Expansion'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');

    $allowedStatuses = ['suggested', 'planned', 'implemented', 'dismissed'];
    $allowedPriorities = ['High', 'Medium', 'Low'];
    $allowedComplexities = ['Low', 'Medium', 'High'];

    if ($action === 'quick_status') {
        $fid = (int)($_POST['feature_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        if ($fid > 0 && in_array($newStatus, $allowedStatuses, true)) {
            $stGet = $pdo->prepare('SELECT title FROM feature_suggestions WHERE id = ?');
            $stGet->execute([$fid]);
            $featTitle = $stGet->fetchColumn();

            if ($featTitle) {
                $stUp = $pdo->prepare('UPDATE feature_suggestions SET status = ? WHERE id = ?');
                $stUp->execute([$newStatus, $fid]);

                $statusLabel = $newStatus === 'suggested' ? 'Review / Suggested' : ucfirst($newStatus);
                log_audit('update_feature_status', 'feature_suggestion', $fid, 'Changed status of "' . $featTitle . '" to ' . $newStatus);
                flash('Feature "' . $featTitle . '" marked as ' . $statusLabel . '.');
            } else {
                flash('Feature recommendation not found.', 'err');
            }
        } else {
            flash('Invalid feature or status requested.', 'err');
        }
    } elseif ($action === 'update_feature') {
        $fid = (int)($_POST['feature_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $catInput = trim($_POST['category'] ?? '');
        $prioInput = trim($_POST['priority'] ?? 'Medium');
        $compInput = trim($_POST['complexity'] ?? 'Medium');
        $statInput = trim($_POST['status'] ?? 'suggested');
        $audience = trim($_POST['target_audience'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $benefit = trim($_POST['benefit'] ?? '');
        $dbChanges = trim($_POST['db_changes'] ?? '');
        $deps = trim($_POST['dependencies'] ?? '');

        if ($fid <= 0) {
            flash('Invalid feature ID.', 'err');
        } elseif (strlen($title) < 3) {
            flash('Feature title must be at least 3 characters.', 'err');
        } elseif (!in_array($catInput, $categories, true)) {
            flash('Invalid category selected.', 'err');
        } elseif (!in_array($prioInput, $allowedPriorities, true)) {
            flash('Invalid priority selected.', 'err');
        } elseif (!in_array($compInput, $allowedComplexities, true)) {
            flash('Invalid complexity selected.', 'err');
        } elseif (!in_array($statInput, $allowedStatuses, true)) {
            flash('Invalid status selected.', 'err');
        } elseif ($desc === '' || $benefit === '') {
            flash('Both description and benefit fields are required.', 'err');
        } else {
            $stCheck = $pdo->prepare('SELECT id FROM feature_suggestions WHERE id = ?');
            $stCheck->execute([$fid]);
            if ($stCheck->fetch()) {
                $stUpdate = $pdo->prepare('
                    UPDATE feature_suggestions 
                    SET title = ?, category = ?, priority = ?, complexity = ?, status = ?, target_audience = ?, description = ?, benefit = ?, db_changes = ?, dependencies = ?
                    WHERE id = ?
                ');
                $stUpdate->execute([
                    $title, $catInput, $prioInput, $compInput, $statInput,
                    $audience, $desc, $benefit, $dbChanges, $deps, $fid
                ]);

                log_audit('edit_feature', 'feature_suggestion', $fid, 'Updated details for feature: ' . $title);
                flash('Feature recommendation "' . $title . '" updated successfully.');
            } else {
                flash('Target feature recommendation does not exist.', 'err');
            }
        }
    } elseif ($action === 'create_feature') {
        $title = trim($_POST['title'] ?? '');
        $catInput = trim($_POST['category'] ?? '');
        $prioInput = trim($_POST['priority'] ?? 'Medium');
        $compInput = trim($_POST['complexity'] ?? 'Medium');
        $statInput = trim($_POST['status'] ?? 'suggested');
        $audience = trim($_POST['target_audience'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $benefit = trim($_POST['benefit'] ?? '');
        $dbChanges = trim($_POST['db_changes'] ?? '');
        $deps = trim($_POST['dependencies'] ?? '');

        if (strlen($title) < 3) {
            flash('Feature title must be at least 3 characters.', 'err');
        } elseif (!in_array($catInput, $categories, true)) {
            flash('Invalid category selected.', 'err');
        } elseif (!in_array($prioInput, $allowedPriorities, true)) {
            flash('Invalid priority selected.', 'err');
        } elseif (!in_array($compInput, $allowedComplexities, true)) {
            flash('Invalid complexity selected.', 'err');
        } elseif (!in_array($statInput, $allowedStatuses, true)) {
            flash('Invalid status selected.', 'err');
        } elseif ($desc === '' || $benefit === '') {
            flash('Both description and benefit fields are required.', 'err');
        } else {
            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $baseSlug = trim($baseSlug, '-');
            if ($baseSlug === '') $baseSlug = 'feature-' . time();
            $slug = $baseSlug;
            $counter = 1;
            while ($pdo->prepare('SELECT id FROM feature_suggestions WHERE slug = ?')->execute([$slug]) && $pdo->prepare('SELECT id FROM feature_suggestions WHERE slug = ?')->fetchColumn()) {
                $slug = $baseSlug . '-' . (++$counter);
            }

            $stIns = $pdo->prepare('
                INSERT INTO feature_suggestions
                (slug, title, category, priority, complexity, status, target_audience, description, benefit, db_changes, dependencies)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stIns->execute([
                $slug, $title, $catInput, $prioInput, $compInput, $statInput,
                $audience, $desc, $benefit, $dbChanges, $deps
            ]);
            $newId = (int)$pdo->lastInsertId();

            log_audit('create_feature', 'feature_suggestion', $newId, 'Added new recommendation: ' . $title);
            flash('New recommendation "' . $title . '" created successfully.');
        }
    }

    $retParams = [];
    if (!empty($_GET['cat'])) $retParams['cat'] = $_GET['cat'];
    elseif (!empty($_GET['category'])) $retParams['cat'] = $_GET['category'];
    if (!empty($_GET['prio'])) $retParams['prio'] = $_GET['prio'];
    elseif (!empty($_GET['priority'])) $retParams['prio'] = $_GET['priority'];
    if (!empty($_GET['status'])) $retParams['status'] = $_GET['status'];

    $redirectUrl = page_url('feature-suggestions.php' . ($retParams ? '?' . http_build_query($retParams) : ''));
    header('Location: ' . $redirectUrl);
    exit;
}

$cat = trim($_GET['cat'] ?? $_GET['category'] ?? '');
$prio = trim($_GET['prio'] ?? $_GET['priority'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = 'SELECT * FROM feature_suggestions WHERE 1=1';
$params = [];

if ($cat !== '') {
    $sql .= ' AND category = ?';
    $params[] = $cat;
}
if ($prio !== '') {
    $sql .= ' AND priority = ?';
    $params[] = $prio;
}
if ($status !== '') {
    $sql .= ' AND status = ?';
    $params[] = $status;
}

$sql .= ' ORDER BY FIELD(priority, "High", "Medium", "Low"), FIELD(status, "implemented", "planned", "suggested", "dismissed"), id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$features = $stmt->fetchAll();

$totalFeatures = (int)$pdo->query('SELECT COUNT(*) FROM feature_suggestions')->fetchColumn();
$implementedCount = (int)$pdo->query('SELECT COUNT(*) FROM feature_suggestions WHERE status = "implemented"')->fetchColumn();
$plannedCount = (int)$pdo->query('SELECT COUNT(*) FROM feature_suggestions WHERE status = "planned"')->fetchColumn();
$suggestedCount = (int)$pdo->query('SELECT COUNT(*) FROM feature_suggestions WHERE status = "suggested"')->fetchColumn();
$dismissedCount = (int)$pdo->query('SELECT COUNT(*) FROM feature_suggestions WHERE status = "dismissed"')->fetchColumn();

$diagUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();
$diagLinks = (int)$pdo->query('SELECT COUNT(*) FROM links')->fetchColumn();
$diagClicks = (int)$pdo->query('SELECT COALESCE(SUM(clicks), 0) FROM links')->fetchColumn();
$diagVisits = (int)$pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
$diagAudit = (int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>System Insights &amp; Recommendations</h1>
        <p>Evidence-based system telemetry, architectural intelligence, and feature roadmap management.</p>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div class="seg">
          <a href="<?= page_url('feature-suggestions.php' . ($cat || $prio ? '?' . http_build_query(array_filter(['cat' => $cat ?: null, 'prio' => $prio ?: null])) : '')) ?>" class="<?= $status === '' ? 'on' : '' ?>">All (<?= $totalFeatures ?>)</a>
          <a href="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['status' => 'implemented', 'cat' => $cat ?: null, 'prio' => $prio ?: null]))) ?>" class="<?= $status === 'implemented' ? 'on' : '' ?>">Implemented (<?= $implementedCount ?>)</a>
          <a href="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['status' => 'planned', 'cat' => $cat ?: null, 'prio' => $prio ?: null]))) ?>" class="<?= $status === 'planned' ? 'on' : '' ?>">Planned (<?= $plannedCount ?>)</a>
          <a href="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['status' => 'suggested', 'cat' => $cat ?: null, 'prio' => $prio ?: null]))) ?>" class="<?= $status === 'suggested' ? 'on' : '' ?>">Review / Suggested (<?= $suggestedCount ?>)</a>
          <a href="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['status' => 'dismissed', 'cat' => $cat ?: null, 'prio' => $prio ?: null]))) ?>" class="<?= $status === 'dismissed' ? 'on' : '' ?>">Dismissed (<?= $dismissedCount ?>)</a>
        </div>
        <button type="button" class="btn s" onclick="openEditModal(null)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
          Add Recommendation
        </button>
      </div>
    </div>

    <div class="grid g4 mb">
      <div class="card kpi" style="--c:var(--ok)">
        <div class="l">Implemented Upgrades</div>
        <div class="v"><?= nf($implementedCount) ?></div>
        <div class="d">fully operational in production</div>
      </div>
      <div class="card kpi" style="--c:var(--a1)">
        <div class="l">Planned Roadmap</div>
        <div class="v"><?= nf($plannedCount) ?></div>
        <div class="d">approved for future delivery</div>
      </div>
      <div class="card kpi" style="--c:var(--a2)">
        <div class="l">Review / Suggested</div>
        <div class="v"><?= nf($suggestedCount) ?></div>
        <div class="d">under architecture evaluation</div>
      </div>
      <div class="card kpi" style="--c:var(--a3)">
        <div class="l">Total Tracked</div>
        <div class="v"><?= nf($totalFeatures) ?></div>
        <div class="d">across 10 core categories</div>
      </div>
    </div>

    <div class="card mb" style="border-left:3px solid var(--a1)">
      <h2>
        <span>System Diagnostics &amp; Operational Evaluation</span>
        <span style="font-size:12px;color:var(--ok)">Engine: Rule-Based Telemetry</span>
      </h2>
      <div class="grid g3">
        <div style="background:rgba(255,255,255,.02);padding:12px 14px;border-radius:12px;border:1px solid var(--line)">
          <div style="font-size:11px;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.05em">Engagement Activity</div>
          <div style="font-size:14px;font-weight:600;margin:6px 0 2px"><?= nf($diagUsers) ?> Active Users · <?= nf($diagLinks) ?> Links</div>
          <div style="font-size:12px;color:var(--mut)"><?= nf($diagClicks) ?> total clicks generated across <?= nf($diagVisits) ?> visits.</div>
        </div>
        <div style="background:rgba(255,255,255,.02);padding:12px 14px;border-radius:12px;border:1px solid var(--line)">
          <div style="font-size:11px;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.05em">Security Posture</div>
          <div style="font-size:14px;font-weight:600;margin:6px 0 2px">Role-Based &amp; Isolated</div>
          <div style="font-size:12px;color:var(--mut)">CSRF tokens enforced, SameSite=Lax, prepared SQL. Recommended: TOTP 2FA.</div>
        </div>
        <div style="background:rgba(255,255,255,.02);padding:12px 14px;border-radius:12px;border:1px solid var(--line)">
          <div style="font-size:11px;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.05em">Audit Integrity</div>
          <div style="font-size:14px;font-weight:600;margin:6px 0 2px"><?= nf($diagAudit) ?> Recorded Events</div>
          <div style="font-size:12px;color:var(--mut)">All administrative mutations &amp; exports tracked with actor IP and timestamps.</div>
        </div>
      </div>
    </div>

    <div class="card mb">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px">
        <h2>Filter by Category &amp; Priority</h2>
        <?php if ($cat !== '' || $prio !== '' || $status !== ''): ?>
          <a href="<?= page_url('feature-suggestions.php') ?>" class="btn s g">Reset all filters</a>
        <?php endif; ?>
      </div>

      <div class="filter-bar">
        <a href="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['status' => $status ?: null, 'prio' => $prio ?: null]))) ?>" class="filter-pill <?= $cat === '' ? 'on' : '' ?>">All Categories</a>
        <?php foreach ($categories as $c): ?>
          <?php
            $queryUrl = page_url('feature-suggestions.php?' . http_build_query(array_filter([
                'cat' => $c,
                'prio' => $prio ?: null,
                'status' => $status ?: null
            ])));
          ?>
          <a href="<?= $queryUrl ?>" class="filter-pill <?= $cat === $c ? 'on' : '' ?>"><?= e($c) ?></a>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <span style="font-size:12px;color:var(--mut);margin-right:4px">Priority:</span>
        <a href="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['cat' => $cat ?: null, 'status' => $status ?: null]))) ?>" class="filter-pill <?= $prio === '' ? 'on' : '' ?>">All Priorities</a>
        <?php foreach (['High', 'Medium', 'Low'] as $p): ?>
          <?php
            $queryUrl = page_url('feature-suggestions.php?' . http_build_query(array_filter([
                'cat' => $cat ?: null,
                'prio' => $p,
                'status' => $status ?: null
            ])));
          ?>
          <a href="<?= $queryUrl ?>" class="filter-pill <?= $prio === $p ? 'on' : '' ?>"><?= e($p) ?> Priority</a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (empty($features)): ?>
      <div class="card" style="text-align:center;padding:48px 20px">
        <h3 style="font-size:18px;margin-bottom:8px">No recommendations match your filter criteria</h3>
        <p style="color:var(--mut);font-size:14px">Try adjusting category, priority, or status filters.</p>
        <p style="margin-top:16px"><a href="<?= page_url('feature-suggestions.php') ?>" class="btn s">View all recommendations</a></p>
      </div>
    <?php else: ?>
      <div class="feature-grid">
        <?php foreach ($features as $f): ?>
          <?php
            $statusClass = 'tag-sugg';
            $statusTitle = 'Review / Suggested';
            if ($f['status'] === 'implemented') {
                $statusClass = 'tag-impl';
                $statusTitle = 'Implemented';
            } elseif ($f['status'] === 'planned') {
                $statusClass = 'tag-plan';
                $statusTitle = 'Planned';
            } elseif ($f['status'] === 'dismissed') {
                $statusClass = 'tag-dism';
                $statusTitle = 'Dismissed';
            }

            $prioClass = 'tag-low';
            if ($f['priority'] === 'High') $prioClass = 'tag-high';
            elseif ($f['priority'] === 'Medium') $prioClass = 'tag-med';
          ?>
          <div class="feature-card">
            <div>
              <div class="feature-header">
                <span class="badge"><?= e($f['category']) ?></span>
                <div style="display:flex;gap:6px">
                  <span class="tag <?= $prioClass ?>"><?= e($f['priority']) ?></span>
                  <span class="tag <?= $statusClass ?>"><?= e($statusTitle) ?></span>
                </div>
              </div>
              <h3 class="feature-title" style="margin-top:12px"><?= e($f['title']) ?></h3>
              <p class="feature-desc" style="margin-top:8px"><?= e($f['description']) ?></p>
              <div style="margin-top:10px;padding:8px 10px;background:rgba(255,255,255,.02);border-radius:10px;border-left:2px solid var(--a1)">
                <div style="font-size:11px;font-weight:600;color:var(--txt);margin-bottom:2px">Benefit to MY-TAP:</div>
                <div style="font-size:12.5px;color:var(--mut);line-height:1.4"><?= e($f['benefit']) ?></div>
              </div>
            </div>

            <div>
              <div class="feature-meta">
                <div><span>Audience</span><strong><?= e($f['target_audience']) ?></strong></div>
                <div><span>Complexity</span><strong><?= e($f['complexity']) ?></strong></div>
                <div><span>Database</span><strong title="<?= e($f['db_changes']) ?>" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block"><?= e($f['db_changes'] ?: 'None') ?></strong></div>
                <div><span>Dependencies</span><strong title="<?= e($f['dependencies']) ?>" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block"><?= e($f['dependencies'] ?: 'Standard') ?></strong></div>
              </div>

              <div class="feature-actions">
                <div style="display:inline-flex;gap:6px;flex-wrap:wrap">
                  <?php if ($f['status'] !== 'implemented'): ?>
                    <form method="post" action="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['cat' => $cat ?: null, 'prio' => $prio ?: null, 'status' => $status ?: null]))) ?>">
                      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="quick_status">
                      <input type="hidden" name="feature_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="status" value="implemented">
                      <button type="submit" class="btn s g" style="font-size:12px;padding:5px 10px;min-height:30px">Mark Implemented</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($f['status'] !== 'planned'): ?>
                    <form method="post" action="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['cat' => $cat ?: null, 'prio' => $prio ?: null, 'status' => $status ?: null]))) ?>">
                      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="quick_status">
                      <input type="hidden" name="feature_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="status" value="planned">
                      <button type="submit" class="btn s g" style="font-size:12px;padding:5px 10px;min-height:30px">Mark Planned</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($f['status'] !== 'dismissed'): ?>
                    <form method="post" action="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['cat' => $cat ?: null, 'prio' => $prio ?: null, 'status' => $status ?: null]))) ?>" onsubmit="return confirm('Dismiss this recommendation?');">
                      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="quick_status">
                      <input type="hidden" name="feature_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="status" value="dismissed">
                      <button type="submit" class="btn s d" style="font-size:12px;padding:5px 10px;min-height:30px">Dismiss</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['cat' => $cat ?: null, 'prio' => $prio ?: null, 'status' => $status ?: null]))) ?>">
                      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="quick_status">
                      <input type="hidden" name="feature_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="status" value="suggested">
                      <button type="submit" class="btn s g" style="font-size:12px;padding:5px 10px;min-height:30px">Restore</button>
                    </form>
                  <?php endif; ?>
                </div>

                <div>
                  <button type="button" class="btn s" style="font-size:12px;padding:5px 12px;min-height:30px" onclick="openEditModal(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)">Update</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<div id="editFeatureModal" class="modal-overlay">
  <div class="modal">
    <div class="modal-head">
      <h2 id="modalTitleText">Edit Feature Recommendation</h2>
      <button type="button" class="modal-close" onclick="closeEditModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>

    <form method="post" action="<?= page_url('feature-suggestions.php?' . http_build_query(array_filter(['cat' => $cat ?: null, 'prio' => $prio ?: null, 'status' => $status ?: null]))) ?>" class="modal-form">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" id="modalAction" value="update_feature">
      <input type="hidden" name="feature_id" id="modalFeatureId" value="">

      <div class="full">
        <label>Feature Title</label>
        <input type="text" name="title" id="modalTitle" required placeholder="e.g. Two-Factor Authentication">
      </div>

      <div>
        <label>Category</label>
        <select name="category" id="modalCategory" required>
          <?php foreach ($categories as $catOpt): ?>
            <option value="<?= e($catOpt) ?>"><?= e($catOpt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Status</label>
        <select name="status" id="modalStatus" required>
          <option value="suggested">Review / Suggested</option>
          <option value="planned">Mark Planned</option>
          <option value="implemented">Mark Implemented</option>
          <option value="dismissed">Dismissed</option>
        </select>
      </div>

      <div>
        <label>Priority</label>
        <select name="priority" id="modalPriority" required>
          <option value="High">High Priority</option>
          <option value="Medium">Medium Priority</option>
          <option value="Low">Low Priority</option>
        </select>
      </div>

      <div>
        <label>Implementation Complexity</label>
        <select name="complexity" id="modalComplexity" required>
          <option value="Low">Low Complexity</option>
          <option value="Medium">Medium Complexity</option>
          <option value="High">High Complexity</option>
        </select>
      </div>

      <div class="full">
        <label>Target Audience</label>
        <input type="text" name="target_audience" id="modalAudience" required placeholder="e.g. System Administrators, Public Visitors">
      </div>

      <div class="full">
        <label>Feature Description</label>
        <textarea name="description" id="modalDescription" rows="3" required placeholder="Detailed description of the functionality and capability..."></textarea>
      </div>

      <div class="full">
        <label>Why it benefits MY-TAP</label>
        <textarea name="benefit" id="modalBenefit" rows="2" required placeholder="Explain the operational, security, or user value..."></textarea>
      </div>

      <div>
        <label>Database Requirements</label>
        <input type="text" name="db_changes" id="modalDbChanges" placeholder="e.g. Table xyz, column abc (optional)">
      </div>

      <div>
        <label>Dependencies</label>
        <input type="text" name="dependencies" id="modalDependencies" placeholder="e.g. Auth module, cURL (optional)">
      </div>

      <div class="modal-actions">
        <button type="button" class="btn s g" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn s">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
