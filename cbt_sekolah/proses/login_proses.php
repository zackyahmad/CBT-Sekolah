<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM tb_users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Login Sukses
                $_SESSION['id_user']   = $user['id_user'];
                $_SESSION['nama']      = $user['nama_lengkap'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['id_kelas']  = $user['id_kelas'];

                // Set Notifikasi Sukses
                $_SESSION['notif'] = [
                    'type' => 'success',
                    'title' => 'Login Berhasil!',
                    'text' => 'Selamat datang kembali, ' . $user['nama_lengkap']
                ];

                // Redirect sesuai role
                if ($user['role'] == 'admin') header("Location: ../pages/admin/dashboard.php");
                elseif ($user['role'] == 'guru') header("Location: ../pages/guru/dashboard.php");
                elseif ($user['role'] == 'siswa') header("Location: ../pages/siswa/dashboard.php");
                elseif ($user['role'] == 'pengawas')  header("Location: ../pages/pengawas/dashboard.php");
                exit;
            } else {
                // Password Salah
                $_SESSION['notif'] = [
                    'type' => 'error',
                    'title' => 'Gagal Masuk',
                    'text' => 'Password yang Anda masukkan salah!'
                ];
                header("Location: ../index.php");
                exit;
            }
        } else {
            // Username Tidak Ditemukan
            $_SESSION['notif'] = [
                'type' => 'error',
                'title' => 'Gagal Masuk',
                'text' => 'Username tidak terdaftar di sistem.'
            ];
            header("Location: ../index.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['notif'] = [
            'type' => 'error',
            'title' => 'Error Sistem',
            'text' => 'Terjadi kesalahan pada database.'
        ];
        header("Location: ../index.php");
        exit;
    }
}
?>