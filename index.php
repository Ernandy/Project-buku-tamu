<?php
declare(strict_types=1);
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/config/helpers.php';

// Data dari database (semua query tanpa input user -> aman)
$slides = $pdo->query('SELECT judul, keterangan, gambar FROM slide WHERE is_active = 1 ORDER BY urutan, id DESC')->fetchAll();
$berita = $pdo->query('SELECT judul, isi, jenis, created_at FROM berita WHERE is_published = 1 ORDER BY created_at DESC LIMIT 6')->fetchAll();
$daftarSeksi = $pdo->query('SELECT id, nama_seksi, deskripsi FROM seksi ORDER BY urutan, nama_seksi')->fetchAll();
$daftarPejabat = $pdo->query('SELECT id, seksi_id, nama, jabatan, foto FROM pejabat WHERE is_active = 1 ORDER BY nama')->fetchAll();

// Kelompokkan pejabat per seksi
$pejabatPerSeksi = [];
foreach ($daftarPejabat as $p) {
    $pejabatPerSeksi[$p['seksi_id']][] = $p;
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Buku Tamu Online - Nama Instansi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-instansi sticky-top">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="#">Nama Instansi Pemerintah</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div id="menu" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#form-tamu">Buku Tamu</a></li>
        <li class="nav-item"><a class="nav-link" href="#informasi">Informasi</a></li>
        <li class="nav-item"><a class="nav-link" href="#pejabat">Pejabat &amp; Seksi</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/login.php">Login Admin</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Banner / Carousel -->
<?php if ($slides): ?>
<div id="banner" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <?php foreach ($slides as $i => $s): ?>
      <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
        <img src="<?= BASE_URL ?>/assets/uploads/slide/<?= e($s['gambar']) ?>" class="d-block w-100 banner-img" alt="<?= e($s['judul']) ?>">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded">
          <h5><?= e($s['judul']) ?></h5>
          <p class="mb-0 d-none d-md-block"><?= e($s['keterangan']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="carousel-control-prev" data-bs-target="#banner" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
  <button class="carousel-control-next" data-bs-target="#banner" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
</div>
<?php endif; ?>

<main class="container py-5">

  <!-- Form Tamu -->
  <section id="form-tamu" class="mb-5">
    <h2 class="section-title">Formulir Buku Tamu</h2>
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/proses_tamu.php" method="post" class="card card-body shadow-sm">
      <?= csrf_field() ?>
      <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off"> <!-- honeypot -->
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" maxlength="100" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nomor HP</label>
          <input type="tel" name="no_hp" class="form-control" maxlength="20" required>
        </div>
        <div class="col-md-8">
          <label class="form-label">Instansi / Asal</label>
          <input type="text" name="instansi" class="form-control" maxlength="150" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Jumlah Orang</label>
          <input type="number" name="jumlah_orang" class="form-control" min="1" max="100" value="1" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Seksi / Bidang Tujuan</label>
          <select name="seksi_id" id="seksi_id" class="form-select" required>
            <option value="">-- Pilih Seksi --</option>
            <?php foreach ($daftarSeksi as $s): ?>
              <option value="<?= (int) $s['id'] ?>"><?= e($s['nama_seksi']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Pejabat yang Ingin Ditemui</label>
          <select name="pejabat_id" id="pejabat_id" class="form-select">
            <option value="">-- Tidak spesifik --</option>
            <?php foreach ($daftarPejabat as $p): ?>
              <option value="<?= (int) $p['id'] ?>" data-seksi="<?= (int) $p['seksi_id'] ?>">
                <?= e($p['nama']) ?> - <?= e($p['jabatan']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Keperluan</label>
          <textarea name="keperluan" class="form-control" rows="3" required></textarea>
        </div>
        <div class="col-12 text-end">
          <button class="btn btn-instansi px-4" type="submit">Kirim</button>
        </div>
      </div>
    </form>
  </section>

  <!-- Informasi & Pengumuman -->
  <section id="informasi" class="mb-5">
    <h2 class="section-title">Informasi &amp; Pengumuman</h2>
    <div class="row g-3">
      <?php foreach ($berita as $b): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <span class="badge <?= $b['jenis'] === 'pengumuman' ? 'bg-warning text-dark' : 'bg-primary' ?>"><?= e(ucfirst($b['jenis'])) ?></span>
              <h5 class="card-title mt-2"><?= e($b['judul']) ?></h5>
              <p class="card-text text-muted"><?= e(mb_strimwidth($b['isi'], 0, 140, '...')) ?></p>
            </div>
            <div class="card-footer small text-muted"><?= e(date('d M Y', strtotime($b['created_at']))) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$berita): ?><p class="text-muted">Belum ada informasi.</p><?php endif; ?>
    </div>
  </section>

  <!-- Pejabat & Seksi -->
  <section id="pejabat">
    <h2 class="section-title">Daftar Pejabat &amp; Seksi Bidang</h2>
    <div class="row g-3">
      <?php foreach ($daftarSeksi as $s): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-header bg-instansi text-white"><?= e($s['nama_seksi']) ?></div>
            <ul class="list-group list-group-flush">
              <?php foreach ($pejabatPerSeksi[$s['id']] ?? [] as $p): ?>
                <li class="list-group-item d-flex align-items-center gap-3">
                  <?php if ($p['foto']): ?>
                    <img src="<?= BASE_URL ?>/assets/uploads/pejabat/<?= e($p['foto']) ?>" class="avatar" alt="">
                  <?php else: ?>
                    <span class="avatar avatar-placeholder"><?= e(mb_substr($p['nama'], 0, 1)) ?></span>
                  <?php endif; ?>
                  <div>
                    <div class="fw-semibold"><?= e($p['nama']) ?></div>
                    <small class="text-muted"><?= e($p['jabatan']) ?></small>
                  </div>
                </li>
              <?php endforeach; ?>
              <?php if (empty($pejabatPerSeksi[$s['id']])): ?>
                <li class="list-group-item text-muted">Belum ada data pejabat.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<footer class="bg-instansi text-white text-center py-3 small">
  &copy; <?= date('Y') ?> Nama Instansi Pemerintah. Seluruh hak dilindungi.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
