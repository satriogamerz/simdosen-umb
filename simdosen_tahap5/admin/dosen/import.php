<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'OPERATOR'], true)) {
    http_response_code(403);
    exit('Kamu tidak memiliki izin untuk mengimpor data dosen.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$hasil = [];
$berhasil = 0;
$gagal = 0;

function normalisasi_header($teks) {
    $teks = preg_replace('/^\xEF\xBB\xBF/', '', (string)$teks);
    return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', trim($teks)));
}

function cari_id_referensi($conn, $tabel, $kolom_id, $kolom_nama, $nama) {
    if (trim((string)$nama) === '') {
        return null;
    }

    // Nama tabel dan kolom berasal dari daftar tetap di bawah.
    $sql = "SELECT `$kolom_id` AS id
            FROM `$tabel`
            WHERE LOWER(TRIM(`$kolom_nama`)) = LOWER(TRIM(?))
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $nama);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $data ? (int)$data['id'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } elseif (
        !isset($_FILES['file_csv']) ||
        $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK
    ) {
        $error = 'Pilih file CSV terlebih dahulu.';
    } elseif (
        strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION)) !== 'csv'
    ) {
        $error = 'File harus berformat CSV.';
    } elseif ($_FILES['file_csv']['size'] > 2 * 1024 * 1024) {
        $error = 'Ukuran file maksimal 2 MB.';
    } else {
        $file = fopen($_FILES['file_csv']['tmp_name'], 'r');

        if (!$file) {
            $error = 'File CSV tidak dapat dibaca.';
        } else {
            // Deteksi pemisah koma atau titik koma.
            $barisPertama = fgets($file);

            if ($barisPertama === false) {
                $error = 'File CSV kosong.';
            } else {
                $koma = substr_count($barisPertama, ',');
                $titikKoma = substr_count($barisPertama, ';');
                $pemisah = $titikKoma > $koma ? ';' : ',';

                rewind($file);
                $headerAsli = fgetcsv($file, 0, $pemisah);

                $header = [];
                foreach ($headerAsli ?: [] as $i => $namaKolom) {
                    $header[normalisasi_header($namaKolom)] = $i;
                }

                $kolomWajib = [
                    'nomorregistrasi',
                    'namadosen',
                    'programstudi'
                ];

                foreach ($kolomWajib as $kolom) {
                    if (!array_key_exists($kolom, $header)) {
                        $error = 'Kolom wajib tidak ditemukan: ' . $kolom .
                                 '. Gunakan format CSV contoh dari halaman ini.';
                        break;
                    }
                }

                if ($error === '') {
                    $nomorBaris = 1;

                    while (($baris = fgetcsv($file, 0, $pemisah)) !== false) {
                        $nomorBaris++;

                        if (
                            count($baris) === 1 &&
                            trim((string)$baris[0]) === ''
                        ) {
                            continue;
                        }

                        $ambil = function ($nama) use ($header, $baris) {
                            $kunci = normalisasi_header($nama);
                            if (!isset($header[$kunci])) {
                                return '';
                            }

                            return trim((string)($baris[$header[$kunci]] ?? ''));
                        };

                        $nomor = $ambil('Nomor Registrasi');
                        $nama = $ambil('Nama Dosen');
                        $prodi = $ambil('Program Studi');

                        if ($nomor === '' || $nama === '' || $prodi === '') {
                            $gagal++;
                            $hasil[] = "Baris $nomorBaris: Nomor Registrasi, Nama Dosen, dan Program Studi wajib diisi.";
                            continue;
                        }

                        // Cocokkan nama referensi ke ID database.
                        $referensi = [
                            'id_jabatan' => [
                                'Jabatan Fungsional', 'jabatan_fungsional',
                                'id_jabatan', 'nama_jabatan'
                            ],
                            'id_pendidikan' => [
                                'Pendidikan', 'pendidikan',
                                'id_pendidikan', 'jenjang'
                            ],
                            'id_rumpun' => [
                                'Rumpun Ilmu', 'rumpun_ilmu',
                                'id_rumpun', 'nama_rumpun'
                            ],
                            'id_status_serdos' => [
                                'Status Serdos', 'status_serdos',
                                'id_status_serdos', 'nama_status'
                            ],
                            'id_status_pegawai' => [
                                'Status Pegawai', 'status_pegawai',
                                'id_status_pegawai', 'nama_status'
                            ],
                            'id_ikatan' => [
                                'Ikatan Kerja', 'ikatan_kerja',
                                'id_ikatan', 'nama_ikatan'
                            ],
                            'id_status_aktivitas' => [
                                'Status Aktivitas', 'status_aktivitas',
                                'id_status_aktivitas', 'nama_status'
                            ],
                            'id_penempatan' => [
                                'Penempatan', 'penempatan',
                                'id_penempatan', 'nama_penempatan'
                            ],
                            'id_prodi' => [
                                'Program Studi', 'program_studi',
                                'id_prodi', 'nama_prodi'
                            ]
                        ];

                        $nilaiReferensi = [];
                        $masalah = [];

                        foreach ($referensi as $field => $ref) {
                            $namaReferensi = $ambil($ref[0]);

                            if ($field === 'id_prodi' && $namaReferensi === '') {
                                $masalah[] = 'Program Studi wajib diisi';
                                continue;
                            }

                            if ($namaReferensi === '') {
                                $nilaiReferensi[$field] = null;
                                continue;
                            }

                            $id = cari_id_referensi(
                                $conn,
                                $ref[1],
                                $ref[2],
                                $ref[3],
                                $namaReferensi
                            );

                            if ($id === null) {
                                $masalah[] = $ref[0] .
                                    " tidak ditemukan: $namaReferensi";
                            } else {
                                $nilaiReferensi[$field] = $id;
                            }
                        }

                        if ($masalah) {
                            $gagal++;
                            $hasil[] = "Baris $nomorBaris: " . implode('; ', $masalah);
                            continue;
                        }

                        $nuptk = $ambil('NUPTK');
                        $nip = $ambil('NIP');

                        $values = [
                            $nomor,
                            $nuptk !== '' ? $nuptk : null,
                            $nama,
                            $nip !== '' ? $nip : null,
                            $nilaiReferensi['id_jabatan'],
                            $nilaiReferensi['id_pendidikan'],
                            $nilaiReferensi['id_rumpun'],
                            $nilaiReferensi['id_status_serdos'],
                            $nilaiReferensi['id_status_pegawai'],
                            $nilaiReferensi['id_ikatan'],
                            $nilaiReferensi['id_status_aktivitas'],
                            $nilaiReferensi['id_penempatan'],
                            $nilaiReferensi['id_prodi']
                        ];

                        try {
                            $sql = "INSERT INTO dosen (
                                nomor_registrasi, nuptk, nama, nip,
                                id_jabatan, id_pendidikan, id_rumpun,
                                id_status_serdos, id_status_pegawai,
                                id_ikatan, id_status_aktivitas,
                                id_penempatan, id_prodi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param(
                                'ssssiiiiiiiii',
                                ...$values
                            );
                            $stmt->execute();

                            $idBaru = $stmt->insert_id;
                            $stmt->close();

                            try {
                                audit_log(
                                    $conn,
                                    (int)$_SESSION['user_id'],
                                    'dosen',
                                    (string)$idBaru,
                                    'INSERT'
                                );
                            } catch (Throwable $e) {
                                // Impor tetap berhasil jika audit log gagal.
                            }

                            $berhasil++;
                        } catch (mysqli_sql_exception $e) {
                            $gagal++;

                            if ($e->getCode() === 1062) {
                                $hasil[] = "Baris $nomorBaris: Nomor Registrasi, NUPTK, atau NIP sudah terdaftar.";
                            } else {
                                $hasil[] = "Baris $nomorBaris: Gagal disimpan. Periksa relasi database atau struktur tabel.";
                            }
                        }
                    }
                }
            }

            fclose($file);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Import Data Dosen - SIMDOSEN</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    background: #f3f6fa;
    margin: 0;
    padding: 25px;
    color: #243247;
}
.container {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 18px #0000000d;
}
h2 { margin-top: 0; }
a { color: #175cd3; }
button {
    border: 0;
    border-radius: 6px;
    padding: 11px 16px;
    color: white;
    background: #175cd3;
    cursor: pointer;
}
input[type=file] {
    display: block;
    margin: 15px 0;
    max-width: 100%;
}
.error, .hasil {
    padding: 12px;
    margin: 15px 0;
    border-radius: 6px;
    overflow-wrap: anywhere;
}
.error { background: #fee4e2; color: #b42318; }
.hasil { background: #f2f4f7; }
.petunjuk {
    background: #eff8ff;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}
li { margin-bottom: 7px; }
</style>
</head>
<body>
<main class="container">
    <p><a href="index.php">← Kembali ke Daftar Dosen</a></p>
    <h2>Import Data Dosen</h2>
    <p>Unggah file CSV untuk memasukkan beberapa data dosen sekaligus.</p>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === ''): ?>
        <div class="hasil">
            <strong>Hasil import</strong>
            <p>Berhasil: <?= $berhasil ?> data</p>
            <p>Gagal: <?= $gagal ?> data</p>

            <?php if ($hasil): ?>
                <strong>Rincian data gagal:</strong>
                <ul>
                <?php foreach ($hasil as $pesan): ?>
                    <li><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="petunjuk">
        <strong>Kolom CSV yang digunakan</strong>
        <p>Nomor Registrasi, NUPTK, Nama Dosen, NIP, Jabatan Fungsional,
        Pendidikan, Rumpun Ilmu, Status Serdos, Status Pegawai, Ikatan Kerja,
        Status Aktivitas, Penempatan, Program Studi.</p>
        <p>Nomor Registrasi, Nama Dosen, dan Program Studi wajib diisi.
        Kolom referensi lainnya boleh dikosongkan.</p>
        <p>Nama referensi harus cocok dengan data yang tersedia di database.</p>
        <button type="button" onclick="unduhTemplate()">Unduh Template CSV</button>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="file_csv"><strong>Pilih file CSV</strong></label>
        <input type="file" id="file_csv" name="file_csv"
            accept=".csv,text/csv" required>

        <button type="submit"
            onclick="return confirm('Pastikan file CSV sudah benar. Data baru akan ditambahkan ke database. Lanjutkan?')">
            Import Data
        </button>
    </form>
</main>

<script>
function unduhTemplate() {
    const header = [
        'Nomor Registrasi',
        'NUPTK',
        'Nama Dosen',
        'NIP',
        'Jabatan Fungsional',
        'Pendidikan',
        'Rumpun Ilmu',
        'Status Serdos',
        'Status Pegawai',
        'Ikatan Kerja',
        'Status Aktivitas',
        'Penempatan',
        'Program Studi'
    ];

    const csv = '\uFEFF' + header.join(',') + '\r\n';
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = 'template_import_dosen.csv';
    link.click();

    URL.revokeObjectURL(url);
}
</script>
</body>
</html>