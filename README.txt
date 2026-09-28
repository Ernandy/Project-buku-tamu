BUKU TAMU ONLINE + PROFIL INSTANSI (PHP Native + MySQL + Bootstrap 5)

CARA PASANG
1. Salin folder "buku-tamu" ke htdocs (XAMPP) atau www (Laragon).
2. Import database/schema.sql lewat phpMyAdmin.
3. Sesuaikan DB_USER / DB_PASS di config/koneksi.php
   dan BASE_URL di config/helpers.php.
4. Buat admin lewat terminal di dalam folder proyek:
   php setup_admin.php admin PasswordKuat123
   lalu HAPUS file setup_admin.php.
5. Buka http://localhost/buku-tamu/  (admin: /admin/login.php)

CATATAN
- Folder assets/uploads/* harus bisa ditulis oleh web server.
- Aktifkan HTTPS di server produksi.
