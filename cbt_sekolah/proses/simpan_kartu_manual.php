<?php
session_start();
header('Content-Type: application/json');

// Cek login admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/koneksi.php';

// Fungsi untuk upload file
function uploadFoto($file, $nis) {
    $target_dir = "../uploads/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate nama file unik
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = "foto_" . $nis . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Validasi file gambar
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar'];
    }
    
    // Batasi ukuran file (max 2MB)
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)'];
    }
    
    // Izinkan format tertentu
    $allowed_formats = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_formats)) {
        return ['success' => false, 'message' => 'Format file tidak diizinkan (JPG, JPEG, PNG, GIF)'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Ambil data dari form
$nis = trim($_POST['nis'] ?? '');
$nama_siswa = trim($_POST['nama_siswa'] ?? '');
$kelas = trim($_POST['kelas'] ?? '');
$jurusan = trim($_POST['jurusan'] ?? '');
$sid = trim($_POST['sid'] ?? '');
$tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
$username_ujian = trim($_POST['username_ujian'] ?? '');
$password_ujian = trim($_POST['password_ujian'] ?? '');
$foto_siswa = null;

// Validasi data wajib
if (empty($nis)) {
    echo json_encode(['success' => false, 'message' => 'NIS tidak boleh kosong']);
    exit;
}

if (empty($nama_siswa)) {
    echo json_encode(['success' => false, 'message' => 'Nama siswa tidak boleh kosong']);
    exit;
}

// Cek apakah NIS sudah ada
$checkQuery = "SELECT id_kartu FROM tb_kartu_ujian WHERE nis = ?";
$checkStmt = $pdo->prepare($checkQuery);
$checkStmt->execute([$nis]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'NIS sudah terdaftar! Gunakan NIS yang berbeda.']);
    exit;
}

// Set default jika kosong
if (empty($username_ujian)) {
    $username_ujian = $nis;
}
if (empty($password_ujian)) {
    $password_ujian = rand(100000, 999999);
}
if (empty($kelas)) {
    $kelas = 'X RPL 1';
}

// Proses upload foto jika ada
if (isset($_FILES['foto_siswa']) && $_FILES['foto_siswa']['error'] === UPLOAD_ERR_OK) {
    $upload_result = uploadFoto($_FILES['foto_siswa'], $nis);
    if ($upload_result['success']) {
        $foto_siswa = $upload_result['filename'];
    } else {
        echo json_encode(['success' => false, 'message' => $upload_result['message']]);
        exit;
    }
}

// Query INSERT menggunakan prepared statement
$query = "INSERT INTO tb_kartu_ujian (nis, nama_siswa, kelas, jurusan, sid, tempat_lahir, tanggal_lahir, username_ujian, password_ujian, foto_siswa) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

try {
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        $nis, 
        $nama_siswa, 
        $kelas, 
        $jurusan, 
        $sid, 
        $tempat_lahir, 
        $tanggal_lahir, 
        $username_ujian, 
        $password_ujian, 
        $foto_siswa
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>