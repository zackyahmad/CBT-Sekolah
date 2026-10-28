<?php
session_start();
require_once '../config/koneksi.php';

// --- LOGIKA TAMBAH JADWAL BARU ---
if (isset($_POST['add_ujian'])) {
    $judul_ujian    = $_POST['judul_ujian'];
    $id_mapel       = $_POST['id_mapel'];
    $id_guru        = $_POST['id_guru'];
    $tanggal_ujian  = $_POST['tanggal_ujian'];
    $jam_mulai      = $_POST['jam_mulai'];
    $durasi_menit   = $_POST['durasi_menit'];

    $jam_selesai    = date('H:i:s', strtotime($jam_mulai . ' + ' . $durasi_menit . ' minutes'));
    $kelas_target   = isset($_POST['id_kelas']) ? $_POST['id_kelas'] : [];

    $acak_soal      = isset($_POST['acak_soal']) ? '1' : '0';
    $tampil_nilai   = isset($_POST['tampil_nilai']) ? '1' : '0';
    $anti_tab       = isset($_POST['anti_tab']) ? '1' : '0';

    $token          = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    if (empty($kelas_target)) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Pilih minimal satu kelas!'];
        header("Location: ../pages/admin/rencana_ujian.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO tb_ujian (id_mapel, id_guru, judul_ujian, tanggal_ujian, jam_mulai, jam_selesai, durasi_menit, acak_soal, tampil_nilai, anti_tab, token, status_ujian) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_mapel,
            $id_guru,
            $judul_ujian,
            $tanggal_ujian,
            $jam_mulai,
            $jam_selesai,
            $durasi_menit,
            $acak_soal,
            $tampil_nilai,
            $anti_tab,
            $token
        ]);

        $id_ujian_baru = $pdo->lastInsertId();

        // --- 🚀 FITUR OTOMATISASI: HUBUNGKAN SOAL ---
        // Mencari soal yang id_mapel-nya sama DAN masih NULL (belum punya jadwal)
        $sql_update_soal = "UPDATE tb_soal SET id_ujian = ? WHERE id_mapel = ? AND id_ujian IS NULL";
        $stmt_soal = $pdo->prepare($sql_update_soal);
        $stmt_soal->execute([$id_ujian_baru, $id_mapel]);
        // ----------------------------------------------------------------------------------

        $sql_kelas = "INSERT INTO tb_ujian_kelas (id_ujian, id_kelas) VALUES (?, ?)";
        $stmt_kelas = $pdo->prepare($sql_kelas);

        foreach ($kelas_target as $id_kelas) {
            $stmt_kelas->execute([$id_ujian_baru, $id_kelas]);
        }

        $pdo->commit();
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Jadwal dibuat & Soal otomatis terhubung.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => $e->getMessage()];
    }

    header("Location: ../pages/admin/rencana_ujian.php");
    exit;
}

// --- LOGIKA HAPUS JADWAL ---
if (isset($_GET['hapus_ujian'])) {
    $id = $_GET['hapus_ujian'];
    try {
        $pdo->beginTransaction();

        // Sebelum hapus ujian, kembalikan id_ujian di tb_soal menjadi NULL agar soal tidak ikut terhapus
        $pdo->prepare("UPDATE tb_soal SET id_ujian = NULL WHERE id_ujian = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM tb_ujian_kelas WHERE id_ujian = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM tb_ujian WHERE id_ujian = ?")->execute([$id]);
        $pdo->commit();
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Dihapus!', 'text' => 'Jadwal ujian telah dibersihkan.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/rencana_ujian.php");
    exit;
}

// --- LOGIKA UPDATE JADWAL ---
if (isset($_POST['update_ujian'])) {
    $id_ujian       = $_POST['id_ujian'];
    $judul_ujian    = $_POST['judul_ujian'];
    $id_mapel       = $_POST['id_mapel'];
    $id_guru        = $_POST['id_guru'];
    $tanggal_ujian  = $_POST['tanggal_ujian'];
    $jam_mulai      = $_POST['jam_mulai'];
    $durasi_menit   = $_POST['durasi_menit'];
    $jam_selesai    = date('H:i:s', strtotime($jam_mulai . ' + ' . $durasi_menit . ' minutes'));

    $tampil_nilai   = isset($_POST['tampil_nilai']) ? '1' : '0';
    $acak_soal      = isset($_POST['acak_soal']) ? '1' : '0';
    $anti_tab       = isset($_POST['anti_tab']) ? '1' : '0';

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE tb_ujian SET id_mapel=?, id_guru=?, judul_ujian=?, tanggal_ujian=?, jam_mulai=?, jam_selesai=?, durasi_menit=?, tampil_nilai=?, acak_soal=?, anti_tab=? WHERE id_ujian=?";
        $pdo->prepare($sql)->execute([$id_mapel, $id_guru, $judul_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai, $durasi_menit, $tampil_nilai, $acak_soal, $anti_tab, $id_ujian]);

        // Re-sync soal jika mapel diubah
        $pdo->prepare("UPDATE tb_soal SET id_ujian = ? WHERE id_mapel = ? AND id_ujian IS NULL")->execute([$id_ujian, $id_mapel]);

        $pdo->prepare("DELETE FROM tb_ujian_kelas WHERE id_ujian = ?")->execute([$id_ujian]);
        if (isset($_POST['id_kelas'])) {
            foreach ($_POST['id_kelas'] as $id_k) {
                $pdo->prepare("INSERT INTO tb_ujian_kelas (id_ujian, id_kelas) VALUES (?, ?)")->execute([$id_ujian, $id_k]);
            }
        }

        $pdo->commit();
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Perubahan jadwal disimpan.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/rencana_ujian.php");
    exit;
}

// --- LOGIKA SET STATUS CEPAT ---
if (isset($_GET['set_status'])) {
    $id_ujian = $_GET['set_status'];
    $status   = $_GET['status'];

    try {
        $sql = "UPDATE tb_ujian SET status_ujian = ? WHERE id_ujian = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $id_ujian]);
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Status Diterapkan!', 'text' => 'Ujian sekarang ' . strtoupper($status)];
    } catch (Exception $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/rencana_ujian.php");
    exit;
}
