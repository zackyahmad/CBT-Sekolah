<?php
session_start();
require_once '../config/koneksi.php';

// 1. TAMBAH SISWA MANUAL
if (isset($_POST['add_siswa'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['username'], PASSWORD_DEFAULT); // Default password = username
    $nama     = $_POST['nama_lengkap'];
    $id_kelas = $_POST['id_kelas'];
    $nisn     = $_POST['nisn'];
    $nis      = $_POST['nis'];
    $jk       = $_POST['jenis_kelamin'];
    $agama    = $_POST['agama'];
    $tmpt     = $_POST['tempat_lahir'];
    $tgl      = $_POST['tanggal_lahir'];
    $alamat   = $_POST['alamat'];

    try {
        $sql = "INSERT INTO tb_users (username, password, nama_lengkap, role, id_kelas, nisn, nis, jenis_kelamin, agama, tempat_lahir, tanggal_lahir, alamat) 
                VALUES (?, ?, ?, 'siswa', ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $password, $nama, $id_kelas, $nisn, $nis, $jk, $agama, $tmpt, $tgl, $alamat]);

        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil', 'text' => 'Siswa SMKN 8 BL berhasil ditambahkan!'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/data_siswa.php");
    exit;
}

// 2. EDIT SISWA
if (isset($_POST['edit_siswa'])) {
    $id       = $_POST['id_user'];
    $username = $_POST['username'];
    $nama     = $_POST['nama_lengkap'];
    $id_kelas = $_POST['id_kelas'];
    $nisn     = $_POST['nisn'];
    $nis      = $_POST['nis'];
    $jk       = $_POST['jenis_kelamin'];
    $agama    = $_POST['agama'];
    $tmpt     = $_POST['tempat_lahir'];
    $tgl      = $_POST['tanggal_lahir'];
    $alamat   = $_POST['alamat'];

    try {
        // Logika Ganti Password: Jika input password diisi, maka di-update
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE tb_users SET username=?, password=?, nama_lengkap=?, id_kelas=?, nisn=?, nis=?, jenis_kelamin=?, agama=?, tempat_lahir=?, tanggal_lahir=?, alamat=? WHERE id_user=?";
            $params = [$username, $pass, $nama, $id_kelas, $nisn, $nis, $jk, $agama, $tmpt, $tgl, $alamat, $id];
        } else {
            $sql = "UPDATE tb_users SET username=?, nama_lengkap=?, id_kelas=?, nisn=?, nis=?, jenis_kelamin=?, agama=?, tempat_lahir=?, tanggal_lahir=?, alamat=? WHERE id_user=?";
            $params = [$username, $nama, $id_kelas, $nisn, $nis, $jk, $agama, $tmpt, $tgl, $alamat, $id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Update Berhasil', 'text' => 'Profil siswa telah diperbarui!'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/data_siswa.php");
    exit;
}

// 3. HAPUS SISWA
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        // Pastikan menghapus user yang benar-benar siswa
        $pdo->prepare("DELETE FROM tb_users WHERE id_user = ? AND role = 'siswa'")->execute([$id]);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Terhapus', 'text' => 'Data siswa berhasil dihapus permanen.'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => 'Data gagal dihapus karena masih terkait dengan data ujian/nilai.'];
    }
    header("Location: ../pages/admin/data_siswa.php");
    exit;
}
