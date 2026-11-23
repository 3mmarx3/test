<?php
require_once __DIR__ . '/config.php';

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
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
