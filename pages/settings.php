<?php
require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$currentUser = current_user();
$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_email') {
        $email = trim($_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmtCheck->execute([$email, $userId]);
            if ($stmtCheck->fetch()) {
                flash('This email address is already in use by another account.', 'err');
            } else {
                $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
                $stmt->execute([$email, $userId]);
                flash('Email updated.');
            }
        } else {
            flash('Enter a valid email address.', 'err');
        }
        header('Location: ' . page_url('settings.php'));
        exit;
    } elseif ($action === 'change_password') {
        $currentPass = (string)($_POST['current_password'] ?? '');
        $newPass = (string)($_POST['new_password'] ?? '');
        $confirmPass = (string)($_POST['confirm_password'] ?? '');

        $stmtPass = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmtPass->execute([$userId]);
        $hash = (string)$stmtPass->fetchColumn();

        if (!password_verify($currentPass, $hash)) {
            flash('Current password is incorrect.', 'err');
        } elseif (strlen($newPass) < 8) {
            flash('New password must be at least 8 characters long.', 'err');
        } elseif ($newPass !== $confirmPass) {
            flash('New password and confirmation do not match.', 'err');
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmtUpdate = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmtUpdate->execute([$newHash, $userId]);
            flash('Password updated.');
        }
        header('Location: ' . page_url('settings.php'));
        exit;
    } elseif ($action === 'delete_account') {
        if ($currentUser['role'] === 'admin') {
            flash('Administrator account cannot be deleted.', 'err');
            header('Location: ' . page_url('settings.php'));
            exit;
        }

        $password = (string)($_POST['delete_password'] ?? '');
        $confirm = trim($_POST['confirm_delete'] ?? '');

        $stmtPass = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmtPass->execute([$userId]);
        $hash = (string)$stmtPass->fetchColumn();

        if (!password_verify($password, $hash)) {
            flash('Password verification failed.', 'err');
            header('Location: ' . page_url('settings.php'));
            exit;
        }

        if ($confirm !== 'DELETE') {
            flash('Type DELETE to confirm deletion.', 'err');
            header('Location: ' . page_url('settings.php'));
            exit;
        }

        $stmtDel = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmtDel->execute([$userId]);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        header('Location: ' . auth_url('signup.php'));
        exit;
    }
}

$stmtUser = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

$pageTitle  = 'Wander — Settings';
$activePage = 'settings';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
  <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>Settings</h1>
        <p>Manage your account credentials and personal preferences.</p>
      </div>
    </div>

    <div class="grid g2 mb">
      <div class="card">
        <h2>Email address</h2>
        <form method="post" action="<?= page_url('settings.php') ?>">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="update_email">
          <label>Username</label>
          <input value="<?= e($user['username']) ?>" disabled style="opacity:.6;cursor:not-allowed">
          <label>Email</label>
          <input type="email" name="email" value="<?= e($user['email']) ?>" required autocomplete="email">
          <p style="margin-top:20px"><button class="btn s">Save email</button></p>
        </form>
      </div>

      <div class="card">
        <h2>Change password <span>min 8 characters</span></h2>
        <form method="post" action="<?= page_url('settings.php') ?>">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="change_password">
          <label>Current password</label>
          <input type="password" name="current_password" required autocomplete="current-password">
          <label>New password</label>
          <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
          <label>Confirm new password</label>
          <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
          <p style="margin-top:20px"><button class="btn s">Update password</button></p>
        </form>
      </div>
    </div>

    <?php if ($currentUser['role'] !== 'admin'): ?>
      <div class="card" style="border-color:var(--bad)">
        <h2>Delete account <span>permanent action</span></h2>
        <p class="empty" style="padding:0;margin-bottom:16px">This will permanently delete your account, your link destinations, visitor statistics, and click history.</p>
        <form method="post" action="<?= page_url('settings.php') ?>" onsubmit="return confirm('Permanently delete your account?');">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="delete_account">
          <div class="grid g2 mb">
            <div>
              <label>Your password</label>
              <input type="password" name="delete_password" required placeholder="Enter password to verify">
            </div>
            <div>
              <label>Confirmation phrase</label>
              <input type="text" name="confirm_delete" required placeholder="Type DELETE to confirm" autocomplete="off">
            </div>
          </div>
          <button class="btn s d">Permanently delete account</button>
        </form>
      </div>
    <?php endif; ?>
  </main>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
