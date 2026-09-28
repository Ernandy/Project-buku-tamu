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
            $id        = (int) ($_POST['id'] ?? 0);
            $nama      = trim($_POST['nama_seksi'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $urutan    = (int) ($_POST['urutan'] ?? 0);

            if ($nama === '' || mb_strlen($nama) > 100 || mb_strlen($deskripsi) > 255) {
                throw new RuntimeException('Nama seksi wajib diisi (maks. 100 karakter), deskripsi maks. 255 karakter.');
            }

            if ($id > 0) {
                $pdo->prepare('UPDATE seksi SET nama_seksi = ?, deskripsi = ?, urutan = ? WHERE id = ?')
                    ->execute([$nama, $deskripsi ?: null, $urutan, $id]);
                set_flash('success', 'Seksi berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO seksi (nama_seksi, deskripsi, urutan) VALUES (?, ?, ?)')
                    ->execute([$nama, $deskripsi ?: null, $urutan]);
                set_flash('success', 'Seksi berhasil ditambahkan.');
            }
        } elseif ($aksi === 'hapus') {
            $pdo->prepare('DELETE FROM seksi WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            set_flash('success', 'Seksi berhasil dihapus.');
        }
    } catch (RuntimeException $e) {
        set_flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        // Kode 1451 = masih dipakai tabel lain (foreign key RESTRICT)
        if (($e->errorInfo[1] ?? 0) === 1451) {
            set_flash('danger', 'Seksi tidak bisa dihapus karena masih dipakai data pejabat atau tamu.');
        } else {
            error_log($e->getMessage());
            set_flash('danger', 'Terjadi kesalahan pada database.');
        }
    }
    redirect(BASE_URL . '/admin/kelola_seksi.php');
}

/* ==================== TAMPILAN ==================== */
$daftar = $pdo->query(
    'SELECT s.*, COUNT(p.id) AS jml_pejabat
       FROM seksi s LEFT JOIN pejabat p ON p.seksi_id = s.id
      GROUP BY s.id ORDER BY s.urutan, s.nama_seksi'
)->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM seksi WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$judul = 'Kelola Seksi';
require __DIR__ . '/includes/header.php';
?>

<h3 class="section-title">Kelola Seksi / Bidang</h3>

<div class="card shadow-sm mb-4">
  <div class="card-header"><?= $edit ? 'Ubah Seksi' : 'Tambah Seksi' ?></div>
  <form method="post" class="card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nama Seksi</label>
        <input name="nama_seksi" class="form-control" maxlength="100" required value="<?= e($edit['nama_seksi'] ?? '') ?>">
      </div>
      <div class="col-md-5">
        <label class="form-label">Deskripsi (opsional)</label>
        <input name="deskripsi" class="form-control" maxlength="255" value="<?= e($edit['deskripsi'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Urutan</label>
        <input type="number" name="urutan" class="form-control" value="<?= (int) ($edit['urutan'] ?? 0) ?>">
      </div>
      <div class="col-12 text-end">
        <?php if ($edit): ?><a href="kelola_seksi.php" class="btn btn-outline-secondary">Batal</a><?php endif; ?>
        <button class="btn btn-instansi">Simpan</button>
      </div>
    </div>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle bg-white shadow-sm">
    <thead class="table-light">
      <tr><th>Urutan</th><th>Seksi</th><th>Deskripsi</th><th>Pejabat</th><th class="text-end">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($daftar as $s): ?>
        <tr>
          <td><?= (int) $s['urutan'] ?></td>
          <td><?= e($s['nama_seksi']) ?></td>
          <td><?= e($s['deskripsi']) ?></td>
          <td><?= (int) $s['jml_pejabat'] ?></td>
          <td class="text-end text-nowrap">
            <a href="?edit=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus seksi ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$daftar): ?><tr><td colspan="5" class="text-center text-muted">Belum ada seksi.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
