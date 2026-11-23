<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Wander — Set New Password';
$showToast = false;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth">
  <div class="card">
    <h1>New password</h1>
    <p class="s">Enter your new secure password.</p>
    <form method="post" action="<?= auth_url('login.php') ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <label>New password (min 8 characters)</label>
      <input type="password" name="password" minlength="8" required autofocus>
      <label>Confirm new password</label>
      <input type="password" name="password_confirm" minlength="8" required>
      <p style="margin-top:20px"><button class="btn" style="width:100%">Update password</button></p>
    </form>
    <p style="margin-top:16px;text-align:center;font-size:13px;color:var(--mut)">
      <a href="<?= auth_url('login.php') ?>" style="text-decoration:underline">Return to sign in</a>
    </p>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
