<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

// Hanya ADMIN dan OPERATOR yang boleh mengekspor data
if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'OPERATOR'], true)) {
    http_response_code(403);
    exit('Kamu tidak memiliki izin untuk mengekspor data dosen.');
}

// Ambil kata kunci pencarian jika ada
$q = trim($_GET['q'] ?? '');

// Query data dosen sesuai database SIMDOSEN
$sql = "SELECT
            d.nomor_registrasi,
            d.nuptk,
            d.nama,
            d.nip,
            p.nama_prodi
        FROM dosen d
        LEFT JOIN program_studi p ON p.id_prodi = d.id_prodi";

if ($q !== '') {
    $sql .= " WHERE d.nama LIKE CONCAT('%', ?, '%')
              OR d.nip LIKE CONCAT('%', ?, '%')
              OR d.nuptk LIKE CONCAT('%', ?, '%')
              OR d.nomor_registrasi LIKE CONCAT('%', ?, '%')";

    $sql .= " ORDER BY d.nama";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        exit('Gagal menyiapkan query ekspor.');
    }

    $stmt->bind_param('ssss', $q, $q, $q, $q);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql . " ORDER BY d.nama");

    if (!$result) {
        exit('Gagal mengambil data dosen.');
    }
}

// Catat aktivitas ekspor
try {
    audit_log(
        $conn,
        (int) $_SESSION['user_id'],
        'dosen',
        null,
        'VIEW'
    );
} catch (Throwable $e) {
    // Kegagalan pencatatan log tidak menghentikan ekspor
}

// Unduh sebagai file CSV yang bisa dibuka di Microsoft Excel
$filename = 'data_dosen_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM agar karakter Indonesia terbaca dengan baik di Excel
fwrite($output, "\xEF\xBB\xBF");

// Judul kolom
fputcsv($output, [
    'Nomor Registrasi',
    'NUPTK',
    'Nama Dosen',
    'NIP',
    'Program Studi'
]);

while ($row = $result->fetch_assoc()) {
    // Cegah isi sel diperlakukan sebagai formula oleh Excel
    foreach ($row as $key => $value) {
        if (is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value)) {
            $row[$key] = "'" . $value;
        }
    }

    fputcsv($output, [
        $row['nomor_registrasi'],
        $row['nuptk'],
        $row['nama'],
        $row['nip'],
        $row['nama_prodi']
    ]);
}

fclose($output);

if (isset($stmt)) {
    $stmt->close();
}

exit;