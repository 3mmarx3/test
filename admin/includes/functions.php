<?php
require_once __DIR__ . '/config.php';

if (!function_exists('e')) {
    function e($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('photo_url')) {
    function photo_url($p) {
        if (!$p) {
            return '';
        }
        return upload_url($p);
    }
}

if (!function_exists('safe_url')) {
    function safe_url($u) {
        $u = trim((string)$u);
        if ($u === '') {
            return '';
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $u)) {
            $u = 'https://' . $u;
        }
        return preg_match('#^https?://[^\s/]+#i', $u) ? $u : '';
    }
}

if (!function_exists('ts_of')) {
    function ts_of($v) {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            $t = (int)$v;
            return $t > 1e12 ? intdiv($t, 1000) : $t;
        }
        try {
            return (new DateTime($v, new DateTimeZone(DB_TZ)))->getTimestamp();
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('flag')) {
    function flag($cc) {
        if (!$cc || strlen($cc) !== 2 || !function_exists('mb_chr')) {
            return '🌐';
        }
        $cc = strtoupper($cc);
        return mb_chr(0x1F1E6 + ord($cc[0]) - 65, 'UTF-8') . mb_chr(0x1F1E6 + ord($cc[1]) - 65, 'UTF-8');
    }
}

if (!function_exists('country_name')) {
    function country_name($cc) {
        if (!$cc) {
            return 'Unknown';
        }
        if (class_exists('Locale')) {
            $n = Locale::getDisplayRegion('-' . $cc, 'en');
            if ($n && $n !== $cc) {
                return $n;
            }
        }
        return $cc;
    }
}

if (!function_exists('ago')) {
    function ago($ts) {
        $d = time() - $ts;
        if ($d < 60) {
            return 'just now';
        }
        if ($d < 3600) {
            return floor($d / 60) . 'm ago';
        }
        if ($d < 86400) {
            return floor($d / 3600) . 'h ago';
        }
        return floor($d / 86400) . 'd ago';
    }
}

if (!function_exists('pct_delta')) {
    function pct_delta($cur, $prev) {
        if ($prev == 0) {
            return $cur > 0 ? ['+100%', 'up'] : ['0%', 'flat'];
        }
        $p = round(($cur - $prev) / $prev * 100);
        return [($p >= 0 ? '+' : '') . $p . '%', $p > 0 ? 'up' : ($p < 0 ? 'down' : 'flat')];
    }
}

if (!function_exists('nf')) {
    function nf($n) {
        return number_format((int)$n);
    }
}

if (!function_exists('bars')) {
    function bars($arr, $total, $labelFn = null, $limit = 8) {
        if (!$arr) {
            echo '<p class="empty">No data in this period yet.</p>';
            return;
        }
        $max = max($arr);
        $i = 0;
        foreach ($arr as $k => $v) {
            if ($i++ >= $limit) break;
            $lab = $labelFn ? $labelFn($k) : e($k);
            $w   = $max ? round($v / $max * 100) : 0;
            $p   = $total ? round($v / $total * 100) : 0;
            echo '<div class="bar"><div class="bar-fill" style="width:' . $w . '%"></div><span class="bar-l">' . $lab . '</span><span class="bar-v">' . nf($v) . ' <i>' . $p . '%</i></span></div>';
        }
    }
}

if (!function_exists('drop_input')) {
    function drop_input($src = '') { ?>
<label class="drop" data-drop data-src="<?= e($src) ?>">
  <input type="file" name="photo" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
  <span class="drop-prev">
    <img alt="" hidden>
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8a2 2 0 012-2h1.5l1.4-2h6.2l1.4 2H18a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
  </span>
  <span class="drop-txt">
    <b>Upload a photo</b>
    <span class="sub">Drag &amp; drop, or click to browse</span>
    <span class="hint">JPG, PNG, WEBP or GIF · up to 3MB</span>
  </span>
  <button type="button" class="drop-clear" hidden aria-label="Remove selected photo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
</label>
<?php }
}

if (!function_exists('save_photo')) {
    function save_photo($f, $subDir = 'avatars') {
        if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($f['size'] > 3 * 1024 * 1024) {
            return null;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];
        $ext = $extensions[$mime] ?? null;
        if (!$ext) {
            return null;
        }
        $projectDir = dirname(__DIR__);
        $targetDir = $projectDir . '/uploads/' . trim($subDir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $relName = 'uploads/' . trim($subDir, '/') . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $projectDir . '/' . $relName;
        if (move_uploaded_file($f['tmp_name'], $destPath)) {
            return $relName;
        }
        return null;
    }
}

if (!function_exists('log_audit')) {
    function log_audit($action, $targetType = 'system', $targetId = null, $details = '') {
        global $pdo;
        if (!$pdo) {
            return false;
        }
        $actorId = (int)($_SESSION['uid'] ?? 0);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        try {
            $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
            return $stmt->execute([$actorId, $action, $targetType, $targetId, $details, $ip]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('create_notification')) {
    function create_notification($userId, $title, $message, $type = 'info', $linkUrl = '') {
        global $pdo;
        if (!$pdo || !(int)$userId) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, ?, ?, 0)');
            return $stmt->execute([(int)$userId, $title, $message, $type, $linkUrl]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('unread_notifications_count')) {
    function unread_notifications_count($userId) {
        global $pdo;
        if (!$pdo || !(int)$userId) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
            $stmt->execute([(int)$userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('safe_csv_val')) {
    function safe_csv_val($val) {
        $val = (string)$val;
        if (preg_match('/^\s*[=+\-@|%\t\r]/', $val)) {
            $val = "'" . $val;
        }
        return $val;
    }
}

if (!function_exists('is_valid_username_format')) {
    function is_valid_username_format($username) {
        if (!is_string($username)) {
            return false;
        }
        $username = trim($username);
        $len = strlen($username);
        if ($len < 3 || $len > 30) {
            return false;
        }
        return (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $username);
    }
}

if (!function_exists('is_username_reserved')) {
    function is_username_reserved($username) {
        static $reserved = [
            'admin', 'administrator', 'dashboard', 'login', 'logout', 'register',
            'signup', 'auth', 'api', 'assets', 'config', 'uploads', 'settings',
            'profile', 'search', 'support', 'about', 'contact', 'terms', 'privacy',
            'robots', 'robots.txt', 'sitemap', 'sitemap.xml', 'favicon', 'favicon.ico',
            'actions', 'pages', 'includes', 'export', 'links', 'notifications',
            'features', 'feature-suggestions', 'analytics', 'index', 'index.php', 'null'
        ];
        return in_array(strtolower(trim((string)$username)), $reserved, true);
    }
}

if (!function_exists('is_username_taken')) {
    function is_username_taken($username, $excludeUserId = 0, $pdo = null) {
        if (!$pdo) {
            global $pdo;
        }
        if (!$pdo) {
            return false;
        }
        $username = strtolower(trim((string)$username));
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $stmt->execute([$username, (int)$excludeUserId]);
        if ($stmt->fetch()) {
            return true;
        }
        try {
            $stmtA = $pdo->prepare('SELECT user_id FROM username_aliases WHERE old_username = ? AND user_id != ? LIMIT 1');
            $stmtA->execute([$username, (int)$excludeUserId]);
            if ($stmtA->fetch()) {
                return true;
            }
        } catch (Throwable $e) {}
        return false;
    }
}

if (!function_exists('generate_username')) {
    function generate_username($name, $pdo = null, $excludeUserId = 0) {
        if (!$pdo) {
            global $pdo;
        }
        $slug = strtolower(trim((string)$name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');
        $slug = preg_replace('/-+/', '-', $slug);
        if (strlen($slug) < 3) {
            $slug = 'user-' . ($slug !== '' ? $slug : bin2hex(random_bytes(2)));
            $slug = trim($slug, '-');
        }
        if (strlen($slug) > 24) {
            $slug = substr($slug, 0, 24);
            $slug = rtrim($slug, '-');
        }
        if (strlen($slug) < 3) {
            $slug = 'user';
        }
        $candidate = $slug;
        $i = 1;
        while (is_username_reserved($candidate) || is_username_taken($candidate, $excludeUserId, $pdo)) {
            $suffix = '-' . $i;
            $maxBaseLen = 30 - strlen($suffix);
            $basePart = substr($slug, 0, $maxBaseLen);
            $basePart = rtrim($basePart, '-');
            $candidate = $basePart . $suffix;
            $i++;
        }
        return $candidate;
    }
}

if (!function_exists('check_rate_limit')) {
    function check_rate_limit($action, $ip = null, $maxAttempts = 5, $decay = 900) {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $file = sys_get_temp_dir() . '/wander_rl_' . md5($action . '_' . $ip);
        if (!file_exists($file)) {
            return true;
        }
        $raw = @file_get_contents($file);
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data) || empty($data['time']) || !isset($data['count'])) {
            return true;
        }
        if (time() - (int)$data['time'] > $decay) {
            @unlink($file);
            return true;
        }
        return (int)$data['count'] < $maxAttempts;
    }
}

if (!function_exists('record_rate_limit_failure')) {
    function record_rate_limit_failure($action, $ip = null) {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $file = sys_get_temp_dir() . '/wander_rl_' . md5($action . '_' . $ip);
        $count = 1;
        $now = time();
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $data = $raw ? json_decode($raw, true) : null;
            if (is_array($data) && !empty($data['time']) && ($now - (int)$data['time'] < 900)) {
                $count = (int)($data['count'] ?? 0) + 1;
            }
        }
        @file_put_contents($file, json_encode(['count' => $count, 'time' => $now]), LOCK_EX);
    }
}

if (!function_exists('clear_rate_limit')) {
    function clear_rate_limit($action, $ip = null) {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $file = sys_get_temp_dir() . '/wander_rl_' . md5($action . '_' . $ip);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
