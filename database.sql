-- Jalankan file ini di phpMyAdmin atau MySQL CLI agar database khusus penyimpanan siap.

CREATE DATABASE IF NOT EXISTS `anime_storage` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `anime_storage`;

CREATE TABLE IF NOT EXISTS `storage_anime` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `poster` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `genres` VARCHAR(255),
    `rating` DECIMAL(3,1),
    `episodes` INT,
    `year` INT,
    `status` VARCHAR(50),
    `download_360` TEXT,
    `download_720` TEXT,
    `download_1080` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `storage_admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `storage_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `anime_id` INT NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_storage_comments_anime` FOREIGN KEY (`anime_id`) REFERENCES `storage_anime`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS `idx_storage_comments_anime` ON `storage_comments` (`anime_id`, `created_at` DESC);

-- Contoh data awal (opsional, boleh dihapus)
INSERT INTO `storage_anime` (`title`, `poster`, `description`, `genres`, `rating`, `episodes`, `year`, `status`, `download_360`, `download_720`, `download_1080`) VALUES
('One Piece', 'img/one-piece.jpg', 'Petualangan bajak laut Topi Jerami mencari harta karun legendaris One Piece.', 'Adventure, Action, Fantasy', 8.7, 1000, 1999, 'Ongoing', 'https://mega.nz/fake-one-piece-360', 'https://mega.nz/fake-one-piece-720', 'https://mega.nz/fake-one-piece-1080'),
('Attack on Titan', 'img/attack-on-titan.jpg', 'Manusia bertahan hidup di balik tembok raksasa dari ancaman Titan.', 'Action, Dark Fantasy', 9.1, 87, 2013, 'Completed', NULL, 'https://mega.nz/fake-aot-720', 'https://mega.nz/fake-aot-1080'),
('Spy × Family', 'img/spy-x-family.jpg', 'Seorang mata-mata, pembunuh bayaran, dan espers berpura-pura menjadi keluarga harmonis.', 'Comedy, Slice of Life, Action', 8.5, 25, 2022, 'Ongoing', 'https://mega.nz/fake-spyfamily-360', 'https://mega.nz/fake-spyfamily-720', NULL);

-- Admin default (username: admin / password: admin123). Hapus jika tidak diperlukan.
INSERT INTO `storage_admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$OBYgA8cET5tpKcLoEmK5cevOjY3D43itawW.L3AUB51w927aRDQzO')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);
