<?php
require __DIR__ . '/backend/db.php';

$pdo = get_storage_pdo();

$searchQuery = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$heroSlides = $pdo->query('SELECT * FROM storage_anime ORDER BY RAND() LIMIT 5')->fetchAll();

$whereSql = '';
if ($searchQuery !== '') {
    $whereSql = ' WHERE title LIKE :searchTitle OR genres LIKE :searchGenres OR description LIKE :searchDescription';
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM storage_anime' . $whereSql);
if ($searchQuery !== '') {
    $searchWildcard = '%' . $searchQuery . '%';
    $countStmt->bindValue(':searchTitle', $searchWildcard, PDO::PARAM_STR);
    $countStmt->bindValue(':searchGenres', $searchWildcard, PDO::PARAM_STR);
    $countStmt->bindValue(':searchDescription', $searchWildcard, PDO::PARAM_STR);
}
$countStmt->execute();
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare('SELECT * FROM storage_anime' . $whereSql . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
if ($searchQuery !== '') {
    $searchWildcard = '%' . $searchQuery . '%';
    $listStmt->bindValue(':searchTitle', $searchWildcard, PDO::PARAM_STR);
    $listStmt->bindValue(':searchGenres', $searchWildcard, PDO::PARAM_STR);
    $listStmt->bindValue(':searchDescription', $searchWildcard, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$animeList = $listStmt->fetchAll();

function build_page_url(int $page, string $searchQuery): string
{
    $params = [];
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    $query = http_build_query($params);
    return 'index.php' . ($query ? '?' . $query : '');
}

function normalize_description(?string $text): string
{
    if ($text === null) {
        return '';
    }
    $text = preg_replace('/<br\s*\/?>(\s*)/i', "\n", $text);
    $text = strip_tags($text);
    return trim($text);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyimpanan Anime</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-toggle.js" defer></script>
    <script src="js/hero-slider.js" defer></script>
</head>
<body class="theme-dark">
    <header>
        <nav data-nav>
            <a class="brand" href="#beranda">
                <span>Penyimpanan Anime</span>
            </a>
            <button type="button" class="menu-toggle" data-menu-toggle aria-label="Buka menu" aria-expanded="false">☰</button>
            <div class="nav-links" data-nav-links>
                <a class="active" href="#beranda">Beranda</a>
                <a href="#koleksi">Koleksi</a>
                <a href="#genre">Genre</a>
                <a href="#kontak">Kontak</a>
            </div>
            <div class="nav-controls">
                <form class="search" method="get" action="index.php">
                    <input id="search-anime" name="q" type="search" placeholder="Cari judul atau genre..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <input type="hidden" name="page" value="1">
                    <button type="submit">Cari</button>
                </form>
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Ganti tema">🌙</button>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero" id="beranda" data-hero>
            <?php if ($heroSlides): ?>
                <div class="hero-slider" data-hero-slider>
                    <?php foreach ($heroSlides as $slide): ?>
                        <article class="hero-slide">
                            <div class="hero-media">
                                <img src="<?php echo htmlspecialchars($slide['poster']); ?>" alt="Poster <?php echo htmlspecialchars($slide['title']); ?>">
                            </div>
                            <div class="hero-content">
                                <span class="hero-tag">Rekomendasi</span>
                                <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                                <?php if (!empty($slide['genres'])): ?>
                                    <p class="hero-genres"><?php echo htmlspecialchars($slide['genres']); ?></p>
                                <?php endif; ?>
                                <?php
                                $heroDescription = normalize_description($slide['description'] ?? '');
                                if ($heroDescription !== ''): ?>
                                    <p class="hero-description"><?php echo htmlspecialchars(mb_strimwidth($heroDescription, 0, 220, '...')); ?></p>
                                <?php endif; ?>
                                <div class="hero-actions">
                                    <a class="primary" href="detail.php?id=<?php echo (int) $slide['id']; ?>">Lihat Detail</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="hero-indicators" data-hero-indicators>
                    <?php foreach ($heroSlides as $index => $slide): ?>
                        <button type="button" data-hero-dot aria-label="Slide <?php echo $index + 1; ?>" data-index="<?php echo $index; ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="hero-empty">
                    <h1>Mulai Bangun Koleksi Anime</h1>
                    <p>Tambahkan judul baru dari panel admin untuk menampilkan rekomendasi di halaman depan.</p>
                    <a class="primary" href="admin.php" target="_blank" rel="noopener">Buka Panel Admin</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="value-section" id="fitur">
            <div class="section-header">
                <h2>Kenapa Penyimpanan Anime?</h2>
                <p>Kami membantu kamu mengarsipkan tontonan, menyusun daftar unduhan, serta menyiapkan catatan singkat untuk setiap judul.</p>
            </div>
            <div class="value-grid">
                <article class="value-card">
                    <h3>Centralized Library</h3>
                    <p>Satu dashboard untuk seluruh koleksi anime kamu. Cari berdasarkan judul, genre, atau status hanya dalam hitungan detik.</p>
                </article>
                <article class="value-card">
                    <h3>Download Link Tracker</h3>
                    <p>Simpan tautan Mega 360p, 720p, hingga 1080p supaya tidak tercecer dan mudah dibagikan kapan pun.</p>
                </article>
                <article class="value-card">
                    <h3>Catatan & Komentar</h3>
                    <p>Tambahkan deskripsi singkat dan biarkan pengunjung meninggalkan komentar untuk mencatat progres atau memberi ulasan.</p>
                </article>
            </div>
        </section>

        <section class="genre-section" id="genre">
            <div class="section-header">
                <h2>Genre Populer</h2>
                <p>Telusuri kategori favorit untuk mempercepat pencarian anda.</p>
            </div>
            <div class="genre-tags">
                <?php
                $defaultGenres = ['Action', 'Adventure', 'Comedy', 'Drama', 'Fantasy', 'Horror', 'Romance', 'Sci-Fi', 'Slice of Life', 'Sports' ,'Supernatural', 'Thriller' ,'Mecha', 'Mystery', 'Music', 'Psychological', 'Historical', 'Military', 'Dementia', 'Cars' ,'Ecchi', 'Harem', 'Josei', 'Kids', 'Magic', 'Martial Arts', 'Parody', 'Samurai', 'School', 'Shoujo', 'Shounen', 'Space', 'Vampire', 'Yaoi', 'Yuri'];
                foreach ($defaultGenres as $genre): ?>
                    <a href="<?php echo htmlspecialchars(build_page_url(1, $genre)); ?>#koleksi" class="genre-tag"><?php echo htmlspecialchars($genre); ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card-section" id="koleksi">
            <div class="section-header">
                <h2>Koleksi Anime</h2>
                <p><?php echo $totalItems; ?> judul ditemukan<?php echo $searchQuery !== '' ? ' untuk "' . htmlspecialchars($searchQuery) . '"' : ''; ?>.</p>
            </div>

            <?php if (!$animeList): ?>
                <div class="empty-state">
                    <h3>Belum ada anime yang cocok</h3>
                    <p><?php echo $searchQuery === '' ? 'Tambahkan anime dari panel admin untuk mulai mengisi koleksi.' : 'Coba gunakan kata kunci lain atau periksa ejaan.'; ?></p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($animeList as $anime): ?>
                        <article class="anime-card">
                            <figure class="card-media">
                                <img src="<?php echo htmlspecialchars($anime['poster']); ?>" alt="Poster <?php echo htmlspecialchars($anime['title']); ?>">
                                <?php if ($anime['rating'] !== null && $anime['rating'] !== ''): ?>
                                    <span class="card-badge">Rating <?php echo htmlspecialchars($anime['rating']); ?></span>
                                <?php endif; ?>
                            </figure>
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($anime['title']); ?></h3>
                                <?php if (!empty($anime['genres'])): ?>
                                    <p class="genres"><?php echo htmlspecialchars($anime['genres']); ?></p>
                                <?php endif; ?>
                                <?php
                                $cardDescription = normalize_description($anime['description'] ?? '');
                                if ($cardDescription !== ''): ?>
                                    <p class="description"><?php echo htmlspecialchars($cardDescription); ?></p>
                                <?php endif; ?>
                                <dl class="meta">
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
                                    <?php if (!empty($anime['status'])): ?>
                                        <div>
                                            <dt>Status</dt>
                                            <dd><?php echo htmlspecialchars($anime['status']); ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>
                                <a class="card-action" href="detail.php?id=<?php echo (int) $anime['id']; ?>">Lihat Detail</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination">
                        <?php if ($page > 1): ?>
                            <a class="page-btn" href="<?php echo htmlspecialchars(build_page_url($page - 1, $searchQuery)); ?>#koleksi">Sebelumnya</a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a class="page-btn<?php echo $i === $page ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars(build_page_url($i, $searchQuery)); ?>#koleksi"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a class="page-btn" href="<?php echo htmlspecialchars(build_page_url($page + 1, $searchQuery)); ?>#koleksi">Berikutnya</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>



        <section class="contact-section" id="kontak">
            <div class="section-header">
                <h2>Butuh Bantuan?</h2>
                <p>Hubungi admin jika ada kendala pada link unduhan atau ingin menambahkan katalog baru.</p>
            </div>
            <div class="contact-card">
                <div class="contact-item">
                    <span class="contact-label">Email</span>
                    <a href="mailto:admin@nimestorage.local">admin@nimestorage.local</a>
                </div>
                <div class="contact-item">
                    <span class="contact-label">Discord</span>
                    <a href="https://discord.gg/animestore" target="_blank" rel="noopener">discord.gg/animestore</a>
                </div>
                <div class="contact-item">
                    <span class="contact-label">Telegram</span>
                    <a href="https://t.me/anime_storage" target="_blank" rel="noopener">@anime_storage</a>
                </div>
                <p class="contact-note">Balasan biasanya kurang dari 24 jam di hari kerja.</p>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>© <?php echo date('Y'); ?> Penyimpanan Anime</span>
            <span>Didesain dengan ❤️ oleh Admin</span>
        </div>
    </footer>
    <?php if ($searchQuery !== '' || $page > 1): ?>
    <script>
        window.addEventListener('load', function () {
            var target = document.getElementById('koleksi');
            if (target) {
                target.scrollIntoView({behavior: 'smooth'});
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
