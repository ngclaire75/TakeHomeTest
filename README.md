# TerangAPJ

Sistem katalog produk Alat Penerangan Jalan (APJ) yang datanya diambil langsung dari Katalog Elektronik INAPROC milik pemerintah. Proyek ini terdiri dari tiga bagian utama, yaitu mesin scraping berbasis PHP, Backend API berbasis Laravel, dan antarmuka web berbasis Vue.js, ditambah satu rangkaian Feature Test untuk menjaga kualitas API.

## Gambaran Arsitektur Sistem

Alur data dari hulu ke hilir berjalan seperti berikut.

1. Scraper PHP memanggil endpoint GraphQL milik INAPROC dengan filter kategori Alat Penerangan Jalan, lalu menelusuri halaman demi halaman sampai jumlah minimal produk terpenuhi.
2. Untuk setiap produk, scraper juga membuka halaman detailnya untuk mengambil teks spesifikasi, kemudian mengurai nilai Daya, Lumen, Efikasi, dan Voltase memakai pola regex.
3. Hasilnya disimpan ke database PostgreSQL bernama `inaproc_apj` pada tabel `apj_products` memakai mekanisme upsert, sehingga proses boleh dijalankan berulang tanpa membuat data ganda.
4. Perintah impor di sisi Laravel menyalin data tersebut ke database kedua bernama `catalog_api`. Di tahap ini empat atribut dinamis digabung menjadi satu kolom bertipe JSONB bernama `dynamic_attributes`.
5. Backend Laravel menyajikan data lewat endpoint REST `GET /api/products` dengan dukungan pencarian teks penuh bawaan PostgreSQL, pagination 15 item per halaman, dan filter rentang harga. Setiap kombinasi permintaan disimpan sementara di Redis supaya respons cepat dan beban query ke PostgreSQL berkurang.
6. Frontend Vue.js mengonsumsi API tersebut, menampilkan produk dalam bentuk grid card, dan menyediakan fitur wishlist yang tersimpan permanen di sisi client melalui Pinia dan LocalStorage, sehingga data wishlist tetap ada meski browser dimuat ulang.

Ringkasnya, aliran datanya adalah INAPROC → Scraper PHP → PostgreSQL (inaproc_apj) → Perintah Impor → PostgreSQL (catalog_api, JSONB) → Laravel API + Redis Cache → Frontend Vue.js.

### Detail Teknis per Komponen

**Mesin Scraper (folder `src` dan `bin`)**

* `InaprocClient.php` membungkus dua endpoint publik yang juga dipakai situs INAPROC sendiri, yaitu endpoint GraphQL untuk daftar produk dan halaman detail produk yang dirender di server. Setiap permintaan dilengkapi retry sampai tiga kali dengan jeda bertingkat.
* `ProductSpecParser.php` mengurai teks bebas berjudul Spesifikasi Produk dari halaman detail, lalu mengekstrak angka Daya (Watt), Lumen, Efikasi (lm/W), dan Voltase (V) memakai regex yang menoleransi beragam gaya penulisan vendor, baik dalam Bahasa Indonesia maupun Inggris. Nilai yang tidak ditemukan dibiarkan kosong karena memang bersifat opsional.
* `ProductRepository.php` menyimpan data dengan upsert berdasarkan `external_id`, sehingga eksekusi ulang hanya memperbarui baris lama.
* `ApjScraper.php` mengatur alur pagination dan berhenti berdasarkan jumlah baris unik yang benar tersimpan di database, bukan sekadar jumlah item yang diproses. Ini penting karena hasil pencarian INAPROC bisa memunculkan produk yang sama di halaman berbeda.
* Skrip masuk lewat `bin/scrape-apj.php` dan mengembalikan kode keluar 0 bila target minimal terpenuhi, 2 bila selesai namun kurang dari target, serta 1 bila terjadi kegagalan fatal. Kode keluar ini memudahkan pemantauan saat dijalankan lewat penjadwal.

**Backend API (folder `backend-api`)**

* Skema tabel `products` menyimpan atribut dinamis pada kolom JSONB `dynamic_attributes` sesuai ketentuan, ditambah kolom `search_vector` bertipe tsvector yang dibangkitkan otomatis dari nama produk dan diindeks dengan GIN untuk pencarian teks penuh.
* Endpoint `GET /api/products` menerima parameter `q` untuk pencarian nama, `min_price` dan `max_price` untuk rentang harga, serta `page` untuk navigasi halaman. Hasil selalu dibatasi 15 item per halaman.
* Caching memakai Redis dengan masa simpan 300 detik. Kunci cache dibentuk dari kombinasi seluruh parameter sehingga tiap variasi permintaan tidak saling tercampur. Bila Redis mati, endpoint otomatis beralih ke query langsung ke PostgreSQL dan menandai respons dengan header `X-Cache: BYPASS`, jadi layanan tetap hidup.
* Perintah `php artisan products:import` menarik data dari database scraper dan melakukan upsert ke tabel `products`.

**Frontend (folder `frontend`)**

* Dibangun dengan Vue 3, Vite, Pinia, dan Vue Router. Tampilan responsif dari layar ponsel sampai monitor lebar.
* Produk tampil sebagai grid card lengkap dengan gambar, vendor, harga, nilai TKDN, dan spesifikasi daya. Pagination, pencarian, dan filter rentang harga memakai parameter API yang sama dengan backend.
* Wishlist disimpan lewat store Pinia yang otomatis ditulis ke LocalStorage pada setiap perubahan, sehingga isi wishlist bertahan walau halaman dimuat ulang atau browser ditutup.
* Gambar produk dicerminkan ke folder `frontend/public/img` karena CDN INAPROC menolak permintaan gambar yang membawa referer dari domain lain.

**Feature Test (folder `backend-api/tests`)**

* File `tests/Feature/ProductApiTest.php` berisi lima pengujian, mencakup kepastian endpoint mengembalikan HTTP 200 dengan struktur JSON yang tepat, pagination 15 item, filter harga, pencarian teks penuh, dan validasi input.
* Pengujian berjalan pada database terpisah bernama `catalog_api_test` supaya data asli tidak pernah tersentuh, dengan cache diarahkan ke driver array agar tidak bergantung pada Redis.

## Prasyarat

* macOS atau Linux dengan Homebrew, atau lingkungan lain yang menyediakan paket setara.
* PHP 8.1 ke atas beserta ekstensi `pdo_pgsql` dan `pgsql`.
* Composer 2.
* PostgreSQL 16.
* Redis.
* Node.js 18 ke atas beserta npm untuk frontend.

Contoh pemasangan seluruh kebutuhan lewat Homebrew:

```bash
brew install php postgresql@16 composer redis node
brew services start postgresql@16
brew services start redis
```

## Instalasi

Seluruh perintah dijalankan dari folder akar proyek kecuali disebutkan lain.

Langkah 1, siapkan database:

```bash
createdb inaproc_apj
createdb catalog_api
createdb catalog_api_test
```

Langkah 2, pasang dependensi scraper lalu salin berkas konfigurasi:

```bash
composer install
cp .env.example .env
```

Buka `.env` dan sesuaikan kredensial PostgreSQL dengan mesin Anda, terutama `PGUSER` dan `PGPASSWORD`.

Langkah 3, buat tabel scraper:

```bash
php bin/migrate.php
```

Langkah 4, pasang dependensi backend lalu siapkan konfigurasinya:

```bash
cd backend-api
composer install
cp .env.example .env
php artisan key:generate
```

Pastikan bagian database pada `backend-api/.env` mengarah ke PostgreSQL, contohnya:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=catalog_api
DB_USERNAME=nama_user_anda
DB_PASSWORD=
SCRAPER_DB_DATABASE=inaproc_apj
CACHE_STORE=redis
REDIS_CLIENT=predis
```

Lalu jalankan migrasi backend:

```bash
php artisan migrate
```

Langkah 5, pasang dependensi frontend:

```bash
cd ../frontend
npm install
```

## Cara Menjalankan Scraper

Scraper dijalankan sebagai perintah mandiri dari folder akar proyek:

```bash
php bin/scrape-apj.php
```

Proses akan menelusuri kategori Alat Penerangan Jalan di INAPROC halaman demi halaman, membuka halaman detail tiap produk, lalu menyimpan hasilnya ke tabel `apj_products` di database `inaproc_apj`. Eksekusi berhenti setelah jumlah baris unik di database mencapai nilai `SCRAPER_MIN_RECORDS`, bawaannya 120. Menjalankan perintah yang sama berulang kali aman karena data lama hanya diperbarui, tidak digandakan.

Perilaku scraper diatur lewat `.env`:

* `SCRAPER_CATEGORY_ID` berisi ID kategori Alat Penerangan Jalan di INAPROC dan sudah terisi nilai bawaan yang benar.
* `SCRAPER_MIN_RECORDS` menentukan jumlah minimal produk unik sebelum proses berhenti, bawaannya 120.
* `SCRAPER_PER_PAGE` menentukan jumlah item per halaman pencarian, bawaannya 60.
* `SCRAPER_DELAY_MS` menentukan jeda antar permintaan dalam milidetik agar sopan terhadap server, bawaannya 400.

Untuk menjadikannya skrip berjadwal, daftarkan ke cron. Contoh berikut menjalankan scraper setiap enam jam dan menyimpan lognya:

```bash
0 */6 * * * cd /lokasi/proyek/anda && php bin/scrape-apj.php >> scraper.log 2>&1
```

## Menjalankan Backend API

Setelah scraper pernah dijalankan, salin datanya ke database katalog lalu nyalakan server:

```bash
cd backend-api
php artisan products:import
php artisan serve --port=8123
```

API kini tersedia di `http://127.0.0.1:8123/api/products`. Beberapa contoh pemakaian:

```bash
curl "http://127.0.0.1:8123/api/products"
curl "http://127.0.0.1:8123/api/products?page=2"
curl "http://127.0.0.1:8123/api/products?q=lampu"
curl "http://127.0.0.1:8123/api/products?min_price=1000000&max_price=5000000"
curl "http://127.0.0.1:8123/api/products?q=surya&min_price=5000000"
```

Header `X-Cache` pada respons menunjukkan status cache. Nilai `MISS` berarti hasil baru diambil dari PostgreSQL, `HIT` berarti dilayani dari Redis, dan `BYPASS` berarti Redis sedang tidak tersedia sehingga query dilayani langsung oleh database.

## Menjalankan Frontend

```bash
cd frontend
npm run dev
```

Buka `http://localhost:5173` di browser. Halaman utama menampilkan katalog lengkap dengan pencarian, filter harga, dan pagination. Tekan ikon hati atau tombol Wishlist pada kartu produk untuk menyimpannya, lalu buka halaman Wishlist lewat menu di kanan atas. Isi wishlist tetap tersimpan meski halaman dimuat ulang.

Catatan, backend harus berjalan di port 8123 karena alamat itulah yang dipakai frontend secara bawaan. Alamat lain bisa diatur lewat variabel `VITE_API_BASE`.

## Menjalankan Feature Test

```bash
cd backend-api
php artisan test
```

Pengujian memakai database `catalog_api_test` yang terisolasi penuh, jadi data hasil scraping maupun katalog asli tidak akan berubah sedikit pun oleh proses testing.

## Keterbatasan yang Diketahui

* Sebagian vendor di INAPROC tidak mencantumkan harga sesungguhnya dan sistem sumber menampilkan nilai simbolis sangat kecil dengan tombol Hubungi Vendor. Scraper menyimpan apa adanya sesuai sumber, dan frontend menampilkannya sebagai Hubungi Vendor.
* Atribut dinamis diambil dari teks bebas yang ditulis vendor, jadi produk yang vendornya tidak menuliskan spesifikasi lengkap akan memiliki nilai kosong. Ini sesuai sifat datanya yang memang opsional.
* Data merupakan potret pada saat scraping. Harga dan stok di situs sumber dapat berubah sewaktu waktu, jadi jalankan scraper secara berkala bila membutuhkan data terkini.
