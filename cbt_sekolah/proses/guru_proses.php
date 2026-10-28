<?php
session_start();
require_once '../config/koneksi.php';

// --- LOGIKA EDIT GURU ---
if (isset($_POST['edit_guru'])) {
    $id_user  = $_POST['id_user'];
    $nama     = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password_baru = trim($_POST['password']);

    try {
        if (!empty($password_baru)) {
            // Jika Admin mengisi kolom password (berarti ganti password)
            $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $sql = "UPDATE tb_users SET nama_lengkap=:nama, username=:user, password=:pass WHERE id_user=:id";
            $params = ['nama' => $nama, 'user' => $username, 'pass' => $pass_hash, 'id' => $id_user];
        } else {
            // Jika password dikosongkan (berarti password lama tetap dipakai)
            $sql = "UPDATE tb_users SET nama_lengkap=:nama, username=:user WHERE id_user=:id";
            $params = ['nama' => $nama, 'user' => $username, 'id' => $id_user];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['notif'] = [
            'type' => 'success',
            'title' => 'Update Berhasil',
            'text' => 'Data guru berhasil diperbarui!'
        ];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => 'Eror: ' . $e->getMessage()];
    }
    header("Location: ../pages/admin/data_guru.php");
    exit;
}

// --- LOGIKA HAPUS GURU ---
if (isset($_GET['hapus'])) {
    $id_user = $_GET['hapus'];

    try {
        $sql = "DELETE FROM tb_users WHERE id_user = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id_user]);

        $_SESSION['notif'] = [
            'type' => 'success',
            'title' => 'Terhapus',
            'text' => 'Data guru berhasil dihapus dari sistem.'
        ];
    } catch (PDOException $e) {
        $_SESSION['notif'] = [
            'type' => 'error',
            'title' => 'Gagal',
            'text' => 'Data guru ini tidak bisa dihapus karena terkait dengan data soal/ujian.'
        ];
    }
    header("Location: ../pages/admin/data_guru.php");
    exit;
}
