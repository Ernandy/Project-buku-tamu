<?php
declare(strict_types=1);

/**
 * helpers.php - Fungsi bantu: session aman, escape output, CSRF,
 * flash message, proteksi login, dan upload gambar.
 */
const BASE_URL  = '/buku-tamu';          // sesuaikan dengan folder proyek
define('ROOT_PATH', dirname(__DIR__));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/** Escape output agar aman dari XSS. Selalu pakai e() saat mencetak data. */
function e(?string $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(419);
        exit('Sesi tidak valid. Silakan muat ulang halaman.');
    }
}

/* ---------- Flash message ---------- */
function set_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Proteksi halaman admin ---------- */
function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

/* ---------- Upload gambar aman ---------- */
/**
 * Validasi berdasarkan isi file (bukan ekstensi), batasi ukuran,
 * dan simpan dengan nama acak. Return nama file, atau lempar RuntimeException.
 */
function upload_gambar(array $file, string $folder, int $maxByte = 2097152): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gambar gagal.');
    }
    if ($file['size'] > $maxByte) {
        throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $izin  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($izin[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    }

    $nama   = bin2hex(random_bytes(16)) . '.' . $izin[$mime];
    $tujuan = ROOT_PATH . '/assets/uploads/' . $folder . '/' . $nama;
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        throw new RuntimeException('Gagal menyimpan gambar.');
    }
    return $nama;
}

function hapus_gambar(?string $nama, string $folder): void
{
    if ($nama) {
        $path = ROOT_PATH . '/assets/uploads/' . $folder . '/' . basename($nama);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
