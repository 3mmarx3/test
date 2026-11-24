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
        $pdo->prepare("INSERT INTO users (name, username, handle, email, password, role, status) VALUES ('Mariam Ali', 'mariam', '@mariam', 'mariam@test.local', '$2y$10$vNl41r4zI1qFsmw9X9qf.u4yKjZzLqD8l9JbQhXgK/K4gq0ZkC.6e', 'user', 'active')")->execute();
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
        $pdo->prepare("INSERT INTO users (name, username, handle, email, password, role, status) VALUES ('Omar Qadry', 'omar-qadry', '@omar-qadry', 'omar@test.local', '$2y$10$vNl41r4zI1qFsmw9X9qf.u4yKjZzLqD8l9JbQhXgK/K4gq0ZkC.6e', 'user', 'active')")->execute();
        $oId = (int)$pdo->lastInsertId();
        if ($oId > 0) {
            $stmtL = $pdo->prepare("INSERT INTO links (user_id, name, url, sort_order, clicks) VALUES (?, ?, ?, ?, 0)");
            $stmtL->execute([$oId, 'YouTube Channel', 'https://youtube.com/@omarqadry', 1]);
            $stmtL->execute([$oId, 'Instagram Photos', 'https://instagram.com/omarqadry', 2]);
        }
    }
} catch (Throwable $migrationErr) {}
