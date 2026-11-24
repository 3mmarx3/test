<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!check_rate_limit('login')) {
        flash('Too many failed login attempts. Please wait 15 minutes before trying again.', 'err');
        header('Location: ' . auth_url('login.php'));
        exit;
    }
    $login = trim($_POST['email'] ?? $_POST['login'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    $st = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $st->execute([$login, $login]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        if ($u['status'] !== 'active') {
            flash('Your account is currently suspended.', 'err');
        } else {
            clear_rate_limit('login');
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            log_audit('login', 'user', (int)$u['id'], 'Signed in as @' . $u['username']);
            header('Location: ' . dashboard_url('index.php'));
            exit;
        }
    } else {
        record_rate_limit_failure('login');
        flash('Invalid email, username, or password.', 'err');
    }
    header('Location: ' . auth_url('login.php'));
    exit;
}

[$msg, $msgt] = pull_flash();
$pageTitle = 'Wander — Sign In';
$showToast = false;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth">
  <div class="card">
    <?php if ($msg): ?><p class="s" style="color:var(--bad)"><?= e($msg) ?></p><?php endif; ?>
    <h1>Welcome back</h1>
    <p class="s">Sign in to your Wander account.</p>
    <form method="post" action="<?= auth_url('login.php') ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <label>Email or Username</label>
      <input type="text" name="email" placeholder="you@example.com or username" autocomplete="username" required autofocus>
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
      <p style="margin-top:20px"><button class="btn" style="width:100%">Sign in</button></p>
    </form>
    <div style="margin-top:16px;text-align:center;font-size:13px;color:var(--mut);display:flex;justify-content:space-between">
      <a href="<?= auth_url('signup.php') ?>" style="text-decoration:underline">Create an account</a>
      <a href="<?= auth_url('forgot-password.php') ?>" style="text-decoration:underline">Forgot password?</a>
    </div>
    <p style="margin-top:16px;text-align:center;font-size:13px;color:var(--mut)">
      <a href="<?= base_url('') ?>" style="text-decoration:underline">Return to public site</a>
    </p>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
