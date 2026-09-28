<?php
declare(strict_types=1);
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/includes/filter_tamu.php';
require_login();

$f = ambil_filter($_GET);
[$dasar, $params] = bangun_query($f);

$stmt = $pdo->prepare(
    'SELECT t.*, s.nama_seksi, p.nama AS nama_pejabat' . $dasar . ' ORDER BY t.waktu_masuk ASC'
);
$stmt->execute($params);

/** Cegah CSV/formula injection: sel yang diawali = + - @ diberi tanda petik. */
function csv_aman(?string $v): string
{
    $v = (string) $v;
    return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v;
}

$namaFile = 'buku-tamu_' . $f['dari'] . '_sd_' . $f['sampai'] . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $namaFile . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // BOM agar Excel membaca UTF-8 dengan benar

// Pemisah ";" cocok untuk Excel dengan pengaturan regional Indonesia
fputcsv($out, ['No', 'Waktu Masuk', 'Nama', 'No HP', 'Instansi', 'Jumlah Orang',
               'Seksi Tujuan', 'Pejabat', 'Keperluan', 'Waktu Keluar', 'Status'], ';');

$no = 1;
while ($t = $stmt->fetch()) {
    fputcsv($out, [
        $no++,
        tanggal_id($t['waktu_masuk']),
        csv_aman($t['nama']),
        csv_aman($t['no_hp']),
        csv_aman($t['instansi']),
        $t['jumlah_orang'],
        csv_aman($t['nama_seksi']),
        csv_aman($t['nama_pejabat']),
        csv_aman($t['keperluan']),
        tanggal_id($t['waktu_keluar']),
        $t['status'] === 'selesai' ? 'Selesai' : 'Di dalam',
    ], ';');
}
fclose($out);
