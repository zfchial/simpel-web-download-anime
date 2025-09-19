# Penyimpanan Anime

Aplikasi web sederhana berbasis PHP/MySQL untuk mengelola koleksi anime pribadi. Fitur utama meliputi pencarian & pagination koleksi, detail anime lengkap dengan link unduhan multi-kualitas, panel admin untuk CRUD data serta moderasi komentar, dan dukungan metadata otomatis dari AniList + terjemahan sinopsis via DeepL.

## Fitur

- **Landing Page**
  - Hero slider rekomendasi anime (random 5 judul).
  - Pencarian dengan pagination & genre chips.
  - Mode gelap/terang + tampilan responsif (mobile friendly).
- **Detail Anime**
  - Deskripsi terformat, status, rating, link unduhan (360p/720p/1080p opsional).
  - Komentar pengunjung + form komentar (anti-XSS).
- **Panel Admin**
  - Login protected.
  - Statistik singkat (total anime, komentar, rata-rata rating, jumlah link).
  - Form tambah/edit dengan tombol “Isi Otomatis” (AniList GraphQL + DeepL translate).
  - Listing anime & komentar (support mobile layout), delete komentar instan.

## Teknologi

- PHP 8.x (PDO, cURL)
- MySQL/MariaDB
- HTML/CSS (vanilla) + sedikit JavaScript
- AniList GraphQL API
- (Opsional) DeepL Free API untuk terjemahan sinopsis

## Persyaratan

- PHP 8 dengan ekstensi `curl`, `pdo_mysql`.
- MySQL atau MariaDB.
- Server lokal seperti XAMPP/LAMPP atau Apache + PHP + MySQL.
- Composer tidak diperlukan.

## Instalasi

1. Clone repo:
   ```bash
   git clone https://github.com/username/penyimpanan-anime.git
   cd penyimpanan-anime
