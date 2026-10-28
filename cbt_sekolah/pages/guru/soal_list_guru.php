<?php
// =============================================
// FILE: pages/guru/soal_list_guru.php
// Tampil & kelola soal per mapel (versi guru)
// Terhubung dengan tb_soal & tb_mapel milik admin
// =============================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$nama_guru = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Guru';
$id_mapel  = isset($_GET['id_mapel']) ? (int)$_GET['id_mapel'] : 0;

if (!$id_mapel) { header("Location: bank_soal.php"); exit; }

// Ambil info mapel
$stmt = $pdo->prepare("SELECT * FROM tb_mapel WHERE id_mapel = ?");
$stmt->execute([$id_mapel]);
$mapel = $stmt->fetch();
if (!$mapel) { header("Location: bank_soal.php"); exit; }

// Hapus soal
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Hapus pilihan dulu jika ada
    try {
        $pdo->prepare("DELETE FROM tb_pilihan WHERE id_soal = ?")->execute([$id]);
    } catch(Exception $e) { /* kolom mungkin tidak ada */ }
    $pdo->prepare("DELETE FROM tb_soal WHERE id_soal = ? AND id_mapel = ?")->execute([$id, $id_mapel]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Soal berhasil dihapus.'];
    header("Location: soal_list_guru.php?id_mapel=$id_mapel"); exit;
}

// Pagination
$per_page   = 10;
$page       = max(1,(int)($_GET['page']??1));
$offset     = ($page-1)*$per_page;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_soal WHERE id_mapel = ?");
$count_stmt->execute([$id_mapel]);
$total      = $count_stmt->fetchColumn();
$total_page = ceil($total/$per_page);

$stmt = $pdo->prepare("SELECT * FROM tb_soal WHERE id_mapel = ? ORDER BY id_soal DESC LIMIT $per_page OFFSET $offset");
$stmt->execute([$id_mapel]);
$soal_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Soal <?= htmlspecialchars($mapel['nama_mapel']) ?> – Guru CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family:'Inter',sans-serif; }
        .gradient-bg { background:linear-gradient(135deg,#0A192F 0%,#1E3A5F 30%,#F4A261 60%,#90BE6D 100%); background-size:200% 200%; animation:gm 10s ease infinite; }
        @keyframes gm { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .btn-gradient { background:linear-gradient(135deg,#0A192F,#1E3A5F,#F4A261,#90BE6D); background-size:300% 300%; animation:bg 5s ease infinite; }
        @keyframes bg { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        tr:hover td { background:#f9fafb; }
    </style>
</head>
<body class="bg-gray-50">

<header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
    <div class="flex items-center space-x-3">
        <a href="bank_soal.php" class="text-white/70 hover:text-white mr-1"><i class="fas fa-arrow-left"></i></a>
        <i class="fas fa-book-open text-white text-xl"></i>
        <div>
            <h1 class="text-xl font-bold text-white leading-tight"><?= htmlspecialchars($mapel['nama_mapel']) ?></h1>
            <p class="text-white/60 text-xs">Bank Soal Guru</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="tambah_soal_guru.php?id_mapel=<?= $id_mapel ?>"
           class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition border border-white/30">
            <i class="fas fa-plus"></i> Tambah Soal
        </a>
        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
            <span class="text-white font-bold"><?= strtoupper(substr($nama_guru,0,1)) ?></span>
        </div>
    </div>
</header>

<main class="p-6 max-w-6xl mx-auto">
    <!-- Kembali -->
    <div class="mb-5">
        <a href="bank_soal.php" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:border-[#F4A261] shadow-sm transition group">
            <i class="fas fa-arrow-left mr-2 text-sm group-hover:-translate-x-1 transition-transform"></i>
            <span class="font-medium">Kembali ke Daftar Mapel</span>
        </a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
    <div class="mb-4 p-4 rounded-xl <?= $_SESSION['flash']['type']==='success'?'bg-green-100 text-green-800':'bg-red-100 text-red-800' ?>">
        <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); endif; ?>

    <!-- Info mapel + stat -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#0A192F] to-[#1E3A5F] flex items-center justify-center">
                <i class="fas fa-book-open text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($mapel['nama_mapel']) ?></h2>
                <p class="text-sm text-gray-500 mt-1">
                    <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full text-xs font-bold mr-2">
                        <?= $total ?> Soal
                    </span>
                    Total soal dalam mata pelajaran ini
                </p>
            </div>
        </div>
        <a href="tambah_soal_guru.php?id_mapel=<?= $id_mapel ?>"
           class="btn-gradient text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2">
            <i class="fas fa-plus"></i> Tambah Soal Baru
        </a>
    </div>

    <!-- Tabel soal -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <i class="fas fa-list text-[#0A192F]"></i>
            <h3 class="font-bold text-gray-800">Daftar Soal</h3>
            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full font-semibold"><?= $total ?> soal</span>
        </div>

        <?php if (count($soal_list) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-10">No</th>
                        <th class="px-4 py-3 text-left font-semibold">Pertanyaan</th>
                        <th class="px-4 py-3 text-left font-semibold">Tipe</th>
                        <th class="px-4 py-3 text-left font-semibold">Kesulitan</th>
                        <th class="px-4 py-3 text-left font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php $no=$offset+1; foreach ($soal_list as $s): ?>
                <tr>
                    <td class="px-4 py-3 text-gray-400"><?= $no++ ?></td>
                    <td class="px-4 py-3 max-w-md font-medium text-gray-800">
                        <?= htmlspecialchars(mb_substr(strip_tags($s['pertanyaan']),0,90)) ?>
                        <?= mb_strlen(strip_tags($s['pertanyaan']))>90?'...':'' ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-purple-50 text-purple-700 px-2 py-1 rounded-lg text-xs font-semibold">
                            <?= strtoupper($s['tipe_soal']??'PG') ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <?php $t=$s['tingkat_kesulitan']??'';
                        $tc=$t==='mudah'?'bg-green-100 text-green-700':($t==='sulit'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700'); ?>
                        <span class="<?= $tc ?> px-2 py-1 rounded-lg text-xs font-semibold"><?= ucfirst($t?:'-') ?></span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="detail_soal_guru.php?id=<?= $s['id_soal'] ?>&id_mapel=<?= $id_mapel ?>"
                               class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 transition">
                                <i class="fas fa-eye mr-1"></i>Detail
                            </a>
                            <a href="tambah_soal_guru.php?edit=<?= $s['id_soal'] ?>&id_mapel=<?= $id_mapel ?>"
                               class="px-3 py-1.5 border border-blue-200 rounded-lg text-xs font-medium text-blue-600 hover:bg-blue-50 transition">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <a href="?id_mapel=<?= $id_mapel ?>&hapus=<?= $s['id_soal'] ?>"
                               onclick="return confirm('Hapus soal ini? Data tidak dapat dikembalikan.')"
                               class="px-3 py-1.5 border border-red-200 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 transition">
                                <i class="fas fa-trash mr-1"></i>Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_page > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-center">
            <?php for ($i=1;$i<=$total_page;$i++): ?>
            <a href="?id_mapel=<?=$id_mapel?>&page=<?=$i?>"
               class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold transition
                      <?= $i===$page?'bg-[#0A192F] text-white':'border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-gray-600 font-semibold mb-1">Belum ada soal</h3>
            <p class="text-gray-400 text-sm mb-4">Mulai tambahkan soal untuk mata pelajaran ini.</p>
            <a href="tambah_soal_guru.php?id_mapel=<?= $id_mapel ?>"
               class="btn-gradient text-white px-6 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Tambah Soal Pertama
            </a>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>