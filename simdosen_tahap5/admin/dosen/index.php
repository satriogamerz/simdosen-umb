<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

// Hak akses pengelolaan data
$can = in_array(
    $_SESSION['role'] ?? '',
    ['ADMIN', 'OPERATOR'],
    true
);

// Token keamanan untuk formulir hapus
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$q = trim($_GET['q'] ?? '');

$sql = "SELECT
            d.id_dosen,
            d.nomor_registrasi,
            d.nuptk,
            d.nama,
            d.nip,
            p.nama_prodi
        FROM dosen d
        LEFT JOIN program_studi p
            ON p.id_prodi = d.id_prodi";

if ($q !== '') {
    $sql .= " WHERE d.nama LIKE CONCAT('%', ?, '%')
              OR d.nip LIKE CONCAT('%', ?, '%')
              OR d.nuptk LIKE CONCAT('%', ?, '%')
              OR d.nomor_registrasi LIKE CONCAT('%', ?, '%')";

    $stmt = $conn->prepare($sql . " ORDER BY d.nama");

    if (!$stmt) {
        die('Query gagal: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param('ssss', $q, $q, $q, $q);
    $stmt->execute();
    $rows = $stmt->get_result();
} else {
    $rows = $conn->query($sql . " ORDER BY d.nama");

    if (!$rows) {
        die('Query gagal: ' . htmlspecialchars($conn->error));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Dosen - SIMDOSEN</title>

<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;color:#222}
.nav{background:#1769aa;color:white;padding:18px 5%}
.wrap{max-width:1100px;margin:25px auto;padding:0 15px}
.panel{background:white;padding:20px;border-radius:12px;overflow-x:auto}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.btn{display:inline-block;background:#1769aa;color:white;padding:9px 12px;border:0;border-radius:6px;text-decoration:none;cursor:pointer;font-size:13px}
.btn:hover{opacity:.85}
.btn-hapus{background:#c62828}
input{padding:10px;border:1px solid #ccc;border-radius:5px}
table{border-collapse:collapse;width:100%;margin-top:18px;font-size:13px}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;white-space:nowrap}
th{background:#edf3f8}
.alert{padding:12px;border-radius:6px;margin:15px 0}
.sukses{background:#e5f6e9;color:#17652b}
.gagal{background:#fdecea;color:#a61b1b}
form.hapus-form{display:inline}
</style>
</head>

<body>
<div class="nav"><b>SIMDOSEN — Data Dosen</b></div>

<main class="wrap">
<div class="panel">

    <div class="top">
        <div>
            <a class="btn" href="../../dashboard.php">Dashboard</a>

            <?php if ($can): ?>
                <a class="btn" href="tambah.php">+ Tambah Dosen</a> <a class="btn" href="export.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">
    Export Excel
</a> <a class="btn" href="import.php">Import CSV</a>
            <?php endif; ?>
        </div>

        <form method="get">
            <input
                name="q"
                value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Cari nama, NIP, NUPTK"
            >
            <button class="btn" type="submit">Cari</button>
            <?php if ($q !== ''): ?>
                <a class="btn" href="index.php">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Daftar Dosen</h2>

    <?php if (($_GET['hapus'] ?? '') === 'berhasil'): ?>
        <div class="alert sukses">Data dosen berhasil dihapus.</div>
    <?php elseif (($_GET['hapus'] ?? '') === 'gagal'): ?>
        <div class="alert gagal">
            Data dosen gagal dihapus. Data mungkin masih digunakan oleh tabel lain.
        </div>
    <?php endif; ?>

    <table>
        <thead>
        <tr>
            <th>No.</th>
            <th>Nomor Registrasi</th>
            <th>NUPTK</th>
            <th>Nama Dosen</th>
            <th>NIP</th>
            <th>Program Studi</th>
            <th>Aksi</th>
        </tr>
        </thead>

        <tbody>
        <?php if ($rows->num_rows > 0): ?>
            <?php $no = 1; ?>

            <?php while ($r = $rows->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nomor_registrasi'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['nuptk'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['nip'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['nama_prodi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>

                <td>
                    <a class="btn"
                       href="detail.php?id=<?= (int)$r['id_dosen'] ?>">
                        Detail
                    </a>

                    <?php if ($can): ?>
                        <a class="btn"
                           href="edit.php?id=<?= (int)$r['id_dosen'] ?>">
                            Edit
                        </a>

                        <form
                            class="hapus-form"
                            method="post"
                            action="hapus.php"
                            onsubmit="return confirm('Yakin ingin menghapus data dosen ini? Data yang dihapus tidak dapat dikembalikan.');"
                        >
                            <input type="hidden" name="id_dosen"
                                   value="<?= (int)$r['id_dosen'] ?>">

                            <input type="hidden" name="csrf_token"
                                   value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                            <button class="btn btn-hapus" type="submit">
                                Hapus
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>

        <?php else: ?>
            <tr>
                <td colspan="7">Data dosen belum tersedia.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>
</main>

<div style="max-width:1200px; margin:20px auto; padding:20px;">
    <a href="/simdosen_tahap5/admin/laporan/index.php"
       class="btn"
       style="display:inline-block;">
        ← Kembali ke Laporan SIMDOSEN
    </a>
</div>

</body>
</html>