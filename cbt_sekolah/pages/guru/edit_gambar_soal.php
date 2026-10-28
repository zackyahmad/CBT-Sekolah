<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$id_soal = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_ujian = isset($_GET['ujian']) ? (int)$_GET['ujian'] : 0;

// Ambil data soal
$stmt = $pdo->prepare("SELECT id_soal, pertanyaan, gambar FROM tb_soal WHERE id_soal = ?");
$stmt->execute([$id_soal]);
$soal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$soal) {
    die("Soal tidak ditemukan.");
}

// PROSES UPLOAD GAMBAR
if (isset($_POST['upload_gambar'])) {
    $upload_dir = '../../uploads/soal/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_name = $_FILES['gambar']['name'];
    $file_tmp = $_FILES['gambar']['tmp_name'];
    
    if (!empty($file_name)) {
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $valid_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $valid_ext)) {
            // Hapus gambar lama jika ada
            if (!empty($soal['gambar']) && file_exists($upload_dir . $soal['gambar'])) {
                unlink($upload_dir . $soal['gambar']);
            }

            $nama_baru = 'soal_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $nama_baru)) {
                // Update ke database
                $update = $pdo->prepare("UPDATE tb_soal SET gambar = ? WHERE id_soal = ?");
                $update->execute([$nama_baru, $id_soal]);
                
                echo "<script>alert('Gambar berhasil diupload!'); window.location.href='kelola_soal_ujian.php?id=$id_ujian';</script>";
                exit;
            }
        } else {
            $error = "Format file tidak didukung! Harus JPG, PNG, atau GIF.";
        }
    }
}

// PROSES HAPUS GAMBAR
if (isset($_POST['hapus_gambar'])) {
    $upload_dir = '../../uploads/soal/';
    if (!empty($soal['gambar']) && file_exists($upload_dir . $soal['gambar'])) {
        unlink($upload_dir . $soal['gambar']);
    }
    $update = $pdo->prepare("UPDATE tb_soal SET gambar = NULL WHERE id_soal = ?");
    $update->execute([$id_soal]);
    echo "<script>alert('Gambar dihapus!'); window.location.href='kelola_soal_ujian.php?id=$id_ujian';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Gambar Soal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 p-8 flex justify-center items-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-2xl border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800"><i class="fas fa-image text-blue-500 mr-2"></i> Kelola Gambar Soal</h2>
            <a href="kelola_soal_ujian.php?id=<?= $id_ujian ?>" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></a>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= $error ?></div>
        <?php endif; ?>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6 text-slate-700">
            <p class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-2">Pertanyaan:</p>
            <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <?php if (!empty($soal['gambar'])): ?>
                <div class="mb-6 text-center border-2 border-dashed border-slate-300 p-4 rounded-xl">
                    <p class="text-sm font-bold text-green-600 mb-2"><i class="fas fa-check-circle"></i> Gambar Saat Ini:</p>
                    <img src="../../uploads/soal/<?= $soal['gambar'] ?>" class="mx-auto max-h-48 rounded-lg shadow-sm mb-3">
                    <button type="submit" name="hapus_gambar" class="text-xs bg-red-100 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-200 font-bold"><i class="fas fa-trash"></i> Hapus Gambar</button>
                </div>
            <?php endif; ?>

            <label class="block text-sm font-bold text-slate-700 mb-2">Upload Gambar Baru (Opsional)</label>
            <input type="file" name="gambar" accept="image/*" class="w-full border border-slate-300 rounded-xl p-2 mb-6 focus:outline-none focus:border-blue-500" required>
            
            <div class="flex gap-3">
                <a href="kelola_soal_ujian.php?id=<?= $id_ujian ?>" class="flex-1 bg-slate-100 text-slate-600 text-center py-3 rounded-xl font-bold hover:bg-slate-200 transition">Batal</a>
                <button type="submit" name="upload_gambar" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition"><i class="fas fa-upload mr-1"></i> Simpan Gambar</button>
            </div>
        </form>
    </div>
</body>
</html>