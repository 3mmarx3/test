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

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stCheck->execute([$email, $userId]);
        if ($stCheck->fetch()) {
            flash('This email address is already in use by another account.', 'err');
        } else {
            $pdo->prepare('UPDATE users SET name = ?, email = ?, handle = ?, bio = ? WHERE id = ?')
                ->execute([$name, $email, trim($_POST['handle'] ?? ''), trim($_POST['bio'] ?? ''), $userId]);

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
                flash('Profile updated successfully.');
            }
        }
    } else {
        flash('Full name and a valid email are required.', 'err');
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
      <div>
        <a href="<?= is_admin() ? base_url('index.php') : user_url($me['username']) ?>" target="_blank" rel="noopener" class="btn s g">Preview public page</a>
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
