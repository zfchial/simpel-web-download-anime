<?php
require_once __DIR__ . '/backend/admin_auth.php';

$currentPath = $_SERVER['REQUEST_URI'] ?? '/storage/admin.php';
require_storage_admin_login($currentPath);

require_once __DIR__ . '/backend/db.php';

$pdo = get_storage_pdo();
$currentAdmin = get_storage_admin();

$stats = [
    'anime' => (int) $pdo->query('SELECT COUNT(*) FROM storage_anime')->fetchColumn(),
    'comments' => (int) $pdo->query('SELECT COUNT(*) FROM storage_comments')->fetchColumn(),
    'avg_rating' => $pdo->query('SELECT AVG(rating) FROM storage_anime WHERE rating IS NOT NULL')->fetchColumn(),
    'download_links' => (int) $pdo->query('SELECT SUM(download_360 IS NOT NULL AND download_360 <> "") + SUM(download_720 IS NOT NULL AND download_720 <> "") + SUM(download_1080 IS NOT NULL AND download_1080 <> "") FROM storage_anime')->fetchColumn(),
];
$stats['avg_rating'] = $stats['avg_rating'] !== null ? number_format((float) $stats['avg_rating'], 2) : '0.00';

$errors = [];
$flashMessage = null;
$flashType = null;
$editingAnime = null;

function collect_anime_payload(): array
{
    return [
        'title' => trim($_POST['title'] ?? ''),
        'poster' => trim($_POST['poster'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'genres' => trim($_POST['genres'] ?? ''),
        'rating' => $_POST['rating'] === '' ? null : (float) $_POST['rating'],
        'episodes' => $_POST['episodes'] === '' ? null : (int) $_POST['episodes'],
        'year' => $_POST['year'] === '' ? null : (int) $_POST['year'],
        'status' => trim($_POST['status'] ?? ''),
        'download_360' => trim($_POST['download_360'] ?? ''),
        'download_720' => trim($_POST['download_720'] ?? ''),
        'download_1080' => trim($_POST['download_1080'] ?? ''),
    ];
}

function validate_anime_payload(array $data): array
{
    $errors = [];

    if ($data['title'] === '') {
        $errors[] = 'Judul wajib diisi.';
    }

    if ($data['poster'] === '') {
        $errors[] = 'URL poster wajib diisi (boleh dari folder img atau link eksternal).';
    }

    if ($data['rating'] !== null && ($data['rating'] < 0 || $data['rating'] > 10)) {
        $errors[] = 'Rating harus di antara 0 sampai 10.';
    }

    if ($data['episodes'] !== null && $data['episodes'] < 0) {
        $errors[] = 'Jumlah episode tidak boleh negatif.';
    }

    if ($data['year'] !== null && ($data['year'] < 1960 || $data['year'] > (int) date('Y') + 1)) {
        $errors[] = 'Tahun rilis tidak valid.';
    }

    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $payload = collect_anime_payload();
        $errors = validate_anime_payload($payload);

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO storage_anime (title, poster, description, genres, rating, episodes, year, status, download_360, download_720, download_1080) VALUES (:title, :poster, :description, :genres, :rating, :episodes, :year, :status, :download_360, :download_720, :download_1080)');
            $stmt->execute([
                ':title' => $payload['title'],
                ':poster' => $payload['poster'],
                ':description' => $payload['description'],
                ':genres' => $payload['genres'],
                ':rating' => $payload['rating'],
                ':episodes' => $payload['episodes'],
                ':year' => $payload['year'],
                ':status' => $payload['status'] ?: null,
                ':download_360' => $payload['download_360'] ?: null,
                ':download_720' => $payload['download_720'] ?: null,
                ':download_1080' => $payload['download_1080'] ?: null,
            ]);

            header('Location: admin.php?flash=created');
            exit;
        }
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $payload = collect_anime_payload();
        $errors = validate_anime_payload($payload);

        if (!$errors) {
            $stmt = $pdo->prepare('UPDATE storage_anime SET title = :title, poster = :poster, description = :description, genres = :genres, rating = :rating, episodes = :episodes, year = :year, status = :status, download_360 = :download_360, download_720 = :download_720, download_1080 = :download_1080 WHERE id = :id');
            $stmt->execute([
                ':title' => $payload['title'],
                ':poster' => $payload['poster'],
                ':description' => $payload['description'],
                ':genres' => $payload['genres'],
                ':rating' => $payload['rating'],
                ':episodes' => $payload['episodes'],
                ':year' => $payload['year'],
                ':status' => $payload['status'] ?: null,
                ':download_360' => $payload['download_360'] ?: null,
                ':download_720' => $payload['download_720'] ?: null,
                ':download_1080' => $payload['download_1080'] ?: null,
                ':id' => $id,
            ]);

            header('Location: admin.php?flash=updated');
            exit;
        } else {
            $editingAnime = array_merge(['id' => $id], $payload);
        }
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $stmt = $pdo->prepare('DELETE FROM storage_anime WHERE id = :id');
        $stmt->execute([':id' => $id]);

        header('Location: admin.php?flash=deleted');
        exit;
    }
}

if (isset($_GET['edit_id'])) {
    $id = (int) $_GET['edit_id'];
    $stmt = $pdo->prepare('SELECT * FROM storage_anime WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editingAnime = $stmt->fetch();

    if (!$editingAnime) {
        header('Location: admin.php?flash=not_found');
        exit;
    }
}

if (isset($_GET['flash'])) {
    $map = [
        'created' => ['Data anime berhasil ditambahkan.', 'success'],
        'updated' => ['Data anime berhasil diperbarui.', 'success'],
        'deleted' => ['Data anime berhasil dihapus.', 'success'],
        'not_found' => ['Data anime tidak ditemukan.', 'error'],
    ];
    if (isset($map[$_GET['flash']])) {
        [$flashMessage, $flashType] = $map[$_GET['flash']];
    }
}

$stmt = $pdo->query('SELECT * FROM storage_anime ORDER BY created_at DESC');
$animeList = $stmt->fetchAll();

$defaultFormValues = [
    'title' => '',
    'poster' => '',
    'description' => '',
    'genres' => '',
    'rating' => '',
    'episodes' => '',
    'year' => '',
    'status' => '',
    'download_360' => '',
    'download_720' => '',
    'download_1080' => '',
];

$formValues = $editingAnime ? array_intersect_key($editingAnime, $defaultFormValues) : $defaultFormValues;

?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Penyimpanan Anime</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="js/admin-comments.js" defer></script>
    <script src="js/admin-metadata.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="admin-topbar">
            <span>Masuk sebagai <strong><?php echo htmlspecialchars($currentAdmin['username'] ?? 'Admin'); ?></strong></span>
            <a class="button secondary" href="admin-logout.php">Keluar</a>
        </div>
        <header class="admin-header">
            <h1>Panel Admin Penyimpanan Anime</h1>
            <p>Kelola koleksi anime yang akan tampil di halaman pengguna.</p>
        </header>

        <section class="card stats-card-wrapper">
            <div class="stats-grid">
                <article class="stats-card">
                    <span>Total Anime</span>
                    <strong><?php echo $stats['anime']; ?></strong>
                </article>
                <article class="stats-card">
                    <span>Total Komentar</span>
                    <strong><?php echo $stats['comments']; ?></strong>
                </article>
                <article class="stats-card">
                    <span>Rata-rata Rating</span>
                    <strong><?php echo $stats['avg_rating']; ?></strong>
                </article>
                <article class="stats-card">
                    <span>Total Link Unduhan</span>
                    <strong><?php echo $stats['download_links']; ?></strong>
                </article>
            </div>
        </section>

        <?php if ($flashMessage): ?>
            <div class="flash flash-<?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flashMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="flash flash-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">
                <h2><?php echo $editingAnime ? 'Edit Anime' : 'Tambah Anime Baru'; ?></h2>
                <?php if ($editingAnime): ?>
                    <a class="button secondary" href="admin.php">Batal Edit</a>
                <?php endif; ?>
            </div>
            <form method="post" class="anime-form">
                <input type="hidden" name="action" value="<?php echo $editingAnime ? 'update' : 'create'; ?>">
                <?php if ($editingAnime): ?>
                    <input type="hidden" name="id" value="<?php echo (int) $editingAnime['id']; ?>">
                <?php endif; ?>

                <div class="metadata-helper">
                    <input type="text" placeholder="Contoh: One Piece" data-metadata-title>
                    <button type="button" class="button secondary" data-fetch-metadata>Isi Otomatis</button>
                    <span class="form-hint">Demo metadata menggunakan dataset lokal.</span>
                </div>

                <div class="form-grid">
                    <label>Judul
                        <input type="text" name="title" value="<?php echo htmlspecialchars($formValues['title']); ?>" required>
                    </label>
                    <label>URL Poster (jpg/png)
                        <input type="text" name="poster" value="<?php echo htmlspecialchars($formValues['poster']); ?>" placeholder="contoh: img/one-piece.jpg" required>
                    </label>
                    <label>Genre (pisahkan dengan koma)
                        <input type="text" name="genres" value="<?php echo htmlspecialchars($formValues['genres']); ?>" placeholder="Action, Adventure">
                    </label>
                    <label>Rating (0 - 10)
                        <input type="number" step="0.1" min="0" max="10" name="rating" value="<?php echo htmlspecialchars($formValues['rating']); ?>">
                    </label>
                    <label>Jumlah Episode
                        <input type="number" min="0" name="episodes" value="<?php echo htmlspecialchars($formValues['episodes']); ?>">
                    </label>
                    <label>Tahun Rilis
                        <input type="number" min="1960" max="<?php echo (int) date('Y') + 1; ?>" name="year" value="<?php echo htmlspecialchars($formValues['year']); ?>">
                    </label>
                    <label>Status
                        <input type="text" name="status" value="<?php echo htmlspecialchars($formValues['status']); ?>" placeholder="Ongoing / Completed">
                    </label>
                    <label>Link Unduhan 360p (opsional)
                        <input type="url" name="download_360" value="<?php echo htmlspecialchars($formValues['download_360']); ?>" placeholder="https://mega.nz/...">
                    </label>
                    <label>Link Unduhan 720p (opsional)
                        <input type="url" name="download_720" value="<?php echo htmlspecialchars($formValues['download_720']); ?>" placeholder="https://mega.nz/...">
                    </label>
                    <label>Link Unduhan 1080p (opsional)
                        <input type="url" name="download_1080" value="<?php echo htmlspecialchars($formValues['download_1080']); ?>" placeholder="https://mega.nz/...">
                    </label>
                </div>

                <label>Deskripsi (opsional)
                    <textarea name="description" rows="4" placeholder="Ringkasan singkat anime..."><?php echo htmlspecialchars($formValues['description']); ?></textarea>
                </label>
                <p class="form-hint">Gunakan link dari Mega atau penyimpanan lain. Kosongkan jika kualitas tertentu belum tersedia.</p>

                <button type="submit" class="button primary"><?php echo $editingAnime ? 'Simpan Perubahan' : 'Tambahkan Anime'; ?></button>
            </form>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Daftar Anime</h2>
                <p>Total: <?php echo count($animeList); ?> judul</p>
            </div>

            <?php if (!$animeList): ?>
                <p>Belum ada data anime. Tambahkan menggunakan form di atas.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Poster</th>
                                <th>Judul</th>
                                <th>Genre</th>
                                <th>Rating</th>
                                <th>Episode</th>
                                <th>Tahun</th>
                                <th>Status</th>
                                <th>Link</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($animeList as $anime): ?>
                                <tr>
                                    <td data-label="Poster"><img src="<?php echo htmlspecialchars($anime['poster']); ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>" class="table-poster"></td>
                                    <td data-label="Judul">
                                        <strong><?php echo htmlspecialchars($anime['title']); ?></strong>
                                        <div class="text-muted">Ditambahkan: <?php echo htmlspecialchars($anime['created_at']); ?></div>
                                    </td>
                                    <td data-label="Genre"><?php echo htmlspecialchars($anime['genres']); ?></td>
                                    <td data-label="Rating"><?php echo htmlspecialchars($anime['rating'] ?? '-'); ?></td>
                                    <td data-label="Episode"><?php echo htmlspecialchars($anime['episodes'] ?? '-'); ?></td>
                                    <td data-label="Tahun"><?php echo htmlspecialchars($anime['year'] ?? '-'); ?></td>
                                    <td data-label="Status"><?php echo htmlspecialchars($anime['status'] ?? '-'); ?></td>
                                    <td class="download-links" data-label="Link">
                                        <?php if (!empty($anime['download_360'])): ?>
                                            <a href="<?php echo htmlspecialchars($anime['download_360']); ?>" target="_blank" rel="noopener">360p</a>
                                        <?php endif; ?>
                                        <?php if (!empty($anime['download_720'])): ?>
                                            <a href="<?php echo htmlspecialchars($anime['download_720']); ?>" target="_blank" rel="noopener">720p</a>
                                        <?php endif; ?>
                                        <?php if (!empty($anime['download_1080'])): ?>
                                            <a href="<?php echo htmlspecialchars($anime['download_1080']); ?>" target="_blank" rel="noopener">1080p</a>
                                        <?php endif; ?>
                                        <?php if (empty($anime['download_360']) && empty($anime['download_720']) && empty($anime['download_1080'])): ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions" data-label="Aksi">
                                        <a class="button secondary" href="admin.php?edit_id=<?php echo (int) $anime['id']; ?>">Edit</a>
                                        <form method="post" onsubmit="return confirm('Hapus anime ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $anime['id']; ?>">
                                            <button type="submit" class="button danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Komentar Pengunjung</h2>
            </div>
            <?php
            $comments = $pdo->query('SELECT c.id, c.display_name, c.message, c.created_at, a.title FROM storage_comments c JOIN storage_anime a ON a.id = c.anime_id ORDER BY c.created_at DESC')->fetchAll();
            ?>
            <?php if (!$comments): ?>
                <p>Belum ada komentar yang masuk.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table data-comments-table>
                        <thead>
                            <tr>
                                <th>Pengunjung</th>
                                <th>Komentar</th>
                                <th>Anime</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td data-label="Pengunjung">
                                        <strong><?php echo htmlspecialchars($comment['display_name']); ?></strong>
                                    </td>
                                    <td data-label="Komentar"><?php echo nl2br(htmlspecialchars($comment['message'])); ?></td>
                                    <td data-label="Anime"><?php echo htmlspecialchars($comment['title']); ?></td>
                                    <td data-label="Tanggal"><?php echo htmlspecialchars($comment['created_at']); ?></td>
                                    <td data-label="Aksi">
                                        <button type="button" class="button danger" data-delete-comment data-id="<?php echo (int) $comment['id']; ?>">Hapus</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
