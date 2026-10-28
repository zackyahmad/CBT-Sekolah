<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Fitur pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 1. Ambil data Mapel, Guru, dan Kelas
$mapel_list = $pdo->query("SELECT * FROM tb_mapel ORDER BY nama_mapel ASC")->fetchAll();
$guru_list  = $pdo->query("SELECT * FROM tb_users WHERE role IN ('admin', 'guru') ORDER BY nama_lengkap ASC")->fetchAll();
$kelas_list = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC")->fetchAll();

// 2. Query Ujian dengan filter pencarian
if (!empty($search)) {
    $query = "SELECT u.*, 
              COALESCE(m.nama_mapel, 'Mapel telah dihapus') as nama_mapel, 
              COALESCE(g.nama_lengkap, 'Tidak diketahui') as nama_guru,
              (SELECT COUNT(*) FROM tb_soal WHERE id_mapel = u.id_mapel) as jml_bank_soal 
              FROM tb_ujian u 
              LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel 
              LEFT JOIN tb_users g ON u.id_guru = g.id_user
              WHERE u.judul_ujian LIKE :search 
                 OR m.nama_mapel LIKE :search 
                 OR g.nama_lengkap LIKE :search
              ORDER BY u.tanggal_ujian DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => "%$search%"]);
} else {
    $query = "SELECT u.*, 
              COALESCE(m.nama_mapel, 'Mapel telah dihapus') as nama_mapel, 
              COALESCE(g.nama_lengkap, 'Tidak diketahui') as nama_guru,
              (SELECT COUNT(*) FROM tb_soal WHERE id_mapel = u.id_mapel) as jml_bank_soal 
              FROM tb_ujian u 
              LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel 
              LEFT JOIN tb_users g ON u.id_guru = g.id_user
              ORDER BY u.tanggal_ujian DESC";
    $stmt = $pdo->query($query);
}
$ujian = $stmt->fetchAll();

// PISAHKAN UJIAN AKTIF DAN TIDAK AKTIF
$ujian_aktif = [];
$ujian_tidak_aktif = [];

foreach ($ujian as $u) {
    $status = isset($u['status_ujian']) ? trim($u['status_ujian']) : '';
    
    // KATEGORI AKTIF
    if ($status == 'aktif') {
        $ujian_aktif[] = $u;
    } 
    // KATEGORI TIDAK AKTIF
    else {
        $ujian_tidak_aktif[] = $u;
        
        // Perbaiki status di database jika perlu (hanya sekali per halaman)
        if ($status == '' || $status == '-' || $status == 'NULL' || $status == 'null' || $status == '0' || $status == null) {
            $pdo->prepare("UPDATE tb_ujian SET status_ujian = 'tidak_aktif' WHERE id_ujian = ?")->execute([$u['id_ujian']]);
        }
    }
}

// 3. Hitung statistik ujian
$total_ujian = count($ujian);
$total_aktif = count($ujian_aktif);
$total_tidak_aktif = count($ujian_tidak_aktif);

// Hapus semua debug variable
// $show_debug = false; // sudah dihapus
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rencana Ujian - Admin CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #1B2F4F 0%, #2C5F4A 40%, #E6A57E 80%, #F4C9A2 100%);
            background-size: 200% 200%;
            animation: gradientMove 12s ease infinite;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .back-btn {
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #1B2F4F, #2C5F4A, #E6A57E, #F4C9A2);
            background-size: 300% 300%;
            animation: btnGradient 6s ease infinite;
            transition: all 0.3s ease;
        }
        
        @keyframes btnGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .btn-gradient:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(27, 47, 79, 0.3);
        }

        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }

        .section-title {
            position: relative;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #1B2F4F, #2C5F4A);
            border-radius: 4px;
        }
        
        /* Notifikasi perbaikan status */
        .notification-banner {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .close-notif {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .close-notif:hover {
            opacity: 0.8;
            transform: scale(1.1);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <div class="w-full transition-all duration-300">
        <!-- Header -->
        <header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
            <div class="flex items-center space-x-3">
                <i class="fas fa-calendar-alt text-white text-2xl"></i>
                <h1 class="text-2xl font-bold text-white">Rencana Ujian</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <span class="text-white text-sm opacity-90">Selamat datang,</span>
                    <p class="text-white font-semibold"><?= $_SESSION['nama']; ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
                    <span class="text-white font-bold text-lg"><?= substr($_SESSION['nama'], 0, 1); ?></span>
                </div>
            </div>
        </header>

        <main class="p-6">
            <!-- Tombol Kembali -->
            <div class="mb-6">
                <a href="dashboard.php" class="back-btn inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 hover:border-[#E6A57E] hover:text-[#1B2F4F] shadow-sm transition-all duration-300 group">
                    <i class="fas fa-arrow-left mr-2 text-sm group-hover:-translate-x-1 transition-transform duration-300"></i>
                    <span class="font-medium">Kembali ke Dashboard</span>
                </a>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Manajemen Jadwal Ujian</h2>
                        <p class="text-sm text-gray-500 mt-1">Kelola jadwal dan rencana pelaksanaan ujian</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                        <form method="GET" action="" class="flex-1 sm:flex-initial">
                            <div class="relative">
                                <input type="text" 
                                       name="search" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Cari judul/mapel/guru..." 
                                       class="w-full sm:w-80 px-4 py-2.5 pl-10 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 text-sm"></i>
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="absolute right-3 top-3.5 text-gray-400 hover:text-[#E6A57E] transition">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <button onclick="bukaModalTambahUjian()" class="btn-gradient text-white font-semibold py-2.5 px-6 rounded-xl flex items-center justify-center shadow-lg transition-all duration-300">
                            <i class="fas fa-calendar-plus mr-2"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>

                <!-- Statistik Ringkas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-blue-700 font-semibold uppercase">Total Ujian</p>
                                <p class="text-3xl font-bold text-blue-800"><?= $total_ujian ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-white text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-green-700 font-semibold uppercase">Ujian Aktif</p>
                                <p class="text-3xl font-bold text-green-800"><?= $total_aktif ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-orange-700 font-semibold uppercase">Ujian Tidak Aktif</p>
                                <p class="text-3xl font-bold text-orange-800"><?= $total_tidak_aktif ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-orange-500 flex items-center justify-center">
                                <i class="fas fa-pause-circle text-white text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Ujian Aktif -->
                <div class="mb-8">
                    <div class="section-title flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            Ujian Aktif (<?= $total_aktif ?>)
                        </h3>
                    </div>
                    
                    <?php if ($total_aktif > 0): ?>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr class="border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Info Ujian</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mata Pelajaran</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu & Durasi</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Kapasitas Soal</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($ujian_aktif as $u): ?>
                                        <tr class="hover:bg-green-50/50 transition duration-300">
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($u['judul_ujian']) ?></p>
                                                <p class="text-xs text-green-600 font-bold">Oleh: <?= htmlspecialchars($u['nama_guru']) ?></p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-medium text-gray-600"><?= htmlspecialchars($u['nama_mapel']) ?></p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="text-xs font-bold"><?= date('d M Y', strtotime($u['tanggal_ujian'])) ?></p>
                                                <p class="text-xs text-gray-400"><?= $u['jam_mulai'] ?> (<?= $u['durasi_menit'] ?> Menit)</p>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="bg-gradient-to-r from-[#F4C9A2] to-[#E6A57E] text-[#1B2F4F] text-xs font-bold px-3 py-1.5 rounded-full border border-[#E6A57E]">
                                                    <i class="fas fa-database mr-1"></i> <?= $u['jml_bank_soal'] ?> Soal
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-3 py-1.5 rounded-full text-xs font-bold uppercase bg-green-100 text-green-700 border border-green-300">
                                                    <i class="fas fa-circle text-[6px] mr-1 text-green-700"></i>
                                                    AKTIF
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <a href="ujian_edit.php?id=<?= $u['id_ujian'] ?>" class="action-btn w-8 h-8 rounded-xl bg-yellow-50 text-yellow-600 hover:bg-[#E6A57E] hover:text-white flex items-center justify-center transition-all border border-yellow-200" title="Edit Ujian">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </a>
                                                    <button onclick="ubahStatus(<?= $u['id_ujian'] ?>, '<?= $u['status_ujian'] ?>', '<?= htmlspecialchars(addslashes($u['judul_ujian'])) ?>')" 
                                                            class="action-btn w-8 h-8 rounded-xl bg-green-100 text-green-700 hover:bg-orange-600 hover:text-white flex items-center justify-center transition-all border border-green-300" 
                                                            title="Nonaktifkan Ujian">
                                                        <i class="fas fa-toggle-on text-sm"></i>
                                                    </button>
                                                    <button onclick="hapusUjian(<?= $u['id_ujian'] ?>, '<?= htmlspecialchars(addslashes($u['judul_ujian'])) ?>')" 
                                                            class="action-btn w-8 h-8 rounded-xl bg-red-50 text-red-600 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all border border-red-200" 
                                                            title="Hapus Ujian">
                                                        <i class="fas fa-trash text-sm"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 rounded-xl p-8 text-center border-2 border-dashed border-gray-300">
                            <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-check-circle text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-gray-500">Belum ada ujian yang aktif</p>
                            <p class="text-sm text-gray-400 mt-1">Ujian yang aktif akan muncul di sini</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tabel Ujian Tidak Aktif -->
                <div>
                    <div class="section-title flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-pause-circle text-orange-600 mr-2"></i>
                            Ujian Tidak Aktif (<?= $total_tidak_aktif ?>)
                        </h3>
                    </div>
                    
                    <?php if ($total_tidak_aktif > 0): ?>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr class="border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Info Ujian</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mata Pelajaran</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu & Durasi</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Kapasitas Soal</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($ujian_tidak_aktif as $u): ?>
                                        <tr class="hover:bg-orange-50/50 transition duration-300">
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($u['judul_ujian']) ?></p>
                                                <p class="text-xs text-orange-600 font-bold">Oleh: <?= htmlspecialchars($u['nama_guru']) ?></p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-medium text-gray-600"><?= htmlspecialchars($u['nama_mapel']) ?></p>
                                            <tr>
                                            <td class="px-6 py-4">
                                                <p class="text-xs font-bold"><?= date('d M Y', strtotime($u['tanggal_ujian'])) ?></p>
                                                <p class="text-xs text-gray-400"><?= $u['jam_mulai'] ?> (<?= $u['durasi_menit'] ?> Menit)</p>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="bg-gradient-to-r from-[#F4C9A2] to-[#E6A57E] text-[#1B2F4F] text-xs font-bold px-3 py-1.5 rounded-full border border-[#E6A57E]">
                                                    <i class="fas fa-database mr-1"></i> <?= $u['jml_bank_soal'] ?> Soal
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-3 py-1.5 rounded-full text-xs font-bold uppercase bg-orange-100 text-orange-700 border border-orange-300">
                                                    <i class="fas fa-circle text-[6px] mr-1 text-orange-700"></i>
                                                    TIDAK AKTIF
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <a href="ujian_edit.php?id=<?= $u['id_ujian'] ?>" class="action-btn w-8 h-8 rounded-xl bg-yellow-50 text-yellow-600 hover:bg-[#E6A57E] hover:text-white flex items-center justify-center transition-all border border-yellow-200" title="Edit Ujian">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </a>
                                                    <button onclick="ubahStatus(<?= $u['id_ujian'] ?>, '<?= $u['status_ujian'] ?>', '<?= htmlspecialchars(addslashes($u['judul_ujian'])) ?>')" 
                                                            class="action-btn w-8 h-8 rounded-xl bg-orange-100 text-orange-700 hover:bg-green-600 hover:text-white flex items-center justify-center transition-all border border-orange-300" 
                                                            title="Aktifkan Ujian">
                                                        <i class="fas fa-toggle-off text-sm"></i>
                                                    </button>
                                                    <button onclick="hapusUjian(<?= $u['id_ujian'] ?>, '<?= htmlspecialchars(addslashes($u['judul_ujian'])) ?>')" 
                                                            class="action-btn w-8 h-8 rounded-xl bg-red-50 text-red-600 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all border border-red-200" 
                                                            title="Hapus Ujian">
                                                        <i class="fas fa-trash text-sm"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 rounded-xl p-8 text-center border-2 border-dashed border-gray-300">
                            <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-pause-circle text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-gray-500">Belum ada ujian tidak aktif</p>
                            <p class="text-sm text-gray-400 mt-1">Ujian yang dinonaktifkan akan muncul di sini</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Footer Info -->
                <div class="mt-8 flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
                    <div>
                        <i class="fas fa-info-circle mr-1 text-[#1B2F4F]"></i>
                        Total menampilkan <?= $total_ujian ?> jadwal ujian
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-xs font-medium border border-green-300">
                            <i class="fas fa-circle text-[6px] mr-1"></i> Aktif: <?= $total_aktif ?>
                        </span>
                        <span class="px-3 py-1.5 bg-orange-100 text-orange-700 rounded-full text-xs font-medium border border-orange-300">
                            <i class="fas fa-circle text-[6px] mr-1"></i> Tidak Aktif: <?= $total_tidak_aktif ?>
                        </span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Tambah Ujian -->
    <div id="modalTambahUjian" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#1B2F4F] to-[#2C5F4A] rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Tambah Jadwal Ujian Baru
                    </h3>
                    <button onclick="tutupModalTambahUjian()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form action="ujian_proses.php" method="POST" onsubmit="return validasiForm()" class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Judul Ujian <span class="text-red-500">*</span></label>
                        <input type="text" name="judul_ujian" id="judul_ujian" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition"
                               placeholder="Contoh: UTS Ganjil 2024">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mata Pelajaran <span class="text-red-500">*</span></label>
                        <select name="id_mapel" id="id_mapel" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition">
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php foreach ($mapel_list as $mapel): ?>
                                <option value="<?= $mapel['id_mapel'] ?>"><?= htmlspecialchars($mapel['nama_mapel']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Guru Pengampu <span class="text-red-500">*</span></label>
                        <select name="id_guru" id="id_guru" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($guru_list as $guru): ?>
                                <option value="<?= $guru['id_user'] ?>"><?= htmlspecialchars($guru['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-span-1 md:col-span-2 mt-2">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tugaskan ke Kelas <span class="text-red-500">*</span></label>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 border border-gray-300 rounded-xl bg-gray-50 max-h-40 overflow-y-auto">
        <?php if (!empty($kelas_list)): ?>
            <?php foreach ($kelas_list as $kelas): ?>
                <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-1 rounded transition">
                    <input type="checkbox" name="kelas[]" value="<?= $kelas['id_kelas'] ?>" 
                           class="w-4 h-4 text-[#1B2F4F] border-gray-300 rounded focus:ring-[#1B2F4F]">
                    <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($kelas['nama_kelas']) ?></span>
                </label>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-xs text-red-500 col-span-3">Data kelas kosong. Silakan tambah data kelas terlebih dahulu.</p>
        <?php endif; ?>
    </div>
    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i> Centang satu atau lebih kelas yang akan mengikuti ujian ini.</p>
</div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="tanggal_ujian" id="tanggal_ujian" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Jam Mulai <span class="text-red-500">*</span></label>
                            <input type="time" name="jam_mulai" id="jam_mulai" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Durasi (Menit) <span class="text-red-500">*</span></label>
                        <input type="number" name="durasi_menit" id="durasi_menit" value="90" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B2F4F] focus:border-[#1B2F4F] transition">
                    </div>
                </div>
                
                <div class="mt-6 flex gap-3 justify-end">
                    <button type="button" onclick="tutupModalTambahUjian()" class="px-6 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit" name="tambah_ujian" class="btn-gradient text-white font-semibold py-2 px-6 rounded-xl transition-all duration-300">
                        <i class="fas fa-save mr-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal tambah ujian
        function bukaModalTambahUjian() {
            document.getElementById('modalTambahUjian').classList.replace('hidden', 'flex');
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal_ujian').min = today;
            document.querySelector('#modalTambahUjian form').reset();
        }

        function tutupModalTambahUjian() {
            document.getElementById('modalTambahUjian').classList.replace('flex', 'hidden');
        }

        function validasiForm() {
    const judul = document.getElementById('judul_ujian').value.trim();
    const mapel = document.getElementById('id_mapel').value;
    const guru = document.getElementById('id_guru').value;
    const tanggal = document.getElementById('tanggal_ujian').value;
    const jam = document.getElementById('jam_mulai').value;
    const durasi = document.getElementById('durasi_menit').value;
    
    // CEK APAKAH ADA KELAS YANG DICENTANG
    const kelasDicentang = document.querySelectorAll('input[name="kelas[]"]:checked');

    if (!judul || !mapel || !guru || !tanggal || !jam || !durasi) {
        Swal.fire({
            icon: 'error', title: 'Oops...', text: 'Semua field harus diisi!', confirmButtonColor: '#1B2F4F'
        });
        return false;
    }
    
    // VALIDASI KELAS
    if (kelasDicentang.length === 0) {
        Swal.fire({
            icon: 'warning', title: 'Kelas Belum Dipilih!', text: 'Anda harus menugaskan ujian ini minimal ke 1 kelas.', confirmButtonColor: '#1B2F4F'
        });
        return false;
    }
    
    return true;
}

        function ubahStatus(id, statusSekarang, judul) {
            let statusBaru = statusSekarang == 'aktif' ? 'tidak_aktif' : 'aktif';
            let warna = statusBaru == 'aktif' ? '#2C5F4A' : '#E6A57E';
            let aksi = statusBaru == 'aktif' ? 'Aktifkan' : 'Nonaktifkan';
            
            Swal.fire({
                title: `${aksi} Ujian?`,
                html: `
                    <div class="text-left p-2">
                        <p class="font-bold text-lg text-[#1B2F4F] text-center my-3">"${judul}"</p>
                        <p class="text-sm text-gray-600 text-center">
                            Status akan berubah dari <span class="font-bold ${statusSekarang == 'aktif' ? 'text-green-600' : 'text-orange-600'}">${statusSekarang.toUpperCase()}</span> 
                            menjadi <span class="font-bold ${statusBaru == 'aktif' ? 'text-green-600' : 'text-orange-600'}">${statusBaru.toUpperCase()}</span>
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: warna,
                cancelButtonColor: '#6B7280',
                confirmButtonText: `Ya, ${aksi}!`,
                cancelButtonText: 'Batal',
                background: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "ujian_proses.php?ubah_status=" + id + "&status=" + statusBaru;
                }
            });
        }

        function hapusUjian(id, judul) {
            Swal.fire({
                title: 'Hapus Jadwal Ujian?',
                html: `
                    <div class="text-left p-2">
                        <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                            <p class="font-bold text-red-700">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                PERHATIAN! Tindakan ini permanen:
                            </p>
                        </div>
                        <p class="font-bold text-lg text-[#1B2F4F] text-center my-3">"${judul}"</p>
                        <p class="text-xs font-bold text-red-500 mt-4 text-center">
                            DATA YANG DIHAPUS TIDAK DAPAT DIKEMBALIKAN!
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Hapus Permanen!',
                cancelButtonText: 'Batal',
                background: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "ujian_proses.php?hapus_ujian=" + id;
                }
            });
        }

        window.onclick = function(event) {
            const modalTambah = document.getElementById('modalTambahUjian');
            if (event.target === modalTambah) {
                tutupModalTambahUjian();
            }
        }
    </script>

    <?php if (isset($_SESSION['notif'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['notif']['type'] ?>',
                title: '<?= $_SESSION['notif']['title'] ?>',
                text: '<?= $_SESSION['notif']['text'] ?>',
                timer: 3000,
                showConfirmButton: true,
                confirmButtonColor: '#1B2F4F',
                background: '#fff'
            });
        </script>
        <?php unset($_SESSION['notif']); ?>
    <?php endif; ?>
</body>

</html>