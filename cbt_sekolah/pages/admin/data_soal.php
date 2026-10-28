<?php

//bank soal di dashboard admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Fitur pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Mengambil daftar mapel dan menghitung jumlah soal di tabel tb_soal
if (!empty($search)) {
    $query = "SELECT m.*, 
              (SELECT COUNT(*) FROM tb_soal WHERE id_mapel = m.id_mapel) as total_soal 
              FROM tb_mapel m 
              WHERE m.nama_mapel LIKE :search 
              ORDER BY m.nama_mapel ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => "%$search%"]);
} else {
    $query = "SELECT m.*, 
              (SELECT COUNT(*) FROM tb_soal WHERE id_mapel = m.id_mapel) as total_soal 
              FROM tb_mapel m 
              ORDER BY nama_mapel ASC";
    $stmt = $pdo->query($query);
}
$mapel = $stmt->fetchAll();

// Hitung total mapel
$total_mapel = count($mapel);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal - Admin CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Gradien biru navy, orange, dan hijau soft */
        .gradient-bg {
            background: linear-gradient(135deg, #0A192F 0%, #1E3A5F 30%, #F4A261 60%, #90BE6D 100%);
            background-size: 200% 200%;
            animation: gradientMove 10s ease infinite;
        }
        
        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .back-btn {
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }

        /* Gradien untuk tombol */
        .btn-gradient {
            background: linear-gradient(135deg, #0A192F, #1E3A5F, #F4A261, #90BE6D);
            background-size: 300% 300%;
            animation: btnGradient 5s ease infinite;
            transition: all 0.3s ease;
        }
        
        @keyframes btnGradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .btn-gradient:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(10, 25, 47, 0.3);
        }

        /* Style untuk card total mapel yang lebih jelas */
        .total-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(244, 162, 97, 0.3);
            box-shadow: 0 20px 30px -10px rgba(10, 25, 47, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .total-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0A192F, #F4A261, #90BE6D);
        }
        
        .total-card::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: -10px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(244, 162, 97, 0.1) 0%, rgba(144, 190, 109, 0.05) 70%);
            border-radius: 50%;
            z-index: 0;
        }
        
        .total-icon {
            background: linear-gradient(145deg, #0A192F, #1E3A5F);
            box-shadow: 0 10px 15px -5px rgba(10, 25, 47, 0.4);
            position: relative;
            z-index: 10;
        }
        
        .total-content {
            position: relative;
            z-index: 10;
        }
        
        .total-label {
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            background: linear-gradient(90deg, #F4A261, #90BE6D);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        
        .total-number {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #0A192F, #1E3A5F, #F4A261);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Warna aksen */
        .text-navy {
            color: #0A192F;
        }
        
        .text-orange-soft {
            color: #F4A261;
        }
        
        .text-green-soft {
            color: #90BE6D;
        }
        
        .bg-navy {
            background-color: #0A192F;
        }
        
        .bg-orange-soft {
            background-color: #F4A261;
        }
        
        .bg-green-soft {
            background-color: #90BE6D;
        }
        
        .border-navy {
            border-color: #0A192F;
        }
        
        .border-orange-soft {
            border-color: #F4A261;
        }
        
        .border-green-soft {
            border-color: #90BE6D;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Sidebar tidak di-include -->

    <div class="w-full transition-all duration-300">
        <!-- Header dengan gradient biru navy, orange, hijau soft -->
        <header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
            <div class="flex items-center space-x-3">
                <i class="fas fa-layer-group text-white text-2xl"></i>
                <h1 class="text-2xl font-bold text-white">Bank Soal</h1>
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
            <!-- Tombol Kembali ke Dashboard -->
            <div class="mb-6">
                <a href="dashboard.php" class="back-btn inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 hover:border-[#F4A261] hover:text-[#0A192F] shadow-sm transition-all duration-300 group">
                    <i class="fas fa-arrow-left mr-2 text-sm group-hover:-translate-x-1 transition-transform duration-300"></i>
                    <span class="font-medium">Kembali ke Dashboard</span>
                </a>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Gudang Bank Soal</h2>
                        <p class="text-sm text-gray-500 mt-1">Kelola koleksi pertanyaan berdasarkan mata pelajaran sekolah</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                        <form method="GET" action="" class="flex-1 sm:flex-initial">
                            <div class="relative">
                                <input type="text" 
                                       name="search" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Cari mata pelajaran..." 
                                       class="w-full sm:w-80 px-4 py-2.5 pl-10 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 text-sm"></i>
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="absolute right-3 top-3.5 text-gray-400 hover:text-[#F4A261] transition">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistik Total Mapel - Diperjelas -->
                <div class="mb-10">
                    <div class="total-card rounded-2xl p-8 relative">
                        <div class="flex items-center gap-8 relative z-10">
                            <div class="total-icon w-24 h-24 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-layer-group text-white text-4xl"></i>
                            </div>
                            <div class="total-content flex-1">
                                <div class="flex flex-col">
                                    <span class="total-label text-sm font-bold uppercase tracking-wider mb-2">
                                        <i class="fas fa-database mr-2 text-[#F4A261]"></i>
                                        TOTAL MATA PELAJARAN
                                    </span>
                                    <div class="flex items-baseline gap-3">
                                        <span class="total-number"><?= $total_mapel ?></span>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">
                                            <i class="fas fa-circle text-[6px] mr-1"></i> Aktif
                                        </span>
                                    </div>
                                    <p class="text-gray-500 text-sm mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-2 text-[#F4A261]"></i>
                                        Total mata pelajaran yang tersedia di bank soal
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Decorative elements -->
                        <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                            <i class="fas fa-book-open text-8xl text-[#0A192F]"></i>
                        </div>
                    </div>
                </div>

                <!-- Grid Mapel -->
                <?php if (count($mapel) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($mapel as $m): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover group">
                            <div class="flex justify-between items-start mb-6">
                                <div class="bg-gradient-to-r from-blue-50 to-orange-50 p-4 rounded-2xl group-hover:from-[#0A192F] group-hover:to-[#1E3A5F] group-hover:text-white transition-all duration-300">
                                    <i class="fas fa-book-open text-2xl text-[#0A192F] group-hover:text-white"></i>
                                </div>
                                <div class="text-right">
                                    <span class="bg-gradient-to-r from-orange-50 to-green-50 text-[#0A192F] text-xs font-bold px-3 py-1 rounded-full border border-[#F4A261]">
                                        <?= $m['total_soal'] ?> Soal
                                    </span>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($m['nama_mapel']) ?></h3>
                            <p class="text-xs text-gray-400 mb-6 leading-relaxed">Kelola draf soal, kunci jawaban, dan materi untuk mapel ini.</p>

                            <a href="soal_list.php?id_mapel=<?= $m['id_mapel'] ?>" class="btn-gradient text-white w-full py-3 rounded-xl text-sm font-bold flex items-center justify-center shadow-lg transition-all duration-300">
                                Kelola Koleksi <i class="fas fa-arrow-right ml-2 text-xs"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <!-- Tampilan ketika data kosong -->
                    <div class="py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="h-24 w-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-folder-open text-gray-400 text-4xl"></i>
                            </div>
                            <h3 class="text-gray-700 font-semibold text-lg mb-1">Belum ada mata pelajaran</h3>
                            <p class="text-gray-500 text-sm mb-4">
                                <?php if (!empty($search)): ?>
                                    Pencarian "<?= htmlspecialchars($search) ?>" tidak ditemukan
                                <?php else: ?>
                                    Tambahkan mata pelajaran terlebih dahulu
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search)): ?>
                                <a href="?" class="text-[#0A192F] hover:text-[#F4A261] font-medium text-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke semua data
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Footer -->
                <?php if (count($mapel) > 0): ?>
                <div class="mt-6 flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
                    <div>
                        <i class="fas fa-info-circle mr-1 text-[#0A192F]"></i>
                        Menampilkan <?= count($mapel) ?> mata pelajaran
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                            <i class="fas fa-circle text-[8px] mr-1"></i> Aktif
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>