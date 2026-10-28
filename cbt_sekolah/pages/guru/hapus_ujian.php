<?php
// =============================================
// FILE: pages/guru/hapus_ujian.php
// Handler hapus ujian (dipanggil via GET/POST)
// =============================================
require_once '../includes/config.php';
cekLoginGuru();

$guru_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Pastikan ujian milik guru ini
    $cek = $conn->query("SELECT id FROM ujian WHERE id=$id AND guru_id=$guru_id");
    if ($cek->num_rows > 0) {
        // Hapus data terkait
        $conn->query("DELETE FROM hasil_ujian WHERE ujian_id=$id");
        $conn->query("DELETE FROM soal_ujian WHERE ujian_id=$id");
        $conn->query("DELETE FROM absensi WHERE ujian_id=$id");
        $conn->query("DELETE FROM ujian WHERE id=$id");
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Ujian dan semua datanya berhasil dihapus.'];
    } else {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Ujian tidak ditemukan atau Anda tidak berhak menghapusnya.'];
    }
} else {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'ID ujian tidak valid.'];
}

header("Location: setting_ujian.php");
exit();
?>
