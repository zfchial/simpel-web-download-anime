<?php
// PDO-based database bootstrap for the anime storage module.
// Adjust credentials to match your local MySQL configuration.

define('STORAGE_DB_HOST', 'localhost');
define('STORAGE_DB_USER', 'root');
define('STORAGE_DB_PASS', '');
define('STORAGE_DB_NAME', 'anime_storage');

function get_storage_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . STORAGE_DB_HOST;
    $pdo = new PDO($dsn, STORAGE_DB_USER, STORAGE_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . STORAGE_DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . STORAGE_DB_NAME . '`');

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS storage_anime (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            poster VARCHAR(255) NOT NULL,
            description TEXT,
            genres VARCHAR(255),
            rating DECIMAL(3,1),
            episodes INT,
            year INT,
            status VARCHAR(50),
            download_360 TEXT,
            download_720 TEXT,
            download_1080 TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    // Ensure newly added columns exist when upgrading from older schema versions.
    $pdo->exec('ALTER TABLE storage_anime ADD COLUMN IF NOT EXISTS download_360 TEXT AFTER status');
    $pdo->exec('ALTER TABLE storage_anime ADD COLUMN IF NOT EXISTS download_720 TEXT AFTER download_360');
    $pdo->exec('ALTER TABLE storage_anime ADD COLUMN IF NOT EXISTS download_1080 TEXT AFTER download_720');

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS storage_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS storage_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anime_id INT NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_storage_comments_anime FOREIGN KEY (anime_id) REFERENCES storage_anime(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_storage_comments_anime ON storage_comments (anime_id, created_at DESC)');

    return $pdo;
}
