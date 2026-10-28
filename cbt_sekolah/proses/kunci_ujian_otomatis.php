<?php
session_start();
require_once '../config/koneksi.php';

// Pastikan ada sesi siswa dan ID ujian yang dikirim
if (!isset($_SESSION['id_user']) || !isset($_GET['id'])) {
    header("Location: ../pages/siswa/dashboard.php");
    exit;
}

$id_siswa = $_SESSION['id_user'];
$id_ujian = $_GET['id'];

try {
    // 1. Update status pengerjaan menjadi 'terkunci' di tabel tb_nilai
    // Ini adalah hukuman bagi siswa yang terdeteksi pindah tab browser
    $sql = "UPDATE tb_nilai SET status_pengerjaan = 'terkunci' 
            WHERE id_ujian = ? AND id_siswa = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ujian, $id_siswa]);

    // 2. Tambahkan catatan di log atau aktivitas jika diperlukan
    // (Opsional: Bapak bisa membuat tabel tb_log_kecurangan jika ingin merekam waktu kejadian)

    // 3. Arahkan siswa kembali ke dashboard
    // Di dashboard nanti, tombol "KERJAKAN" akan otomatis berubah jadi "TERKUNCI" merah
    $_SESSION['notif'] = [
        'type' => 'error',
        'title' => 'AKSES DIBLOKIR!',
        'text' => 'Anda terdeteksi keluar dari layar ujian. Silakan hubungi pengawas.'
    ];

    header("Location: ../pages/siswa/dashboard.php");
    exit;
} catch (Exception $e) {
    // Jika ada error database
    die("Gagal mengunci ujian: " . $e->getMessage());
}
