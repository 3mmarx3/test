<?php
require_once __DIR__ . '/config.php';

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        $pdoOptions
    );
} catch (PDOException $e) {
    try {
        $pdo = new PDO(
            "mysql:host=127.0.0.1;port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            $pdoOptions
        );
    } catch (PDOException $e2) {
        http_response_code(500);
        exit('Database connection failed. Please check database configuration.');
    }
}

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'username_aliases'")->fetch();
    if (!$tableCheck) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS username_aliases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            old_username VARCHAR(60) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_alias_user (user_id),
            CONSTRAINT fk_alias_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    $stmtAmmar = $pdo->prepare("SELECT id, username FROM users WHERE id = 1 LIMIT 1");
    $stmtAmmar->execute();
    $u1 = $stmtAmmar->fetch();
    if ($u1 && $u1['username'] === 'ammar') {
        $pdo->exec("INSERT IGNORE INTO username_aliases (user_id, old_username) VALUES (1, 'ammar')");
        $pdo->exec("UPDATE users SET username = 'ammar-ahmed', handle = '@ammar-ahmed' WHERE id = 1");
    }

    $stmtMariam = $pdo->prepare("SELECT id FROM users WHERE username = 'mariam' LIMIT 1");
    $stmtMariam->execute();
    $mRow = $stmtMariam->fetch();
    if (!$mRow) {
        $pdo->prepare('INSERT INTO users (name, username, handle, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute(['Mariam Ali', 'mariam', '@mariam', 'mariam@test.local', '$2y$10$vNl41r4zI1qFsmw9X9qf.u4yKjZzLqD8l9JbQhXgK/K4gq0ZkC.6e', 'user', 'active']);
        $mId = (int)$pdo->lastInsertId();
        if ($mId > 0) {
            $stmtL = $pdo->prepare("INSERT INTO links (user_id, name, url, sort_order, clicks) VALUES (?, ?, ?, ?, 0)");
            $stmtL->execute([$mId, 'Behance Portfolio', 'https://behance.net/mariam', 1]);
            $stmtL->execute([$mId, 'LinkedIn', 'https://linkedin.com/in/mariam', 2]);
        }
    }

    $stmtOmar = $pdo->prepare("SELECT id FROM users WHERE username = 'omar-qadry' LIMIT 1");
    $stmtOmar->execute();
    $oRow = $stmtOmar->fetch();
    if (!$oRow) {
        $pdo->prepare('INSERT INTO users (name, username, handle, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute(['Omar Qadry', 'omar-qadry', '@omar-qadry', 'omar@test.local', '$2y$10$vNl41r4zI1qFsmw9X9qf.u4yKjZzLqD8l9JbQhXgK/K4gq0ZkC.6e', 'user', 'active']);
        $oId = (int)$pdo->lastInsertId();
        if ($oId > 0) {
            $stmtL = $pdo->prepare("INSERT INTO links (user_id, name, url, sort_order, clicks) VALUES (?, ?, ?, ?, 0)");
            $stmtL->execute([$oId, 'YouTube Channel', 'https://youtube.com/@omarqadry', 1]);
            $stmtL->execute([$oId, 'Instagram Photos', 'https://instagram.com/omarqadry', 2]);
        }
    }

    $stmtMedo = $pdo->prepare("SELECT id FROM users WHERE username = 'medo123' LIMIT 1");
    $stmtMedo->execute();
    $medoRow = $stmtMedo->fetch();
    if (!$medoRow) {
        $pdo->prepare('INSERT INTO users (name, username, handle, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute(['mohamed abdlfatah khedr', 'medo123', '@medo123', 'medo123@wander.local', '$2y$10$vNl41r4zI1qFsmw9X9qf.u4yKjZzLqD8l9JbQhXgK/K4gq0ZkC.6e', 'user', 'active']);
        $medoId = (int)$pdo->lastInsertId();
        if ($medoId > 0) {
            $stmtL = $pdo->prepare("INSERT INTO links (user_id, name, url, sort_order, clicks) VALUES (?, ?, ?, ?, 0)");
            $stmtL->execute([$medoId, 'Personal Portfolio', 'https://github.com/medo123', 1]);
        }
    }

    $htFile = dirname(__DIR__, 2) . '/.htaccess';
    $htExpected = "<IfModule mod_rewrite.c>\nRewriteEngine On\n\nRewriteRule ^robots\\.txt$ robots.txt [L]\nRewriteRule ^sitemap\\.xml$ sitemap.php [L]\n\nRewriteRule ^(\\.git|config|backups) - [F,L]\nRewriteRule (^|/)(\\.env|\\.git|\\.htaccess|.*\\.sql|.*\\.log|.*\\.bak|.*\\.md)$ - [F,L]\n\nRewriteRule ^index\\.php$ - [L]\n\nRewriteCond %{REQUEST_FILENAME} -f [OR]\nRewriteCond %{REQUEST_FILENAME} -d\nRewriteRule ^ - [L]\n\nRewriteRule ^login/?$ admin/auth/login.php [QSA,L]\nRewriteRule ^register/?$ admin/auth/signup.php [QSA,L]\nRewriteRule ^signup/?$ admin/auth/signup.php [QSA,L]\nRewriteRule ^logout/?$ admin/auth/logout.php [QSA,L]\nRewriteRule ^dashboard/?$ dashboard.php [QSA,L]\nRewriteRule ^auth/?$ admin/auth/login.php [QSA,L]\n\nRewriteRule ^u/([a-zA-Z0-9-]+)/?$ index.php?username=$1 [QSA,L]\nRewriteRule ^([a-zA-Z0-9-]+)/?$ index.php?username=$1 [QSA,L]\n</IfModule>\n\n<FilesMatch \"^\\.|\\.(sql|env|log|bak|conf|ini|sh|md)$\">\n    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n    <IfModule !mod_authz_core.c>\n        Order allow,deny\n        Deny from all\n    </IfModule>\n</FilesMatch>\n\n<IfModule mod_headers.c>\n    Header set X-Content-Type-Options \"nosniff\"\n    Header set X-Frame-Options \"SAMEORIGIN\"\n    Header set X-XSS-Protection \"1; mode=block\"\n    Header set Referrer-Policy \"strict-origin-when-cross-origin\"\n</IfModule>\n\nOptions -Indexes\nErrorDocument 404 /index.php\n";
    if (!file_exists($htFile) || @file_get_contents($htFile) !== $htExpected) {
        @file_put_contents($htFile, $htExpected);
    }
} catch (Throwable $migrationErr) {}
