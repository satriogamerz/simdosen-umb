<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

$fields = [
    'nomor_registrasi',
    'nuptk',
    'nama',
    'nip',
    'id_jabatan',
    'id_pendidikan',
    'id_rumpun',
    'id_status_serdos',
    'id_status_pegawai',
    'id_ikatan',
    'id_status_aktivitas',
    'id_penempatan',
    'id_prodi'
];

$d = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        $d[$f] = trim($_POST[$f] ?? '');
    }

    // Validasi kolom wajib
    if ($d['nomor_registrasi'] === '' || $d['nama'] === '' || $d['id_prodi'] === '') {
        $error = 'Nomor Registrasi, Nama Dosen, dan Program Studi wajib diisi.';
    } else {
        // Empat kolom pertama berupa teks, sisanya ID angka.
        $v = [];

        foreach ($fields as $i => $f) {
            if ($i < 4) {
                $v[] = $d[$f] !== '' ? $d[$f] : null;
            } else {
                $v[] = $d[$f] !== '' ? (int)$d[$f] : null;
            }
        }

        $sql = "INSERT INTO dosen (
            nomor_registrasi,
            nuptk,
            nama,
            nip,
            id_jabatan,
            id_pendidikan,
            id_rumpun,
            id_status_serdos,
            id_status_pegawai,
            id_ikatan,
            id_status_aktivitas,
            id_penempatan,
            id_prodi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $conn->prepare($sql);
            $types = 'ssss' . str_repeat('i', 9);
            $stmt->bind_param($types, ...$v);

            if ($stmt->execute()) {
                $idBaru = $stmt->insert_id;

                audit_log(
                    $conn,
                    (int)$_SESSION['user_id'],
                    'dosen',
                    (string)$idBaru,
                    'INSERT'
                );

                header('Location: index.php');
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $error = 'Nomor Registrasi, NUPTK, atau NIP sudah digunakan.';
            } else {
                $error = 'Data gagal disimpan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dosen - SIMDOSEN</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .06);
        }

        h2 {
            margin-top: 0;
            color: #1769aa;
        }

        .error {
            padding: 12px;
            color: #a61b1b;
            background: #fdecec;
            border-radius: 6px;
            overflow-wrap: anywhere;
        }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #1769aa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="container">
        <a class="back" href="index.php">&larr; Kembali ke Daftar Dosen</a>

        <h2>Tambah Data Dosen</h2>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php include __DIR__ . '/form_fields.php'; ?>
        </form>
    </main>
</body>
</html>
```
