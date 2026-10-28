
<?php
// =============================================
// FILE: pages/guru/bank_soal.php
// Tampil daftar mapel — sama persis alurnya dengan admin
// Guru klik mapel → masuk soal_list_guru.php
// =============================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php'; // pakai PDO punya admin

$nama_guru = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Guru';
$search    = isset($_GET['search']) ? $_GET['search'] : '';

// ── Ambil daftar mapel + hitung soal per mapel ──
// SAMA PERSIS dengan query admin, hanya beda halaman tujuannya
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT m.*, 
                           (SELECT COUNT(*) FROM tb_soal WHERE id_mapel = m.id_mapel) as total_soal 
                           FROM tb_mapel m 
                           WHERE m.nama_mapel LIKE :search 
                           ORDER BY m.nama_mapel ASC");
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT m.*, 
                         (SELECT COUNT(*) FROM tb_soal WHERE id_mapel = m.id_mapel) as total_soal 
                         FROM tb_mapel m 
                         ORDER BY nama_mapel ASC");
}
$mapel_list  = $stmt->fetchAll();
$total_mapel = count($mapel_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bank Soal – Guru CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family:'Inter',sans-serif; }
        .gradient-bg { background:linear-gradient(135deg,#0A192F 0%,#1E3A5F 30%,#F4A261 60%,#90BE6D 100%); background-size:200% 200%; animation:gradientMove 10s ease infinite; }
        @keyframes gradientMove { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .btn-gradient { background:linear-gradient(135deg,#0A192F,#1E3A5F,#F4A261,#90BE6D); background-size:300% 300%; animation:btnGradient 5s ease infinite; transition:all .3s; }
        @keyframes btnGradient { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .btn-gradient:hover { transform:scale(1.02); box-shadow:0 10px 30px rgba(10,25,47,.3); }
        .card-hover { transition:all .3s ease; }
        .card-hover:hover { transform:translateY(-5px); box-shadow:0 15px 30px rgba(0,0,0,.1); }
        .total-card { background:linear-gradient(145deg,#fff 0%,#f8fafc 100%); border:1px solid rgba(244,162,97,.3); box-shadow:0 20px 30px -10px rgba(10,25,47,.2); position:relative; overflow:hidden; }
        .total-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,#0A192F,#F4A261,#90BE6D); }
        .total-icon { background:linear-gradient(145deg,#0A192F,#1E3A5F); box-shadow:0 10px 15px -5px rgba(10,25,47,.4); }
        .total-number { font-size:3.5rem; font-weight:900; line-height:1; background:linear-gradient(135deg,#0A192F,#1E3A5F,#F4A261); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .total-label { font-size:.75rem; letter-spacing:.1em; background:linear-gradient(90deg,#F4A261,#90BE6D); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-weight:700; }
    </style>
</head>
<body class="bg-gray-50">

<!-- HEADER -->
<header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
    <div class="flex items-center space-x-3">
        <a href="dashboard.php" class="text-white/70 hover:text-white mr-1 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <i class="fas fa-layer-group text-white text-2xl"></i>
        <h1 class="text-2xl font-bold text-white">Bank Soal</h1>
    </div>
    <div class="flex items-center space-x-4">
        <div class="text-right">
            <span class="text-white text-sm opacity-90">Selamat datang,</span>
            <p class="text-white font-semibold"><?= htmlspecialchars($nama_guru) ?></p>
        </div>
        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
            <span class="text-white font-bold text-lg"><?= strtoupper(substr($nama_guru,0,1)) ?></span>
        </div>
    </div>
</header>

<main class="p-6">
    <!-- Tombol kembali -->
    <div class="mb-6">
        <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:border-[#F4A261] shadow-sm transition-all group">
            <i class="fas fa-arrow-left mr-2 text-sm group-hover:-translate-x-1 transition-transform"></i>
            <span class="font-medium">Kembali ke Dashboard</span>
        </a>
    </div>

    <!-- Flash message -->
    <?php if (isset($_SESSION['flash'])): ?>
    <div class="mb-4 p-4 rounded-xl <?= $_SESSION['flash']['type']==='success'?'bg-green-100 text-green-800':'bg-red-100 text-red-800' ?>">
        <i class="fas fa-<?= $_SESSION['flash']['type']==='success'?'check':'exclamation' ?>-circle mr-2"></i>
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); endif; ?>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <!-- Header + Search -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Bank Soal per Mata Pelajaran</h2>
                <p class="text-sm text-gray-500 mt-1">Pilih mata pelajaran untuk melihat dan mengelola soal</p>
            </div>
            <form method="GET" class="flex-1 md:flex-initial">
                <div class="relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari mata pelajaran..."
                           class="w-full md:w-80 px-4 py-2.5 pl-10 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0A192F] outline-none transition">
                    <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 text-sm"></i>
                    <?php if (!empty($search)): ?>
                    <a href="?" class="absolute right-3 top-3.5 text-gray-400 hover:text-[#F4A261]">
                        <i class="fas fa-times-circle"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Stat total mapel -->
        <div class="mb-8">
            <div class="total-card rounded-2xl p-8 relative">
                <div class="flex items-center gap-8 relative z-10">
                    <div class="total-icon w-24 h-24 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-4xl"></i>
                    </div>
                    <div class="flex-1">
                        <span class="total-label uppercase tracking-wider mb-2 block">
                            <i class="fas fa-database mr-2 text-[#F4A261]"></i>
                            TOTAL MATA PELAJARAN
                        </span>
                        <div class="flex items-baseline gap-3">
                            <span class="total-number"><?= $total_mapel ?></span>
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">
                                <i class="fas fa-circle text-[6px] mr-1"></i> Aktif
                            </span>
                        </div>
                        <p class="text-gray-500 text-sm mt-2">
                            <i class="fas fa-info-circle mr-2 text-[#F4A261]"></i>
                            Klik mata pelajaran untuk melihat dan menambah soal
                        </p>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                    <i class="fas fa-book-open text-8xl text-[#0A192F]"></i>
                </div>
            </div>
        </div>

        <!-- Grid Mapel — sama persis dengan admin, hanya link ke soal_list_guru.php -->
        <?php if (count($mapel_list) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($mapel_list as $m): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover group">
                <div class="flex justify-between items-start mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-orange-50 p-4 rounded-2xl group-hover:from-[#0A192F] group-hover:to-[#1E3A5F] transition-all">
                        <i class="fas fa-book-open text-2xl text-[#0A192F] group-hover:text-white"></i>
                    </div>
                    <span class="bg-gradient-to-r from-orange-50 to-green-50 text-[#0A192F] text-xs font-bold px-3 py-1 rounded-full border border-[#F4A261]">
                        <?= $m['total_soal'] ?> Soal
                    </span>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($m['nama_mapel']) ?></h3>
                <p class="text-xs text-gray-400 mb-6 leading-relaxed">Lihat, tambah, dan kelola soal untuk mata pelajaran ini.</p>
                <!-- Link ke halaman soal guru, bukan soal_list.php admin -->
                <a href="soal_list_guru.php?id_mapel=<?= $m['id_mapel'] ?>"
                   class="btn-gradient text-white w-full py-3 rounded-xl text-sm font-bold flex items-center justify-center shadow-lg">
                    Kelola Soal <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
            <div><i class="fas fa-info-circle mr-1 text-[#0A192F]"></i> Menampilkan <?= count($mapel_list) ?> mata pelajaran</div>
            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                <i class="fas fa-circle text-[8px] mr-1"></i> Aktif
            </span>
        </div>

        <?php else: ?>
        <div class="py-12 text-center">
            <div class="h-24 w-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-folder-open text-gray-400 text-4xl"></i>
            </div>
            <h3 class="text-gray-700 font-semibold text-lg mb-1">Belum ada mata pelajaran</h3>
            <p class="text-gray-500 text-sm">
                <?= !empty($search) ? 'Pencarian "'.htmlspecialchars($search).'" tidak ditemukan' : 'Mata pelajaran ditambahkan oleh Admin' ?>
            </p>
            <?php if (!empty($search)): ?>
            <a href="?" class="text-[#0A192F] hover:text-[#F4A261] font-medium text-sm mt-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke semua data
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>