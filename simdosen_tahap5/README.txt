# SIMDOSEN — SISTEM INFORMASI MANAJEMEN DOSEN

## A. Persyaratan Sistem

1. Windows dengan XAMPP terpasang.
2. Apache dan MySQL/MariaDB.
3. PHP yang mendukung aplikasi.
4. Browser web.
5. Postman untuk pengujian REST API.

## B. Cara Menjalankan Aplikasi

1. Letakkan folder `simdosen_tahap5` di `C:\xampp\htdocs\`.
2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
3. Pastikan database `simdosen` sudah dibuat dan diimpor melalui phpMyAdmin.
4. Periksa konfigurasi koneksi database pada `config/database.php`.
5. Buka `http://localhost/simdosen_tahap5/` melalui browser.
6. Login menggunakan akun yang tersedia di tabel `users`. Pastikan username dan password sesuai dengan data di database.

## C. Fitur Aplikasi

1. Login dan logout menggunakan session.
2. Pembatasan akses berdasarkan role pengguna.
3. Dashboard aplikasi.
4. Pengelolaan data dosen (CRUD: tambah, tampil, ubah, dan hapus).
5. Pencarian dan detail data dosen.
6. Import dan export data dosen melalui CSV.
7. Pengelolaan data referensi.
8. Laporan data dosen dan filter data.
9. REST API untuk mengambil, menambah, memperbarui, dan menghapus data dosen.
10. Audit log untuk mencatat aktivitas tertentu.

## D. Pengujian REST API

Pengujian dilakukan menggunakan Postman dengan endpoint berikut:

* GET semua dosen: `http://localhost/simdosen_tahap5/api/index.php`
* GET detail dosen: `http://localhost/simdosen_tahap5/api/index.php?id=5`
* POST untuk menambah data dosen.
* PUT untuk memperbarui data dosen.
* DELETE untuk menghapus data dosen.

Hasil pengujian GET, POST, PUT, dan DELETE telah berhasil dijalankan.

Operasi POST, PUT, dan DELETE memerlukan API Key melalui header `X-API-Key`. Konfigurasi API Key harus tersedia di lingkungan Apache.

## E. Struktur Folder

* `admin/` — halaman pengelolaan dosen, referensi, dan laporan.
* `api/` — endpoint REST API.
* `auth/` — autentikasi dan fungsi otorisasi.
* `config/` — konfigurasi koneksi database.
* `index.php` — halaman awal aplikasi.
* `login.php` — halaman login.
* `dashboard.php` — dashboard aplikasi.
* `logout.php` — proses logout.
* `.htaccess` — konfigurasi Apache untuk API Key.

## F. Catatan

1. Pastikan database dan struktur tabel sesuai dengan aplikasi.
2. Gunakan akun yang telah terdaftar di tabel `users`.
3. Jangan membagikan API Key kepada orang lain.
4. Sebelum aplikasi dipasang di server publik, gunakan API Key yang kuat dan konfigurasi keamanan yang sesuai.
5. Lakukan backup database sebelum perubahan data penting.

Dokumentasi ini disusun untuk membantu menjalankan dan menguji aplikasi SIMDOSEN.
