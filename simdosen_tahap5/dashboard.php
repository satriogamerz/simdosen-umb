<?php
require_once __DIR__ . '/auth/auth.php';
require_login();

$role = strtoupper($_SESSION['role'] ?? '');
$nama = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna';

if (!in_array($role, ['ADMIN', 'OPERATOR', 'PIMPINAN'], true)) {
    http_response_code(403);
    exit('Akses ditolak. Role pengguna tidak valid.');
}

function hitungData($conn, $sql) {
    $result = $conn->query($sql);
    return $result ? (int) $result->fetch_assoc()['n'] : 0;
}

$total = hitungData($conn, 'SELECT COUNT(*) n FROM dosen');
$prodi = hitungData($conn, 'SELECT COUNT(*) n FROM program_studi');
$fak   = hitungData($conn, 'SELECT COUNT(*) n FROM fakultas');

$menu = [
    [
        'judul' => 'Data Dosen',
        'deskripsi' => 'Lihat dan kelola data dosen.',
        'url' => 'admin/dosen/index.php',
        'ikon' => '👨‍🏫',
        'roles' => ['ADMIN', 'OPERATOR']
    ],
    [
        'judul' => 'Data Referensi',
        'deskripsi' => 'Kelola program studi dan data pendukung.',
        'url' => 'admin/referensi/index.php',
        'ikon' => '📚',
        'roles' => ['ADMIN']
    ],
    [
        'judul' => 'Laporan',
        'deskripsi' => 'Lihat, filter, dan ekspor laporan dosen.',
        'url' => 'admin/laporan/index.php',
        'ikon' => '📊',
        'roles' => ['ADMIN', 'OPERATOR']
    ],
];

$judulRole = [
    'ADMIN' => 'Administrator',
    'OPERATOR' => 'Operator Data Dosen',
    'PIMPINAN' => 'Pimpinan'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard SIMDOSEN</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f3f7fc;
    color: #243247;
}

.navbar {
    background: #1769aa;
    color: white;
    padding: 17px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    box-shadow: 0 3px 12px rgba(23, 105, 170, .15);
}

.brand {
    font-size: 22px;
    font-weight: bold;
    letter-spacing: .5px;
}

.brand small {
    display: block;
    font-size: 11px;
    font-weight: normal;
    margin-top: 4px;
    letter-spacing: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 13px;
    flex-wrap: wrap;
    font-size: 13px;
}

.role {
    background: rgba(255,255,255,.17);
    padding: 6px 10px;
    border-radius: 20px;
}

.logout {
    color: white;
    text-decoration: none;
    border: 1px solid rgba(255,255,255,.7);
    padding: 8px 12px;
    border-radius: 7px;
}

.logout:hover {
    background: white;
    color: #1769aa;
}

.container {
    max-width: 1150px;
    margin: 32px auto;
    padding: 0 20px 40px;
}

.welcome {
    background: white;
    padding: 28px;
    border-radius: 15px;
    border-left: 5px solid #1769aa;
    box-shadow: 0 5px 20px rgba(28, 55, 90, .05);
    margin-bottom: 25px;
}

.welcome h1 {
    color: #155a91;
    font-size: 27px;
    margin: 0 0 10px;
}

.welcome p {
    color: #68778a;
    line-height: 1.6;
    margin: 0;
}

.section-title {
    font-size: 19px;
    margin: 28px 0 15px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.stat-card {
    background: white;
    padding: 23px;
    border-radius: 13px;
    box-shadow: 0 5px 20px rgba(28, 55, 90, .05);
    border: 1px solid #e8eef5;
}

.stat-label {
    color: #6c7b8d;
    font-size: 14px;
}

.stat-number {
    color: #1769aa;
    font-size: 32px;
    font-weight: bold;
    margin-top: 12px;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.menu-card {
    background: white;
    padding: 23px;
    border-radius: 13px;
    border: 1px solid #e4ebf3;
    box-shadow: 0 5px 20px rgba(28, 55, 90, .04);
    text-decoration: none;
    color: inherit;
    transition: transform .15s, box-shadow .15s;
}

.menu-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(28, 55, 90, .10);
}

.menu-icon {
    font-size: 30px;
    margin-bottom: 15px;
}

.menu-card h3 {
    color: #1769aa;
    margin: 0 0 10px;
    font-size: 17px;
}

.menu-card p {
    color: #6c7b8d;
    font-size: 13px;
    line-height: 1.6;
    margin: 0 0 17px;
}

.menu-link {
    color: #1769aa;
    font-size: 13px;
    font-weight: bold;
}

.footer {
    text-align: center;
    color: #8591a0;
    font-size: 12px;
    margin-top: 35px;
}

@media (max-width: 760px) {
    .stats, .menu-grid {
        grid-template-columns: 1fr;
    }

    .container {
        margin-top: 20px;
        padding-left: 14px;
        padding-right: 14px;
    }

    .welcome {
        padding: 22px;
    }

    .welcome h1 {
        font-size: 23px;
    }
}
</style>
</head>

<body>

<header class="navbar">
    <div class="brand">
        SIMDOSEN
        <small>Universitas Muhammadiyah Bengkulu</small>
    </div>

    <div class="user-info">
        <span><?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="role">
            <?= htmlspecialchars($judulRole[$role], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <a class="logout" href="logout.php">Keluar</a>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <h1>Selamat Datang, <?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p>
            Anda masuk sebagai
            <strong><?= htmlspecialchars($judulRole[$role], ENT_QUOTES, 'UTF-8') ?></strong>.
            Silakan gunakan menu sesuai hak akses Anda untuk mengakses
            Sistem Informasi Manajemen Dosen UM Bengkulu.
        </p>
    </section>

    
<?php if ($role === 'PIMPINAN'): ?>

    <h2 class="section-title">Ringkasan untuk Pimpinan</h2>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Dosen</div>
            <div class="stat-number"><?= $total ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Program Studi</div>
            <div class="stat-number"><?= $prodi ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Fakultas</div>
            <div class="stat-number"><?= $fak ?></div>
        </div>
    </section>

<?php else: ?>

    <h2 class="section-title">Ringkasan Sistem</h2>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Dosen</div>
            <div class="stat-number"><?= $total ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Program Studi</div>
            <div class="stat-number"><?= $prodi ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Fakultas</div>
            <div class="stat-number"><?= $fak ?></div>
        </div>
    </section>

    <h2 class="section-title">Menu Utama</h2>

    <section class="menu-grid">
        <?php foreach ($menu as $item): ?>
            <?php if (in_array($role, $item['roles'], true)): ?>
                <a class="menu-card"
                   href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="menu-icon"><?= $item['ikon'] ?></div>

                    <h3><?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?></h3>

                    <p><?= htmlspecialchars($item['deskripsi'], ENT_QUOTES, 'UTF-8') ?></p>

                    <span class="menu-link">Buka menu &rarr;</span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>

<?php endif; ?>


    <footer class="footer">
        &copy; 2026 SIMDOSEN — Universitas Muhammadiyah Bengkulu
    </footer>

</main>

</body>
</html>