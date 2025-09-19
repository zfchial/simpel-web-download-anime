<?php
require_once __DIR__ . '/../backend/admin_auth.php';

if (!is_storage_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak berwenang']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$response = ['success' => false, 'message' => 'Data tidak ditemukan'];

if ($q === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Masukkan judul anime']);
    exit;
}

$dataPath = __DIR__ . '/../data/metadata.json';
if (!file_exists($dataPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Metadata stub tidak tersedia']);
    exit;
}

$data = json_decode(file_get_contents($dataPath), true);
$needle = mb_strtolower($q);

foreach ($data as $item) {
    if (mb_strtolower($item['title']) === $needle) {
        $response = ['success' => true, 'data' => $item];
        break;
    }
}

echo json_encode($response);
