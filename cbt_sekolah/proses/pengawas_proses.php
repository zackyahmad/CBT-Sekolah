<?php
session_start();
require_once '../config/koneksi.php';

// Inisialisasi koneksi
if (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
} else if (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} else {
    $db = new mysqli('localhost', 'root', '', 'db_ujian_sekolah');
}

// Tambah pengawas
if (isset($_POST['add_pengawas'])) {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $ruangan = $_POST['ruangan'] ?? '';
    $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
    
    if ($db instanceof mysqli) {
        $stmt = $db->prepare("INSERT INTO tb_users (username, nama_lengkap, password, ruangan, role) VALUES (?, ?, ?, ?, 'guru')");
        $stmt->bind_param("ssss", $username, $nama_lengkap, $password, $ruangan);
        $result = $stmt->execute();
    } else {
        $stmt = $db->prepare("INSERT INTO tb_users (username, nama_lengkap, password, ruangan, role) VALUES (?, ?, ?, ?, 'guru')");
        $result = $stmt->execute([$username, $nama_lengkap, $password, $ruangan]);
    }
    
    if ($result) {
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Pengawas berhasil ditambahkan'];
    } else {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal menambahkan pengawas'];
    }
    header("Location: ../pages/admin/data_pengawas.php");
    exit;
}

// Edit pengawas
if (isset($_POST['edit_pengawas'])) {
    $id = $_POST['id_user'];
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $ruangan = $_POST['ruangan'] ?? '';
    
    if ($db instanceof mysqli) {
        $stmt = $db->prepare("UPDATE tb_users SET username = ?, nama_lengkap = ?, ruangan = ? WHERE id_user = ? AND role = 'guru'");
        $stmt->bind_param("sssi", $username, $nama_lengkap, $ruangan, $id);
    } else {
        $stmt = $db->prepare("UPDATE tb_users SET username = ?, nama_lengkap = ?, ruangan = ? WHERE id_user = ? AND role = 'guru'");
        $result = $stmt->execute([$username, $nama_lengkap, $ruangan, $id]);
    }
    $result = $stmt->execute();
    
    if ($result) {
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Data pengawas berhasil diupdate'];
    } else {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal mengupdate data'];
    }
    header("Location: ../pages/admin/data_pengawas.php");
    exit;
}

// Hapus pengawas
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    if ($db instanceof mysqli) {
        $stmt = $db->prepare("DELETE FROM tb_users WHERE id_user = ? AND role = 'guru'");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
    } else {
        $stmt = $db->prepare("DELETE FROM tb_users WHERE id_user = ? AND role = 'guru'");
        $result = $stmt->execute([$id]);
    }
    
    if ($result) {
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Pengawas berhasil dihapus'];
    } else {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal menghapus pengawas'];
    }
    header("Location: ../pages/admin/data_pengawas.php");
    exit;
}
?>