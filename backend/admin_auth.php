<?php
// Session and authentication helpers for the storage admin panel.

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

const STORAGE_ADMIN_SESSION_KEY = 'storage_admin_id';

function is_storage_admin_logged_in(): bool
{
    return isset($_SESSION[STORAGE_ADMIN_SESSION_KEY]);
}

function storage_admin_login(string $username, string $password): bool
{
    $pdo = get_storage_pdo();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM storage_admins WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    $_SESSION[STORAGE_ADMIN_SESSION_KEY] = (int) $admin['id'];
    session_regenerate_id(true);

    return true;
}

function storage_admin_logout(): void
{
    unset($_SESSION[STORAGE_ADMIN_SESSION_KEY]);
    session_regenerate_id(true);
}

function require_storage_admin_login(string $redirectPath = '/storage/admin.php'): void
{
    if (!is_storage_admin_logged_in()) {
        $target = sanitize_storage_redirect($redirectPath);
        header('Location: admin-login.php?redirect=' . urlencode($target));
        exit;
    }
}

function get_storage_admin(): ?array
{
    if (!is_storage_admin_logged_in()) {
        return null;
    }

    static $cachedAdmin = null;

    if ($cachedAdmin !== null) {
        return $cachedAdmin;
    }

    $pdo = get_storage_pdo();
    $stmt = $pdo->prepare('SELECT id, username, created_at FROM storage_admins WHERE id = :id');
    $stmt->execute([':id' => $_SESSION[STORAGE_ADMIN_SESSION_KEY]]);
    $cachedAdmin = $stmt->fetch() ?: null;

    return $cachedAdmin;
}

function sanitize_storage_redirect(?string $target): string
{
    if (!$target) {
        return 'admin.php';
    }

    $target = trim($target);
    if ($target === '') {
        return 'admin.php';
    }

    $parsed = parse_url($target);
    if ($parsed === false) {
        return 'admin.php';
    }

    if (isset($parsed['scheme']) || isset($parsed['host'])) {
        return 'admin.php';
    }

    $path = $parsed['path'] ?? '';
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

    if ($path === '') {
        return 'admin.php';
    }

    if (strpos($path, '..') !== false) {
        return 'admin.php';
    }

    $normalized = ltrim($path, '/');

    return $normalized . $query;
}
