<?php
require_once __DIR__ . '/../backend/admin_auth.php';
require_once __DIR__ . '/db.php';

if (!is_storage_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak berwenang']);
    exit;
}

$title = trim($_GET['q'] ?? '');
if ($title === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Masukkan judul anime']);
    exit;
}

$apiUrl = 'https://graphql.anilist.co';
$query = <<<'GRAPHQL'
query ($search: String) {
  Media(search: $search, type: ANIME) {
    title {
      romaji
      english
      native
    }
    description(asHtml: false)
    genres
    episodes
    status
    averageScore
    coverImage {
      extraLarge
      large
    }
    startDate {
      year
    }
  }
}
GRAPHQL;

$variables = ['search' => $title];

$payload = json_encode([
    'query' => $query,
    'variables' => $variables,
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 10,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke AniList: ' . $curlError]);
    exit;
}

$response = json_decode($responseBody, true);
if (!isset($response['data']['Media']) || $response['data']['Media'] === null) {
    $dataPath = __DIR__ . '/../data/metadata.json';
    if (!file_exists($dataPath)) {
        http_response_code($httpCode ?: 404);
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
        exit;
    }

    $fallbackData = json_decode(file_get_contents($dataPath), true) ?: [];
    $needle = mb_strtolower($title);
    foreach ($fallbackData as $item) {
        if (mb_strtolower($item['title']) === $needle) {
            echo json_encode(['success' => true, 'data' => $item, 'source' => 'local']);
            exit;
        }
    }

    http_response_code($httpCode ?: 404);
    echo json_encode(['success' => false, 'message' => 'Anime tidak ditemukan pada AniList maupun dataset lokal.']);
    exit;
}

$media = $response['data']['Media'];

$titleResult = $media['title']['romaji'] ?? $media['title']['english'] ?? $media['title']['native'] ?? $title;
$rating = $media['averageScore'] !== null ? number_format($media['averageScore'] / 10, 1) : null;

$deeplKey = getenv('DEEPL_API_KEY');
$description = $media['description'] ?? '';
if ($deeplKey && $description !== '') {
    $translated = deepl_translate($description, $deeplKey);
    if ($translated !== null) {
        $description = $translated;
    }
}

$result = [
    'title' => $titleResult,
    'description' => $description,
    'genres' => !empty($media['genres']) ? implode(', ', $media['genres']) : '',
    'rating' => $rating,
    'episodes' => $media['episodes'] ?? null,
    'year' => $media['startDate']['year'] ?? null,
    'status' => $media['status'] ?? '',
    'poster' => $media['coverImage']['extraLarge'] ?? $media['coverImage']['large'] ?? '',
];

echo json_encode(['success' => true, 'data' => $result]);

function deepl_translate(string $text, string $apiKey): ?string
{
    $endpoint = 'https://api-free.deepl.com/v2/translate';
    $cleanText = preg_replace('/<br\s*\/?>(\s*)/i', "\n", $text);
    $cleanText = strip_tags($cleanText);

    $postFields = http_build_query([
        'text' => $cleanText,
        'target_lang' => 'ID',
        'preserve_formatting' => 1,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: DeepL-Auth-Key ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return null;
    }

    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        return null;
    }

    $payload = json_decode($response, true);
    if (!isset($payload['translations'][0]['text'])) {
        return null;
    }

    return trim($payload['translations'][0]['text']);
}
