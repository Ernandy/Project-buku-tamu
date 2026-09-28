<?php
declare(strict_types=1);

/**
 * koneksi.php - Koneksi database memakai PDO.
 * Prepared statement (dipakai di seluruh proyek) mencegah SQL Injection.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'buku_tamu';
const DB_USER = 'root';
const DB_PASS = '';          // Ganti di server produksi!

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // prepared statement asli
        ]
    );
} catch (PDOException $e) {
    error_log('Koneksi DB gagal: ' . $e->getMessage()); // detail hanya di log
    http_response_code(500);
    exit('Layanan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
}
