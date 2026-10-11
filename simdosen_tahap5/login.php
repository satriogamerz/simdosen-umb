<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT u.id_user, u.username, u.password,
               u.nama_lengkap, u.status, r.nama_role
        FROM users u
        JOIN roles r ON r.id_role = u.id_role
        WHERE u.username = ? OR u.email = ?
        LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (
        $user &&
        $user['status'] === 'Aktif' &&
        password_verify($password, $user['password'])
    ) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['nama_role'];

        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah, atau akun tidak aktif.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login SIMDOSEN - UM Bengkulu</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    padding: 35px 18px;
    background: #eef2f7;
    font-family: Arial, Helvetica, sans-serif;
    color: #192338;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-card {
    width: 100%;
    max-width: 680px;
    padding: 42px 52px;
    background: #ffffff;
    border: 1px solid #e3e9f2;
    border-radius: 5px;
    box-shadow: 0 8px 35px rgba(24, 47, 80, 0.06);
}

.brand {
    display: flex;
    align-items: center;
    gap: 17px;
    margin-bottom: 58px;
}

.logo {
    width: 66px;
    height: 66px;
    flex-shrink: 0;
    border-radius: 18px;
    background: #0860df;
    color: #ffffff;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 24px;
    font-weight: bold;
}

.brand-name {
    font-size: 25px;
    font-weight: bold;
    margin-bottom: 7px;
}

.brand-subtitle {
    font-size: 17px;
    color: #718096;
}

h1 {
    font-size: 48px;
    line-height: 1.15;
    margin: 0 0 35px;
    letter-spacing: -1px;
}

.description {
    color: #657991;
    font-size: 22px;
    line-height: 1.7;
    margin: 0 0 27px;
}

.error {
    background: #fee2e2;
    color: #b42318;
    padding: 18px 21px;
    border-radius: 13px;
    margin-bottom: 25px;
    font-size: 16px;
    line-height: 1.5;
}

label {
    display: block;
    font-size: 17px;
    font-weight: bold;
    margin: 25px 0 13px;
}

input {
    width: 100%;
    height: 77px;
    padding: 0 20px;
    border: 1px solid #c8d5e6;
    border-radius: 15px;
    background: white;
    outline: none;
    font-size: 21px;
    color: #192338;
    transition: border-color .2s, box-shadow .2s;
}

input::placeholder {
    color: #8190a5;
}

input:focus {
    border-color: #0860df;
    box-shadow: 0 0 0 3px rgba(8, 96, 223, .12);
}

button {
    width: 100%;
    min-height: 65px;
    margin-top: 0;
    border: none;
    border-radius: 15px;
    background: #0860df;
    color: white;
    font-size: 19px;
    font-weight: bold;
    cursor: pointer;
    transition: background .2s;
}

button:hover {
    background: #064db5;
}

.test-accounts {
    margin-top: 27px;
    padding: 22px 20px;
    background: #f7f9fc;
    border: 1px solid #dce5f0;
    border-radius: 14px;
    color: #647991;
    font-size: 16px;
    line-height: 1.9;
}

.test-accounts strong {
    display: block;
    margin-bottom: 3px;
    color: #647991;
}

.test-accounts code {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 16px;
}

.back-link {
    display: block;
    margin-top: 25px;
    text-align: center;
    color: #1769aa;
    text-decoration: none;
    font-size: 15px;
}

.back-link:hover {
    text-decoration: underline;
}

.footer {
    margin-top: 35px;
    text-align: center;
    font-size: 12px;
    color: #8793a3;
}

@media (max-width: 600px) {
    body {
        padding: 15px 10px;
        align-items: flex-start;
    }

    .login-card {
        padding: 28px 22px;
        border-radius: 12px;
    }

    .brand {
        margin-bottom: 43px;
        gap: 13px;
    }

    .logo {
        width: 55px;
        height: 55px;
        border-radius: 15px;
        font-size: 20px;
    }

    .brand-name {
        font-size: 21px;
    }

    .brand-subtitle {
        font-size: 15px;
    }

    h1 {
        font-size: 36px;
        margin-bottom: 25px;
    }

    .description {
        font-size: 17px;
        margin-bottom: 23px;
    }

    label {
        font-size: 15px;
        margin-top: 22px;
    }

    input {
        height: 62px;
        font-size: 17px;
        border-radius: 12px;
    }

    button {
        min-height: 60px;
        font-size: 17px;
        border-radius: 12px;
    }

    .test-accounts {
        font-size: 14px;
        padding: 17px;
    }
}
</style>
</head>

<body>

<main class="login-card">

    <div class="brand">
        <div class="logo">UMB</div>
        <div>
            <div class="brand-name">SIMDOSEN</div>
            <div class="brand-subtitle">UM Bengkulu</div>
        </div>
    </div>

    <h1>Selamat Datang</h1>

    <p class="description">
        Silakan masuk untuk mengelola Sistem Informasi Dosen.
    </p>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="username">Username</label>
        <input
    type="text"
    id="username"
    name="username"
    placeholder="Masukkan username atau email"
    autocomplete="username"
    required
>

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Masuk ke Sistem</button>
    </form>

    <a href="index.php" class="back-link">
        &larr; Kembali ke halaman utama
    </a>

    <div class="footer">
        &copy; 2026 SIMDOSEN — Universitas Muhammadiyah Bengkulu
    </div>

</main>

</body>
</html>