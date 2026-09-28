<?php
// Variabel yang diharapkan: $judul (judul halaman)
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul ?? 'Admin') ?> - Admin Buku Tamu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-instansi">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/admin/buku_tamu.php">Admin Buku Tamu</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#m"><span class="navbar-toggler-icon"></span></button>
    <div id="m" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/buku_tamu.php">Buku Tamu</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/kelola_slide.php">Slide</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/kelola_berita.php">Berita</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/kelola_seksi.php">Seksi</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/kelola_pejabat.php">Pejabat</a></li>
      </ul>
      <form action="<?= BASE_URL ?>/admin/logout.php" method="post" class="d-flex">
        <?= csrf_field() ?>
        <button class="btn btn-outline-light btn-sm">Keluar (<?= e($_SESSION['admin_nama'] ?? '') ?>)</button>
      </form>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>
