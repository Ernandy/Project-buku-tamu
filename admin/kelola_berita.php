<?php
declare(strict_types=1);
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../config/helpers.php';
require_login();

/* ==================== PROSES ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan') {
            $id      = (int) ($_POST['id'] ?? 0);
            $judul   = trim($_POST['judul'] ?? '');
            $isi     = trim($_POST['isi'] ?? '');
            $jenis   = $_POST['jenis'] ?? '';
            $publish = isset($_POST['is_published']) ? 1 : 0;

            if ($judul === '' || mb_strlen($judul) > 200 || $isi === '') {
                throw new RuntimeException('Judul (maks. 200 karakter) dan isi wajib diisi.');
            }
            if (!in_array($jenis, ['berita', 'pengumuman'], true)) {   // whitelist nilai enum
                throw new RuntimeException('Jenis tidak valid.');
            }

            $gambarBaru = null;
            if (!empty($_FILES['gambar']['name'])) {
                $gambarBaru = upload_gambar($_FILES['gambar'], 'berita');
            }

            if ($id > 0) {                                   // UPDATE
                $lama = $pdo->prepare('SELECT gambar FROM berita WHERE id = ?');
                $lama->execute([$id]);
                $gambarLama = $lama->fetchColumn();

                $pdo->prepare(
                    'UPDATE berita SET judul = :j, isi = :i, jenis = :jn,
                            is_published = :p, gambar = COALESCE(:g, gambar) WHERE id = :id'
                )->execute([':j' => $judul, ':i' => $isi, ':jn' => $jenis,
                            ':p' => $publish, ':g' => $gambarBaru, ':id' => $id]);
                if ($gambarBaru) {
                    hapus_gambar($gambarLama ?: null, 'berita');
                }
                set_flash('success', 'Berita berhasil diperbarui.');
            } else {                                         // CREATE
                $pdo->prepare(
                    'INSERT INTO berita (judul, isi, jenis, gambar, is_published)
                     VALUES (:j, :i, :jn, :g, :p)'
                )->execute([':j' => $judul, ':i' => $isi, ':jn' => $jenis,
                            ':g' => $gambarBaru, ':p' => $publish]);
                set_flash('success', 'Berita berhasil ditambahkan.');
            }
        } elseif ($aksi === 'hapus') {                       // DELETE
            $id = (int) ($_POST['id'] ?? 0);
            $lama = $pdo->prepare('SELECT gambar FROM berita WHERE id = ?');
            $lama->execute([$id]);
            $gambar = $lama->fetchColumn();

            $pdo->prepare('DELETE FROM berita WHERE id = ?')->execute([$id]);
            hapus_gambar($gambar ?: null, 'berita');
            set_flash('success', 'Berita berhasil dihapus.');
        }
    } catch (RuntimeException $e) {
        set_flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        error_log($e->getMessage());
        set_flash('danger', 'Terjadi kesalahan pada database.');
    }
    redirect(BASE_URL . '/admin/kelola_berita.php');
}

/* ==================== TAMPILAN ==================== */
$daftar = $pdo->query('SELECT id, judul, jenis, is_published, created_at FROM berita ORDER BY created_at DESC')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM berita WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$judul = 'Kelola Berita';
require __DIR__ . '/includes/header.php';
?>

<h3 class="section-title">Kelola Berita &amp; Pengumuman</h3>

<div class="card shadow-sm mb-4">
  <div class="card-header"><?= $edit ? 'Ubah Berita' : 'Tambah Berita / Pengumuman' ?></div>
  <form method="post" enctype="multipart/form-data" class="card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Judul</label>
        <input name="judul" class="form-control" maxlength="200" required value="<?= e($edit['judul'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Jenis</label>
        <select name="jenis" class="form-select" required>
          <?php foreach (['pengumuman' => 'Pengumuman', 'berita' => 'Berita'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($edit['jenis'] ?? 'pengumuman') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Isi</label>
        <textarea name="isi" rows="5" class="form-control" required><?= e($edit['isi'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Gambar (opsional, maks. 2 MB)</label>
        <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_published" id="pub"
                 <?= !$edit || !empty($edit['is_published']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="pub">Terbitkan di halaman publik</label>
        </div>
      </div>
      <div class="col-12 text-end">
        <?php if ($edit): ?><a href="kelola_berita.php" class="btn btn-outline-secondary">Batal</a><?php endif; ?>
        <button class="btn btn-instansi">Simpan</button>
      </div>
    </div>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle bg-white shadow-sm">
    <thead class="table-light">
      <tr><th>Judul</th><th>Jenis</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($daftar as $b): ?>
        <tr>
          <td><?= e($b['judul']) ?></td>
          <td><span class="badge <?= $b['jenis'] === 'pengumuman' ? 'bg-warning text-dark' : 'bg-primary' ?>"><?= e(ucfirst($b['jenis'])) ?></span></td>
          <td class="text-nowrap"><?= e(date('d/m/Y', strtotime($b['created_at']))) ?></td>
          <td><span class="badge <?= $b['is_published'] ? 'bg-success' : 'bg-secondary' ?>"><?= $b['is_published'] ? 'Terbit' : 'Draf' ?></span></td>
          <td class="text-end text-nowrap">
            <a href="?edit=<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus berita ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$daftar): ?><tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
