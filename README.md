# Penyimpanan Anime

Aplikasi web PHP/MySQL untuk menyimpan dan mengelola koleksi anime pribadi. Proyek ini menyediakan halaman utama untuk menampilkan daftar anime, halaman detil dengan link unduhan, serta panel admin untuk CRUD data, komentar, dan pengisian metadata otomatis dari AniList.

## Fitur Utama

- **Landing Page**
  - Hero slider rekomendasi anime (random 5 judul).
  - Pencarian dengan pagination dan genre chips.
  - Mode gelap/terang dan desain responsif (mobile friendly).

- **Detail Anime**
  - Informasi lengkap (status, rating, episode, tahun).
  - Link unduhan 360p/720p/1080p (opsional) dengan label host.
  - Deskripsi otomatis diterjemahkan ke bahasa Indonesia jika DeepL tersedia.
  - Komentar pengunjung dengan sanitasi XSS.

- **Panel Admin**
  - Statistik singkat (total anime, komentar, rata-rata rating, jumlah link).
  - Form tambah/edit anime dengan tombol "Isi Otomatis" (AniList GraphQL + fallback dataset lokal).
  - Moderasi komentar (hapus instan) dan daftar anime siswa mobile-friendly.
  - Link unduhan bersifat opsional, disertai placeholder bila kosong di frontend.

## Teknologi

- PHP 8.x dengan PDO (prepared statements) & cURL.
- MySQL/MariaDB.
- HTML/CSS/JS murni (tanpa framework).
- AniList GraphQL API.
- DeepL Free API (opsional untuk terjemahan synopsis).

## Persyaratan

- PHP 8 dengan ekstensi `pdo_mysql` dan `curl` aktif.
- MySQL/MariaDB.
- Server lokal (XAMPP/LAMPP) atau stack Apache + PHP + MySQL.

## Instalasi

1. Clone repository:
   ```bash
   git clone https://github.com/namamu/penyimpanan-anime.git
   cd penyimpanan-anime
   ```
2. Pindahkan folder `storage/` ke direktori web server (`htdocs` atau `/opt/lampp/htdocs`).
3. Buat database `anime_storage`:
   ```sql
   CREATE DATABASE anime_storage CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import skema awal (opsional berisi data contoh & akun admin default) dari `storage/database.sql`.
5. Sesuaikan kredensial database di `storage/backend/db.php` jika perlu.
6. Jalankan aplikasi melalui `http://localhost/coding/storage/index.php`.

### Akun Admin Default
- Username: `admin`
- Password: `admin123`

## Metadata Otomatis & Terjemahan

- Tombol "Isi Otomatis" di panel admin mencari data anime melalui AniList GraphQL.
- Jika variabel lingkungan `DEEPL_API_KEY` diset, sinopsis diterjemahkan ke bahasa Indonesia via DeepL Free API.
- Jika API gagal, sistem fallback ke dataset lokal `storage/data/metadata.json`.

Contoh set environment di Linux/macOS:
```bash
export DEEPL_API_KEY=your_deepl_free_api_key
```

## Struktur Direktori

```
storage/
  index.php              # Halaman utama
  detail.php             # Halaman detail anime
  admin.php              # Panel admin
  backend/
    db.php               # Koneksi PDO + migrasi tabel
    admin_auth.php       # Autentikasi admin berbasis session
    fetch_metadata.php   # Pengambilan metadata AniList + DeepL
    delete_comment.php   # Endpoint hapus komentar
  css/
    style.css            # Styling frontend
    admin.css            # Styling panel admin
  js/
    theme-toggle.js
    hero-slider.js
    admin-comments.js
    admin-metadata.js
  data/
    metadata.json        # Dataset fallback metadata
  database.sql           # Skema + data contoh + admin default
```

## Keamanan

- Semua query menggunakan prepared statement (`PDO::prepare`, `bindValue`).
- Komentar disanitasi sebelum disimpan/ditampilkan.
- Akses admin diproteksi session login.

## Kontribusi & Isu

Pull request dan issue dipersilakan. Untuk bug atau saran fitur, sertakan langkah reproduksi dan screenshot bila diperlukan.

Selamat menata koleksi anime! 🎌
