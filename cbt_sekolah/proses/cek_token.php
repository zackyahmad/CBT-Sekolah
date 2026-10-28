<?php
session_start();
require_once '../config/koneksi.php';

header('Content-Type: application/json');

$id_ujian = $_GET['id'] ?? '';
$token_input = strtoupper(trim($_GET['token'] ?? ''));

try {
    $stmt = $pdo->prepare("SELECT token FROM tb_ujian WHERE id_ujian = ?");
    $stmt->execute([$id_ujian]);
    $ujian = $stmt->fetch();

    if ($ujian && $ujian['token'] === $token_input) {
        // Token Benar
        $_SESSION['ujian_aktif'] = $id_ujian; // Tandai siswa sedang ujian ini
        echo json_encode(['status' => 'success']);
    } else {
        // Token Salah
        echo json_encode(['status' => 'error', 'message' => 'Token yang Anda masukkan salah!']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem.']);
}
