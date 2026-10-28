<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') { header("Location: ../../index.php"); exit; }
require_once '../../config/koneksi.php';

$id_hasil = isset($_GET['id_hasil']) ? (int)$_GET['id_hasil'] : 0;
$ujian_id_redirect = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

// Update Nilai Akhir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_koreksi'])) {
    $nilai_akhir = (float)$_POST['nilai_akhir'];
    $pdo->prepare("UPDATE tb_hasil_ujian SET nilai = ? WHERE id_hasil = ?")->execute([$nilai_akhir, $id_hasil]);
    
    // Redirect kembali ke halaman monitoring ujian
    header("Location: nilai_ujian.php?ujian_id=" . $ujian_id_redirect); 
    exit;
}

// Ambil Data Siswa & Ujian
$stmt_h = $pdo->prepare("
    SELECT h.*, u.nama_lengkap, u.nis, uj.judul_ujian, uj.id_ujian 
    FROM tb_hasil_ujian h 
    JOIN tb_users u ON h.id_user = u.id_user 
    JOIN tb_ujian uj ON h.id_ujian = uj.id_ujian 
    WHERE h.id_hasil = ?
");
$stmt_h->execute([$id_hasil]);
$hasil = $stmt_h->fetch(PDO::FETCH_ASSOC);

if (!$hasil) { die("Data pengerjaan tidak ditemukan."); }

// Ambil Rincian Jawaban Siswa
$stmt_j = $pdo->prepare("
    SELECT j.jawaban, s.pertanyaan, s.jenis_soal, s.kunci_jawaban 
    FROM tb_jawaban j 
    JOIN tb_soal s ON j.id_soal = s.id_soal 
    WHERE j.id_hasil = ?
");
$stmt_j->execute([$id_hasil]);
$daftar_jawaban = $stmt_j->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Koreksi Jawaban</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen p-6">

    <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-6">
        
        <div class="md:w-2/3 space-y-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between mb-2">
                <h2 class="font-bold text-slate-800 text-lg"><i class="fas fa-file-alt text-blue-500 mr-2"></i> Lembar Jawaban Siswa</h2>
                <a href="nilai_ujian.php?ujian_id=<?= $ujian_id_redirect ?>" class="text-sm bg-slate-100 text-slate-600 px-4 py-2 rounded-lg font-bold hover:bg-slate-200 transition">Kembali</a>
            </div>

            <?php $no = 1; foreach ($daftar_jawaban as $j): 
                $is_pg = ($j['jenis_soal'] == '1');
                $jwb_siswa = strtoupper(trim($j['jawaban']));
                $kunci = strtoupper(trim($j['kunci_jawaban']));
                $is_benar = $is_pg && ($jwb_siswa === $kunci);
            ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex gap-4">
                        <div class="w-8 h-8 flex-shrink-0 bg-blue-50 text-blue-600 font-bold rounded-lg flex items-center justify-center"><?= $no++ ?></div>
                        <div class="flex-1">
                            <div class="text-slate-800 font-medium mb-3"><?= strip_tags($j['pertanyaan']) ?></div>
                            
                            <?php if ($is_pg): ?>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Jawaban Siswa:</p>
                                        <span class="font-bold text-lg <?= $is_benar ? 'text-emerald-600' : 'text-red-500' ?>"><?= $jwb_siswa ?: '-' ?></span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-slate-500 mb-1">Kunci:</p>
                                        <span class="font-bold text-slate-700 bg-slate-200 px-2 py-1 rounded"><?= $kunci ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                                    <p class="text-xs text-amber-600 font-bold uppercase mb-2"><i class="fas fa-pen mr-1"></i> Jawaban Essay:</p>
                                    <p class="text-slate-700 whitespace-pre-wrap"><?= htmlspecialchars($j['jawaban']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="md:w-1/3">
            <div class="bg-white p-6 rounded-3xl shadow-lg border border-slate-200 sticky top-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-3"><i class="fas fa-user-graduate"></i></div>
                    <h3 class="font-bold text-xl text-slate-800"><?= htmlspecialchars($hasil['nama_lengkap']) ?></h3>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($hasil['judul_ujian']) ?></p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100 text-center">
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">Nilai Saat Ini (Skor PG)</p>
                    <p class="text-4xl font-black text-slate-800"><?= round($hasil['nilai'], 1) ?></p>
                </div>

                <form method="POST">
                    <label class="block text-xs font-bold text-blue-600 uppercase tracking-wide mb-2 text-center">Update Nilai Akhir</label>
                    <input type="number" step="0.1" max="100" min="0" name="nilai_akhir" value="<?= round($hasil['nilai'], 1) ?>" required 
                           class="w-full text-center text-4xl font-black text-emerald-600 py-4 border-2 border-emerald-200 rounded-2xl focus:border-emerald-500 outline-none transition bg-emerald-50 mb-4">
                    
                    <button type="submit" name="simpan_koreksi" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Simpan Nilai Akhir
                    </button>
                </form>
            </div>
        </div>

    </div>
</body>
</html>