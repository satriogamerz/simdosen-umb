
<?php
require_once __DIR__ . '/../../auth/auth.php';
require_login();

if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'OPERATOR'], true)) {
    http_response_code(403);
    exit('Kamu tidak memiliki izin untuk mengelola tabel pendukung.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| KONFIGURASI TABEL YANG BOLEH DIKELOLA
|--------------------------------------------------------------------------
*/

$configTabel = [
    'jabatan_fungsional' => [
        'judul' => 'Jabatan Fungsional',
        'pk' => 'id_jabatan',
        'fields' => [
            'nama_jabatan' => [
                'label' => 'Nama Jabatan',
                'required' => true
            ]
        ],
        'list' => ['nama_jabatan' => 'Nama Jabatan']
    ],

    'kepangkatan' => [
        'judul' => 'Kepangkatan',
        'pk' => 'id_kepangkatan',
        'fields' => [
            'nama_pangkat' => [
                'label' => 'Nama Pangkat',
                'required' => true
            ],
            'golongan' => [
                'label' => 'Golongan',
                'required' => false
            ]
        ],
        'list' => [
            'nama_pangkat' => 'Nama Pangkat',
            'golongan' => 'Golongan'
        ]
    ],

    'pendidikan' => [
        'judul' => 'Pendidikan',
        'pk' => 'id_pendidikan',
        'fields' => [
            'jenjang' => [
                'label' => 'Jenjang Pendidikan',
                'required' => true
            ]
        ],
        'list' => ['jenjang' => 'Jenjang Pendidikan']
    ],

    'rumpun_ilmu' => [
        'judul' => 'Rumpun Ilmu',
        'pk' => 'id_rumpun',
        'fields' => [
            'nama_rumpun' => [
                'label' => 'Nama Rumpun Ilmu',
                'required' => true
            ]
        ],
        'list' => ['nama_rumpun' => 'Nama Rumpun Ilmu']
    ],

    'status_serdos' => [
        'judul' => 'Status Sertifikasi Dosen',
        'pk' => 'id_status_serdos',
        'fields' => [
            'nama_status' => [
                'label' => 'Nama Status',
                'required' => true
            ]
        ],
        'list' => ['nama_status' => 'Nama Status']
    ],

    'status_pegawai' => [
        'judul' => 'Status Pegawai',
        'pk' => 'id_status_pegawai',
        'fields' => [
            'nama_status' => [
                'label' => 'Nama Status',
                'required' => true
            ]
        ],
        'list' => ['nama_status' => 'Nama Status']
    ],

    'ikatan_kerja' => [
        'judul' => 'Ikatan Kerja',
        'pk' => 'id_ikatan',
        'fields' => [
            'nama_ikatan' => [
                'label' => 'Nama Ikatan Kerja',
                'required' => true
            ]
        ],
        'list' => ['nama_ikatan' => 'Nama Ikatan Kerja']
    ],

    'status_aktivitas' => [
        'judul' => 'Status Aktivitas',
        'pk' => 'id_status_aktivitas',
        'fields' => [
            'nama_status' => [
                'label' => 'Nama Status',
                'required' => true
            ]
        ],
        'list' => ['nama_status' => 'Nama Status']
    ],

    'penempatan' => [
        'judul' => 'Penempatan',
        'pk' => 'id_penempatan',
        'fields' => [
            'nama_penempatan' => [
                'label' => 'Nama Penempatan',
                'required' => true
            ]
        ],
        'list' => ['nama_penempatan' => 'Nama Penempatan']
    ],

    'program_studi' => [
        'judul' => 'Program Studi',
        'pk' => 'id_prodi',
        'fields' => [
            'id_fakultas' => [
                'label' => 'Fakultas',
                'required' => true,
                'type' => 'fakultas'
            ],
            'kode_prodi' => [
                'label' => 'Kode Program Studi',
                'required' => true
            ],
            'nama_prodi' => [
                'label' => 'Nama Program Studi',
                'required' => true
            ],
            'jenjang' => [
                'label' => 'Jenjang',
                'required' => true
            ]
        ],
        'list' => [
            'kode_prodi' => 'Kode Prodi',
            'nama_prodi' => 'Nama Program Studi',
            'jenjang' => 'Jenjang',
            'nama_fakultas' => 'Fakultas'
        ]
    ]
];

$tabel = $_GET['tabel'] ?? $_POST['tabel'] ?? 'jabatan_fungsional';

if (!isset($configTabel[$tabel])) {
    $tabel = 'jabatan_fungsional';
}

$config = $configTabel[$tabel];
$pk = $config['pk'];

$pesan = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$error = '';

function h($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| PROSES TAMBAH, EDIT, DAN HAPUS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        try {
            if ($aksi === 'hapus') {
                $id = filter_var(
                    $_POST['id'] ?? '',
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new Exception('ID data tidak valid.');
                }

                $stmt = $conn->prepare(
                    "DELETE FROM `$tabel` WHERE `$pk` = ?"
                );
                $stmt->execute([(string)$id]);

                if ($stmt->affected_rows < 1) {
                    $stmt->close();
                    throw new Exception('Data tidak ditemukan.');
                }

                $stmt->close();

                audit_log(
                    $conn,
                    (int)$_SESSION['user_id'],
                    $tabel,
                    (string)$id,
                    'DELETE'
                );

                $_SESSION['flash_message'] = 'Data berhasil dihapus.';

                header(
                    'Location: index.php?tabel=' . urlencode($tabel)
                );
                exit;
            }

            if (in_array($aksi, ['tambah', 'edit'], true)) {
                $id = null;

                if ($aksi === 'edit') {
                    $id = filter_var(
                        $_POST['id'] ?? '',
                        FILTER_VALIDATE_INT
                    );

                    if (!$id || $id < 1) {
                        throw new Exception('ID data tidak valid.');
                    }
                }

                $values = [];
                $kolom = [];

                foreach ($config['fields'] as $field => $info) {
                    $value = trim((string)($_POST[$field] ?? ''));

                    if (!empty($info['required']) && $value === '') {
                        throw new Exception(
                            $info['label'] . ' wajib diisi.'
                        );
                    }

                    if (
                        ($info['type'] ?? '') === 'fakultas'
                        && $value !== ''
                    ) {
                        if (!ctype_digit($value) || (int)$value < 1) {
                            throw new Exception('Fakultas tidak valid.');
                        }

                        $cek = $conn->prepare(
                            'SELECT id_fakultas
                             FROM fakultas
                             WHERE id_fakultas = ?
                             LIMIT 1'
                        );
                        $cek->execute([$value]);
                        $ada = $cek->get_result()->fetch_assoc();
                        $cek->close();

                        if (!$ada) {
                            throw new Exception(
                                'Fakultas yang dipilih tidak ditemukan.'
                            );
                        }
                    }

                    $kolom[] = $field;
                    $values[] = $value === '' ? null : $value;
                }

                if ($aksi === 'tambah') {
                    $daftarKolom = implode(
                        ', ',
                        array_map(
                            fn($field) => "`$field`",
                            $kolom
                        )
                    );

                    $placeholder = implode(
                        ', ',
                        array_fill(0, count($kolom), '?')
                    );

                    $sql = "INSERT INTO `$tabel`
                            ($daftarKolom)
                            VALUES ($placeholder)";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($values);
                    $idBaru = $stmt->insert_id;
                    $stmt->close();

                    audit_log(
                        $conn,
                        (int)$_SESSION['user_id'],
                        $tabel,
                        (string)$idBaru,
                        'INSERT'
                    );

                    $_SESSION['flash_message'] = 'Data berhasil ditambahkan.';
                } else {
                    $set = implode(
                        ', ',
                        array_map(
                            fn($field) => "`$field` = ?",
                            $kolom
                        )
                    );

                    $values[] = (string)$id;

                    $sql = "UPDATE `$tabel`
                            SET $set
                            WHERE `$pk` = ?";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($values);
                    $stmt->close();

                    audit_log(
                        $conn,
                        (int)$_SESSION['user_id'],
                        $tabel,
                        (string)$id,
                        'UPDATE'
                    );

                    $_SESSION['flash_message'] = 'Data berhasil diperbarui.';
                }

                header(
                    'Location: index.php?tabel=' . urlencode($tabel)
                );
                exit;
            }
        } catch (Throwable $e) {
            if ($e instanceof mysqli_sql_exception
                && (int)$e->getCode() === 1062) {
                $error = 'Data sudah ada. Periksa apakah kode atau nama tersebut duplikat.';
            } elseif (
                $e instanceof mysqli_sql_exception
                && (int)$e->getCode() === 1451
            ) {
                $error = 'Data tidak bisa dihapus karena masih digunakan oleh data lain.';
            } elseif (
                $e instanceof mysqli_sql_exception
                && (int)$e->getCode() === 1452
            ) {
                $error = 'Relasi data tidak valid. Periksa fakultas atau data referensi yang dipilih.';
            } else {
                $error = $e->getMessage();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA UNTUK FORM EDIT
|--------------------------------------------------------------------------
*/

$mode = $_GET['mode'] ?? '';
$idEdit = $_GET['id'] ?? '';
$dataEdit = [];

if ($mode === 'edit') {
    $idValid = filter_var($idEdit, FILTER_VALIDATE_INT);

    if ($idValid && $idValid > 0) {
        $stmt = $conn->prepare(
            "SELECT * FROM `$tabel` WHERE `$pk` = ? LIMIT 1"
        );
        $stmt->execute([(string)$idValid]);
        $dataEdit = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        if (!$dataEdit) {
            $error = 'Data yang akan diedit tidak ditemukan.';
            $mode = '';
        }
    } else {
        $error = 'ID data tidak valid.';
        $mode = '';
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DAFTAR FAKULTAS
|--------------------------------------------------------------------------
*/

$daftarFakultas = [];

if ($tabel === 'program_studi') {
    $resultFakultas = $conn->query(
        'SELECT id_fakultas, nama_fakultas
         FROM fakultas
         ORDER BY nama_fakultas'
    );

    while ($row = $resultFakultas->fetch_assoc()) {
        $daftarFakultas[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DAFTAR DATA
|--------------------------------------------------------------------------
*/

if ($tabel === 'program_studi') {
    $sqlData = "
        SELECT p.id_prodi, p.kode_prodi, p.nama_prodi,
               p.jenjang, f.nama_fakultas
        FROM program_studi p
        LEFT JOIN fakultas f
            ON p.id_fakultas = f.id_fakultas
        ORDER BY p.id_prodi DESC
    ";
} else {
    $sqlData = "SELECT * FROM `$tabel` ORDER BY `$pk` DESC";
}

$resultData = $conn->query($sqlData);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD Tabel Pendukung - SIMDOSEN</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    padding: 24px;
    background: #f3f6fa;
    color: #243247;
    font-family: Arial, sans-serif;
}
.container {
    max-width: 1150px;
    margin: auto;
}
.panel {
    background: white;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 3px 14px #0000000a;
}
h1 { margin-top: 0; font-size: 25px; }
h2 { font-size: 19px; }
a { color: #175cd3; text-decoration: none; }
a:hover { text-decoration: underline; }
select, input {
    width: 100%;
    padding: 10px;
    margin: 7px 0 14px;
    border: 1px solid #ccd5e0;
    border-radius: 6px;
    font-size: 14px;
}
button, .btn {
    display: inline-block;
    border: 0;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    background: #175cd3;
    color: white;
}
.btn-edit { background: #b54708; }
.btn-delete { background: #b42318; }
.btn-light { background: #e9eef5; color: #243247; }
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}
.alert {
    padding: 13px;
    border-radius: 7px;
    margin-bottom: 15px;
    overflow-wrap: anywhere;
}
.success { background: #dcfae6; color: #067647; }
.error { background: #fee4e2; color: #b42318; }
.table-wrap { overflow-x: auto; }
table {
    border-collapse: collapse;
    width: 100%;
    min-width: 560px;
}
th, td {
    border-bottom: 1px solid #e5eaf0;
    padding: 12px;
    text-align: left;
    vertical-align: top;
}
th { background: #f7f9fc; }
.actions { display: flex; gap: 7px; flex-wrap: wrap; }
small { color: #667085; }
@media(max-width: 600px) {
    body { padding: 12px; }
    .panel { padding: 15px; }
}
</style>
</head>
<body>
<main class="container">

<div class="panel">
    <p>
        <a href="../../dashboard.php">← Kembali ke Dashboard</a>
        &nbsp; | &nbsp;
        <a href="../dosen/index.php">Daftar Dosen</a>
    </p>

    <h1>CRUD Tabel Pendukung SIMDOSEN</h1>
    <p>Kelola data referensi yang digunakan dalam sistem dosen.</p>

    <form method="get">
        <label for="tabel"><strong>Pilih tabel yang ingin dikelola</strong></label>
        <select name="tabel" id="tabel" onchange="this.form.submit()">
            <?php foreach ($configTabel as $namaTabel => $item): ?>
                <option
                    value="<?= h($namaTabel) ?>"
                    <?= $tabel === $namaTabel ? 'selected' : '' ?>
                >
                    <?= h($item['judul']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Pilih Tabel</button></noscript>
    </form>
</div>

<div class="panel">
    <h2>
        <?= $mode === 'edit' ? 'Edit Data' : 'Tambah Data' ?>
        — <?= h($config['judul']) ?>
    </h2>

    <?php if ($pesan !== ''): ?>
        <div class="alert success"><?= h($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?tabel=<?= h($tabel) ?>">
        <input type="hidden" name="csrf_token"
               value="<?= h($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="tabel" value="<?= h($tabel) ?>">
        <input type="hidden" name="aksi"
               value="<?= $mode === 'edit' ? 'edit' : 'tambah' ?>">

        <?php if ($mode === 'edit'): ?>
            <input type="hidden" name="id" value="<?= h($idEdit) ?>">
        <?php endif; ?>

        <div class="grid">
        <?php foreach ($config['fields'] as $field => $info): ?>
            <div>
                <label for="<?= h($field) ?>">
                    <strong><?= h($info['label']) ?></strong>
                    <?= !empty($info['required']) ? '*' : '' ?>
                </label>

                <?php if (($info['type'] ?? '') === 'fakultas'): ?>
                    <select id="<?= h($field) ?>" name="<?= h($field) ?>"
                            <?= !empty($info['required']) ? 'required' : '' ?>>
                        <option value="">-- Pilih Fakultas --</option>
                        <?php foreach ($daftarFakultas as $f): ?>
                            <option value="<?= h($f['id_fakultas']) ?>"
                                <?= (string)($dataEdit[$field] ?? '') ===
                                    (string)$f['id_fakultas'] ? 'selected' : '' ?>>
                                <?= h($f['nama_fakultas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input
                        type="text"
                        id="<?= h($field) ?>"
                        name="<?= h($field) ?>"
                        value="<?= h($dataEdit[$field] ?? '') ?>"
                        <?= !empty($info['required']) ? 'required' : '' ?>
                    >
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>

        <button type="submit">
            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Data' ?>
        </button>

        <?php if ($mode === 'edit'): ?>
            <a class="btn btn-light"
               href="index.php?tabel=<?= h($tabel) ?>">
                Batal Edit
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <h2>Daftar <?= h($config['judul']) ?></h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <?php foreach ($config['list'] as $label): ?>
                        <th><?= h($label) ?></th>
                    <?php endforeach; ?>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; ?>
            <?php while ($row = $resultData->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>

                    <?php foreach ($config['list'] as $field => $label): ?>
                        <td><?= h($row[$field] ?? '-') ?></td>
                    <?php endforeach; ?>

                    <td>
                        <div class="actions">
                            <a class="btn btn-edit"
                               href="index.php?tabel=<?= h($tabel) ?>&mode=edit&id=<?= h($row[$pk]) ?>">
                                Edit
                            </a>

                            <form method="post"
                                  action="index.php?tabel=<?= h($tabel) ?>"
                                  onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                <input type="hidden" name="csrf_token"
                                       value="<?= h($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="tabel"
                                       value="<?= h($tabel) ?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id"
                                       value="<?= h($row[$pk]) ?>">
                                <button type="submit" class="btn-delete">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>

            <?php if ($no === 1): ?>
                <tr>
                    <td colspan="<?= count($config['list']) + 2 ?>">
                        Belum ada data pada tabel ini.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p><small>Pastikan data referensi benar sebelum menyimpan atau menghapusnya.</small></p>
</div>

</main>
</body>
</html>