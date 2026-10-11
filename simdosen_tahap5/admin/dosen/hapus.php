<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

// Periksa izin pengguna untuk menghapus data dosen
if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'OPERATOR'], true)) {
    http_response_code(403);
    exit('Kamu tidak memiliki izin untuk menghapus data dosen.');
}

// Penghapusan hanya boleh melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Periksa token keamanan
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    exit('Permintaan tidak valid. Silakan kembali dan coba lagi.');
}

// Periksa ID dosen
$id_dosen = filter_input(INPUT_POST, 'id_dosen', FILTER_VALIDATE_INT);

if (!$id_dosen || $id_dosen < 1) {
    header('Location: index.php');
    exit;
}

try {
    // Pastikan data dosen tersedia
    $stmt = $conn->prepare(
        'SELECT id_dosen FROM dosen WHERE id_dosen = ?'
    );
    $stmt->bind_param('i', $id_dosen);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        header('Location: index.php');
        exit;
    }

    // Hapus data dosen
    $stmt = $conn->prepare(
        'DELETE FROM dosen WHERE id_dosen = ?'
    );
    $stmt->bind_param('i', $id_dosen);
    $stmt->execute();
    $berhasil = $stmt->affected_rows > 0;
    $stmt->close();

    if ($berhasil) {
        // Catat aktivitas penghapusan
        try {
            audit_log(
                $conn,
                (int) $_SESSION['user_id'],
                'dosen',
                (string) $id_dosen,
                'DELETE'
            );
        } catch (Throwable $e) {
            // Penghapusan sudah terjadi; jangan mengulanginya.
        }

        header('Location: index.php?hapus=berhasil');
        exit;
    }

    header('Location: index.php?hapus=gagal');
    exit;

} catch (mysqli_sql_exception $e) {
    // Contohnya, data masih digunakan oleh tabel lain.
    header('Location: index.php?hapus=gagal');
    exit;
}