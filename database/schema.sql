-- =====================================================
-- Skema Database: Buku Tamu Online + Profil Instansi
-- =====================================================
CREATE DATABASE IF NOT EXISTS buku_tamu
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buku_tamu;

-- Akun admin (password disimpan sebagai hash, BUKAN teks asli)
CREATE TABLE admin (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nama_lengkap  VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seksi / Bidang
CREATE TABLE seksi (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_seksi  VARCHAR(100) NOT NULL,
  deskripsi   VARCHAR(255) NULL,
  urutan      SMALLINT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Pejabat (setiap pejabat milik satu seksi)
CREATE TABLE pejabat (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seksi_id    INT UNSIGNED NOT NULL,
  nama        VARCHAR(100) NOT NULL,
  jabatan     VARCHAR(100) NOT NULL,
  foto        VARCHAR(255) NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pejabat_seksi FOREIGN KEY (seksi_id)
    REFERENCES seksi(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Data tamu
CREATE TABLE tamu (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  no_hp         VARCHAR(20)  NOT NULL,
  instansi      VARCHAR(150) NOT NULL,
  jumlah_orang  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  seksi_id      INT UNSIGNED NOT NULL,
  pejabat_id    INT UNSIGNED NULL,
  keperluan     TEXT NOT NULL,
  waktu_masuk   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  waktu_keluar  DATETIME NULL,
  status        ENUM('di_dalam','selesai') NOT NULL DEFAULT 'di_dalam',
  INDEX idx_waktu_masuk (waktu_masuk),
  INDEX idx_status (status),
  CONSTRAINT fk_tamu_seksi FOREIGN KEY (seksi_id)
    REFERENCES seksi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tamu_pejabat FOREIGN KEY (pejabat_id)
    REFERENCES pejabat(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Slide / banner beranda
CREATE TABLE slide (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul       VARCHAR(150) NOT NULL,
  keterangan  VARCHAR(255) NULL,
  gambar      VARCHAR(255) NOT NULL,
  urutan      SMALLINT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Berita & pengumuman
CREATE TABLE berita (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul         VARCHAR(200) NOT NULL,
  isi           TEXT NOT NULL,
  jenis         ENUM('berita','pengumuman') NOT NULL DEFAULT 'pengumuman',
  gambar        VARCHAR(255) NULL,
  is_published  TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_publish (is_published, created_at)
) ENGINE=InnoDB;

-- Data contoh
INSERT INTO seksi (nama_seksi, deskripsi, urutan) VALUES
 ('Sekretariat', 'Administrasi umum dan kepegawaian', 1),
 ('Bidang Pelayanan', 'Pelayanan publik dan pengaduan', 2),
 ('Bidang Perencanaan', 'Perencanaan dan evaluasi program', 3);

INSERT INTO pejabat (seksi_id, nama, jabatan) VALUES
 (1, 'Drs. Budi Santoso, M.Si', 'Sekretaris'),
 (2, 'Siti Aminah, S.H., M.H', 'Kepala Bidang Pelayanan'),
 (3, 'Ir. Agus Wibowo, M.T', 'Kepala Bidang Perencanaan');

INSERT INTO berita (judul, isi, jenis) VALUES
 ('Jam Pelayanan Tamu', 'Pelayanan tamu dibuka Senin-Jumat pukul 08.00-15.00 WIB.', 'pengumuman');
-- Akun admin dibuat lewat: php setup_admin.php <username> <password>
