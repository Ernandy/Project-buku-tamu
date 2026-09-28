<?php
declare(strict_types=1);
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/index.php');
}
csrf_check();

// Honeypot anti-bot: field ini disembunyikan, manusia tidak akan mengisinya
if (!empty($_POST['website'])) {
    redirect(BASE_URL . '/index.php');
}

$nama       = trim($_POST['nama'] ?? '');
$no_hp      = trim($_POST['no_hp'] ?? '');
$instansi   = trim($_POST['instansi'] ?? '');
$jumlah     = (int) ($_POST['jumlah_orang'] ?? 0);
$seksi_id   = (int) ($_POST['seksi_id'] ?? 0);
$pejabat_id = (int) ($_POST['pejabat_id'] ?? 0);
$keperluan  = trim($_POST['keperluan'] ?? '');

$error = [];
if ($nama === '' || mb_strlen($nama) > 100)          $error[] = 'Nama wajib diisi (maks. 100 karakter).';
if (!preg_match('/^[0-9+\-\s]{8,20}$/', $no_hp))     $error[] = 'Nomor HP tidak valid.';
if ($instansi === '' || mb_strlen($instansi) > 150)  $error[] = 'Instansi wajib diisi.';
if ($jumlah < 1 || $jumlah > 100)                    $error[] = 'Jumlah orang harus 1-100.';
if ($keperluan === '')                               $error[] = 'Keperluan wajib diisi.';

if (!$error) {
    // Pastikan seksi ada, dan pejabat (jika dipilih) memang milik seksi tersebut
    $cek = $pdo->prepare('SELECT COUNT(*) FROM seksi WHERE id = ?');
    $cek->execute([$seksi_id]);
    if (!$cek->fetchColumn()) {
        $error[] = 'Seksi tujuan tidak valid.';
    }
    if ($pejabat_id > 0) {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM pejabat WHERE id = ? AND seksi_id = ? AND is_active = 1');
        $cek->execute([$pejabat_id, $seksi_id]);
        if (!$cek->fetchColumn()) {
            $error[] = 'Pejabat tidak sesuai dengan seksi.';
        }
    }
}

if ($error) {
    set_flash('danger', implode(' ', $error));
    redirect(BASE_URL . '/index.php#form-tamu');
}

$stmt = $pdo->prepare(
    'INSERT INTO tamu (nama, no_hp, instansi, jumlah_orang, seksi_id, pejabat_id, keperluan)
     VALUES (:nama, :hp, :instansi, :jumlah, :seksi, :pejabat, :keperluan)'
);
$stmt->execute([
    ':nama'      => $nama,
    ':hp'        => $no_hp,
    ':instansi'  => $instansi,
    ':jumlah'    => $jumlah,
    ':seksi'     => $seksi_id,
    ':pejabat'   => $pejabat_id > 0 ? $pejabat_id : null,
    ':keperluan' => $keperluan,
]);

set_flash('success', 'Terima kasih, data kunjungan Anda telah tercatat. Silakan menunggu petugas.');
redirect(BASE_URL . '/index.php#form-tamu');
