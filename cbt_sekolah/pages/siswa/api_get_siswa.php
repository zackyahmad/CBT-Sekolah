<?php
session_start();
ob_start(); // Tahan semua output error agar tidak merusak format JSON
error_reporting(0); // Matikan tampilan error sementara

require_once '../../config/koneksi.php';

header('Content-Type: application/json');

$id_ujian = isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0;

// Gunakan koneksi PDO kamu
if ($id_ujian > 0 && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id_soal, s.pertanyaan, s.opsi_a, s.opsi_b, s.opsi_c, s.opsi_d, s.opsi_e 
            FROM tb_ujian_soal us 
            JOIN tb_soal s ON us.id_soal = s.id_soal 
            WHERE us.id_ujian = ? 
            ORDER BY us.ditambahkan_pada ASC
        ");
        $stmt->execute([$id_ujian]);
        $soal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_end_clean(); // Bersihkan layar di balik layar
        echo json_encode(['status' => 'success', 'data' => $soal]);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
} else {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'ID Ujian tidak ditemukan atau Koneksi PDO gagal']);
    exit;
}
?>