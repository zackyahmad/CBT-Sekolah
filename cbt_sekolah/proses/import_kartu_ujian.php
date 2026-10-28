<?php
session_start();
header('Content-Type: application/json');

// Cek login admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/koneksi.php';

// Cek file diupload
if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau gagal diupload']);
    exit;
}

// Load library PHPExcel/PhpSpreadsheet (jika pakai PhpSpreadsheet)
// Pastikan sudah install via composer atau gunakan library sederhana untuk CSV

$file = $_FILES['file_excel']['tmp_name'];
$file_extension = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

// Untuk CSV sederhana
if ($file_extension == 'csv') {
    $handle = fopen($file, "r");
    $headers = fgetcsv($handle, 1000, ",");
    
    $success_count = 0;
    $error_count = 0;
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row = array_combine($headers, $data);
        
        $nis = trim($row['nis'] ?? '');
        $nama_siswa = trim($row['nama_siswa'] ?? '');
        $kelas = trim($row['kelas'] ?? '');
        $jurusan = trim($row['jurusan'] ?? '');
        $sid = trim($row['sid'] ?? '');
        $tempat_lahir = trim($row['tempat_lahir'] ?? '');
        $tanggal_lahir = !empty($row['tanggal_lahir']) ? $row['tanggal_lahir'] : null;
        $username_ujian = !empty($row['username_ujian']) ? $row['username_ujian'] : $nis;
        $password_ujian = !empty($row['password_ujian']) ? $row['password_ujian'] : rand(100000, 999999);
        
        if (empty($nis) || empty($nama_siswa)) {
            $error_count++;
            continue;
        }
        
        // Cek duplikat NIS
        $checkStmt = $pdo->prepare("SELECT id_kartu FROM tb_kartu_ujian WHERE nis = ?");
        $checkStmt->execute([$nis]);
        if ($checkStmt->fetch()) {
            $error_count++;
            continue;
        }
        
        $query = "INSERT INTO tb_kartu_ujian (nis, nama_siswa, kelas, jurusan, sid, tempat_lahir, tanggal_lahir, username_ujian, password_ujian) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute([$nis, $nama_siswa, $kelas, $jurusan, $sid, $tempat_lahir, $tanggal_lahir, $username_ujian, $password_ujian])) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    fclose($handle);
    
    echo json_encode(['success' => true, 'total' => $success_count, 'message' => "$success_count data berhasil diimport, $error_count gagal"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan file CSV']);
}
?>