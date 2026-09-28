<?php
declare(strict_types=1);
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../config/helpers.php';

if (!empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/admin/buku_tamu.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Pembatasan percobaan sederhana: 5 kali gagal = kunci 5 menit
    if (($_SESSION['gagal'] ?? 0) >= 5 && time() < ($_SESSION['kunci_sampai'] ?? 0)) {
        $error = 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash, nama_lengkap FROM admin WHERE username = ?');
        $stmt->execute([trim($_POST['username'] ?? '')]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
            session_regenerate_id(true);           // cegah session fixation
            unset($_SESSION['gagal'], $_SESSION['kunci_sampai']);
            $_SESSION['admin_id']   = (int) $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            redirect(BASE_URL . '/admin/buku_tamu.php');
        }
        $_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
        $_SESSION['kunci_sampai'] = time() + 300;
        $error = 'Username atau password salah.';  // pesan sama agar tidak bocor info
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center min-vh-100">
  <div class="container" style="max-width:400px">
    <div class="card shadow">
      <div class="card-header bg-instansi text-white text-center">Login Admin</div>
      <form method="post" class="card-body">
        <?= csrf_field() ?>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <div class="mb-3"><label class="form-label">Username</label>
          <input name="username" class="form-control" required autofocus></div>
        <div class="mb-3"><label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-instansi w-100">Masuk</button>
      </form>
    </div>
  </div>
</body>
</html>
