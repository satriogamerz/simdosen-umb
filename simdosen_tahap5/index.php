<?php
session_start();
$tujuanLogin = isset($_SESSION['user_id'])
    ? 'dashboard.php'
    : 'login.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIMDOSEN - UM Bengkulu</title>

<style>
* {
    box-sizing: border-box;
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #172033;
    background: #f7faff;
}

a {
    text-decoration: none;
}

.navbar {
    height: 100px;
    padding: 0 5.7%;
    background: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e1e8f1;
    gap: 20px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo {
    width: 63px;
    height: 63px;
    border-radius: 17px;
    background: #0860df;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 23px;
}

.brand-name {
    font-size: 24px;
    font-weight: bold;
}

.brand-subtitle {
    color: #718096;
    font-size: 16px;
    margin-top: 7px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 38px;
}

.nav-links a {
    color: #536177;
    font-size: 17px;
}

.nav-links a:hover {
    color: #0860df;
}

.nav-links .login-btn {
    background: #0860df;
    color: white;
    padding: 19px 27px;
    border-radius: 15px;
    font-weight: bold;
}

.hero {
    background:
        radial-gradient(ellipse at 85% 30%, #dceaff 0%, transparent 40%),
        #f7faff;
    padding: 88px 5.7% 65px;
}

.hero-content {
    max-width: 1600px;
    margin: auto;
    display: grid;
    grid-template-columns: 1.15fr 0.9fr;
    gap: 55px;
    align-items: center;
}

.eyebrow {
    color: #0860df;
    font-weight: bold;
    letter-spacing: 2px;
    font-size: 15px;
    margin-bottom: 30px;
}

.hero h1 {
    font-size: clamp(43px, 5.1vw, 76px);
    line-height: 1.08;
    letter-spacing: -2px;
    margin: 0;
    font-weight: 800;
}

.hero h1 span {
    color: #0860df;
}

.hero-description {
    color: #667991;
    font-size: 20px;
    line-height: 1.8;
    margin: 32px 0;
    max-width: 720px;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.btn-primary,
.btn-secondary {
    display: inline-block;
    padding: 19px 26px;
    border-radius: 14px;
    font-size: 17px;
    font-weight: bold;
}

.btn-primary {
    color: white;
    background: #0860df;
}

.btn-primary:hover,
.nav-links .login-btn:hover {
    background: #064db5;
}

.btn-secondary {
    color: #172033;
    background: white;
    border: 1px solid #cbd8e8;
}

.preview {
    background: white;
    border: 1px solid #dfe7f2;
    border-radius: 28px;
    padding: 34px;
    box-shadow: 0 25px 65px rgba(36, 67, 110, .09);
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 28px;
}

.preview-header h2 {
    font-size: 20px;
    margin: 0;
}

.active {
    color: #139b51;
    font-size: 13px;
    font-weight: bold;
}

.preview-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 22px;
}

.preview-stat {
    background: #f4f7fc;
    border: 1px solid #e0e8f3;
    border-radius: 17px;
    padding: 27px 24px;
}

.preview-stat p {
    color: #64748b;
    font-size: 14px;
    margin: 0 0 22px;
}

.preview-stat strong {
    font-size: 31px;
    color: #172033;
}

.preview-table {
    border: 1px solid #e0e8f3;
    border-radius: 17px;
    overflow: hidden;
}

.table-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 19px 20px;
    border-bottom: 1px solid #e0e8f3;
    font-size: 14px;
}

.table-row:last-child {
    border-bottom: none;
}

.table-head {
    background: #f4f7fc;
    font-weight: bold;
}

.status {
    background: #d9fbe7;
    color: #15803d;
    border-radius: 20px;
    padding: 8px 12px;
    font-size: 12px;
    white-space: nowrap;
}

.section {
    padding: 75px 5.7%;
    background: white;
}

.section-inner {
    max-width: 1150px;
    margin: auto;
}

.section h2 {
    font-size: 32px;
    margin-top: 0;
    color: #172033;
}

.section p {
    color: #667991;
    font-size: 17px;
    line-height: 1.8;
}

.features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin-top: 30px;
}

.feature-card {
    padding: 25px;
    border: 1px solid #e0e8f3;
    border-radius: 16px;
    background: #fbfdff;
}

.feature-card h3 {
    color: #0860df;
}

.feature-card p {
    font-size: 15px;
}

footer {
    background: #f1f6fc;
    padding: 25px;
    text-align: center;
    color: #718096;
    font-size: 13px;
}

@media (max-width: 950px) {
    .hero-content {
        grid-template-columns: 1fr;
    }

    .hero {
        padding-top: 60px;
    }

    .preview {
        max-width: 700px;
    }

    .hero h1 {
        font-size: clamp(43px, 8vw, 65px);
    }
}

@media (max-width: 600px) {
    .navbar {
        height: auto;
        padding: 18px 5%;
        align-items: flex-start;
        flex-direction: column;
    }

    .logo {
        width: 52px;
        height: 52px;
        font-size: 19px;
    }

    .brand-name {
        font-size: 20px;
    }

    .nav-links {
        width: 100%;
        gap: 17px;
        flex-wrap: wrap;
    }

    .nav-links a {
        font-size: 14px;
    }

    .nav-links .login-btn {
        padding: 12px 16px;
        border-radius: 10px;
    }

    .hero {
        padding: 45px 5% 50px;
    }

    .hero h1 {
        font-size: 43px;
        letter-spacing: -1px;
    }

    .hero-description {
        font-size: 16px;
    }

    .btn-primary,
    .btn-secondary {
        padding: 15px 18px;
        font-size: 15px;
    }

    .preview {
        padding: 19px;
        border-radius: 20px;
    }

    .preview-stat {
        padding: 17px 13px;
    }

    .preview-stat strong {
        font-size: 25px;
    }

    .table-row {
        padding: 15px 12px;
        font-size: 12px;
    }

    .features {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="brand">
        <div class="logo">UMB</div>
        <div>
            <div class="brand-name">SIMDOSEN</div>
            <div class="brand-subtitle">UM Bengkulu</div>
        </div>
    </a>

    <nav class="nav-links">
        <a href="#beranda">Beranda</a>
        <a href="#tentang">Tentang</a>
        <a href="#fitur">Fitur</a>
        <a class="login-btn" href="<?= htmlspecialchars($tujuanLogin) ?>">
            <?= isset($_SESSION['user_id']) ? 'Dashboard' : 'Login' ?>
        </a>
    </nav>
</header>

<main>
<section class="hero" id="beranda">
    <div class="hero-content">

        <div>
            <div class="eyebrow">SISTEM INFORMASI DOSEN</div>

            <h1>
                Kelola Data Dosen<br>
                <span>UM Bengkulu</span><br>
                dengan Mudah
            </h1>

            <p class="hero-description">
                Sistem informasi untuk mengelola data dosen secara
                terstruktur, aman, dan terintegrasi dengan Web Service/API.
            </p>

            <div class="hero-actions">
                <a class="btn-primary"
                   href="<?= htmlspecialchars($tujuanLogin) ?>">
                    <?= isset($_SESSION['user_id']) ? 'Buka Dashboard' : 'Masuk ke Sistem' ?>
                </a>

                <a class="btn-secondary" href="#fitur">Lihat Fitur</a>
            </div>
        </div>

        <div class="preview">
            <div class="preview-header">
                <h2>Dashboard</h2>
                <span class="active">● Sistem Aktif</span>
            </div>

            <div class="preview-stats">
                <div class="preview-stat">
                    <p>Total Dosen</p>
                    <strong>--</strong>
                </div>

                <div class="preview-stat">
                    <p>Dosen Aktif</p>
                    <strong>--</strong>
                </div>
            </div>

            <div class="preview-table">
                <div class="table-row table-head">
                    <span>Nama Dosen</span>
                    <span>Status</span>
                </div>

                <div class="table-row">
                    <span>Data akan tampil setelah login</span>
                    <span class="status">Aktif</span>
                </div>

                <div class="table-row">
                    <span>Manajemen data terpusat</span>
                    <span class="status">API</span>
                </div>
            </div>
        </div>

    </div>
</section>

<section class="section" id="tentang">
    <div class="section-inner">
        <h2>Tentang SIMDOSEN</h2>
        <p>
            SIMDOSEN merupakan Sistem Informasi Manajemen Dosen
            Universitas Muhammadiyah Bengkulu yang membantu pengelolaan
            data dosen secara terstruktur melalui satu sistem.
        </p>
    </div>
</section>

<section class="section" id="fitur">
    <div class="section-inner">
        <h2>Fitur Sistem</h2>
        <p>Fitur untuk mendukung pengelolaan dan penyajian informasi dosen.</p>

        <div class="features">
            <div class="feature-card">
                <h3>Data Dosen</h3>
                <p>Pengelolaan data dosen dalam satu sistem terpusat.</p>
            </div>

            <div class="feature-card">
                <h3>Data Referensi</h3>
                <p>Pengelolaan program studi dan data referensi pendukung.</p>
            </div>

            <div class="feature-card">
                <h3>Laporan dan API</h3>
                <p>Penyajian laporan serta layanan API untuk pertukaran data.</p>
            </div>
        </div>
    </div>
</section>
</main>

<footer>
    &copy; 2026 SIMDOSEN — Universitas Muhammadiyah Bengkulu
</footer>

</body>
</html>