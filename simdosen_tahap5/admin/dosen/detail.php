<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('ID dosen tidak valid.');
}

$sql = "SELECT
            d.id_dosen,
            d.nomor_registrasi,
            d.nuptk,
            d.nama,
            d.nip,
            d.id_prodi,
            p.nama_prodi
        FROM dosen d
        LEFT JOIN program_studi p
            ON p.id_prodi = d.id_prodi
        WHERE d.id_dosen = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit('Query gagal: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$dosen = $result->fetch_assoc();

if (!$dosen) {
    exit('Data dosen tidak ditemukan.');
}

function e($value) {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detail Dosen - SIMDOSEN</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    margin: 0;
    color: #222;
}
header {
    background: #1769aa;
    color: white;
    padding: 18px 5%;
}
main {
    max-width: 800px;
    margin: 25px auto;
    padding: 20px;
    background: white;
    border-radius: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}
td:first-child {
    width: 40%;
    font-weight: bold;
}
.btn {
    display: inline-block;
    padding: 10px 14px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}
</style>
</head>
<body>
<header><b>SIMDOSEN — Detail Dosen</b></header>
<main>
    <h2>Informasi Dosen</h2>

    <table>
        <tr>
            <td>Nomor Registrasi</td>
            <td><?= e($dosen['nomor_registrasi']) ?></td>
        </tr>
        <tr>
            <td>NUPTK</td>
            <td><?= e($dosen['nuptk']) ?></td>
        </tr>
        <tr>
            <td>Nama Dosen</td>
            <td><?= e($dosen['nama']) ?></td>
        </tr>
        <tr>
            <td>NIP</td>
            <td><?= e($dosen['nip']) ?></td>
        </tr>
        <tr>
            <td>Program Studi</td>
            <td><?= e($dosen['nama_prodi']) ?></td>
        </tr>
    </table>

    <a class="btn" href="index.php">Kembali</a>
    <a class="btn" href="edit.php?id=<?= (int)$dosen['id_dosen'] ?>">Edit Dosen</a>
</main>
</body>
</html