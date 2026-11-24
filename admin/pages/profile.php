<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$currentUser = current_user();
$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newUsername = strtolower(trim($_POST['username'] ?? ''));

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Full name and a valid email are required.', 'err');
    } elseif ($newUsername === '') {
        flash('Username cannot be empty.', 'err');
    } elseif (!is_valid_username_format($newUsername)) {
        flash('Username must be 3-30 characters with lowercase letters, numbers, and single hyphens.', 'err');
    } elseif (is_username_reserved($newUsername)) {
        flash('This username is reserved by the system. Please choose another.', 'err');
    } elseif (is_username_taken($newUsername, $userId, $pdo)) {
        flash('This username is already taken. Please choose another.', 'err');
    } else {
        $stCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stCheck->execute([$email, $userId]);
        if ($stCheck->fetch()) {
            flash('This email address is already in use by another account.', 'err');
        } else {
            $oldUsername = $currentUser['username'];
            if ($newUsername !== $oldUsername) {
                try {
                    $pdo->prepare('INSERT IGNORE INTO username_aliases (user_id, old_username) VALUES (?, ?)')
                        ->execute([$userId, $oldUsername]);
                } catch (Throwable $e) {}
            }

            $handle = trim($_POST['handle'] ?? '');
            if ($handle === '' || $handle === '@' . $oldUsername) {
                $handle = '@' . $newUsername;
            }

            $pdo->prepare('UPDATE users SET name = ?, username = ?, email = ?, handle = ?, bio = ? WHERE id = ?')
                ->execute([$name, $newUsername, $email, $handle, trim($_POST['bio'] ?? ''), $userId]);

            if (strlen($_POST['password'] ?? '') >= 8) {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $userId]);
            }

            if (!empty($_FILES['photo']['name'])) {
                $p = save_photo($_FILES['photo'], 'avatars');
                if ($p) {
                    $pdo->prepare('UPDATE users SET photo = ? WHERE id = ?')->execute([$p, $userId]);
                } else {
                    flash('Photo must be JPG, PNG, WEBP or GIF and under 3MB.', 'err');
                }
            }

            if (empty($_SESSION['msg'])) {
                log_audit('update_profile', 'user', $userId, 'Updated profile details for @' . $newUsername);
                create_notification($userId, 'Profile Updated', 'Your profile information has been successfully updated.', 'success');
                flash('Profile updated successfully.');
            }
        }
    }

    header('Location: ' . page_url('profile.php'));
    exit;
}

$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$userId]);
$me = $st->fetch();

$pageTitle  = 'Wander — Profile';
$activePage = 'profile';
$withDropJs = true;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>Profile</h1>
        <p>This is what appears on your public Wander profile card.</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button type="button" class="btn s copy-btn" data-url="<?= e(full_user_url($me['username'])) ?>" style="cursor:pointer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          <span class="btn-text">Copy Link</span>
        </button>
        <a href="<?= e(user_url($me['username'])) ?>" target="_blank" rel="noopener" class="btn s g">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          <span>Open Profile</span>
        </a>
        <button type="button" class="btn s g share-btn" data-title="<?= e($me['name']) ?>" data-url="<?= e(full_user_url($me['username'])) ?>" style="cursor:pointer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
          <span class="btn-text">Share</span>
        </button>
      </div>
    </div>

    <div class="card">
      <form method="post" enctype="multipart/form-data" action="<?= page_url('profile.php') ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="prof">
          <div class="full">
            <div class="lbl" style="margin-top:0">Profile avatar</div>
            <?php drop_input(photo_url($me['photo'] ?? '')); ?>
          </div>
          <div>
            <label>Full name</label>
            <input name="name" value="<?= e($me['name']) ?>" placeholder="Your full name" required>
          </div>
          <div>
            <label>Username (used in your public URL)</label>
            <input name="username" value="<?= e($me['username']) ?>" placeholder="e.g. ammar-ahmed" required autocomplete="username">
            <small style="display:block;margin-top:4px;color:var(--mut);font-size:12px">URL: <?= e(full_user_url($me['username'])) ?></small>
          </div>
          <div>
            <label>Public handle / tagline</label>
            <input name="handle" value="<?= e($me['handle'] ?? '') ?>" placeholder="@yourname">
          </div>
          <div class="full">
            <label>Biography</label>
            <textarea name="bio" rows="3" placeholder="A brief sentence about who you are and what you do"><?= e($me['bio'] ?? '') ?></textarea>
          </div>
          <div>
            <label>Email address (used to sign in)</label>
            <input type="email" name="email" value="<?= e($me['email']) ?>" placeholder="you@example.com" required>
          </div>
          <div>
            <label>New password (min 8 characters)</label>
            <input type="password" name="password" placeholder="Leave blank to keep current password" minlength="8" autocomplete="new-password">
          </div>
        </div>
        <p style="margin-top:22px"><button class="btn">Save changes</button></p>
      </form>
    </div>
  </main>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
