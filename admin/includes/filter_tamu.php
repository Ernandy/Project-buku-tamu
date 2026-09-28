<?php
declare(strict_types=1);

/**
 * filter_tamu.php - Logika filter yang dipakai bersama oleh
 * buku_tamu.php, export_excel.php, dan cetak.php.
 * Semua input divalidasi, lalu dikirim ke query lewat parameter (aman dari SQL Injection).
 */

/** Ambil & validasi filter dari $_GET atau $_POST. */
function ambil_filter(array $src): array
{
    $tanggalValid = static function ($v): bool {
        if (!is_string($v)) return false;
        $d = DateTime::createFromFormat('Y-m-d', $v);
        return $d && $d->format('Y-m-d') === $v;
    };

    $dari   = $tanggalValid($src['dari'] ?? null)   ? $src['dari']   : date('Y-m-d');
    $sampai = $tanggalValid($src['sampai'] ?? null) ? $src['sampai'] : date('Y-m-d');
    if ($sampai < $dari) {
        [$dari, $sampai] = [$sampai, $dari];
    }

    $status = in_array($src['status'] ?? '', ['di_dalam', 'selesai'], true) ? $src['status'] : '';
    $q      = mb_substr(trim((string) ($src['q'] ?? '')), 0, 100);

    return ['dari' => $dari, 'sampai' => $sampai, 'status' => $status, 'q' => $q];
}

/** Bangun bagian FROM...WHERE beserta parameternya. */
function bangun_query(array $f): array
{
    $sql = ' FROM tamu t
             JOIN seksi s ON s.id = t.seksi_id
             LEFT JOIN pejabat p ON p.id = t.pejabat_id
             WHERE t.waktu_masuk >= :dari
               AND t.waktu_masuk < DATE_ADD(:sampai, INTERVAL 1 DAY)';
    $params = [':dari' => $f['dari'], ':sampai' => $f['sampai']];

    if ($f['status'] !== '') {
        $sql .= ' AND t.status = :status';
        $params[':status'] = $f['status'];
    }
    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';   // escape wildcard LIKE
        $sql .= ' AND (t.nama LIKE :q1 OR t.instansi LIKE :q2)';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
    }
    return [$sql, $params];
}

function tanggal_id(?string $waktu): string
{
    return $waktu ? date('d/m/Y H:i', strtotime($waktu)) : '-';
}
