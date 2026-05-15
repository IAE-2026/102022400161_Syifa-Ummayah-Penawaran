# Laporan Dokumentasi Lengkap: Pengembangan Service Penawaran (Bid Service)
**Mata Kuliah:** Integrasi Aplikasi Enterprise (IAE)
**Penyusun:** Syifa Ummayah

---

## 1. Inisialisasi & Persiapan Lingkungan Kerja (Docker)
Proses dimulai dengan menyiapkan environment menggunakan Docker untuk memastikan isolasi layanan.

* **Kendala Terminal Frozen**: Saat memulai, terminal CMD Windows tidak merespons (freeze), tidak menampilkan path user, dan tidak menerima input perintah.
* **Penyebab**: Terminal masuk ke mode "Selection" atau terdapat sisa proses Docker sebelumnya yang belum berhenti sempurna.
* **Solusi**: Menekan tombol **Enter** berulang kali dan **Ctrl + C** untuk memaksa terminal kembali ke prompt perintah.
* **Setup Database Awal**: Setelah terminal pulih, dilakukan pembersihan database untuk memastikan lingkungan bersih dari data sampah menggunakan perintah: `docker-compose exec app php artisan migrate:fresh`.

---

## 2. Konfigurasi Backend & Troubleshooting Database
Tahap ini melibatkan sinkronisasi antara Laravel di dalam kontainer dengan database MySQL.

### A. Perbaikan Konfigurasi .env
Terjadi kesalahan konfigurasi host yang menyebabkan **Error 500**. Selain itu, ditemukan bahwa Laravel versi baru secara default menggunakan `sqlite`, sementara proyek ini menggunakan `mysql`.

| Parameter | Konfigurasi Awal (Salah) | Perbaikan (Benar) |
| :--- | :--- | :--- |
| **DB_CONNECTION** | `sqlite` | `mysql` |
| **DB_HOST** | `127.0.0.1` | `mysql` (merujuk nama service Docker) |
| **DB_DATABASE** | `laravel` | `bids_db` |

### B. Masalah Migration Database
* **Kendala**: Muncul pesan error *Table 'bids' already exists* saat menjalankan `php artisan migrate`.
* **Penyebab**: Adanya dua file migration untuk tabel `bids` yang saling bentrok karena sempat ter-generate otomatis.
* **Solusi**:
    1. Menghapus file migration lama.
    2. Memperbarui file migration baru agar mencakup kolom: `bidder_id`, `item_id`, `bid_amount`, dan `status`.
    3. Menjalankan perintah `php artisan migrate:fresh` untuk mereset seluruh tabel dari awal.

---

## 3. Implementasi REST API & Dokumentasi Swagger
Pengujian fungsionalitas API dilakukan melalui Swagger UI untuk memastikan endpoint GET dan POST berfungsi dengan baik.

### A. Drama Anotasi Swagger
* **Kendala**: Muncul error *Required @OA\Info() not found* saat menjalankan perintah `php artisan l5-swagger:generate`.
* **Solusi**: Menghapus anotasi `@OA\Info` di `BidController.php` dan memastikan isi file `SwaggerInfo.php` di folder `Controllers` sudah benar agar scanner tidak bingung.
* **Maintenance**: Rutin menghapus cache (`php artisan optimize:clear`) dan folder `storage/api-docs` sebelum generate ulang hingga UI Swagger muncul dengan benar.

### B. Keamanan & Middleware (Error 401 & 500)
* **Masalah 401 Unauthorized**: Request ditolak karena perbedaan penamaan API Key antara pengirim (`X-IAE-KEY`) dan kode (`x-api-key`).
* **Solusi**: Menyamakan nama key di `ApiKeyMiddleware.php` dan menggunakan fitur **Authorize** (ikon gembok) di Swagger dengan key: `bid-service-secret-2024`.
* **Masalah 500 Internal Server Error**: Fungsi `store()` gagal karena mencoba memanggil service eksternal melalui `localhost` yang tidak dikenal di dalam lingkungan Docker.
* **Perbaikan**: Memberikan komentar (`//`) sementara pada bagian validasi eksternal untuk keperluan testing mandiri dan menjalankan `php artisan config:clear`.

---

## 4. Implementasi GraphQL (Lighthouse)
Tahap akhir adalah mengintegrasikan GraphQL untuk pengambilan data yang lebih fleksibel.

* **Instalasi**: Memasang library melalui terminal VS Code dengan perintah: `composer require nuwave/lighthouse` dan `mll-lab/laravel-graphql-playground`.
* **Konfigurasi Schema**: Menghapus isi default `graphql/schema.graphql` dan mendefinisikan tipe data `Bid` serta format query-nya.
* **Error Mutation**: Awalnya muncul error *Schema is not configured for mutations* karena hanya mendukung query (baca data).
* **Solusi**: Menambahkan blok `type Mutation` agar fungsi `createBid` dapat dieksekusi.
* **Cache Management**: Menggunakan perintah `docker-compose exec app php artisan lighthouse:clear-cache` agar perubahan kode di VS Code segera terbaca oleh GraphQL Playground.

---

## 5. Validasi Akhir & Hasil Pengujian
Sistem dinyatakan berhasil setelah melewati pengujian pada URL `/graphql-playground` dan Swagger UI.

| Fitur | Metode | Hasil |
| :--- | :--- | :--- |
| **Create Bid** | POST (Swagger) | **201 Created** (Data masuk ke database) |
| **Create Bid** | Mutation (GraphQL) | **ID Generated** |
| **Read Bids** | Query (GraphQL) | **Data JSON Valid** (Data ditarik sempurna) |

---
**Status Proyek: SELESAI 
