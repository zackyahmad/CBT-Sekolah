<?php
session_start();
require_once '../config/koneksi.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    $file_tmp = $_FILES['file_siswa']['tmp_name'];
    $berhasil = 0;
    $gagal = 0;
    $pesan_gagal = [];

    try {
        $spreadsheet = IOFactory::load($file_tmp);
        $data = $spreadsheet->getActiveSheet()->toArray();

        foreach ($data as $index => $row) {
            if ($index == 0) continue;

            $nisn       = trim($row[0]);
            $nama       = trim($row[1]);
            $jk         = trim($row[2]);
            $tmpt       = trim($row[3]);
            $tgl        = trim($row[4]);
            $nis        = trim($row[5]); // Username
            $tingkat    = strtoupper(trim($row[6]));
            $nama_input = strtoupper(trim($row[7]));

            if (empty($nis)) continue;

            // 1. VALIDASI USERNAME: Cek apakah NIS/Username sudah ada?
            $stmt_cek_user = $pdo->prepare("SELECT username FROM tb_users WHERE username = ?");
            $stmt_cek_user->execute([$nis]);

            if ($stmt_cek_user->rowCount() > 0) {
                // Jika sudah ada, catat sebagai gagal dan lanjut ke baris berikutnya
                $gagal++;
                $pesan_gagal[] = "NIS $nis ($nama) sudah terdaftar.";
                continue;
            }

            // 2. Logika Kelas (Tingkat-Nama)
            $nama_lengkap_kelas = $tingkat . "-" . $nama_input;
            $stmt_cek_kls = $pdo->prepare("SELECT id_kelas FROM tb_kelas WHERE tingkat = ? AND nama_kelas = ?");
            $stmt_cek_kls->execute([$tingkat, $nama_lengkap_kelas]);
            $kelas = $stmt_cek_kls->fetch();

            if ($kelas) {
                $id_kelas = $kelas['id_kelas'];
            } else {
                $stmt_add = $pdo->prepare("INSERT INTO tb_kelas (tingkat, nama_kelas) VALUES (?, ?)");
                $stmt_add->execute([$tingkat, $nama_lengkap_kelas]);
                $id_kelas = $pdo->lastInsertId();
            }

            // 3. Simpan Data Baru
            $pass = password_hash($nis, PASSWORD_DEFAULT);
            $sql = "INSERT INTO tb_users (username, password, nama_lengkap, role, id_kelas, nisn, nis, jenis_kelamin, tempat_lahir, tanggal_lahir) 
                    VALUES (?, ?, ?, 'siswa', ?, ?, ?, ?, ?, ?)";

            $pdo->prepare($sql)->execute([$nis, $pass, $nama, $id_kelas, $nisn, $nis, $jk, $tmpt, $tgl]);
            $berhasil++;
        }

        // Susun Notifikasi Hasil Import
        $text_notif = "Berhasil: $berhasil. ";
        if ($gagal > 0) {
            $text_notif .= "Gagal (Duplikat): $gagal. Detail: " . implode(", ", $pesan_gagal);
        }

        $_SESSION['notif'] = [
            'type' => $gagal > 0 ? 'warning' : 'success',
            'title' => 'Selesai',
            'text' => $text_notif
        ];
    } catch (Exception $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/data_siswa.php");
    exit;
}
