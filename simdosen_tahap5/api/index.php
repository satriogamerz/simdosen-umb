<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function response($status, $data) {
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    exit;
}

function inputData() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        response(400, [
            'success' => false,
            'message' => 'Body harus berupa JSON yang valid.'
        ]);
    }

    return $data;
}

function apiKeyValid() {
    $expected = getenv('SIMDOSEN_API_KEY');
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

    return $expected !== false
        && $expected !== ''
        && $provided !== ''
        && hash_equals($expected, $provided);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? filter_var(
    $_GET['id'],
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) : null;

if (isset($_GET['id']) && $id === false) {
    response(400, [
        'success' => false,
        'message' => 'ID dosen tidak valid.'
    ]);
}

try {
    if ($method === 'GET') {
        if ($id !== null) {
            $stmt = $conn->prepare(
                "SELECT
                    d.id_dosen,
                    d.nomor_registrasi,
                    d.nuptk,
                    d.nama,
                    d.nip,
                    p.nama_prodi,
                    jf.nama_jabatan,
                    pend.jenjang AS pendidikan,
                    sp.nama_status AS status_pegawai
                 FROM dosen d
                 LEFT JOIN program_studi p
                    ON d.id_prodi = p.id_prodi
                 LEFT JOIN jabatan_fungsional jf
                    ON d.id_jabatan = jf.id_jabatan
                 LEFT JOIN pendidikan pend
                    ON d.id_pendidikan = pend.id_pendidikan
                 LEFT JOIN status_pegawai sp
                    ON d.id_status_pegawai = sp.id_status_pegawai
                 WHERE d.id_dosen = ?"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $dosen = $result->fetch_assoc();

            if (!$dosen) {
                response(404, [
                    'success' => false,
                    'message' => 'Data dosen tidak ditemukan.'
                ]);
            }

            response(200, [
                'success' => true,
                'data' => $dosen
            ]);
        }

        $result = $conn->query(
            "SELECT
                d.id_dosen,
                d.nomor_registrasi,
                d.nuptk,
                d.nama,
                d.nip,
                p.nama_prodi,
                jf.nama_jabatan,
                pend.jenjang AS pendidikan,
                sp.nama_status AS status_pegawai
             FROM dosen d
             LEFT JOIN program_studi p
                ON d.id_prodi = p.id_prodi
             LEFT JOIN jabatan_fungsional jf
                ON d.id_jabatan = jf.id_jabatan
             LEFT JOIN pendidikan pend
                ON d.id_pendidikan = pend.id_pendidikan
             LEFT JOIN status_pegawai sp
                ON d.id_status_pegawai = sp.id_status_pegawai
             ORDER BY d.id_dosen DESC"
        );

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        response(200, [
            'success' => true,
            'total' => count($data),
            'data' => $data
        ]);
    }

    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        header('Allow: GET, POST, PUT, DELETE');
        response(405, [
            'success' => false,
            'message' => 'Metode tidak didukung.'
        ]);
    }

    // Semua operasi perubahan data memerlukan API key.
    if (!apiKeyValid()) {
        response(401, [
            'success' => false,
            'message' => 'API key tidak valid atau belum dikonfigurasi.'
        ]);
    }

    if ($method === 'POST') {
        $data = inputData();

        $nomor = trim($data['nomor_registrasi'] ?? '');
        $nama = trim($data['nama'] ?? '');
        $idProdi = filter_var(
            $data['id_prodi'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($nomor === '' || $nama === '' || !$idProdi) {
            response(422, [
                'success' => false,
                'message' => 'nomor_registrasi, nama, dan id_prodi wajib diisi.'
            ]);
        }

        $nuptk = trim($data['nuptk'] ?? '') ?: null;
        $nip = trim($data['nip'] ?? '') ?: null;

        $cek = $conn->prepare(
            "SELECT id_prodi FROM program_studi WHERE id_prodi = ?"
        );
        $cek->bind_param('i', $idProdi);
        $cek->execute();

        if (!$cek->get_result()->fetch_assoc()) {
            response(422, [
                'success' => false,
                'message' => 'Program studi tidak ditemukan.'
            ]);
        }

        $stmt = $conn->prepare(
            "INSERT INTO dosen
                (nomor_registrasi, nuptk, nama, nip, id_prodi)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssi',
            $nomor,
            $nuptk,
            $nama,
            $nip,
            $idProdi
        );
        $stmt->execute();

        response(201, [
            'success' => true,
            'message' => 'Data dosen berhasil ditambahkan.',
            'id_dosen' => $conn->insert_id
        ]);
    }

    if ($method === 'PUT') {
        if ($id === null) {
            response(400, [
                'success' => false,
                'message' => 'Sertakan ID, misalnya ?id=1.'
            ]);
        }

        $data = inputData();
        $nomor = trim($data['nomor_registrasi'] ?? '');
        $nama = trim($data['nama'] ?? '');
        $idProdi = filter_var(
            $data['id_prodi'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($nomor === '' || $nama === '' || !$idProdi) {
            response(422, [
                'success' => false,
                'message' => 'nomor_registrasi, nama, dan id_prodi wajib diisi.'
            ]);
        }

        $nuptk = trim($data['nuptk'] ?? '') ?: null;
        $nip = trim($data['nip'] ?? '') ?: null;

        $cek = $conn->prepare(
            "SELECT id_prodi FROM program_studi WHERE id_prodi = ?"
        );
        $cek->bind_param('i', $idProdi);
        $cek->execute();

        if (!$cek->get_result()->fetch_assoc()) {
            response(422, [
                'success' => false,
                'message' => 'Program studi tidak ditemukan.'
            ]);
        }

        $cekDosen = $conn->prepare(
            "SELECT id_dosen FROM dosen WHERE id_dosen = ?"
        );
        $cekDosen->bind_param('i', $id);
        $cekDosen->execute();

        if (!$cekDosen->get_result()->fetch_assoc()) {
            response(404, [
                'success' => false,
                'message' => 'Data dosen tidak ditemukan.'
            ]);
        }

        $stmt = $conn->prepare(
            "UPDATE dosen
             SET nomor_registrasi = ?, nuptk = ?, nama = ?, nip = ?,
                 id_prodi = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id_dosen = ?"
        );
        $stmt->bind_param(
            'ssssii',
            $nomor,
            $nuptk,
            $nama,
            $nip,
            $idProdi,
            $id
        );
        $stmt->execute();

        response(200, [
            'success' => true,
            'message' => 'Data dosen berhasil diperbarui.'
        ]);
    }

    if ($method === 'DELETE') {
        if ($id === null) {
            response(400, [
                'success' => false,
                'message' => 'Sertakan ID, misalnya ?id=1.'
            ]);
        }

        $cek = $conn->prepare(
            "SELECT id_dosen, nama FROM dosen WHERE id_dosen = ?"
        );
        $cek->bind_param('i', $id);
        $cek->execute();
        $dosen = $cek->get_result()->fetch_assoc();

        if (!$dosen) {
            response(404, [
                'success' => false,
                'message' => 'Data dosen tidak ditemukan.'
            ]);
        }

        $stmt = $conn->prepare(
            "DELETE FROM dosen WHERE id_dosen = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        response(200, [
            'success' => true,
            'message' => 'Data dosen berhasil dihapus.'
        ]);
    }
} catch (mysqli_sql_exception $e) {
    if ((int)$e->getCode() === 1062) {
        response(409, [
            'success' => false,
            'message' => 'Nomor registrasi, NUPTK, atau NIP sudah digunakan.'
        ]);
    }

    if ((int)$e->getCode() === 1451 || (int)$e->getCode() === 1452) {
        response(409, [
            'success' => false,
            'message' => 'Operasi ditolak karena terkait dengan data lain.'
        ]);
    }

    error_log('SIMDOSEN API database error: ' . $e->getMessage());
    response(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan database.'
    ]);
} catch (Throwable $e) {
    error_log('SIMDOSEN API error: ' . $e->getMessage());
    response(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan pada server.'
    ]);
}