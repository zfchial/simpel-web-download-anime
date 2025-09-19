<?php
require_once __DIR__ . '/../backend/admin_auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

if (!is_storage_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak berwenang']);
    exit;
}

$commentId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($commentId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID komentar tidak valid']);
    exit;
}

$pdo = get_storage_pdo();

$delete = $pdo->prepare('DELETE FROM storage_comments WHERE id = :id');
$delete->execute([':id' => $commentId]);

if ($delete->rowCount() > 0) {
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Komentar tidak ditemukan']);
