<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /simdosen_tahap5/login.php');
        exit;
    }
}

function audit_log($conn, $user_id, $tabel, $record_id, $aksi) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $keterangan = null;

    $sql = "INSERT INTO audit_log
            (id_user, aksi, tabel_nama, record_id, alamat_ip, keterangan)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'isssss',
        $user_id,
        $aksi,
        $tabel,
        $record_id,
        $ip,
        $keterangan
    );

    return $stmt->execute();
}