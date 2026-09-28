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
            $id         = (int) ($_POST['id'] ?? 0);
            $judul      = trim($_POST['judul'] ?? '');
            $keterangan = trim($_POST['keterangan'] ?? '');
            $urutan     = (int) ($_POST['urutan'] ?? 0);
            $aktif      = isset($_POST['is_active']) ? 1 : 0;

            if ($judul === '' || mb_strlen($judul) > 150 || mb_strlen($keterangan) > 255) {
                throw new RuntimeException('Judul wajib diisi (maks. 150 karakter), keterangan maks. 255 karakter.');
            }

            $gambarBaru = null;
            if (!empty($_FILES['gambar']['name'])) {
                $gambarBaru = upload_gambar($_FILES['gambar'], 'slide', 3145728); // maks. 3 MB
            }

            if ($id > 0) {                                   // UPDATE
                $lama = $pdo->prepare('SELECT gambar FROM slide WHERE id = ?');
                $lama->execute([$id]);
                $gambarLama = $lama->fetchColumn();

                $pdo->prepare(
                    'UPDATE slide SET judul = :j, keterangan = :k, urutan = :u,
                            is_active = :a, gambar = COALESCE(:g, gambar) WHERE id = :id'
                )->execute([':j' => $judul, ':k' => $keterangan, ':u' => $urutan,
                            ':a' => $aktif, ':g' => $gambarBaru, ':id' => $id]);
                if ($gambarBaru) {
                    hapus_gambar($gambarLama ?: null, 'slide');
                }
                set_flash('success', 'Slide berhasil diperbarui.');
            } else {                                         // CREATE (gambar wajib)
                if (!$gambarBaru) {
                    throw new RuntimeException('Gambar slide wajib diunggah.');
                }
                $pdo->prepare(
                    'INSERT INTO slide (judul, keterangan, gambar, urutan, is_active)
                     VALUES (:j, :k, :g, :u, :a)'
                )->execute([':j' => $judul, ':k' => $keterangan, ':g' => $gambarBaru,
                            ':u' => $urutan, ':a' => $aktif]);
                set_flash('success', 'Slide baru berhasil ditambahkan.');
            }
        } elseif ($aksi === 'hapus') {                       // DELETE
            $id = (int) ($_POST['id'] ?? 0);
            $lama = $pdo->prepare('SELECT gambar FROM slide WHERE id = ?');
            $lama->execute([$id]);
            $gambar = $lama->fetchColumn();

            $pdo->prepare('DELETE FROM slide WHERE id = ?')->execute([$id]);
            hapus_gambar($gambar ?: null, 'slide');
            set_flash('success', 'Slide berhasil dihapus.');
        }
    } catch (RuntimeException $e) {
        set_flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        error_log($e->getMessage());
        set_flash('danger', 'Terjadi kesalahan pada database.');
    }
    redirect(BASE_URL . '/admin/kelola_slide.php');
}

/* ==================== TAMPILAN ==================== */
$slides = $pdo->query('SELECT * FROM slide ORDER BY urutan, id DESC')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM slide WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$judul = 'Kelola Slide';
require __DIR__ . '/includes/header.php';
?>

<h3 class="section-title">Kelola Slide Beranda</h3>

<div class="card shadow-sm mb-4">
  <div class="card-header"><?= $edit ? 'Ubah Slide' : 'Tambah Slide' ?></div>
  <form method="post" enctype="multipart/form-data" class="card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Judul</label>
        <input name="judul" class="form-control" maxlength="150" required value="<?= e($edit['judul'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Keterangan (opsional)</label>
        <input name="keterangan" class="form-control" maxlength="255" value="<?= e($edit['keterangan'] ?? '') ?>">
      </div>
      <div class="col-md-5">
        <label class="form-label">Gambar (JPG/PNG/WEBP, maks. 3 MB)<?= $edit ? ' - kosongkan jika tidak diganti' : '' ?></label>
        <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/png,image/webp" <?= $edit ? '' : 'required' ?>>
      </div>
      <div class="col-md-3">
        <label class="form-label">Urutan</label>
        <input type="number" name="urutan" class="form-control" value="<?= (int) ($edit['urutan'] ?? 0) ?>">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="aktif"
                 <?= !$edit || !empty($edit['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="aktif">Tampilkan di beranda</label>
        </div>
      </div>
      <div class="col-12 text-end">
        <?php if ($edit): ?><a href="kelola_slide.php" class="btn btn-outline-secondary">Batal</a><?php endif; ?>
        <button class="btn btn-instansi">Simpan</button>
      </div>
    </div>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle bg-white shadow-sm">
    <thead class="table-light">
      <tr><th>Gambar</th><th>Judul</th><th>Urutan</th><th>Status</th><th class="text-end">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($slides as $s): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/assets/uploads/slide/<?= e($s['gambar']) ?>" alt="" style="height:50px;width:90px;object-fit:cover" class="rounded"></td>
          <td><?= e($s['judul']) ?><br><small class="text-muted"><?= e($s['keterangan']) ?></small></td>
          <td><?= (int) $s['urutan'] ?></td>
          <td><span class="badge <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td class="text-end text-nowrap">
            <a href="?edit=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus slide ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$slides): ?><tr><td colspan="5" class="text-center text-muted">Belum ada slide.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
