<?php
session_start();
require_once '../config/koneksi.php';

// 1. Tambah Kelas
if (isset($_POST['add_kelas']) || isset($_POST['edit_kelas'])) {
    $tingkat = $_POST['tingkat'];
    $nama_input = strtoupper(trim($_POST['nama_kelas']));

    // Menggabungkan Tingkat dan Nama dengan tanda hubung
    $nama_lengkap_kelas = $tingkat . "-" . $nama_input;

    try {
        if (isset($_POST['add_kelas'])) {
            $sql = "INSERT INTO tb_kelas (tingkat, nama_kelas) VALUES (?, ?)";
            $params = [$tingkat, $nama_lengkap_kelas];
        } else {
            $sql = "UPDATE tb_kelas SET tingkat = ?, nama_kelas = ? WHERE id_kelas = ?";
            $params = [$tingkat, $nama_lengkap_kelas, $_POST['id_kelas']];
        }

        $pdo->prepare($sql)->execute($params);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil', 'text' => 'Rombel ' . $nama_lengkap_kelas . ' tersimpan!'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/data_kelas.php");
    exit;
}

// 2. Edit Kelas
if (isset($_POST['edit_kelas'])) {
    $id_kelas   = $_POST['id_kelas'];
    $tingkat    = $_POST['tingkat'];
    $nama_kelas = strtoupper(trim($_POST['nama_kelas']));

    try {
        $sql = "UPDATE tb_kelas SET tingkat = :tingkat, nama_kelas = :nama WHERE id_kelas = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['tingkat' => $tingkat, 'nama' => $nama_kelas, 'id' => $id_kelas]);

        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Update Berhasil', 'text' => 'Data rombel diperbarui!'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => 'Eror: ' . $e->getMessage()];
    }
    header("Location: ../pages/admin/data_kelas.php");
    exit;
}

// 3. Hapus Kelas
if (isset($_GET['hapus'])) {
    $id_kelas = $_GET['hapus'];
    try {
        $sql = "DELETE FROM tb_kelas WHERE id_kelas = :id";
        $pdo->prepare($sql)->execute(['id' => $id_kelas]);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Terhapus', 'text' => 'Data kelas berhasil dihapus.'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => 'Data masih digunakan oleh Siswa!'];
    }
    header("Location: ../pages/admin/data_kelas.php");
    exit;
}
