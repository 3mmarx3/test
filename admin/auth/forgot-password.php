<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sent = true;
    } else {
        flash('Please enter a valid email address.', 'err');
    }
}

[$msg, $msgt] = pull_flash();
$pageTitle = 'Wander — Reset Password';
$showToast = false;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth">
  <div class="card">
    <?php if ($msg): ?><p class="s" style="color:var(--bad)"><?= e($msg) ?></p><?php endif; ?>
    <h1>Reset password</h1>
    <?php if ($sent): ?>
      <p class="s" style="color:var(--ok)">If an account exists with that email, password reset instructions have been sent.</p>
      <p style="margin-top:20px;text-align:center">
        <a href="<?= auth_url('login.php') ?>" class="btn" style="width:100%">Return to sign in</a>
      </p>
    <?php else: ?>
      <p class="s">Enter your account email to receive a password recovery link.</p>
      <form method="post" action="<?= auth_url('forgot-password.php') ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Account email</label>
        <input type="email" name="email" placeholder="you@example.com" required autofocus>
        <p style="margin-top:20px"><button class="btn" style="width:100%">Send reset link</button></p>
      </form>
      <p style="margin-top:16px;text-align:center;font-size:13px;color:var(--mut)">
        Remembered your password? <a href="<?= auth_url('login.php') ?>" style="text-decoration:underline">Sign in</a>
      </p>
    <?php endif; ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
