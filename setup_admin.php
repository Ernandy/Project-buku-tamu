<?php
// Jalankan SEKALI lewat terminal: php setup_admin.php admin PasswordKuat123
// Lalu hapus file ini dari server.
if (PHP_SAPI !== 'cli') { exit('Hanya bisa dijalankan dari terminal.'); }
require __DIR__ . '/config/koneksi.php';

[$file, $user, $pass] = $argv + [null, null, null];
if (!$user || !$pass || strlen($pass) < 8) {
    exit("Pemakaian: php setup_admin.php <username> <password minimal 8 karakter>\n");
}
$stmt = $pdo->prepare('INSERT INTO admin (username, password_hash, nama_lengkap) VALUES (?, ?, ?)');
$stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), 'Administrator']);
echo "Admin '$user' berhasil dibuat.\n";
