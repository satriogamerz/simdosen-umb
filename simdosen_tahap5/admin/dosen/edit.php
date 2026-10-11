
<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    exit('ID dosen tidak valid.');
}

$stmt = $conn->prepare("SELECT * FROM dosen WHERE id_dosen = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();

if (!$d) {
    exit('Data dosen tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'nomor_registrasi', 'nuptk', 'nama', 'nip',
        'id_jabatan', 'id_pendidikan', 'id_rumpun',
        'id_status_serdos', 'id_status_pegawai', 'id_ikatan',
        'id_status_aktivitas', 'id_penempatan', 'id_prodi'
    ];

    foreach ($fields as $field) {
        $d[$field] = trim((string)($_POST[$field] ?? ''));
    }

    $nomor = $d['nomor_registrasi'];
    $nuptk = $d['nuptk'];
    $nama = $d['nama'];
    $nip = $d['nip'];

    $jabatan = (int)$d['id_jabatan'];
    $pendidikan = (int)$d['id_pendidikan'];
    $rumpun = (int)$d['id_rumpun'];
    $serdos = (int)$d['id_status_serdos'];
    $pegawai = (int)$d['id_status_pegawai'];
    $ikatan = (int)$d['id_ikatan'];
    $aktivitas = (int)$d['id_status_aktivitas'];
    $penempatan = (int)$d['id_penempatan'];
    $prodi = (int)$d['id_prodi'];

    $sql = "UPDATE dosen SET
        nomor_registrasi = ?,
        nuptk = NULLIF(?, ''),
        nama = ?,
        nip = NULLIF(?, ''),
        id_jabatan = NULLIF(?, 0),
        id_pendidikan = NULLIF(?, 0),
        id_rumpun = NULLIF(?, 0),
        id_status_serdos = NULLIF(?, 0),
        id_status_pegawai = NULLIF(?, 0),
        id_ikatan = NULLIF(?, 0),
        id_status_aktivitas = NULLIF(?, 0),
        id_penempatan = NULLIF(?, 0),
        id_prodi = ?
        WHERE id_dosen = ?";

    $stmt = $conn->prepare($sql);

    // 4 variabel teks + 10 variabel angka = 14 variabel
    $stmt->bind_param(
        'ssssiiiiiiiiii',
        $nomor,
        $nuptk,
        $nama,
        $nip,
        $jabatan,
        $pendidikan,
        $rumpun,
        $serdos,
        $pegawai,
        $ikatan,
        $aktivitas,
        $penempatan,
        $prodi,
        $id
    );

    if ($stmt->execute()) {
        if (function_exists('audit_log')) {
            audit_log(
                $conn,
                $_SESSION['user_id'],
                'dosen',
                $id,
                'UPDATE'
            );
        }

        header('Location: index.php');
        exit;
    } else {
        $error = $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Dosen - SIMDOSEN</title>
</head>
<body style="font-family:Arial;background:#f4f7fb">
<main style="max-width:900px;margin:25px auto;background:white;padding:24px">
    <h2>Edit Data Dosen</h2>

    <?php if ($error !== ''): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?php require __DIR__ . '/form_fields.php'; ?>
    </form>
</main>
</body>
</html>