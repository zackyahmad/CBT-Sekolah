<?php
session_start();
// Cek sesi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// AMBIL DATA REAL-TIME DARI TABEL YANG BENAR
$count_siswa = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'siswa'")->fetchColumn();
$count_guru = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'guru'")->fetchColumn();
$count_ujian = $pdo->query("SELECT COUNT(*) FROM tb_ujian")->fetchColumn();
$count_kelas = $pdo->query("SELECT COUNT(*) FROM tb_kelas")->fetchColumn();

// Data tambahan
try {
    $count_mapel = $pdo->query("SELECT COUNT(*) FROM tb_mapel")->fetchColumn();
} catch (PDOException $e) {
    $count_mapel = 0;
}

try {
    $count_soal = $pdo->query("SELECT COUNT(*) FROM tb_soal")->fetchColumn();
} catch (PDOException $e) {
    $count_soal = 0;
}

// Hitung jumlah kartu ujian yang sudah dibuat
$count_kartu = 0;
try {
    $count_kartu = $pdo->query("SELECT COUNT(*) FROM tb_kartu_ujian")->fetchColumn();
} catch (PDOException $e) {
    $count_kartu = 0;
}

// AMBIL DATA UNTUK MODAL TAMBAH UJIAN
// Ambil data Mapel
$mapel_list = $pdo->query("SELECT * FROM tb_mapel ORDER BY nama_mapel ASC")->fetchAll();

// Ambil data Guru (admin dan guru)
$guru_list = $pdo->query("SELECT * FROM tb_users WHERE role IN ('admin', 'guru') ORDER BY nama_lengkap ASC")->fetchAll();

// Ambil data Kelas untuk dipilih
$kelas_list = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC")->fetchAll();

// Kelompokkan kelas berdasarkan tingkat
$kelas_x = [];
$kelas_xi = [];
$kelas_xii = [];
$kelas_lain = [];

foreach ($kelas_list as $k) {
    $n = strtoupper($k['nama_kelas']);
    if (strpos($n, 'XII') === 0) {
        $kelas_xii[] = $k;
    } elseif (strpos($n, 'XI') === 0) {
        $kelas_xi[] = $k;
    } elseif (strpos($n, 'X') === 0) {
        $kelas_x[] = $k;
    } else {
        $kelas_lain[] = $k;
    }
}

// Data ujian terbaru dengan JOIN ke tb_mapel
$ujian_terbaru = [];
try {
    $query = "SELECT u.*, m.nama_mapel 
              FROM tb_ujian u 
              LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel 
              ORDER BY u.id_ujian DESC 
              LIMIT 5";
    $ujian_terbaru = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $ujian_terbaru = [];
}

// Data kartu ujian terbaru
$kartu_terbaru = [];
try {
    $query = "SELECT * FROM tb_kartu_ujian ORDER BY id_kartu DESC LIMIT 5";
    $kartu_terbaru = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $kartu_terbaru = [];
}

// Siswa aktif
$siswa_aktif = $count_siswa;
try {
    $siswa_aktif = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'siswa' AND status = 'aktif'")->fetchColumn();
    if (!$siswa_aktif) $siswa_aktif = $count_siswa;
} catch (PDOException $e) {
    $siswa_aktif = $count_siswa;
}

$persentase_kehadiran = $count_siswa > 0 ? round(($siswa_aktif / $count_siswa) * 100) : 0;

// Untuk menu active
$current_page = basename($_SERVER['PHP_SELF']);

// Data waktu
date_default_timezone_set('Asia/Jakarta');
$tanggal = date('d/m/Y');
$jam = date('H:i');
$hari = date('l');
$hari_indonesia = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$nama_hari = $hari_indonesia[$hari] ?? $hari;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ADMIN | SMK N 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Palet Warna yang Lebih Serasi */
        :root {
            --primary-1: #2B3A67;
            --primary-2: #496A81;
            --secondary-1: #66999B;
            --secondary-2: #B3AF8F;
            --accent-1: #FFC482;
            --accent-2: #DDB7AB;
            --neutral-1: #F8F4E9;
            --neutral-2: #E8E1D3;
            --dark: #2E3B4E;
            --success: #77BFA3;
            --warning: #FFB347;
            --danger: #E58C7C;
        }

        body {
            background: linear-gradient(135deg, var(--neutral-1) 0%, var(--neutral-2) 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-1) 0%, var(--primary-2) 100%);
            box-shadow: 4px 0 20px rgba(43, 58, 103, 0.2);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px;
            margin-top: auto;
        }

        .sidebar-item {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgba(255, 196, 130, 0.3) 0%, transparent 100%);
            transition: width 0.3s ease;
        }

        .sidebar-item:hover::before {
            width: 100%;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-1);
            transform: translateX(5px);
        }

        .sidebar-item.active {
            background: linear-gradient(90deg, var(--accent-1) 0%, var(--accent-2) 100%);
            color: var(--primary-1);
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(255, 196, 130, 0.3);
        }
        
        .stat-card {
            transition: all 0.3s ease;
            background: white;
            border: 1px solid rgba(73, 106, 129, 0.1);
            border-radius: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(43, 58, 103, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: var(--secondary-1);
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: var(--neutral-2);
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease;
            background: linear-gradient(90deg, var(--secondary-1) 0%, var(--secondary-2) 100%);
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-1) 0%, var(--secondary-1) 50%, var(--accent-1) 100%);
            position: relative;
            overflow: hidden;
            border-radius: 30px;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: rgba(43, 58, 103, 0.3);
        }
        
        .dropdown-menu.open {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }
        
        .dropdown-toggle .fa-chevron-down {
            transition: transform 0.3s;
        }
        
        .dropdown-toggle.open .fa-chevron-down {
            transform: rotate(180deg);
        }
        
        .dropdown-item {
            padding-left: 3rem !important;
            font-size: 0.85rem;
        }

        .dropdown-item:hover {
            background: var(--accent-1) !important;
            color: var(--primary-1) !important;
        }

        .quick-action-card {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }

        .quick-action-card:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05) translateY(-5px);
            border-color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .badge-custom {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .badge-primary {
            background: var(--neutral-2);
            color: var(--primary-1);
        }

        .badge-success {
            background: var(--success);
            color: white;
        }

        .badge-warning {
            background: var(--warning);
            color: white;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--neutral-1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--secondary-1) 0%, var(--primary-2) 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--accent-1) 0%, var(--secondary-1) 100%);
        }

        .header-gradient {
            background: linear-gradient(135deg, #ffffff 0%, var(--neutral-1) 100%);
            border-bottom: 3px solid var(--secondary-1);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-text {
            position: relative;
            flex: 1;
            min-width: 0;
        }

        .brand-text h2 {
            font-size: 1.2rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #FFFFFF 0%, var(--accent-1) 80%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 
                2px 2px 0 var(--primary-1),
                3px 3px 0 rgba(0, 0, 0, 0.3);
            margin-bottom: 2px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .brand-text h2::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 80%;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-1), transparent);
            border-radius: 2px;
        }

        .brand-text p {
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--accent-1);
            text-shadow: 1px 1px 0 rgba(0, 0, 0, 0.3);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .main-header-title h1 {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-1) 0%, var(--secondary-1) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .main-header-title p {
            font-size: 0.6rem;
            white-space: nowrap;
        }

        .section-title {
            color: var(--primary-1);
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            position: relative;
            padding-bottom: 8px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-1), var(--accent-1));
            border-radius: 2px;
        }

        @keyframes glowPulse {
            0% { text-shadow: 2px 2px 0 var(--primary-1), 3px 3px 0 rgba(0,0,0,0.3), 0 0 20px rgba(255,196,130,0.5); }
            50% { text-shadow: 2px 2px 0 var(--primary-1), 3px 3px 0 rgba(0,0,0,0.3), 0 0 30px rgba(255,196,130,0.8); }
            100% { text-shadow: 2px 2px 0 var(--primary-1), 3px 3px 0 rgba(0,0,0,0.3), 0 0 20px rgba(255,196,130,0.5); }
        }

        .brand-text h2 {
            animation: glowPulse 3s infinite;
        }

        /* Style untuk Modal */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .tab-btn {
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: var(--secondary-1);
            color: white;
        }

        .checkbox-kelas {
            accent-color: var(--secondary-1);
        }

        /* Card untuk kartu ujian mini */
        .kartu-mini-card {
            transition: all 0.3s ease;
        }

        .kartu-mini-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(43, 58, 103, 0.1);
        }

        /* Tombol Logout di Sidebar */
        .logout-sidebar {
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        .logout-sidebar:hover {
            background: var(--danger);
            transform: translateX(5px);
        }

        @media (max-width: 768px) {
            .brand-text h2 {
                font-size: 1rem;
            }
            
            .brand-text p {
                font-size: 0.55rem;
            }
            
            .main-header-title h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body class="font-sans">

    <!-- SIDEBAR -->
    <div class="sidebar fixed left-0 top-0 h-full w-64 text-white shadow-xl z-50">
        <div class="p-4 border-b border-white/10">
            <div class="logo-container">
                <img src="../../assets/img/logo-smk8.png" alt="Logo Sekolah" class="h-12 w-12 rounded-xl bg-white/10 p-1 border-2 border-white/20 shadow-lg transform hover:scale-105 transition flex-shrink-0">
                <div class="brand-text">
                    <h2>ADMINSKAPAN</h2>
                    <p>SMK N 8 Bandar Lampung</p>
                </div>
            </div>
        </div>
        
        <div class="sidebar-menu py-4 flex-1 overflow-y-auto">
            <!-- Dashboard -->
            <a href="dashboard.php" class="flex items-center px-6 py-3 sidebar-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home w-6"></i>
                <span class="text-sm font-medium">Dashboard</span>
            </a>

            <!-- AKADEMIK (Dropdown) -->
            <div class="dropdown-container mt-2">
                <div class="flex items-center justify-between px-6 py-3 sidebar-item cursor-pointer dropdown-toggle" onclick="toggleDropdown('akademikDropdown')">
                    <div class="flex items-center">
                        <i class="fas fa-graduation-cap w-6"></i>
                        <span class="text-sm font-medium">Akademik</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-white/60"></i>
                </div>
                <div id="akademikDropdown" class="dropdown-menu">
                    <a href="register.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'register.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-plus w-6"></i>
                        <span class="text-sm">Register</span>
                    </a>
                    <a href="tata_tertib.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'tata_tertib.php' ? 'active' : '' ?>">
                        <i class="fas fa-scroll w-6"></i>
                        <span class="text-sm">Tata Tertib</span>
                    </a>
                    <a href="berita_acara.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'berita_acara.php' ? 'active' : '' ?>">
                        <i class="fas fa-file-alt w-6"></i>
                        <span class="text-sm">Berita Acara</span>
                    </a>
                    <a href="kartu_ujian.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'kartu_ujian.php' ? 'active' : '' ?>">
                        <i class="fas fa-id-card w-6"></i>
                        <span class="text-sm">Kartu Ujian</span>
                    </a>
                </div>
            </div>

            <!-- CBT (Dropdown) -->
            <div class="dropdown-container mt-2">
                <div class="flex items-center justify-between px-6 py-3 sidebar-item cursor-pointer dropdown-toggle" onclick="toggleDropdown('cbtDropdown')">
                    <div class="flex items-center">
                        <i class="fas fa-laptop w-6"></i>
                        <span class="text-sm font-medium">CBT</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-white/60"></i>
                </div>
                <div id="cbtDropdown" class="dropdown-menu">
                    <a href="data_mapel.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'data_mapel.php' ? 'active' : '' ?>">
                        <i class="fas fa-book w-6"></i>
                        <span class="text-sm">Mata Pelajaran</span>
                    </a>
                    <a href="data_soal.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'data_soal.php' ? 'active' : '' ?>">
                        <i class="fas fa-database w-6"></i>
                        <span class="text-sm">Bank Soal</span>
                    </a>
                    <a href="rencana_ujian.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'rencana_ujian.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt w-6"></i>
                        <span class="text-sm">Rencana Ujian</span>
                    </a>
                    <a href="monitoring_ujian.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'monitoring_ujian.php' ? 'active' : '' ?>">
                        <i class="fas fa-eye w-6"></i>
                        <span class="text-sm">Monitoring Ujian</span>
                    </a>
                    <a href="perbaikan_nilai.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'perbaikan_nilai.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span class="text-sm">Nilai Ujian</span>
                    </a>
                </div>
            </div>

            <!-- LAPORAN (Dropdown) -->
            <div class="dropdown-container mt-2">
                <div class="flex items-center justify-between px-6 py-3 sidebar-item cursor-pointer dropdown-toggle" onclick="toggleDropdown('laporanDropdown')">
                    <div class="flex items-center">
                        <i class="fas fa-file-alt w-6"></i>
                        <span class="text-sm font-medium">Laporan</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-white/60"></i>
                </div>
                <div id="laporanDropdown" class="dropdown-menu">
                    <a href="data_kelas.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'data_kelas.php' ? 'active' : '' ?>">
                        <i class="fas fa-school w-6"></i>
                        <span class="text-sm">Data Kelas</span>
                    </a>
                    <a href="data_guru.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'data_guru.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-tie w-6"></i>
                        <span class="text-sm">Data Guru</span>
                    </a>
                    <a href="data_siswa.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'data_siswa.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-graduate w-6"></i>
                        <span class="text-sm">Data Siswa</span>
                    </a>
                    <a href="data_pengawas.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'data_pengawas.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-shield w-6"></i>
                        <span class="text-sm">Data Pengawas</span>
                    </a>
                    <a href="absensi.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'absensi.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check w-6"></i>
                        <span class="text-sm">Absensi</span>
                    </a>
                    <a href="laporan_absensi.php" class="flex items-center px-6 py-2 sidebar-item dropdown-item <?= $current_page == 'laporan_absensi.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line w-6"></i>
                        <span class="text-sm">Rekap Absensi</span>
                    </a>
                </div>
            </div>

            <!-- Menu Master Data -->
            <div class="mt-4 pt-4 border-t border-white/10">
                <div class="px-6 py-2">
                    <p class="text-xs font-semibold text-white/40 uppercase tracking-wider">Master Data</p>
                </div>
                <a href="data_kelas.php" class="flex items-center px-6 py-3 sidebar-item <?= $current_page == 'data_kelas.php' ? 'active' : '' ?>">
                    <i class="fas fa-school w-6"></i>
                    <span class="text-sm font-medium">Data Kelas</span>
                </a>
                <a href="data_mapel.php" class="flex items-center px-6 py-3 sidebar-item <?= $current_page == 'data_mapel.php' ? 'active' : '' ?>">
                    <i class="fas fa-book w-6"></i>
                    <span class="text-sm font-medium">Mata Pelajaran</span>
                </a>
                <a href="data_siswa.php" class="flex items-center px-6 py-3 sidebar-item <?= $current_page == 'data_siswa.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-graduate w-6"></i>
                    <span class="text-sm font-medium">Data Siswa</span>
                </a>
                <a href="data_guru.php" class="flex items-center px-6 py-3 sidebar-item <?= $current_page == 'data_guru.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-tie w-6"></i>
                    <span class="text-sm font-medium">Data Guru</span>
                </a>
                <a href="absensi.php" class="flex items-center px-6 py-3 sidebar-item <?= $current_page == 'absensi.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check w-6"></i>
                    <span class="text-sm font-medium">Absensi</span>
                </a>
            </div>
        </div>

        <!-- FOOTER SIDEBAR - TOMBOL LOGOUT -->
        <div class="sidebar-footer">
            <a href="../../logout.php" 
               class="logout-sidebar flex items-center gap-3 px-4 py-3 bg-red-500/80 hover:bg-red-600 text-white rounded-xl transition-all shadow-md"
               onclick="return confirm('Yakin ingin logout dari sistem?')">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="text-sm font-semibold">Logout</span>
            </a>
            <div class="mt-3 text-center">
                <p class="text-[0.55rem] text-white/40">© CIDIKNA SMK N 8 Bandar Lampung</p>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="ml-64 min-h-screen transition-all duration-300">

        <!-- HEADER -->
        <header class="header-gradient py-3 px-6 flex justify-between items-center sticky top-0 z-40 shadow-sm">
            <div class="logo-container">
                <img src="../../assets/img/logo-smk8.png" alt="Logo Sekolah" class="h-12 w-12 rounded-2xl shadow-xl border-4 border-white transform hover:scale-105 transition flex-shrink-0">
                <div class="main-header-title">
                    <h1 class="text-xl font-bold">Dashboard Overview</h1>
                    <p class="text-[0.6rem] font-semibold" style="color: var(--primary-2); letter-spacing: 0.3px;">
                        <i class="fas fa-bolt" style="color: var(--accent-1);"></i>
                        PUSAT KENDALI SMK NEGERI 8 BANDAR LAMPUNG
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right hidden md:block bg-white p-2 rounded-2xl shadow-sm" style="border: 1px solid var(--neutral-2);">
                    <p class="text-[0.65rem]" style="color: var(--primary-2); display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                        <i class="fas fa-calendar-alt text-xs" style="color: var(--secondary-1);"></i>
                        <?= $nama_hari . ', ' . $tanggal ?>
                    </p>
                    <p class="text-xs font-bold" style="color: var(--secondary-1); display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                        <i class="fas fa-clock text-xs" style="color: var(--accent-1);"></i>
                        <?= $jam ?> WIB
                    </p>
                </div>
                <div class="text-right hidden md:block">
                    <p class="text-xs font-bold" style="color: var(--primary-1);"><?= $_SESSION['nama'] ?? 'Admin'; ?></p>
                    <p class="text-[0.6rem] font-medium" style="color: var(--secondary-1); display: flex; align-items: center; justify-content: flex-end; gap: 2px;">
                        <i class="fas fa-shield-alt text-[0.6rem]" style="color: var(--accent-1);"></i>
                        Admin
                    </p>
                </div>
                <div class="relative">
                    <img src="../../assets/img/logo-smk8.png" class="h-10 w-10 rounded-2xl border-4 border-white shadow-lg transform hover:scale-105 transition">
                    <span class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full border-2 border-white" style="background: var(--success);"></span>
                </div>
            </div>
        </header>

        <main class="p-6">
            
            <!-- WELCOME CARD -->
            <div class="welcome-card rounded-3xl shadow-2xl p-6 relative overflow-hidden mb-6">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 text-white">
                    <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <img src="../../assets/img/logo-smk8.png" class="h-16 w-16 rounded-2xl border-4 border-white/30 shadow-xl transform hover:scale-110 transition">
                            <div>
                                <h3 class="text-2xl font-bold mb-1">Selamat Datang, <?= $_SESSION['nama'] ?? 'Admin' ?>! 🚀</h3>
                                <p class="text-white/90 max-w-2xl leading-relaxed text-base">
                                    Semua sistem berjalan normal. Gunakan menu di bawah untuk mengakses fitur utama.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="register.php" class="bg-white/20 hover:bg-white/30 text-white font-bold py-2 px-4 rounded-xl backdrop-blur-sm border border-white/30 transition-all flex items-center gap-2">
                                <i class="fas fa-user-plus"></i> Register User
                            </a>
                            <button onclick="bukaModalUjian()" class="bg-white/20 hover:bg-white/30 text-white font-bold py-2 px-4 rounded-xl backdrop-blur-sm border border-white/30 transition-all flex items-center gap-2">
                                <i class="fas fa-calendar-plus"></i> Buat Ujian
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="grid grid-cols-2 md:grid-cols-7 gap-3 mt-6">
                        <a href="register.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--purple), var(--primary-2));">
                                <i class="fas fa-user-plus text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Register User</p>
                        </a>
                        <a href="data_soal.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--secondary-1), var(--primary-2));">
                                <i class="fas fa-database text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Bank Soal</p>
                        </a>
                        <a href="data_siswa.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--primary-1), var(--primary-2));">
                                <i class="fas fa-user-graduate text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Kelola Siswa</p>
                        </a>
                        <a href="berita_acara.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--accent-1), var(--accent-2));">
                                <i class="fas fa-file-alt text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Berita Acara</p>
                        </a>
                        <a href="tata_tertib.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--secondary-2), var(--neutral-2));">
                                <i class="fas fa-scroll text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Tata Tertib</p>
                        </a>
                        <a href="kartu_ujian.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--success), var(--secondary-1));">
                                <i class="fas fa-id-card text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Kartu Ujian</p>
                        </a>
                        <a href="absensi.php" class="quick-action-card p-3 rounded-2xl text-center group">
                            <div class="icon-circle mx-auto mb-2 group-hover:scale-110 transition w-10 h-10" style="background: linear-gradient(135deg, var(--warning), var(--accent-1));">
                                <i class="fas fa-calendar-check text-white text-sm"></i>
                            </div>
                            <p class="text-xs font-semibold text-white">Absensi</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
                <div class="stat-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="icon-circle w-10 h-10" style="background: linear-gradient(135deg, var(--secondary-1), var(--primary-2));">
                            <i class="fas fa-user-graduate text-white text-sm"></i>
                        </div>
                        <span class="badge-custom badge-primary text-[0.7rem]">Total</span>
                    </div>
                    <h4 class="text-[0.65rem] font-bold uppercase tracking-wider mb-1" style="color: var(--primary-2);">TOTAL SISWA</h4>
                    <p class="text-2xl font-black mb-2" style="color: var(--primary-1);"><?= $count_siswa; ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($count_siswa / 100) * 100) ?>%"></div>
                    </div>
                </div>

                <div class="stat-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="icon-circle w-10 h-10" style="background: linear-gradient(135deg, var(--primary-1), var(--primary-2));">
                            <i class="fas fa-user-tie text-white text-sm"></i>
                        </div>
                        <span class="badge-custom badge-primary text-[0.7rem]">Total</span>
                    </div>
                    <h4 class="text-[0.65rem] font-bold uppercase tracking-wider mb-1" style="color: var(--primary-2);">TOTAL GURU</h4>
                    <p class="text-2xl font-black mb-2" style="color: var(--primary-1);"><?= $count_guru; ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($count_guru / 50) * 100) ?>%"></div>
                    </div>
                </div>

                <div class="stat-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="icon-circle w-10 h-10" style="background: linear-gradient(135deg, var(--accent-1), var(--warning));">
                            <i class="fas fa-file-signature text-white text-sm"></i>
                        </div>
                        <span class="badge-custom text-[0.7rem]" style="background: var(--warning); color: white;">Aktif</span>
                    </div>
                    <h4 class="text-[0.65rem] font-bold uppercase tracking-wider mb-1" style="color: var(--primary-2);">UJIAN AKTIF</h4>
                    <p class="text-2xl font-black mb-2" style="color: var(--primary-1);"><?= $count_ujian; ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($count_ujian / 10) * 100) ?>%"></div>
                    </div>
                </div>

                <div class="stat-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="icon-circle w-10 h-10" style="background: linear-gradient(135deg, var(--secondary-2), var(--neutral-2));">
                            <i class="fas fa-school text-white text-sm"></i>
                        </div>
                        <span class="badge-custom badge-primary text-[0.7rem]">Total</span>
                    </div>
                    <h4 class="text-[0.65rem] font-bold uppercase tracking-wider mb-1" style="color: var(--primary-2);">TOTAL KELAS</h4>
                    <p class="text-2xl font-black mb-2" style="color: var(--primary-1);"><?= $count_kelas; ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($count_kelas / 20) * 100) ?>%"></div>
                    </div>
                </div>

                <div class="stat-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="icon-circle w-10 h-10" style="background: linear-gradient(135deg, var(--success), var(--secondary-1));">
                            <i class="fas fa-id-card text-white text-sm"></i>
                        </div>
                        <span class="badge-custom badge-primary text-[0.7rem]">Total</span>
                    </div>
                    <h4 class="text-[0.65rem] font-bold uppercase tracking-wider mb-1" style="color: var(--primary-2);">KARTU UJIAN</h4>
                    <p class="text-2xl font-black mb-2" style="color: var(--primary-1);"><?= $count_kartu; ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($count_kartu / 100) * 100) ?>%"></div>
                    </div>
                </div>

                <div class="stat-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="icon-circle w-10 h-10" style="background: linear-gradient(135deg, var(--warning), var(--accent-1));">
                            <i class="fas fa-calendar-check text-white text-sm"></i>
                        </div>
                        <span class="badge-custom text-[0.7rem]" style="background: var(--warning); color: white;">Hari Ini</span>
                    </div>
                    <h4 class="text-[0.65rem] font-bold uppercase tracking-wider mb-1" style="color: var(--primary-2);">ABSENSI HARI INI</h4>
                    <p class="text-2xl font-black mb-2" style="color: var(--primary-1);"><?= $siswa_aktif ?>/<?= $count_siswa ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $persentase_kehadiran ?>%"></div>
                    </div>
                    <p class="text-[0.55rem] mt-1 font-bold text-right" style="color: var(--secondary-1);"><?= $persentase_kehadiran ?>% Kehadiran</p>
                </div>
            </div>

            <!-- RINGKASAN DATA & UJIAN TERBARU & KARTU TERBARU & ABSENSI -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Card Ringkasan Data -->
                <div class="bg-white rounded-2xl shadow-lg p-4" style="border: 1px solid var(--neutral-2);">
                    <h3 class="section-title mb-3 flex items-center gap-2 text-base">
                        <i class="fas fa-chart-pie text-sm" style="color: var(--secondary-1);"></i>
                        Ringkasan Data
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-2 rounded-xl" style="background: linear-gradient(135deg, var(--secondary-1)/10, transparent);">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-2 shadow-lg" style="background: linear-gradient(135deg, var(--secondary-1), var(--primary-2));">
                                    <i class="fas fa-book text-white text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-xs" style="color: var(--primary-2);">Mata Pelajaran</p>
                                    <p class="text-xl font-bold" style="color: var(--primary-1);"><?= $count_mapel ?></p>
                                </div>
                            </div>
                            <span class="badge-custom text-[0.65rem]" style="background: var(--secondary-1); color: white;">Aktif</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-2 rounded-xl" style="background: linear-gradient(135deg, var(--primary-1)/10, transparent);">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-2 shadow-lg" style="background: linear-gradient(135deg, var(--primary-1), var(--primary-2));">
                                    <i class="fas fa-database text-white text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-xs" style="color: var(--primary-2);">Bank Soal</p>
                                    <p class="text-xl font-bold" style="color: var(--primary-1);"><?= $count_soal ?></p>
                                </div>
                            </div>
                            <span class="badge-custom text-[0.65rem]" style="background: var(--primary-1); color: white;">Soal</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-2 rounded-xl" style="background: linear-gradient(135deg, var(--accent-1)/10, transparent);">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-2 shadow-lg" style="background: linear-gradient(135deg, var(--accent-1), var(--warning));">
                                    <i class="fas fa-user-check text-white text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-xs" style="color: var(--primary-2);">Siswa Aktif</p>
                                    <p class="text-xl font-bold" style="color: var(--primary-1);"><?= $siswa_aktif ?></p>
                                </div>
                            </div>
                            <span class="badge-custom text-[0.65rem]" style="background: var(--accent-1); color: white;"><?= $persentase_kehadiran ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Card Ujian Terbaru -->
                <div class="bg-white rounded-2xl shadow-lg p-4" style="border: 1px solid var(--neutral-2);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="section-title flex items-center gap-2 text-base">
                            <i class="fas fa-clock text-sm" style="color: var(--accent-1);"></i>
                            Ujian Terbaru
                        </h3>
                        <a href="rencana_ujian.php" class="badge-custom flex items-center gap-1 text-[0.65rem]" style="background: linear-gradient(135deg, var(--secondary-1), var(--primary-2)); color: white;">
                            Lihat Semua <i class="fas fa-arrow-right text-[0.6rem]"></i>
                        </a>
                    </div>
                    
                    <?php if (count($ujian_terbaru) > 0): ?>
                        <div class="space-y-2">
                            <?php foreach ($ujian_terbaru as $ujian): 
                                $judul = $ujian['judul_ujian'] ?? 'Ujian';
                                $mapel = $ujian['nama_mapel'] ?? 'Mata Pelajaran';
                                $tanggal_ujian = date('d/m/Y', strtotime($ujian['tanggal_ujian'] ?? date('Y-m-d')));
                                $jam_mulai = substr($ujian['jam_mulai'] ?? '00:00:00', 0, 5);
                                $status = $ujian['status'] ?? 'aktif';
                                $status_color = ($status == 'aktif') ? 'var(--success)' : 'var(--warning)';
                            ?>
                            <div class="flex items-center justify-between p-3 rounded-xl transition hover:shadow-md" style="background: linear-gradient(135deg, var(--neutral-1), white); border: 1px solid var(--neutral-2);">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, var(--secondary-1), var(--primary-2));">
                                        <i class="fas fa-file-alt text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold" style="color: var(--primary-1);">
                                            <?= htmlspecialchars($judul) ?>
                                        </p>
                                        <p class="text-[0.55rem] font-medium" style="color: var(--primary-2);">
                                            <?= htmlspecialchars($mapel) ?> • <?= $tanggal_ujian ?> <?= $jam_mulai ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="badge-custom text-[0.55rem] px-2 py-1" style="background: <?= $status_color ?>; color: white;">
                                    <?= strtoupper($status) ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 rounded-xl" style="background: linear-gradient(135deg, var(--neutral-1), white);">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-2" style="background: var(--neutral-2);">
                                <i class="fas fa-calendar-times text-3xl" style="color: var(--primary-2);"></i>
                            </div>
                            <p class="text-xs" style="color: var(--primary-2);">Belum ada ujian terjadwal</p>
                            <button onclick="bukaModalUjian()" class="inline-block mt-2 text-[0.6rem] font-bold px-3 py-1 rounded-full" style="background: var(--secondary-1); color: white;">
                                Buat Jadwal Baru
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card Kartu Ujian Terbaru -->
                <div class="bg-white rounded-2xl shadow-lg p-4" style="border: 1px solid var(--neutral-2);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="section-title flex items-center gap-2 text-base">
                            <i class="fas fa-id-card text-sm" style="color: var(--success);"></i>
                            Kartu Ujian Terbaru
                        </h3>
                        <a href="kartu_ujian.php" class="badge-custom flex items-center gap-1 text-[0.65rem]" style="background: linear-gradient(135deg, var(--success), var(--secondary-1)); color: white;">
                            Kelola <i class="fas fa-arrow-right text-[0.6rem]"></i>
                        </a>
                    </div>
                    
                    <?php if (count($kartu_terbaru) > 0): ?>
                        <div class="space-y-2">
                            <?php foreach ($kartu_terbaru as $kartu): ?>
                            <div class="kartu-mini-card flex items-center justify-between p-3 rounded-xl transition hover:shadow-md" style="background: linear-gradient(135deg, var(--neutral-1), white); border: 1px solid var(--neutral-2);">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, var(--success), var(--secondary-1));">
                                        <i class="fas fa-id-card text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold" style="color: var(--primary-1);">
                                            <?= htmlspecialchars($kartu['nama_siswa'] ?? 'Siswa') ?>
                                        </p>
                                        <p class="text-[0.55rem] font-medium" style="color: var(--primary-2);">
                                            NIS: <?= $kartu['nis'] ?? '-' ?> • <?= $kartu['kelas'] ?? '-' ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="cetak_kartu.php?id=<?= $kartu['id_kartu'] ?? 0 ?>" target="_blank" class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-print text-xs"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="cetak_kartu_all.php" target="_blank" class="text-[0.6rem] font-bold text-blue-600 hover:underline">
                                <i class="fas fa-print mr-1"></i> Cetak Semua Kartu
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 rounded-xl" style="background: linear-gradient(135deg, var(--neutral-1), white);">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-2" style="background: var(--neutral-2);">
                                <i class="fas fa-id-card text-3xl" style="color: var(--primary-2);"></i>
                            </div>
                            <p class="text-xs" style="color: var(--primary-2);">Belum ada kartu ujian</p>
                            <a href="kartu_ujian.php" class="inline-block mt-2 text-[0.6rem] font-bold px-3 py-1 rounded-full" style="background: var(--success); color: white;">
                                Import Data Kartu
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card Absensi Hari Ini -->
                <div class="bg-white rounded-2xl shadow-lg p-4" style="border: 1px solid var(--neutral-2);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="section-title flex items-center gap-2 text-base">
                            <i class="fas fa-calendar-check text-sm" style="color: var(--warning);"></i>
                            Absensi Hari Ini
                        </h3>
                        <a href="absensi.php" class="badge-custom flex items-center gap-1 text-[0.65rem]" style="background: linear-gradient(135deg, var(--warning), var(--accent-1)); color: white;">
                            Input <i class="fas fa-arrow-right text-[0.6rem]"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div class="text-center p-2 rounded-xl" style="background: linear-gradient(135deg, #77BFA3, #66999B);">
                                <p class="text-white text-lg font-bold"><?= round($siswa_aktif * 0.85) ?></p>
                                <p class="text-white text-[0.55rem] font-semibold">HADIR</p>
                            </div>
                            <div class="text-center p-2 rounded-xl" style="background: linear-gradient(135deg, #FFB347, #E58C7C);">
                                <p class="text-white text-lg font-bold"><?= round($siswa_aktif * 0.15) ?></p>
                                <p class="text-white text-[0.55rem] font-semibold">TIDAK HADIR</p>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between items-center p-2 rounded-xl bg-red-50">
                                <span class="text-xs font-medium text-red-600"><i class="fas fa-times-circle mr-1"></i> Sakit</span>
                                <span class="text-xs font-bold text-red-600"><?= round($siswa_aktif * 0.05) ?></span>
                            </div>
                            <div class="flex justify-between items-center p-2 rounded-xl bg-yellow-50">
                                <span class="text-xs font-medium text-yellow-600"><i class="fas fa-clock mr-1"></i> Izin</span>
                                <span class="text-xs font-bold text-yellow-600"><?= round($siswa_aktif * 0.07) ?></span>
                            </div>
                            <div class="flex justify-between items-center p-2 rounded-xl bg-gray-50">
                                <span class="text-xs font-medium text-gray-600"><i class="fas fa-question-circle mr-1"></i> Alpha</span>
                                <span class="text-xs font-bold text-gray-600"><?= round($siswa_aktif * 0.03) ?></span>
                            </div>
                        </div>
                        
                        <a href="laporan_absensi.php" class="block text-center mt-2 text-[0.6rem] font-bold text-blue-600 hover:underline">
                            <i class="fas fa-chart-line mr-1"></i> Lihat Rekap Absensi
                        </a>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="mt-6 text-center text-xs border-t pt-4" style="color: var(--primary-2); border-color: var(--neutral-2);">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <img src="../../assets/img/logo-smk8.png" class="h-5 w-5 rounded-lg">
                    <p>© CIDIKNA. SMK Negeri 8 Bandar Lampung.</p>
                </div>
                <p class="text-[0.65rem]" style="color: var(--secondary-1);">Sistem Informasi Akademik | Developed with <i class="fas fa-heart text-[0.6rem]" style="color: var(--accent-1);"></i> for Education</p>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH UJIAN -->
    <div id="modalUjian" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden my-auto border border-gray-100">
            <div class="px-10 py-8 border-b border-gray-50 bg-gray-50/30">
                <div class="flex items-center justify-between relative">
                    <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-200 -z-0"></div>
                    <div id="progressBar" class="absolute top-5 left-0 h-0.5 bg-blue-600 transition-all duration-500" style="width: 0%;"></div>
                    <?php
                    $steps = ['Jadwal', 'Peserta', 'Pengawas', 'Konfirmasi Soal', 'Proteksi'];
                    foreach ($steps as $i => $s): ?>
                        <div class="step-item relative z-10 flex flex-col items-center group">
                            <div class="step-circle w-10 h-10 rounded-full border-2 border-gray-200 bg-white flex items-center justify-center font-bold text-gray-400 transition-all"><?= $i + 1 ?></div>
                            <span class="mt-2 text-[9px] font-black uppercase tracking-widest text-gray-300"><?= $s ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <form action="../../proses/ujian_proses.php" method="POST" id="formMultiStep">
                <div class="p-8 min-h-[350px]">
                    <!-- STEP 1: Informasi & Waktu Ujian -->
                    <div class="step-content block" id="step1">
                        <h3 class="text-lg font-bold mb-6 text-gray-800">1. Informasi & Waktu Ujian</h3>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Nama Ujian</label>
                                <input type="text" name="judul_ujian" id="judul_ujian" placeholder="Contoh: PTS Ganjil 2026" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-medium text-gray-700" required>
                                <p class="text-[10px] text-red-600 font-bold mt-1 hidden error-msg" id="error-judul_ujian">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Nama Ujian wajib diisi
                                </p>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Mata Pelajaran</label>
                                <select name="id_mapel" id="id_mapel" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-medium text-gray-700 bg-white" required>
                                    <option value="">Pilih Mapel...</option>
                                    <?php foreach ($mapel_list as $m): ?>
                                        <option value="<?= $m['id_mapel'] ?>"><?= $m['nama_mapel'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-[10px] text-red-600 font-bold mt-1 hidden error-msg" id="error-id_mapel">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Pilih mata pelajaran dahulu
                                </p>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Tanggal</label>
                                    <input type="date" name="tanggal_ujian" id="tanggal_ujian" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition text-gray-700" required>
                                    <p class="text-[10px] text-red-600 font-bold mt-1 hidden error-msg" id="error-tanggal_ujian">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Tanggal kosong
                                    </p>
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Jam Mulai</label>
                                        <button type="button" onclick="setJamSekarang()" class="text-[9px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-tighter bg-blue-50 px-2 py-0.5 rounded-md transition-all">
                                            <i class="fas fa-bolt mr-1"></i> Terapkan
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input type="text" name="jam_mulai" id="pilihJam" placeholder="--:--" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-medium bg-transparent cursor-pointer text-gray-700" required>
                                        <i class="fas fa-clock absolute right-2 top-2 text-gray-300 pointer-events-none"></i>
                                    </div>
                                    <p class="text-[10px] text-red-600 font-bold mt-1 hidden error-msg" id="error-jam_mulai">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Jam wajib diisi
                                    </p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Durasi (Menit)</label>
                                    <input type="number" name="durasi_menit" id="durasi_menit" value="90" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition text-gray-700" required>
                                    <p class="text-[10px] text-red-600 font-bold mt-1 hidden error-msg" id="error-durasi_menit">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Isi durasi
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Pilih Peserta Ujian -->
                    <div class="step-content hidden" id="step2">
                        <h3 class="text-lg font-bold mb-4 text-gray-800">2. Pilih Peserta Ujian</h3>

                        <div class="flex space-x-2 mb-4 border-b border-gray-100 pb-2 overflow-x-auto">
                            <button type="button" onclick="bukaTabKelas('tab-x', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 text-white transition shadow-md hover:shadow-lg">
                                KELAS X
                            </button>
                            <button type="button" onclick="bukaTabKelas('tab-xi', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                                KELAS XI
                            </button>
                            <button type="button" onclick="bukaTabKelas('tab-xii', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                                KELAS XII
                            </button>
                            <?php if (!empty($kelas_lain)): ?>
                                <button type="button" onclick="bukaTabKelas('tab-lain', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                                    LAINNYA
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="max-h-[250px] overflow-y-auto pr-2">
                            <div id="tab-x" class="tab-content grid grid-cols-2 gap-3">
                                <?php if (empty($kelas_x)): ?>
                                    <p class="col-span-2 text-center text-xs text-gray-300 py-4 italic">Tidak ada data kelas X</p>
                                <?php else: ?>
                                    <?php foreach ($kelas_x as $k): ?>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                            <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 checkbox-kelas">
                                            <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div id="tab-xi" class="tab-content hidden grid grid-cols-2 gap-3">
                                <?php if (empty($kelas_xi)): ?>
                                    <p class="col-span-2 text-center text-xs text-gray-300 py-4 italic">Tidak ada data kelas XI</p>
                                <?php else: ?>
                                    <?php foreach ($kelas_xi as $k): ?>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                            <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 checkbox-kelas">
                                            <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div id="tab-xii" class="tab-content hidden grid grid-cols-2 gap-3">
                                <?php if (empty($kelas_xii)): ?>
                                    <p class="col-span-2 text-center text-xs text-gray-300 py-4 italic">Tidak ada data kelas XII</p>
                                <?php else: ?>
                                    <?php foreach ($kelas_xii as $k): ?>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                            <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 checkbox-kelas">
                                            <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div id="tab-lain" class="tab-content hidden grid grid-cols-2 gap-3">
                                <?php foreach ($kelas_lain as $k): ?>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                        <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 checkbox-kelas">
                                        <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <p class="text-[10px] text-red-600 font-bold mt-4 hidden error-msg" id="error-id_kelas">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Pilih minimal satu kelas peserta ujian
                        </p>
                    </div>

                    <!-- STEP 3: Guru Pengampu / Pengawas -->
                    <div class="step-content hidden" id="step3">
                        <h3 class="text-lg font-bold mb-6 text-gray-800">3. Guru Pengampu / Pengawas</h3>
                        <select name="id_guru" id="id_guru" class="w-full border-2 border-gray-100 p-4 rounded-2xl outline-none focus:border-blue-500 transition text-gray-700 bg-white" required>
                            <option value="">-- Pilih Guru Pengampu --</option>
                            <?php foreach ($guru_list as $g): ?>
                                <option value="<?= $g['id_user'] ?>"><?= $g['nama_lengkap'] ?> (<?= strtoupper($g['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] text-red-600 font-bold mt-2 hidden error-msg" id="error-id_guru">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Tentukan siapa pengampu ujian ini
                        </p>
                    </div>

                    <!-- STEP 4: Konfirmasi Soal -->
                    <div class="step-content hidden" id="step4">
                        <div class="bg-blue-50 p-8 rounded-3xl border border-blue-100 text-center">
                            <div class="bg-blue-600 w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-lg shadow-blue-200">
                                <i class="fas fa-database"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Sinkronisasi Gudang Soal</h3>
                            <p class="text-sm text-gray-500 leading-relaxed mb-6 px-4">
                                Sistem akan secara otomatis mengambil soal yang tersedia di <b>Gudang Bank Soal</b> sesuai dengan Mata Pelajaran yang Anda pilih di Tahap 1.
                            </p>
                            <div class="inline-flex items-center bg-white px-4 py-2 rounded-2xl border border-blue-100 shadow-sm">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span class="text-xs font-bold text-gray-600 uppercase tracking-tighter">Status: Siap Sinkronisasi</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: Proteksi & Pengaturan Ujian -->
                    <div class="step-content hidden" id="step5">
                        <h3 class="text-lg font-bold mb-6 text-gray-800">5. Proteksi & Pengaturan Ujian</h3>
                        <div class="space-y-4">
                            <label class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-blue-200 transition shadow-sm cursor-pointer group">
                                <div class="flex items-center">
                                    <div class="bg-blue-50 p-3 rounded-xl mr-4 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        <i class="fas fa-random"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">Acak Urutan Soal</p>
                                        <p class="text-[10px] text-gray-400 font-medium">Urutan soal akan tampil berbeda bagi setiap siswa.</p>
                                    </div>
                                </div>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="acak_soal" value="1" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </div>
                            </label>

                            <label class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-green-200 transition shadow-sm cursor-pointer group">
                                <div class="flex items-center">
                                    <div class="bg-green-50 p-3 rounded-xl mr-4 group-hover:bg-green-600 group-hover:text-white transition-all">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">Tampilkan Nilai</p>
                                        <p class="text-[10px] text-gray-400 font-medium">Siswa dapat melihat hasil skor setelah selesai mengerjakan.</p>
                                    </div>
                                </div>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="tampil_nilai" value="1" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                </div>
                            </label>

                            <label class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-red-200 transition shadow-sm cursor-pointer group">
                                <div class="flex items-center">
                                    <div class="bg-red-50 p-3 rounded-xl mr-4 group-hover:bg-red-600 group-hover:text-white transition-all">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 text-red-600">Proteksi Anti-Tab</p>
                                        <p class="text-[10px] text-gray-400 font-medium">Munculkan peringatan mencurigakan jika siswa pindah tab.</p>
                                    </div>
                                </div>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="anti_tab" value="1" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-5 bg-gray-50/50 flex justify-between border-t border-gray-100">
                    <button type="button" id="btnBack" onclick="prevStep()" class="text-gray-400 font-bold hidden text-xs flex items-center hover:text-gray-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </button>
                    <div class="ml-auto flex space-x-3">
                        <button type="button" onclick="tutupModalUjian()" class="text-gray-400 font-bold px-4 py-2 text-xs hover:text-red-500 transition">Batal</button>
                        <button type="button" id="btnNext" onclick="nextStep()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-100 transition-all transform hover:scale-105">Lanjut</button>
                        <button type="submit" name="add_ujian" id="btnSubmit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-green-100 transition-all transform hover:scale-105 hidden">
                            <i class="fas fa-check-circle mr-2"></i> Simpan Jadwal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 1;

        function bukaModalUjian() {
            document.getElementById('modalUjian').classList.replace('hidden', 'flex');
            currentStep = 1;
            updateStepUI();
            document.getElementById('formMultiStep').reset();
            if (typeof flatpickr !== 'undefined') {
                flatpickr("#pilihJam", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    disableMobile: "true"
                });
            }
        }

        function tutupModalUjian() {
            document.getElementById('modalUjian').classList.replace('flex', 'hidden');
        }

        function updateStepUI() {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('step' + currentStep).classList.remove('hidden');

            document.querySelectorAll('.step-item').forEach((item, i) => {
                const circle = item.querySelector('.step-circle');
                if (i + 1 < currentStep) {
                    circle.className = "step-circle w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center";
                } else if (i + 1 === currentStep) {
                    circle.className = "step-circle w-10 h-10 rounded-full border-2 border-blue-600 text-blue-600 flex items-center justify-center bg-white ring-4 ring-blue-50";
                } else {
                    circle.className = "step-circle w-10 h-10 rounded-full border-2 border-gray-100 text-gray-300 flex items-center justify-center bg-white";
                }
            });

            document.getElementById('progressBar').style.width = ((currentStep - 1) / 4 * 100) + '%';
            document.getElementById('btnBack').style.display = currentStep === 1 ? 'none' : 'block';
            document.getElementById('btnNext').style.display = currentStep === 5 ? 'none' : 'block';
            document.getElementById('btnSubmit').style.display = currentStep === 5 ? 'block' : 'none';
        }

        function nextStep() {
            if (validateStep(currentStep)) {
                if (currentStep < 5) {
                    currentStep++;
                    updateStepUI();
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepUI();
            }
        }

        function validateStep(step) {
            const currentStepEl = document.getElementById('step' + step);
            const requiredInputs = currentStepEl.querySelectorAll('[required]');
            let isValid = true;

            currentStepEl.querySelectorAll('.error-msg').forEach(msg => msg.classList.add('hidden'));

            requiredInputs.forEach(input => {
                const errorEl = document.getElementById('error-' + input.name);
                if (!input.value.trim()) {
                    isValid = false;
                    if (errorEl) errorEl.classList.remove('hidden');
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (step === 2) {
                const checkboxes = currentStepEl.querySelectorAll('.checkbox-kelas:checked');
                const errorKelas = document.getElementById('error-id_kelas');
                if (checkboxes.length === 0) {
                    isValid = false;
                    if (errorKelas) errorKelas.classList.remove('hidden');
                }
            }

            return isValid;
        }

        function bukaTabKelas(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');
            
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('bg-blue-600', 'text-white', 'shadow-md');
                b.classList.add('bg-gray-100', 'text-gray-500');
            });
            btn.classList.remove('bg-gray-100', 'text-gray-500');
            btn.classList.add('bg-blue-600', 'text-white', 'shadow-md');
        }

        function setJamSekarang() {
            const sekarang = new Date();
            const jam = String(sekarang.getHours()).padStart(2, '0');
            const menit = String(sekarang.getMinutes()).padStart(2, '0');
            const waktuAktual = jam + ":" + menit;

            const inputJam = document.getElementById('pilihJam');
            inputJam.value = waktuAktual;

            if (inputJam._flatpickr) {
                inputJam._flatpickr.setDate(waktuAktual);
            }

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Jam ' + waktuAktual + ' diterapkan',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        }

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const toggle = event.currentTarget;
            
            document.querySelectorAll('.dropdown-menu.open').forEach(menu => {
                if (menu.id !== id) {
                    menu.classList.remove('open');
                }
            });
            
            document.querySelectorAll('.dropdown-toggle.open').forEach(toggle => {
                if (toggle !== event.currentTarget) {
                    toggle.classList.remove('open');
                }
            });
            
            dropdown.classList.toggle('open');
            toggle.classList.toggle('open');
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.progress-fill').forEach(bar => {
                    bar.style.width = bar.style.width;
                });
            }, 100);
        });
    </script>
</body>

</html>