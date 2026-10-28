<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/koneksi.php';

if (isset($_POST['import'])) {
    if (isset($_FILES['file_guru']) && $_FILES['file_guru']['error'] == 0) {
        $file_name = $_FILES['file_guru']['tmp_name'];
        $file = fopen($file_name, "r");

        $berhasil = 0;
        $gagal = 0;
        $baris = 0;

        // Coba deteksi separator (koma atau titik koma)
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $baris++;
            if ($baris == 1) continue;

            // Jika baris kosong, skip
            if (empty($data[0])) continue;

            $username = trim($data[0]);
            $nama     = isset($data[1]) ? trim($data[1]) : '';

            if (empty($username) || empty($nama)) {
                $gagal++;
                continue;
            }

            try {
                $cek = $pdo->prepare("SELECT id_user FROM tb_users WHERE username = ?");
                $cek->execute([$username]);

                if ($cek->rowCount() == 0) {
                    $password = password_hash($username, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO tb_users (nama_lengkap, username, password, role) VALUES (?, ?, ?, 'guru')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nama, $username, $password]);
                    $berhasil++;
                } else {
                    $gagal++; // Username duplikat
                }
            } catch (Exception $e) {
                // Tampilkan error jika ada masalah database
                die("Error Database di baris $baris: " . $e->getMessage());
            }
        }
        fclose($file);

        $_SESSION['notif'] = [
            'type' => 'success',
            'title' => 'Import Selesai',
            'text' => "Berhasil: $berhasil guru. Gagal: $gagal."
        ];
    } else {
        $error_code = $_FILES['file_guru']['error'];
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Upload Gagal', 'text' => "Error Code: $error_code"];
    }
    header("Location: ../pages/admin/data_guru.php");
    exit;
} else {
    die("Akses langsung tidak diizinkan atau tombol submit tidak terdeteksi.");
}
