<?php
session_start();
// Cek Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../config/koneksi.php';

$aksi     = $_GET['aksi'];
$id_ujian = $_GET['u'];
$id_siswa = $_GET['s'];

if ($aksi == 'reset') {
    // LOGIKA RESET LOGIN:
    // 1. Ubah status jadi 'sedang'
    // 2. Kosongkan waktu_selesai (biar timer jalan lagi)
    // 3. (Opsional) Reset pelanggaran jadi 0 kalau mau memaafkan siswa

    $sql = "UPDATE tb_nilai SET 
            status_pengerjaan = 'sedang', 
            waktu_selesai = NULL,
            pelanggaran = 0 
            WHERE id_ujian = ? AND id_siswa = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ujian, $id_siswa]);

    // Redirect balik dengan pesan sukses
    header("Location: ../pages/admin/monitoring_detail.php?id=$id_ujian&msg=reset_ok");
} elseif ($aksi == 'blokir') {
    // LOGIKA BLOKIR / PAKSA SELESAI:
    // Ubah status jadi 'terkunci'

    $sql = "UPDATE tb_nilai SET 
            status_pengerjaan = 'terkunci',
            waktu_selesai = NOW() 
            WHERE id_ujian = ? AND id_siswa = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ujian, $id_siswa]);

    header("Location: ../pages/admin/monitoring_detail.php?id=$id_ujian&msg=blokir_ok");
}
