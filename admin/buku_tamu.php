<?php
declare(strict_types=1);
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/includes/filter_tamu.php';
require_login();

/* ============ PROSES CHECKOUT ============ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['aksi'] ?? '') === 'checkout') {
        $id = (int) ($_POST['id'] ?? 0);
        // Hanya tamu yang masih "di_dalam" yang bisa di-checkout
        $stmt = $pdo->prepare(
            "UPDATE tamu SET waktu_keluar = NOW(), status = 'selesai'
              WHERE id = ? AND status = 'di_dalam'"
        );
        $stmt->execute([$id]);
        set_flash($stmt->rowCount() ? 'success' : 'warning',
                  $stmt->rowCount() ? 'Tamu berhasil di-checkout.' : 'Data tidak ditemukan atau sudah selesai.');
    }
    // Kembali ke halaman semula dengan filter yang sama (divalidasi ulang, bukan URL mentah)
    $f = ambil_filter($_POST);
    $hal = max(1, (int) ($_POST['hal'] ?? 1));
    redirect('buku_tamu.php?' . http_build_query($f + ['hal' => $hal]));
}

/* ============ DATA ============ */
$f = ambil_filter($_GET);
[$dasar, $params] = bangun_query($f);

// Ringkasan
$ringkas = $pdo->prepare(
    "SELECT COUNT(*) AS total_kunjungan,
            COALESCE(SUM(t.jumlah_orang), 0) AS total_orang,
            COALESCE(SUM(t.status = 'di_dalam'), 0) AS masih_di_dalam" . $dasar
);
$ringkas->execute($params);
$sum = $ringkas->fetch();

// Paginasi
$perHalaman = 20;
$totalHalaman = max(1, (int) ceil($sum['total_kunjungan'] / $perHalaman));
$hal = min(max(1, (int) ($_GET['hal'] ?? 1)), $totalHalaman);
$offset = ($hal - 1) * $perHalaman;

$stmt = $pdo->prepare(
    'SELECT t.*, s.nama_seksi, p.nama AS nama_pejabat' . $dasar .
    ' ORDER BY t.waktu_masuk DESC LIMIT :lim OFFSET :off'
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

$qs = http_build_query($f);   // dipakai untuk link ekspor & paginasi

$judul = 'Buku Tamu';
require __DIR__ . '/includes/header.php';
?>

<h3 class="section-title">Data Buku Tamu</h3>

<!-- Filter -->
<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-2">
      <label class="form-label mb-0 small">Dari</label>
      <input type="date" name="dari" class="form-control" value="<?= e($f['dari']) ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label mb-0 small">Sampai</label>
      <input type="date" name="sampai" class="form-control" value="<?= e($f['sampai']) ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label mb-0 small">Status</label>
      <select name="status" class="form-select">
        <option value="">Semua</option>
        <option value="di_dalam" <?= $f['status'] === 'di_dalam' ? 'selected' : '' ?>>Masih di dalam</option>
        <option value="selesai"  <?= $f['status'] === 'selesai'  ? 'selected' : '' ?>>Selesai</option>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label mb-0 small">Cari nama / instansi</label>
      <input type="text" name="q" class="form-control" maxlength="100" value="<?= e($f['q']) ?>">
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
      <button class="btn btn-instansi flex-fill">Terapkan</button>
      <a href="export_excel.php?<?= e($qs) ?>" class="btn btn-success">Excel</a>
      <a href="cetak.php?<?= e($qs) ?>" target="_blank" class="btn btn-danger">PDF</a>
    </div>
  </div>
</form>

<!-- Ringkasan -->
<div class="row g-3 mb-3 text-center">
  <div class="col-4"><div class="card card-body"><div class="fs-4 fw-bold"><?= (int) $sum['total_kunjungan'] ?></div><small>Kunjungan</small></div></div>
  <div class="col-4"><div class="card card-body"><div class="fs-4 fw-bold"><?= (int) $sum['total_orang'] ?></div><small>Total Orang</small></div></div>
  <div class="col-4"><div class="card card-body"><div class="fs-4 fw-bold text-warning"><?= (int) $sum['masih_di_dalam'] ?></div><small>Masih di Dalam</small></div></div>
</div>

<!-- Tabel -->
<div class="table-responsive">
  <table class="table table-hover align-middle bg-white shadow-sm">
    <thead class="table-light">
      <tr>
        <th>#</th><th>Masuk</th><th>Nama / HP</th><th>Instansi</th><th>Org</th>
        <th>Tujuan</th><th>Keperluan</th><th>Keluar</th><th class="text-end">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($daftar as $i => $t): ?>
        <tr>
          <td><?= $offset + $i + 1 ?></td>
          <td class="text-nowrap"><?= e(tanggal_id($t['waktu_masuk'])) ?></td>
          <td><?= e($t['nama']) ?><br><small class="text-muted"><?= e($t['no_hp']) ?></small></td>
          <td><?= e($t['instansi']) ?></td>
          <td><?= (int) $t['jumlah_orang'] ?></td>
          <td><?= e($t['nama_seksi']) ?><?php if ($t['nama_pejabat']): ?><br><small class="text-muted"><?= e($t['nama_pejabat']) ?></small><?php endif; ?></td>
          <td style="max-width:220px"><?= e($t['keperluan']) ?></td>
          <td class="text-nowrap"><?= e(tanggal_id($t['waktu_keluar'])) ?></td>
          <td class="text-end">
            <?php if ($t['status'] === 'di_dalam'): ?>
              <form method="post" onsubmit="return confirm('Checkout tamu ini?')">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="checkout">
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <input type="hidden" name="dari" value="<?= e($f['dari']) ?>">
                <input type="hidden" name="sampai" value="<?= e($f['sampai']) ?>">
                <input type="hidden" name="status" value="<?= e($f['status']) ?>">
                <input type="hidden" name="q" value="<?= e($f['q']) ?>">
                <input type="hidden" name="hal" value="<?= $hal ?>">
                <button class="btn btn-sm btn-warning">Selesai</button>
              </form>
            <?php else: ?>
              <span class="badge bg-success">Selesai</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$daftar): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data pada filter ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Paginasi -->
<?php if ($totalHalaman > 1): ?>
<nav>
  <ul class="pagination justify-content-center flex-wrap">
    <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
      <li class="page-item <?= $i === $hal ? 'active' : '' ?>">
        <a class="page-link" href="?<?= e($qs) ?>&hal=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
