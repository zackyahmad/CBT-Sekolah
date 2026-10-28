<?php
session_start();
require_once '../config/koneksi.php';

// 1. TAMBAH MAPEL
if (isset($_POST['add_mapel'])) {
    $kode = strtoupper(trim($_POST['kode_mapel']));
    $nama = trim($_POST['nama_mapel']);

    try {
        $sql = "INSERT INTO tb_mapel (kode_mapel, nama_mapel) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kode, $nama]);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil', 'text' => 'Mapel baru ditambahkan!'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/data_mapel.php");
    exit;
}

// 2. EDIT MAPEL
if (isset($_POST['edit_mapel'])) {
    $id   = $_POST['id_mapel'];
    $kode = strtoupper(trim($_POST['kode_mapel']));
    $nama = trim($_POST['nama_mapel']);

    try {
        $sql = "UPDATE tb_mapel SET kode_mapel = ?, nama_mapel = ? WHERE id_mapel = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kode, $nama, $id]);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil', 'text' => 'Data mapel diperbarui!'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/data_mapel.php");
    exit;
}

// 3. HAPUS MAPEL
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $sql = "DELETE FROM tb_mapel WHERE id_mapel = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Terhapus', 'text' => 'Mapel berhasil dihapus.'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => 'Data ini masih digunakan!'];
    }
    header("Location: ../pages/admin/data_mapel.php");
    exit;
}
