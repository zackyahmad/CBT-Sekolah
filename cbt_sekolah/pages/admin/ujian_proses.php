<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// ==========================================
// 1. TAMBAH UJIAN BARU
// ==========================================
if (isset($_POST['tambah_ujian'])) {
    $judul_ujian = $_POST['judul_ujian'];
    $id_mapel = $_POST['id_mapel'];
    $id_guru = $_POST['id_guru'];
    $tanggal_ujian = $_POST['tanggal_ujian'];
    $jam_mulai = $_POST['jam_mulai'];
    $durasi_menit = $_POST['durasi_menit'];
    $status_ujian = $_POST['status_ujian'] ?? 'tidak_aktif';
    
    // Ini adalah array kelas yang dicentang oleh Admin
    $kelas_terpilih = $_POST['kelas'] ?? []; 
    
    // Generate token random
    $token = strtoupper(substr(md5(uniqid()), 0, 6));
    
    try {
        // Mulai transaksi database agar aman
        $pdo->beginTransaction();

        // 1. Simpan data utama ke tb_ujian
        $query = "INSERT INTO tb_ujian (judul_ujian, id_mapel, id_guru, tanggal_ujian, jam_mulai, durasi_menit, status_ujian, token, tampil_nilai, acak_soal, anti_tab) 
                  VALUES (:judul_ujian, :id_mapel, :id_guru, :tanggal_ujian, :jam_mulai, :durasi_menit, :status_ujian, :token, 1, 1, 1)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'judul_ujian' => $judul_ujian,
            'id_mapel' => $id_mapel,
            'id_guru' => $id_guru,
            'tanggal_ujian' => $tanggal_ujian,
            'jam_mulai' => $jam_mulai,
            'durasi_menit' => $durasi_menit,
            'status_ujian' => $status_ujian,
            'token' => $token
        ]);
        
        // Ambil ID ujian yang baru saja dibuat
        $id_ujian_baru = $pdo->lastInsertId();

        // 2. SIMPAN RELASI KELAS KE tb_ujian_kelas (Mata Rantai yang Hilang)
        if (!empty($kelas_terpilih)) {
            $stmt_kelas = $pdo->prepare("INSERT INTO tb_ujian_kelas (id_ujian, id_kelas) VALUES (?, ?)");
            foreach ($kelas_terpilih as $id_kelas) {
                $stmt_kelas->execute([$id_ujian_baru, $id_kelas]);
            }
        }

        // Commit transaksi
        $pdo->commit();
        
        $_SESSION['notif'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Jadwal ujian berhasil ditambahkan dan ditugaskan ke kelas.'
        ];
    } catch (PDOException $e) {
        $pdo->rollBack(); // Batalkan semua jika ada error
        $_SESSION['notif'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'text' => 'Error: ' . $e->getMessage()
        ];
    }
    
    header("Location: rencana_ujian.php");
    exit;
}

// ==========================================
// 2. UBAH STATUS UJIAN (Admin Override)
// ==========================================
if (isset($_GET['ubah_status'])) {
    $id_ujian = $_GET['ubah_status'];
    $status_baru = $_GET['status'];
    
    try {
        $query = "UPDATE tb_ujian SET status_ujian = :status WHERE id_ujian = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'status' => $status_baru,
            'id' => $id_ujian
        ]);
        
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Status ujian berhasil diubah'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Error: ' . $e->getMessage()];
    }
    
    header("Location: rencana_ujian.php");
    exit;
}

// ==========================================
// 3. HAPUS UJIAN
// ==========================================
if (isset($_GET['hapus_ujian'])) {
    $id_ujian = $_GET['hapus_ujian'];
    
    try {
        $query = "DELETE FROM tb_ujian WHERE id_ujian = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id_ujian]);
        
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Jadwal ujian berhasil dihapus'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Error: ' . $e->getMessage()];
    }
    
    header("Location: rencana_ujian.php");
    exit;
}

// ==========================================
// 4. EDIT UJIAN
// ==========================================
if (isset($_POST['update_ujian'])) {
    $id_ujian = $_POST['id_ujian'];
    $judul_ujian = $_POST['judul_ujian'];
    $id_mapel = $_POST['id_mapel'];
    $id_guru = $_POST['id_guru'];
    $tanggal_ujian = $_POST['tanggal_ujian'];
    $jam_mulai = $_POST['jam_mulai'];
    $durasi_menit = $_POST['durasi_menit'];
    $status_ujian = $_POST['status_ujian'];
    
    // Array kelas yang di-update
    $kelas_terpilih = $_POST['kelas'] ?? []; 
    
    try {
        $pdo->beginTransaction();

        // 1. Update data utama
        $query = "UPDATE tb_ujian SET 
                  judul_ujian = :judul_ujian,
                  id_mapel = :id_mapel,
                  id_guru = :id_guru,
                  tanggal_ujian = :tanggal_ujian,
                  jam_mulai = :jam_mulai,
                  durasi_menit = :durasi_menit,
                  status_ujian = :status_ujian
                  WHERE id_ujian = :id_ujian";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'judul_ujian' => $judul_ujian,
            'id_mapel' => $id_mapel,
            'id_guru' => $id_guru,
            'tanggal_ujian' => $tanggal_ujian,
            'jam_mulai' => $jam_mulai,
            'durasi_menit' => $durasi_menit,
            'status_ujian' => $status_ujian,
            'id_ujian' => $id_ujian
        ]);
        
        // 2. Perbarui Relasi Kelas (Hapus yang lama, masukkan yang baru)
        if (!empty($kelas_terpilih)) {
            // Hapus relasi kelas lama untuk ujian ini
            $pdo->prepare("DELETE FROM tb_ujian_kelas WHERE id_ujian = ?")->execute([$id_ujian]);
            
            // Masukkan relasi kelas yang baru dicentang
            $stmt_kelas = $pdo->prepare("INSERT INTO tb_ujian_kelas (id_ujian, id_kelas) VALUES (?, ?)");
            foreach ($kelas_terpilih as $id_kelas) {
                $stmt_kelas->execute([$id_ujian, $id_kelas]);
            }
        }

        $pdo->commit();
        
        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Jadwal ujian berhasil diperbarui'];
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Error: ' . $e->getMessage()];
    }
    
    header("Location: rencana_ujian.php");
    exit;
}

// Jika tidak ada aksi, redirect
header("Location: rencana_ujian.php");
exit;
?>