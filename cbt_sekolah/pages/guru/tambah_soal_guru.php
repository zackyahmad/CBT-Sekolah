<?php
// =============================================
// FILE: pages/guru/tambah_soal_guru.php
// Form tambah / edit soal — simpan ke tb_soal 
// =============================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$nama_guru = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Guru';
$id_mapel  = (int)($_GET['id_mapel'] ?? $_POST['id_mapel'] ?? 0);
$edit_id   = (int)($_GET['edit'] ?? 0);
$is_edit   = false;
$soal      = [];
$errors    = [];

// Ambil info mapel
$stmt = $pdo->prepare("SELECT * FROM tb_mapel WHERE id_mapel = ?");
$stmt->execute([$id_mapel]);
$mapel = $stmt->fetch();
if (!$mapel) { header("Location: bank_soal.php"); exit; }

// Load data soal jika mode edit
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM tb_soal WHERE id_soal = ? AND id_mapel = ?");
    $stmt->execute([$edit_id, $id_mapel]);
    $soal = $stmt->fetch();
    
    if ($soal) {
        $is_edit = true;
    } else {
        header("Location: soal_list_guru.php?id_mapel=$id_mapel"); exit;
    }
}

// ==========================================
// PROSES SIMPAN SOAL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    
    // PERBAIKAN 1: Pastikan menyimpan 1 untuk PG, 2 untuk Essay
    $jenis_soal = $_POST['tipe_soal'] ?? '1'; 
    
    $kunci      = trim($_POST['kunci_jawaban'] ?? '');
    $opsi_a     = trim($_POST['pilihan_a'] ?? '');
    $opsi_b     = trim($_POST['pilihan_b'] ?? '');
    $opsi_c     = trim($_POST['pilihan_c'] ?? '');
    $opsi_d     = trim($_POST['pilihan_d'] ?? '');
    $opsi_e     = trim($_POST['pilihan_e'] ?? '');

    // Validasi Umum
    if (!$pertanyaan) $errors[] = 'Pertanyaan tidak boleh kosong.';
    
    // PERBAIKAN 2: Validasi Khusus (Hanya wajibkan Opsi/Kunci jika itu Pilihan Ganda)
    if ($jenis_soal === '1') {
        if (!$opsi_a || !$opsi_b) $errors[] = 'Minimal 2 pilihan jawaban (A dan B) harus diisi untuk Pilihan Ganda.';
        if (!$kunci)              $errors[] = 'Kunci jawaban wajib dipilih untuk soal Pilihan Ganda.';
    } else {
        // Jika Essay, bersihkan data opsi agar database tetap rapi
        $kunci = '';
        $opsi_a = ''; $opsi_b = ''; $opsi_c = ''; $opsi_d = ''; $opsi_e = '';
    }

    if (empty($errors)) {
        if ($is_edit) {
            $sql = "UPDATE tb_soal SET 
                    pertanyaan=?, jenis_soal=?, kunci_jawaban=?, 
                    opsi_a=?, opsi_b=?, opsi_c=?, opsi_d=?, opsi_e=? 
                    WHERE id_soal=? AND id_mapel=?";
            $pdo->prepare($sql)->execute([
                $pertanyaan, $jenis_soal, $kunci, 
                $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, 
                $edit_id, $id_mapel
            ]);
        } else {
            $sql = "INSERT INTO tb_soal 
                    (id_mapel, pertanyaan, jenis_soal, kunci_jawaban, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e) 
                    VALUES (?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([
                $id_mapel, $pertanyaan, $jenis_soal, $kunci, 
                $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e
            ]);
        }

        $_SESSION['flash'] = ['type'=>'success','msg'=> $is_edit?'Soal berhasil diperbarui.':'Soal berhasil ditambahkan.'];
        header("Location: soal_list_guru.php?id_mapel=$id_mapel"); exit;
    }

    // Jika terjadi error validasi, kembalikan inputan ke form
    $soal = [
        'pertanyaan' => $pertanyaan,
        'jenis_soal' => $jenis_soal,
        'kunci_jawaban' => $kunci,
        'opsi_a' => $opsi_a, 'opsi_b' => $opsi_b, 'opsi_c' => $opsi_c, 'opsi_d' => $opsi_d, 'opsi_e' => $opsi_e
    ];
}

$pj = [
    'A' => $soal['opsi_a'] ?? '',
    'B' => $soal['opsi_b'] ?? '',
    'C' => $soal['opsi_c'] ?? '',
    'D' => $soal['opsi_d'] ?? '',
    'E' => $soal['opsi_e'] ?? ''
];

// Deteksi awal apakah ini PG (1) atau Essay (2)
$jenis_saat_ini = strtolower($soal['jenis_soal'] ?? '1');
if ($jenis_saat_ini === 'pg') $jenis_saat_ini = '1';
if ($jenis_saat_ini === 'essay') $jenis_saat_ini = '2';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $is_edit?'Edit':'Tambah' ?> Soal – <?= htmlspecialchars($mapel['nama_mapel']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family:'Inter',sans-serif; }
        .gradient-bg { background:linear-gradient(135deg,#0A192F 0%,#1E3A5F 30%,#F4A261 60%,#90BE6D 100%); background-size:200% 200%; animation:gm 10s ease infinite; }
        @keyframes gm { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .btn-gradient { background:linear-gradient(135deg,#0A192F,#1E3A5F,#F4A261,#90BE6D); background-size:300% 300%; animation:bg 5s ease infinite; }
        @keyframes bg { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        input,select,textarea { outline:none; }
        input:focus,select:focus,textarea:focus { ring:2px solid #0A192F; border-color:#0A192F; }
        /* Animasi Transisi Form PG */
        #pg_section { transition: all 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-gray-50">

<header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
    <div class="flex items-center space-x-3">
        <a href="soal_list_guru.php?id_mapel=<?= $id_mapel ?>" class="text-white/70 hover:text-white mr-1">
            <i class="fas fa-arrow-left"></i>
        </a>
        <i class="fas fa-<?= $is_edit?'edit':'plus-circle' ?> text-white text-xl"></i>
        <div>
            <h1 class="text-xl font-bold text-white"><?= $is_edit?'Edit':'Tambah' ?> Soal</h1>
            <p class="text-white/60 text-xs"><?= htmlspecialchars($mapel['nama_mapel']) ?></p>
        </div>
    </div>
    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
        <span class="text-white font-bold"><?= strtoupper(substr($nama_guru,0,1)) ?></span>
    </div>
</header>

<main class="p-6 max-w-3xl mx-auto">
    <div class="mb-5">
        <a href="soal_list_guru.php?id_mapel=<?= $id_mapel ?>" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:border-[#F4A261] shadow-sm transition group">
            <i class="fas fa-arrow-left mr-2 text-sm group-hover:-translate-x-1 transition-transform"></i>
            <span class="font-medium">Kembali ke Daftar Soal</span>
        </a>
    </div>

    <?php foreach ($errors as $e): ?>
    <div class="mb-4 p-4 rounded-xl bg-red-100 text-red-800 border border-red-200">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($e) ?>
    </div>
    <?php endforeach; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-file-alt text-[#0A192F]"></i>
                Form <?= $is_edit?'Edit':'Tambah' ?> Soal
                <span class="bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded-full font-semibold">
                    <?= htmlspecialchars($mapel['nama_mapel']) ?>
                </span>
            </h2>
        </div>

        <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_mapel" value="<?= $id_mapel ?>">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Pertanyaan <span class="text-red-500">*</span>
                </label>
                <textarea name="pertanyaan" rows="4" required
                          placeholder="Tulis pertanyaan di sini..."
                          class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition resize-none"><?= htmlspecialchars($soal['pertanyaan']??'') ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Soal <span class="text-red-500">*</span></label>
                <select name="tipe_soal" id="tipe_soal" onchange="toggleForm()" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition bg-blue-50">
                    <option value="1" <?= $jenis_saat_ini === '1' ? 'selected' : '' ?>>Pilihan Ganda (PG)</option>
                    <option value="2" <?= $jenis_saat_ini === '2' ? 'selected' : '' ?>>Essay (Uraian)</option>
                </select>
            </div>

            <div id="pg_section" class="space-y-5">
                <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-200 text-xs text-yellow-800">
                    <i class="fas fa-lightbulb mr-1"></i> Anda sedang membuat soal <strong>Pilihan Ganda</strong>. Silakan isi opsi jawaban di bawah.
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        Pilihan Jawaban <span class="text-gray-400 font-normal">(A dan B wajib diisi)</span>
                    </label>
                    <div class="space-y-3">
                    <?php foreach (['A','B','C','D','E'] as $k): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#0A192F] text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                            <?= $k ?>
                        </div>
                        <input type="text" name="pilihan_<?= strtolower($k) ?>" id="opsi_<?= strtolower($k) ?>"
                               value="<?= htmlspecialchars($pj[$k]) ?>"
                               placeholder="Pilihan <?= $k ?><?= in_array($k,['A','B'])?' (wajib)':' (opsional)' ?>"
                               class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition">
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Kunci Jawaban <span class="text-red-500">*</span>
                    </label>
                    <select name="kunci_jawaban" id="kunci_jawaban" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition">
                        <option value="">-- Pilih kunci jawaban yang benar --</option>
                        <?php foreach (['A','B','C','D','E'] as $k): ?>
                        <option value="<?= $k ?>" <?= ($soal['kunci_jawaban']??'')===$k?'selected':'' ?>>Jawaban <?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="btn-gradient text-white px-6 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 shadow-md">
                    <i class="fas fa-save"></i> <?= $is_edit?'Perbarui Soal':'Simpan Soal' ?>
                </button>
                <a href="soal_list_guru.php?id_mapel=<?= $id_mapel ?>"
                   class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</main>

<script>
    function toggleForm() {
        var tipe = document.getElementById('tipe_soal').value;
        var pgSection = document.getElementById('pg_section');
        
        // Elemen Input yang butuh di-set required
        var opsiA = document.getElementById('opsi_a');
        var opsiB = document.getElementById('opsi_b');
        var kunci = document.getElementById('kunci_jawaban');

        if (tipe === '1') {
            // JIKA PILIHAN GANDA (1)
            pgSection.style.display = 'block';
            opsiA.required = true;
            opsiB.required = true;
            kunci.required = true;
        } else {
            // JIKA ESSAY (2)
            pgSection.style.display = 'none';
            opsiA.required = false;
            opsiB.required = false;
            kunci.required = false;
        }
    }

    // Jalankan fungsi saat halaman pertama kali dimuat
    document.addEventListener("DOMContentLoaded", function() {
        toggleForm();
    });
</script>

</body>
</html>