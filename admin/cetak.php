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
$daftar = $stmt->fetchAll();
$totalOrang = array_sum(array_column($daftar, 'jumlah_orang'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Buku Tamu</title>
  <style>
    @page { size: A4 landscape; margin: 15mm; }
    body { font-family: "Times New Roman", serif; font-size: 12px; color: #000; }
    h2, h3 { text-align: center; margin: 2px 0; }
    .periode { text-align: center; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
    th { background: #e9ecef; }
    .aksi { text-align: center; margin: 10px; }
    .ttd { width: 250px; margin: 30px 0 0 auto; text-align: center; }
    @media print { .aksi { display: none; } }
  </style>
</head>
<body>
  <div class="aksi">
    <button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    <small>(pada dialog cetak, pilih tujuan "Save as PDF")</small>
  </div>

  <h2>LAPORAN BUKU TAMU</h2>
  <h3>NAMA INSTANSI PEMERINTAH</h3>
  <div class="periode">
    Periode: <?= e(date('d/m/Y', strtotime($f['dari']))) ?> s.d. <?= e(date('d/m/Y', strtotime($f['sampai']))) ?>
  </div>

  <table>
    <thead>
      <tr><th>No</th><th>Masuk</th><th>Nama</th><th>No HP</th><th>Instansi</th><th>Org</th>
          <th>Tujuan</th><th>Keperluan</th><th>Keluar</th></tr>
    </thead>
    <tbody>
      <?php foreach ($daftar as $i => $t): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e(tanggal_id($t['waktu_masuk'])) ?></td>
          <td><?= e($t['nama']) ?></td>
          <td><?= e($t['no_hp']) ?></td>
          <td><?= e($t['instansi']) ?></td>
          <td><?= (int) $t['jumlah_orang'] ?></td>
          <td><?= e($t['nama_seksi']) ?><?= $t['nama_pejabat'] ? ' / ' . e($t['nama_pejabat']) : '' ?></td>
          <td><?= e($t['keperluan']) ?></td>
          <td><?= e(tanggal_id($t['waktu_keluar'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$daftar): ?><tr><td colspan="9" style="text-align:center">Tidak ada data.</td></tr><?php endif; ?>
    </tbody>
  </table>

  <p>Total kunjungan: <strong><?= count($daftar) ?></strong> &nbsp;|&nbsp; Total orang: <strong><?= (int) $totalOrang ?></strong></p>

  <div class="ttd">
    <?= e(date('d/m/Y')) ?><br>Petugas,<br><br><br><br>( ______________________ )
  </div>
</body>
</html>
