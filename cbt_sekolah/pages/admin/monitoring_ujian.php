<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Ambil parameter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil data ujian gabung dengan Mapel, Guru, dan Kelas (GROUP_CONCAT agar kelas menyatu dalam 1 baris)
$sql = "SELECT u.*, m.nama_mapel, g.nama_lengkap AS nama_guru, 
        GROUP_CONCAT(k.nama_kelas SEPARATOR ', ') as daftar_kelas,
        (SELECT COUNT(*) FROM tb_soal WHERE id_ujian = u.id_ujian) as total_soal
        FROM tb_ujian u
        JOIN tb_mapel m ON u.id_mapel = m.id_mapel
        JOIN tb_users g ON u.id_guru = g.id_user
        LEFT JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
        LEFT JOIN tb_kelas k ON uk.id_kelas = k.id_kelas";

// Tambahkan kondisi pencarian
if (!empty($search)) {
    $sql .= " WHERE u.judul_ujian LIKE :search 
              OR m.nama_mapel LIKE :search 
              OR g.nama_lengkap LIKE :search";
}

$sql .= " GROUP BY u.id_ujian ORDER BY u.tanggal_ujian DESC, u.jam_mulai DESC";

$stmt = $pdo->prepare($sql);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

$stmt->execute();
$ujian = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Monitoring Ujian - ADMINSKAPAN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navy-bg { background-color: #003366; }
        .navy-text { color: #003366; }
        .navy-border { border-color: #003366; }
        .orange-bg { background-color: #FF6B35; }
        .orange-text { color: #FF6B35; }
        .orange-border { border-color: #FF6B35; }
        .green-bg { background-color: #2E8B57; }
        .green-text { color: #2E8B57; }
        .green-border { border-color: #2E8B57; }
    </style>
</head>

<body class="bg-[#F0F4F8] font-sans">

    <div class="min-h-screen">
        <!-- Header dengan warna navy -->
        <header class="navy-bg py-4 px-8 sticky top-0 z-40 shadow-lg">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <!-- Tombol Kembali ke Dashboard -->
                    <a href="dashboard.php" class="flex items-center bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-xl transition-all duration-300 group">
                        <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                        <span class="font-semibold">Kembali ke Dashboard</span>
                    </a>
                    <div class="border-l-2 border-white border-opacity-30 h-8 mx-2"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Monitoring Ujian</h1>
                        <p class="text-sm text-orange-300 font-semibold tracking-wider">PILIH JADWAL UNTUK MEMANTAU SISWA</p>
                    </div>
                </div>
                
                <!-- Form Pencarian dengan warna orange -->
                <form method="GET" action="" class="flex items-center">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Cari ujian, mapel, guru..." 
                               class="w-80 pl-12 pr-4 py-3 rounded-xl border-2 border-orange-400 focus:border-green-500 focus:outline-none shadow-md">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-orange-500 text-lg"></i>
                    </div>
                    <button type="submit" class="ml-3 orange-bg text-white px-6 py-3 rounded-xl font-bold shadow-md hover:bg-opacity-90 transition-all">
                        Cari
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="?" class="ml-3 bg-gray-500 text-white px-6 py-3 rounded-xl font-bold shadow-md hover:bg-gray-600 transition-all">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center space-x-2 text-xs text-white text-opacity-80 mt-2 ml-2">
                <a href="dashboard.php" class="hover:text-orange-300 transition-colors">Dashboard</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-orange-300 font-semibold">Monitoring Ujian</span>
            </div>
        </header>

        <main class="p-8">
            <!-- Tombol Cepat ke Dashboard (Mobile Version) -->
            <div class="md:hidden mb-6">
                <a href="dashboard.php" class="inline-flex items-center navy-bg text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-opacity-90 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Dashboard
                </a>
            </div>

            <!-- Statistik Cards dengan tema navy, orange, green -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-8 border-[#003366]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm font-bold uppercase">Total Ujian</p>
                            <p class="text-3xl font-bold navy-text"><?= count($ujian) ?></p>
                        </div>
                        <div class="navy-bg p-4 rounded-full">
                            <i class="fas fa-calendar-alt text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-8 border-[#FF6B35]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm font-bold uppercase">Mapel Aktif</p>
                            <p class="text-3xl font-bold orange-text">
                                <?php
                                $mapelUnik = [];
                                foreach ($ujian as $u) {
                                    $mapelUnik[$u['id_mapel']] = true;
                                }
                                echo count($mapelUnik);
                                ?>
                            </p>
                        </div>
                        <div class="orange-bg p-4 rounded-full">
                            <i class="fas fa-book text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-8 border-[#2E8B57]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm font-bold uppercase">Guru Pengawas</p>
                            <p class="text-3xl font-bold green-text">
                                <?php
                                $guruUnik = [];
                                foreach ($ujian as $u) {
                                    $guruUnik[$u['id_guru']] = true;
                                }
                                echo count($guruUnik);
                                ?>
                            </p>
                        </div>
                        <div class="green-bg p-4 rounded-full">
                            <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-8 border-[#003366]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm font-bold uppercase">Total Soal</p>
                            <p class="text-3xl font-bold navy-text">
                                <?php
                                $totalSoal = 0;
                                foreach ($ujian as $u) {
                                    $totalSoal += $u['total_soal'];
                                }
                                echo $totalSoal;
                                ?>
                            </p>
                        </div>
                        <div class="navy-bg p-4 rounded-full">
                            <i class="fas fa-question-circle text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Monitoring -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-t-4 border-orange-500">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h2 class="text-lg font-bold navy-text">
                        <i class="fas fa-list-alt mr-2 text-orange-500"></i>
                        Daftar Ujian Berlangsung
                    </h2>
                    <div class="flex items-center space-x-3">
                        <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-semibold">
                            <i class="fas fa-clock mr-1"></i> <?= count($ujian) ?> Jadwal
                        </span>
                        <a href="dashboard.php" class="text-sm text-gray-500 hover:text-navy transition-colors">
                            <i class="fas fa-th-large mr-1"></i> Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if (empty($ujian)): ?>
                    <div class="text-center py-16">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <p class="text-xl font-bold text-gray-500">Tidak ada data ujian ditemukan</p>
                        <?php if (!empty($search)): ?>
                            <p class="text-gray-400 mt-2">Pencarian: "<?= htmlspecialchars($search) ?>"</p>
                        <?php endif; ?>
                        <a href="dashboard.php" class="inline-block mt-6 navy-bg text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-opacity-90 transition-all">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="navy-bg">
                                <tr class="text-xs font-bold text-white uppercase tracking-wider">
                                    <th class="px-6 py-4">Jenis / Judul Ujian</th>
                                    <th class="px-6 py-4">Waktu Ujian</th>
                                    <th class="px-6 py-4">Target Kelas</th>
                                    <th class="px-6 py-4">Mata Pelajaran</th>
                                    <th class="px-6 py-4">Pembuat Jadwal</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($ujian as $u): ?>
                                    <tr class="hover:bg-orange-50 transition-colors">
                                        <td class="px-6 py-5">
                                            <div>
                                                <span class="block text-base font-bold text-gray-800"><?= htmlspecialchars($u['judul_ujian']) ?></span>
                                                <span class="text-xs inline-flex items-center gap-1 bg-orange-100 text-orange-700 px-3 py-1 rounded-full font-bold mt-2">
                                                    <i class="fas fa-key"></i> TOKEN: <?= htmlspecialchars($u['token']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-800">
                                                    <i class="far fa-calendar-alt mr-2 text-navy"></i>
                                                    <?= date('l, d M Y', strtotime($u['tanggal_ujian'])) ?>
                                                </span>
                                                <span class="text-xs text-gray-500 font-medium mt-1">
                                                    <i class="far fa-clock mr-2 text-green-600"></i>
                                                    <?= substr($u['jam_mulai'], 0, 5) ?> - <?= substr($u['jam_selesai'], 0, 5) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex flex-wrap gap-2">
                                                <?php
                                                $kelasArr = explode(', ', $u['daftar_kelas']);
                                                foreach ($kelasArr as $kls):
                                                    // Warna badge dengan tema baru
                                                    if (strpos($kls, 'XII') !== false) {
                                                        $bg = 'navy-bg text-white';
                                                    } elseif (strpos($kls, 'XI') !== false) {
                                                        $bg = 'green-bg text-white';
                                                    } else {
                                                        $bg = 'orange-bg text-white';
                                                    }
                                                ?>
                                                    <span class="text-xs font-bold px-3 py-1.5 rounded-full <?= $bg ?> shadow-sm">
                                                        <?= htmlspecialchars($kls) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span class="text-sm font-bold text-gray-700"><?= htmlspecialchars($u['nama_mapel']) ?></span>
                                            <span class="block text-xs text-gray-500 mt-1">
                                                <i class="fas fa-file-alt mr-1 text-green-600"></i>
                                                <?= $u['total_soal'] ?> Soal
                                            </span>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex items-center">
                                                <div class="w-9 h-9 rounded-full green-bg flex items-center justify-center text-white text-sm font-bold mr-3 shadow-md">
                                                    <?= strtoupper(substr($u['nama_guru'], 0, 1)) ?>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($u['nama_guru']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <a href="monitoring_detail.php?id=<?= $u['id_ujian'] ?>"
                                                class="inline-flex items-center justify-center orange-bg hover:bg-opacity-90 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                                                <i class="fas fa-desktop mr-2"></i> MONITORING
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Footer Tabel dengan Tombol Dashboard -->
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1 text-navy"></i>
                            Menampilkan <?= count($ujian) ?> jadwal ujian
                        </span>
                        <a href="dashboard.php" class="inline-flex items-center text-sm navy-text hover:text-orange-500 transition-colors font-semibold">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Kembali ke Dashboard
                            <i class="fas fa-home ml-2"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tombol Navigasi Tambahan -->
            <div class="mt-6 flex justify-between items-center">
                <a href="dashboard.php" class="inline-flex items-center text-gray-600 hover:text-navy transition-colors">
                    <i class="fas fa-chevron-left mr-2 text-sm"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <div class="flex space-x-3">
                    <a href="#" class="text-sm text-gray-500 hover:text-orange-500 transition-colors">
                        <i class="fas fa-question-circle mr-1"></i>Bantuan
                    </a>
                    <a href="#" class="text-sm text-gray-500 hover:text-orange-500 transition-colors">
                        <i class="fas fa-cog mr-1"></i>Pengaturan
                    </a>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 px-8">
            <div class="flex justify-between items-center text-xs text-gray-500">
                <span>&copy; 2026 ADMINSKAPAN. All rights reserved.</span>
                <div class="flex space-x-4">
                    <a href="dashboard.php" class="hover:text-navy transition-colors">Dashboard</a>
                    <a href="#" class="hover:text-navy transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-navy transition-colors">Terms of Service</a>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>