<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'OPERATOR'], true)) {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk Admin atau Operator.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$keyword = trim($_GET['q'] ?? '');
$id_prodi = trim($_GET['prodi'] ?? '');
$id_status = trim($_GET['status'] ?? '');

$programStudi = [];
$resultProdi = $conn->query(
    "SELECT id_prodi, nama_prodi
     FROM program_studi
     ORDER BY nama_prodi"
);
while ($row = $resultProdi->fetch_assoc()) {
    $programStudi[] = $row;
}

$statusPegawai = [];
$resultStatus = $conn->query(
    "SELECT id_status_pegawai, nama_status
     FROM status_pegawai
     ORDER BY nama_status"
);
while ($row = $resultStatus->fetch_assoc()) {
    $statusPegawai[] = $row;
}

$sql = "SELECT
            d.id_dosen,
            d.nomor_registrasi,
            d.nuptk,
            d.nama,
            d.nip,
            p.nama_prodi,
            sp.nama_status AS status_pegawai
        FROM dosen d
        LEFT JOIN program_studi p ON d.id_prodi = p.id_prodi
        LEFT JOIN status_pegawai sp
            ON d.id_status_pegawai = sp.id_status_pegawai
        WHERE 1=1";

$types = '';
$params = [];

if ($keyword !== '') {
    $sql .= " AND (
        d.nama LIKE ?
        OR d.nomor_registrasi LIKE ?
        OR d.nuptk LIKE ?
        OR d.nip LIKE ?
    )";
    $cari = '%' . $keyword . '%';
    $types .= 'ssss';
    array_push($params, $cari, $cari, $cari, $cari);
}

if ($id_prodi !== '' && ctype_digit($id_prodi)) {
    $sql .= " AND d.id_prodi = ?";
    $types .= 'i';
    $params[] = (int)$id_prodi;
}

if ($id_status !== '' && ctype_digit($id_status)) {
    $sql .= " AND d.id_status_pegawai = ?";
    $types .= 'i';
    $params[] = (int)$id_status;
}

$sql .= " ORDER BY d.nama ASC";

$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$dataDosen = $stmt->get_result();

$totalDosen = 0;
$rekapProdi = [];
$rekapStatus = [];

foreach ($programStudi as $prodi) {
    $rekapProdi[(int)$prodi['id_prodi']] = [
        'nama' => $prodi['nama_prodi'],
        'jumlah' => 0
    ];
}

foreach ($statusPegawai as $status) {
    $rekapStatus[(int)$status['id_status_pegawai']] = [
        'nama' => $status['nama_status'],
        'jumlah' => 0
    ];
}

while ($row = $dataDosen->fetch_assoc()) {
    $totalDosen++;

    $idP = (int)($row['id_prodi'] ?? 0);
    $idS = (int)($row['id_status_pegawai'] ?? 0);

    if (isset($rekapProdi[$idP])) {
        $rekapProdi[$idP]['jumlah']++;
    }

    if (isset($rekapStatus[$idS])) {
        $rekapStatus[$idS]['jumlah']++;
    }
}

/*
 * Query di atas belum menyertakan id_prodi dan id_status_pegawai
 * untuk keperluan rekap, sehingga kita hitung ulang dari database
 * menggunakan filter yang sama.
 */
$sqlRekap = "SELECT d.id_prodi, d.id_status_pegawai
             FROM dosen d
             WHERE 1=1";

$typesRekap = '';
$paramsRekap = [];

if ($keyword !== '') {
    $sqlRekap .= " AND (
        d.nama LIKE ?
        OR d.nomor_registrasi LIKE ?
        OR d.nuptk LIKE ?
        OR d.nip LIKE ?
    )";
    $cariRekap = '%' . $keyword . '%';
    $typesRekap .= 'ssss';
    array_push(
        $paramsRekap,
        $cariRekap,
        $cariRekap,
        $cariRekap,
        $cariRekap
    );
}

if ($id_prodi !== '' && ctype_digit($id_prodi)) {
    $sqlRekap .= " AND d.id_prodi = ?";
    $typesRekap .= 'i';
    $paramsRekap[] = (int)$id_prodi;
}

if ($id_status !== '' && ctype_digit($id_status)) {
    $sqlRekap .= " AND d.id_status_pegawai = ?";
    $typesRekap .= 'i';
    $paramsRekap[] = (int)$id_status;
}

$stmtRekap = $conn->prepare($sqlRekap);

if ($typesRekap !== '') {
    $stmtRekap->bind_param($typesRekap, ...$paramsRekap);
}

$stmtRekap->execute();
$hasilRekap = $stmtRekap->get_result();

$totalDosen = 0;
$rekapProdi = [];
$rekapStatus = [];

while ($row = $hasilRekap->fetch_assoc()) {
    $totalDosen++;

    $idP = (int)$row['id_prodi'];
    $idS = (int)($row['id_status_pegawai'] ?? 0);

    if (!isset($rekapProdi[$idP])) {
        $rekapProdi[$idP] = [
            'nama' => 'Program Studi tidak diketahui',
            'jumlah' => 0
        ];
    }
    $rekapProdi[$idP]['jumlah']++;

    if (!isset($rekapStatus[$idS])) {
        $rekapStatus[$idS] = [
            'nama' => 'Status tidak diketahui',
            'jumlah' => 0
        ];
    }
    $rekapStatus[$idS]['jumlah']++;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sqlExport = "SELECT
                    d.nomor_registrasi,
                    d.nuptk,
                    d.nama,
                    d.nip,
                    p.nama_prodi,
                    sp.nama_status AS status_pegawai
                  FROM dosen d
                  LEFT JOIN program_studi p
                    ON d.id_prodi = p.id_prodi
                  LEFT JOIN status_pegawai sp
                    ON d.id_status_pegawai = sp.id_status_pegawai
                  WHERE 1=1";

    $typesExport = '';
    $paramsExport = [];

    if ($keyword !== '') {
        $sqlExport .= " AND (
            d.nama LIKE ?
            OR d.nomor_registrasi LIKE ?
            OR d.nuptk LIKE ?
            OR d.nip LIKE ?
        )";
        $cariExport = '%' . $keyword . '%';
        $typesExport .= 'ssss';
        array_push(
            $paramsExport,
            $cariExport,
            $cariExport,
            $cariExport,
            $cariExport
        );
    }

    if ($id_prodi !== '' && ctype_digit($id_prodi)) {
        $sqlExport .= " AND d.id_prodi = ?";
        $typesExport .= 'i';
        $paramsExport[] = (int)$id_prodi;
    }

    if ($id_status !== '' && ctype_digit($id_status)) {
        $sqlExport .= " AND d.id_status_pegawai = ?";
        $typesExport .= 'i';
        $paramsExport[] = (int)$id_status;
    }

    $sqlExport .= " ORDER BY d.nama ASC";
    $stmtExport = $conn->prepare($sqlExport);

    if ($typesExport !== '') {
        $stmtExport->bind_param($typesExport, ...$paramsExport);
    }

    $stmtExport->execute();
    $hasilExport = $stmtExport->get_result();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan_dosen.csv"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Nomor Registrasi',
        'NUPTK',
        'Nama Dosen',
        'NIP',
        'Program Studi',
        'Status Pegawai'
    ], ';');

    while ($row = $hasilExport->fetch_assoc()) {
        fputcsv($output, [
            $row['nomor_registrasi'],
            $row['nuptk'],
            $row['nama'],
            $row['nip'],
            $row['nama_prodi'],
            $row['status_pegawai']
        ], ';');
    }

    fclose($output);
    exit;
}

/* Ambil ulang daftar dosen setelah proses rekap */
$sqlDaftar = "SELECT
                d.nomor_registrasi,
                d.nuptk,
                d.nama,
                d.nip,
                p.nama_prodi,
                sp.nama_status AS status_pegawai
              FROM dosen d
              LEFT JOIN program_studi p ON d.id_prodi = p.id_prodi
              LEFT JOIN status_pegawai sp
                ON d.id_status_pegawai = sp.id_status_pegawai
              WHERE 1=1";

$typesDaftar = '';
$paramsDaftar = [];

if ($keyword !== '') {
    $sqlDaftar .= " AND (
        d.nama LIKE ?
        OR d.nomor_registrasi LIKE ?
        OR d.nuptk LIKE ?
        OR d.nip LIKE ?
    )";
    $cariDaftar = '%' . $keyword . '%';
    $typesDaftar .= 'ssss';
    array_push(
        $paramsDaftar,
        $cariDaftar,
        $cariDaftar,
        $cariDaftar,
        $cariDaftar
    );
}

if ($id_prodi !== '' && ctype_digit($id_prodi)) {
    $sqlDaftar .= " AND d.id_prodi = ?";
    $typesDaftar .= 'i';
    $paramsDaftar[] = (int)$id_prodi;
}

if ($id_status !== '' && ctype_digit($id_status)) {
    $sqlDaftar .= " AND d.id_status_pegawai = ?";
    $typesDaftar .= 'i';
    $paramsDaftar[] = (int)$id_status;
}

$sqlDaftar .= " ORDER BY d.nama ASC";
$stmtDaftar = $conn->prepare($sqlDaftar);

if ($typesDaftar !== '') {
    $stmtDaftar->bind_param($typesDaftar, ...$paramsDaftar);
}

$stmtDaftar->execute();
$dataDosen = $stmtDaftar->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laporan SIMDOSEN</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f3f6fb;
    color: #243047;
}
.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}
.header {
    background: #173b68;
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
}
.header h1 { margin: 0 0 8px; }
.header p { margin: 0; }
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px #0000000d;
}
.card strong {
    display: block;
    font-size: 27px;
    margin-top: 10px;
    color: #173b68;
}
.panel {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px #0000000d;
}
.filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    align-items: end;
}
label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}
input, select {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}
.btn {
    display: inline-block;
    padding: 10px 14px;
    border: 0;
    border-radius: 6px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}
.btn.secondary { background: #526277; }
.btn.green { background: #198754; }
.btn.light { background: #e5edf6; color: #173b68; }
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}
.table-wrap { overflow-x: auto; }
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #e5eaf1;
    text-align: left;
}
th { background: #edf3fa; }
.muted { color: #64748b; font-size: 14px; }
@media (max-width: 600px) {
    .container { padding: 12px; margin: 12px auto; }
    .header, .panel { padding: 15px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Laporan SIMDOSEN</h1>
        <p>Laporan dan rekapitulasi data dosen.</p>
    </div>

    <div class="actions" style="margin-bottom:20px">
        <a class="btn secondary" href="/simdosen_tahap5/dashboard.php">Kembali ke Dashboard</a>
        <a class="btn light" href="/simdosen_tahap5/admin/dosen/index.php">Kelola Data Dosen</a>
    </div>

    <div class="cards">
        <div class="card">
            Total Dosen
            <strong><?= number_format($totalDosen) ?></strong>
            <div class="muted">Sesuai filter saat ini</div>
        </div>
        <div class="card">
            Jumlah Program Studi
            <strong><?= count($rekapProdi) ?></strong>
            <div class="muted">Program studi pada rekap</div>
        </div>
        <div class="card">
            Jumlah Status Pegawai
            <strong><?= count($rekapStatus) ?></strong>
            <div class="muted">Status pada rekap</div>
        </div>
    </div>

    <div class="panel">
        <h2>Filter Laporan</h2>
        <form method="get">
            <div class="filters">
                <div>
                    <label for="q">Cari Dosen</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        placeholder="Nama, NIP, NUPTK..."
                        value="<?= e($keyword) ?>">
                </div>

                <div>
                    <label for="prodi">Program Studi</label>
                    <select id="prodi" name="prodi">
                        <option value="">Semua Program Studi</option>
                        <?php foreach ($programStudi as $p): ?>
                            <option
                                value="<?= (int)$p['id_prodi'] ?>"
                                <?= $id_prodi === (string)$p['id_prodi'] ? 'selected' : '' ?>>
                                <?= e($p['nama_prodi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="status">Status Pegawai</label>
                    <select id="status" name="status">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusPegawai as $s): ?>
                            <option
                                value="<?= (int)$s['id_status_pegawai'] ?>"
                                <?= $id_status === (string)$s['id_status_pegawai'] ? 'selected' : '' ?>>
                                <?= e($s['nama_status']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button class="btn" type="submit">Terapkan Filter</button>
                </div>
            </div>

            <div class="actions">
                <a class="btn secondary"
                   href="/simdosen_tahap5/admin/laporan/index.php">
                    Reset Filter
                </a>
                <button class="btn green" type="submit" name="export" value="csv">
                    Ekspor CSV
                </button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>Rekap Berdasarkan Program Studi</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Program Studi</th><th>Jumlah Dosen</th></tr>
                </thead>
                <tbody>
                <?php if (!$rekapProdi): ?>
                    <tr><td colspan="2">Belum ada data.</td></tr>
                <?php else: ?>
                    <?php foreach ($rekapProdi as $r): ?>
                        <tr>
                            <td><?= e($r['nama']) ?></td>
                            <td><?= (int)$r['jumlah'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Rekap Berdasarkan Status Pegawai</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Status Pegawai</th><th>Jumlah Dosen</th></tr>
                </thead>
                <tbody>
                <?php if (!$rekapStatus): ?>
                    <tr><td colspan="2">Belum ada data.</td></tr>
                <?php else: ?>
                    <?php foreach ($rekapStatus as $r): ?>
                        <tr>
                            <td><?= e($r['nama']) ?></td>
                            <td><?= (int)$r['jumlah'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Daftar Data Dosen</h2>
        <p class="muted">Data mengikuti filter yang dipilih di atas.</p>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Nomor Registrasi</th>
                    <th>NUPTK</th>
                    <th>Nama Dosen</th>
                    <th>NIP</th>
                    <th>Program Studi</th>
                    <th>Status Pegawai</th>
                </tr>
                </thead>
                <tbody>
                <?php $no = 1; ?>
                <?php while ($d = $dataDosen->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($d['nomor_registrasi']) ?></td>
                        <td><?= e($d['nuptk']) ?></td>
                        <td><?= e($d['nama']) ?></td>
                        <td><?= e($d['nip']) ?></td>
                        <td><?= e($d['nama_prodi']) ?></td>
                        <td><?= e($d['status_pegawai']) ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($no === 1): ?>
                    <tr><td colspan="7">Data dosen tidak ditemukan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>