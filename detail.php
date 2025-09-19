<?php
require __DIR__ . '/backend/db.php';

$pdo = get_storage_pdo();

$animeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($animeId <= 0) {
    http_response_code(404);
    echo 'Anime tidak ditemukan.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM storage_anime WHERE id = :id');
$stmt->execute([':id' => $animeId]);
$anime = $stmt->fetch();

if (!$anime) {
    http_response_code(404);
    echo 'Anime tidak ditemukan.';
    exit;
}

$errors = [];
$formValues = [
    'display_name' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_comment') {
    $rawName = trim($_POST['display_name'] ?? '');
    $rawMessage = trim($_POST['message'] ?? '');

    $sanitizedName = strip_tags($rawName);
    $sanitizedMessage = strip_tags($rawMessage);

    $sanitizedName = preg_replace('/\s{2,}/u', ' ', $sanitizedName);
    $sanitizedMessage = preg_replace("/\r\n|\r/", "\n", $sanitizedMessage);

    $formValues['display_name'] = $sanitizedName;
    $formValues['message'] = $sanitizedMessage;

    if ($formValues['display_name'] === '') {
        $errors[] = 'Nama wajib diisi.';
    } elseif (mb_strlen($formValues['display_name']) > 60) {
        $errors[] = 'Nama terlalu panjang (maksimal 60 karakter).';
    }

    if ($formValues['message'] === '') {
        $errors[] = 'Komentar tidak boleh kosong.';
    } elseif (mb_strlen($formValues['message']) > 1000) {
        $errors[] = 'Komentar terlalu panjang (maksimal 1000 karakter).';
    }

    if (!$errors) {
        $insert = $pdo->prepare('INSERT INTO storage_comments (anime_id, display_name, message) VALUES (:anime_id, :display_name, :message)');
        $insert->execute([
            ':anime_id' => $animeId,
            ':display_name' => $formValues['display_name'],
            ':message' => $formValues['message'],
        ]);

        header('Location: detail.php?id=' . $animeId . '#komentar');
        exit;
    }
}

$commentStmt = $pdo->prepare('SELECT display_name, message, created_at FROM storage_comments WHERE anime_id = :anime_id ORDER BY created_at DESC');
$commentStmt->execute([':anime_id' => $animeId]);
$comments = $commentStmt->fetchAll();

$descriptionText = $anime['description'] ?? '';
$descriptionText = preg_replace('/<br\s*\/?>(\s*)/i', "\n", $descriptionText);
$descriptionText = trim(strip_tags($descriptionText));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($anime['title']); ?> · Penyimpanan Anime</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-toggle.js" defer></script>
</head>
<body class="detail-page theme-dark">
    <header class="detail-header">
        <nav>
            <a class="brand" href="index.php">
                <span>Penyimpanan Anime</span>
            </a>
            <div class="detail-actions">
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Ganti tema">🌙</button>
                <a class="back-link" href="index.php">← Kembali ke Koleksi</a>
            </div>
        </nav>
    </header>

    <main class="detail-container">
        <section class="detail-hero">
            <div class="poster">
                <img src="<?php echo htmlspecialchars($anime['poster']); ?>" alt="Poster <?php echo htmlspecialchars($anime['title']); ?>">
            </div>
            <div class="info">
                <h1><?php echo htmlspecialchars($anime['title']); ?></h1>
                <?php if (!empty($anime['genres'])): ?>
                    <p class="genres"><?php echo htmlspecialchars($anime['genres']); ?></p>
                <?php endif; ?>
                <dl class="meta">
                    <?php if ($anime['status']): ?>
                        <div>
                            <dt>Status</dt>
                            <dd><?php echo htmlspecialchars($anime['status']); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($anime['episodes'] !== null && $anime['episodes'] !== ''): ?>
                        <div>
                            <dt>Episode</dt>
                            <dd><?php echo htmlspecialchars($anime['episodes']); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($anime['year'] !== null && $anime['year'] !== ''): ?>
                        <div>
                            <dt>Rilis</dt>
                            <dd><?php echo htmlspecialchars($anime['year']); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($anime['rating'] !== null && $anime['rating'] !== ''): ?>
                        <div>
                            <dt>Rating</dt>
                            <dd><?php echo htmlspecialchars($anime['rating']); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
                <?php if ($descriptionText !== ''): ?>
                    <p class="description"><?php echo nl2br(htmlspecialchars($descriptionText)); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section class="download-section" id="unduhan">
            <h2>Link Unduhan</h2>
            <p>Pilih kualitas video yang tersedia. Setiap tombol akan membuka penyimpanan eksternal sesuai data.</p>
            <div class="download-grid">
                <?php
                $downloadLinks = [
                    '360p' => $anime['download_360'] ?? '',
                    '720p' => $anime['download_720'] ?? '',
                    '1080p' => $anime['download_1080'] ?? '',
                ];
                $hasLink = false;
                foreach ($downloadLinks as $label => $url):
                    if (!empty($url)) {
                        $hasLink = true;
                        $host = parse_url($url, PHP_URL_HOST) ?: '';
                        $hostLabel = $host !== '' ? preg_replace('/^www\./i', '', $host) : 'Link Eksternal';
                        ?>
                        <a class="download-card" href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener">
                            <span class="quality"><?php echo $label; ?></span>
                            <span class="download-host"><?php echo htmlspecialchars(ucwords(str_replace('.', ' ', $hostLabel))); ?></span>
                            <span class="action">Download</span>
                        </a>
                        <?php
                    }
                endforeach;
                ?>
                <?php if (!$hasLink): ?>
                    <p class="empty">Belum ada link unduhan yang tersedia.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="comment-section" id="komentar">
            <div class="comment-header">
                <h2>Komentar Pengunjung</h2>
                <span><?php echo count($comments); ?> komentar</span>
            </div>

            <?php if ($errors): ?>
                <div class="comment-flash">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="comment-form">
                <input type="hidden" name="action" value="add_comment">
                <div class="field-group">
                    <label>Nama
                        <input type="text" name="display_name" value="<?php echo htmlspecialchars($formValues['display_name']); ?>" required>
                    </label>
                    <label>Komentar
                        <textarea name="message" rows="4" required placeholder="Bagikan kesanmu tentang anime ini..."><?php echo htmlspecialchars($formValues['message']); ?></textarea>
                    </label>
                </div>
                <button type="submit">Kirim Komentar</button>
            </form>

            <div class="comment-list">
                <?php if (!$comments): ?>
                    <p class="empty">Belum ada komentar. Jadilah yang pertama!</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <article class="comment-item">
                            <header>
                                <div class="comment-profile">
                                    <div class="comment-initial"><?php echo strtoupper(substr($comment['display_name'], 0, 1)); ?></div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($comment['display_name']); ?></strong>
                                        <div class="comment-meta">
                                            <time datetime="<?php echo htmlspecialchars($comment['created_at']); ?>"><?php echo htmlspecialchars($comment['created_at']); ?></time>
                                        </div>
                                    </div>
                                </div>
                            </header>
                            <p><?php echo nl2br(htmlspecialchars($comment['message'])); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
