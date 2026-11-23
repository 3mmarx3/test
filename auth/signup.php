<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url('index.php'));
    exit;
}

$errors = [];
$name = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');

    if (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    if (!preg_match('/^[a-z0-9_\.-]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters (letters, numbers, underscores, dots, hyphens).';
    } else {
        $stU = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stU->execute([$username]);
        if ($stU->fetch()) {
            $errors[] = 'This username is already taken. Please choose another.';
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $stE = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stE->execute([$email]);
        if ($stE->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtIns = $pdo->prepare('INSERT INTO users (name, username, email, password_hash, role, status, handle) VALUES (?, ?, ?, ?, "user", "active", ?)');
        $stmtIns->execute([$name, $username, $email, $hash, '@' . $username]);
        $newUid = (int)$pdo->lastInsertId();

        $stmtDef = $pdo->prepare('INSERT INTO links (user_id, name, url, sort_order) VALUES (?, ?, ?, ?)');
        $stmtDef->execute([$newUid, 'My Portfolio', 'https://example.com', 1]);
        $stmtDef->execute([$newUid, 'Instagram', 'https://instagram.com/' . $username, 2]);

        session_regenerate_id(true);
        $_SESSION['uid'] = $newUid;
        flash('Account created successfully. Welcome to Wander!');
        header('Location: ' . dashboard_url('index.php'));
        exit;
    }
}

$pageTitle = 'Wander — Create Account';
$showToast = false;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth">
  <div class="card">
    <?php if (!empty($errors)): ?>
      <div style="margin-bottom:14px;padding:10px 14px;border-radius:12px;background:#ff6b7322;border:1px solid #ff6b7355;color:#ffb3b8;font-size:13px">
        <?php foreach ($errors as $err): ?>
          <div><?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <h1>Create page</h1>
    <p class="s">Join Wander and build your unified destinations page.</p>
    <form method="post" action="<?= auth_url('signup.php') ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <label>Full name</label>
      <input type="text" name="name" value="<?= e($name) ?>" placeholder="e.g. Alex Morgan" required autofocus>
      <label>Username</label>
      <input type="text" name="username" value="<?= e($username) ?>" placeholder="e.g. alexmorgan" required autocomplete="username">
      <label>Email address</label>
      <input type="email" name="email" value="<?= e($email) ?>" placeholder="alex@example.com" required autocomplete="email">
      <label>Password (min 8 characters)</label>
      <input type="password" name="password" placeholder="Create a secure password" minlength="8" required autocomplete="new-password">
      <label>Confirm password</label>
      <input type="password" name="password_confirm" placeholder="Confirm your password" minlength="8" required autocomplete="new-password">
      <p style="margin-top:20px"><button class="btn" style="width:100%">Create account</button></p>
    </form>
    <p style="margin-top:16px;text-align:center;font-size:13px;color:var(--mut)">
      Already have an account? <a href="<?= auth_url('login.php') ?>" style="text-decoration:underline">Sign in</a>
    </p>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
