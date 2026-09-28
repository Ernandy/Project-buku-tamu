<?php
declare(strict_types=1);
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../config/helpers.php';
require_login();                                   // hanya admin yang boleh masuk

/* ==================== PROSES (CREATE / UPDATE / DELETE) ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan') {
            $id       = (int) ($_POST['id'] ?? 0);
            $nama     = trim($_POST['nama'] ?? '');
            $jabatan  = trim($_POST['jabatan'] ?? '');
            $seksi_id = (int) ($_POST['seksi_id'] ?? 0);
            $aktif    = isset($_POST['is_active']) ? 1 : 0;

            // Validasi input
            $cek = $pdo->prepare('SELECT COUNT(*) FROM seksi WHERE id = ?');
            $cek->execute([$seksi_id]);
            if ($nama === '' || $jabatan === '' || !$cek->fetchColumn()) {
                throw new RuntimeException('Nama, jabatan, dan seksi wajib diisi dengan benar.');
            }

            // Foto (opsional)
            $fotoBaru = null;
            if (!empty($_FILES['foto']['name'])) {
                $fotoBaru = upload_gambar($_FILES['foto'], 'pejabat');
            }

            if ($id > 0) {                          // UPDATE
                $lama = $pdo->prepare('SELECT foto FROM pejabat WHERE id = ?');
                $lama->execute([$id]);
                $fotoLama = $lama->fetchColumn();

                $stmt = $pdo->prepare(
                    'UPDATE pejabat
                        SET nama = :nama, jabatan = :jabatan, seksi_id = :seksi,
                            is_active = :aktif, foto = COALESCE(:foto, foto)
                      WHERE id = :id'
                );
                $stmt->execute([
                    ':nama' => $nama, ':jabatan' => $jabatan, ':seksi' => $seksi_id,
                    ':aktif' => $aktif, ':foto' => $fotoBaru, ':id' => $id,
                ]);
                if ($fotoBaru) {
                    hapus_gambar($fotoLama ?: null, 'pejabat');
                }
                set_flash('success', 'Data pejabat berhasil diperbarui.');
            } else {                                // CREATE
                $stmt = $pdo->prepare(
                    'INSERT INTO pejabat (seksi_id, nama, jabatan, foto, is_active)
                     VALUES (:seksi, :nama, :jabatan, :foto, :aktif)'
                );
                $stmt->execute([
                    ':seksi' => $seksi_id, ':nama' => $nama, ':jabatan' => $jabatan,
                    ':foto' => $fotoBaru, ':aktif' => $aktif,
                ]);
                set_flash('success', 'Pejabat baru berhasil ditambahkan.');
            }
        } elseif ($aksi === 'hapus') {              // DELETE
            $id = (int) ($_POST['id'] ?? 0);
            $lama = $pdo->prepare('SELECT foto FROM pejabat WHERE id = ?');
            $lama->execute([$id]);
            $foto = $lama->fetchColumn();

            $pdo->prepare('DELETE FROM pejabat WHERE id = ?')->execute([$id]);
            hapus_gambar($foto ?: null, 'pejabat');
            set_flash('success', 'Data pejabat berhasil dihapus.');
        }
    } catch (RuntimeException $e) {
        set_flash('danger', $e->getMessage());      // pesan validasi (aman ditampilkan)
    } catch (PDOException $e) {
        error_log($e->getMessage());                // detail error hanya ke log
        set_flash('danger', 'Terjadi kesalahan pada database.');
    }
    redirect(BASE_URL . '/admin/kelola_pejabat.php');  // Pola PRG: cegah submit ganda
}

/* ==================== TAMPILAN (READ) ==================== */
$seksiList = $pdo->query('SELECT id, nama_seksi FROM seksi ORDER BY urutan, nama_seksi')->fetchAll();
$pejabatList = $pdo->query(
    'SELECT p.*, s.nama_seksi FROM pejabat p
       JOIN seksi s ON s.id = p.seksi_id
      ORDER BY s.urutan, p.nama'
)->fetchAll();

// Mode edit: ?edit=ID
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM pejabat WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$judul = 'Kelola Pejabat';
require __DIR__ . '/includes/header.php';
?>

<h3 class="section-title">Kelola Pejabat</h3>

<div class="card shadow-sm mb-4">
  <div class="card-header"><?= $edit ? 'Ubah Pejabat' : 'Tambah Pejabat' ?></div>
  <form method="post" enctype="multipart/form-data" class="card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nama</label>
        <input name="nama" class="form-control" maxlength="100" required value="<?= e($edit['nama'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Jabatan</label>
        <input name="jabatan" class="form-control" maxlength="100" required value="<?= e($edit['jabatan'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Seksi / Bidang</label>
        <select name="seksi_id" class="form-select" required>
          <option value="">-- Pilih --</option>
          <?php foreach ($seksiList as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= ($edit['seksi_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
              <?= e($s['nama_seksi']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Foto (JPG/PNG/WEBP, maks. 2 MB)</label>
        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="aktif"
                 <?= !$edit || !empty($edit['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="aktif">Tampilkan di halaman publik</label>
        </div>
      </div>
      <div class="col-12 text-end">
        <?php if ($edit): ?><a href="kelola_pejabat.php" class="btn btn-outline-secondary">Batal</a><?php endif; ?>
        <button class="btn btn-instansi">Simpan</button>
      </div>
    </div>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle bg-white shadow-sm">
    <thead class="table-light">
      <tr><th>Foto</th><th>Nama</th><th>Jabatan</th><th>Seksi</th><th>Status</th><th class="text-end">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($pejabatList as $p): ?>
        <tr>
          <td>
            <?php if ($p['foto']): ?>
              <img src="<?= BASE_URL ?>/assets/uploads/pejabat/<?= e($p['foto']) ?>" class="avatar" alt="">
            <?php else: ?>
              <span class="avatar avatar-placeholder"><?= e(mb_substr($p['nama'], 0, 1)) ?></span>
            <?php endif; ?>
          </td>
          <td><?= e($p['nama']) ?></td>
          <td><?= e($p['jabatan']) ?></td>
          <td><?= e($p['nama_seksi']) ?></td>
          <td><span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td class="text-end">
            <a href="?edit=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus pejabat ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$pejabatList): ?>
        <tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
