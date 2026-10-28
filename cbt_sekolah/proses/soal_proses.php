<?php
session_start();
require_once '../config/koneksi.php';

if (isset($_POST['add_soal_manual'])) {
    $id_mapel    = $_POST['id_mapel'];
    $materi      = $_POST['materi'];
    $bobot       = $_POST['bobot'];
    $pertanyaan  = $_POST['pertanyaan'];
    $opsi_a      = $_POST['opsi_a'];
    $opsi_b      = $_POST['opsi_b'];
    $opsi_c      = $_POST['opsi_c'];
    $opsi_d      = $_POST['opsi_d'];
    $opsi_e      = $_POST['opsi_e'];
    $kunci       = $_POST['kunci_jawaban'];
    $nama_gambar = null;

    // Handle Upload Gambar
    if ($_FILES['gambar']['name']) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_gambar = "soal_" . time() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], "../../assets/img/soal/" . $nama_gambar);
    }

    try {
        $sql = "INSERT INTO tb_soal (id_mapel, jenis_soal, bobot, materi, pertanyaan, gambar, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
                VALUES (?, '1', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$id_mapel, $bobot, $materi, $pertanyaan, $nama_gambar, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci]);

        $_SESSION['notif'] = ['type' => 'success', 'title' => 'Berhasil', 'text' => 'Soal baru telah ditambahkan ke gudang.'];
    } catch (PDOException $e) {
        $_SESSION['notif'] = ['type' => 'error', 'title' => 'Gagal', 'text' => $e->getMessage()];
    }
    header("Location: ../pages/admin/soal_list.php?id_mapel=" . $id_mapel);
    exit;
}

// LOGIKA EDIT SOAL
if (isset($_POST['edit_soal_manual'])) {
    $id_soal    = $_POST['id_soal'];
    $id_mapel   = $_POST['id_mapel'];
    $materi     = $_POST['materi'];
    $bobot      = $_POST['bobot'];
    $pertanyaan = $_POST['pertanyaan'];
    $opsi_a     = $_POST['opsi_a'];
    $opsi_b     = $_POST['opsi_b'];
    $opsi_c     = $_POST['opsi_c'];
    $opsi_d     = $_POST['opsi_d'];
    $opsi_e     = $_POST['opsi_e'];
    $kunci      = $_POST['kunci_jawaban'];

    // Handle Upload Gambar Baru
    if ($_FILES['gambar']['name']) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_gambar = "soal_" . time() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], "../../assets/img/soal/" . $nama_gambar);

        $sql = "UPDATE tb_soal SET materi=?, bobot=?, pertanyaan=?, gambar=?, opsi_a=?, opsi_b=?, opsi_c=?, opsi_d=?, opsi_e=?, kunci_jawaban=? WHERE id_soal=?";
        $pdo->prepare($sql)->execute([$materi, $bobot, $pertanyaan, $nama_gambar, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci, $id_soal]);
    } else {
        $sql = "UPDATE tb_soal SET materi=?, bobot=?, pertanyaan=?, opsi_a=?, opsi_b=?, opsi_c=?, opsi_d=?, opsi_e=?, kunci_jawaban=? WHERE id_soal=?";
        $pdo->prepare($sql)->execute([$materi, $bobot, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci, $id_soal]);
    }

    header("Location: ../pages/admin/soal_list.php?id_mapel=" . $id_mapel);
    exit;
}

// LOGIKA HAPUS SOAL
if (isset($_GET['hapus_soal'])) {
    $id_soal = $_GET['hapus_soal'];

    // Ambil data dulu buat tahu ID Mapel-nya (biar bisa balik ke halaman yang bener)
    $s = $pdo->prepare("SELECT id_mapel FROM tb_soal WHERE id_soal = ?");
    $s->execute([$id_soal]);
    $res = $s->fetch();

    $pdo->prepare("DELETE FROM tb_soal WHERE id_soal = ?")->execute([$id_soal]);

    header("Location: ../pages/admin/soal_list.php?id_mapel=" . $res['id_mapel']);
    exit;
}

if (isset($_GET['hapus_semua_soal'])) {
    $id_mapel = $_GET['hapus_semua_soal'];

    // Proses hapus dari database
    $query = "DELETE FROM tb_soal WHERE id_mapel = ?";
    $stmt = $pdo->prepare($query);

    if ($stmt->execute([$id_mapel])) {
        header("Location: ../pages/admin/soal_list.php?id_mapel=$id_mapel&status=sukses_hapus_semua");
    } else {
        header("Location: ../pages/admin/soal_list.php?id_mapel=$id_mapel&status=gagal");
    }
}
