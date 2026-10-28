<?php
session_start();

// Cek login admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

require_once '../config/koneksi.php';

$id_kartu = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kartu > 0) {
    // Ambil nama foto untuk dihapus dari folder
    $stmt = $pdo->prepare("SELECT foto_siswa FROM tb_kartu_ujian WHERE id_kartu = ?");
    $stmt->execute([$id_kartu]);
    $data = $stmt->fetch();
    
    if ($data && !empty($data['foto_siswa'])) {
        $file_path = "../uploads/" . $data['foto_siswa'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Hapus data dari database
    $stmt = $pdo->prepare("DELETE FROM tb_kartu_ujian WHERE id_kartu = ?");
    if ($stmt->execute([$id_kartu])) {
        $_SESSION['success'] = "Data kartu ujian berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data";
    }
}

header("Location: ../admin/kartu_ujian.php");
exit;
?>