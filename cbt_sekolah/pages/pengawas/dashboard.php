<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
// File: pages/pengawas/dashboard.php
session_start();

require_once '../../config/koneksi.php';

// CEK AKSES
if (isset($_GET['test']) && $_GET['test'] == '1') {
    $_SESSION['id_user'] = 999;
    $_SESSION['nama'] = 'Pengawas Ujian';
    $_SESSION['role'] = 'pengawas';
} else {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengawas') {
        header("Location: ../../index.php");
        exit;
    }
}

$today = date('Y-m-d');
$nama_pengawas = $_SESSION['nama'] ?? 'Pengawas Ujian';

// AMBIL DATA DARI DATABASE MENGGUNAKAN PDO
try {
    // 1. Data Kehadiran dari tb_kehadiran
    $query_menunggu = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'alpha' OR status IS NULL";
    $stmt = $pdo->query($query_menunggu);
    $menunggu = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $query_terverifikasi = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'hadir'";
    $stmt = $pdo->query($query_terverifikasi);
    $terverifikasi = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $query_ditolak = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'izin' OR status = 'sakit'";
    $stmt = $pdo->query($query_ditolak);
    $ditolak = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $query_total_absensi = "SELECT COUNT(*) as total FROM tb_kehadiran";
    $stmt = $pdo->query($query_total_absensi);
    $total_absensi = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Status Kehadiran
    $query_hadir = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'hadir'";
    $stmt = $pdo->query($query_hadir);
    $hadir = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $query_sakit = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'sakit'";
    $stmt = $pdo->query($query_sakit);
    $sakit = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $query_izin = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'izin'";
    $stmt = $pdo->query($query_izin);
    $izin = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $query_alpha = "SELECT COUNT(*) as total FROM tb_kehadiran WHERE status = 'alpha'";
    $stmt = $pdo->query($query_alpha);
    $alpha = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Data Ujian Aktif
    $query_ujian_aktif = "SELECT COUNT(*) as total FROM tb_ujian WHERE status_ujian = 'aktif' AND tanggal_ujian = :today";
    $stmt = $pdo->prepare($query_ujian_aktif);
    $stmt->execute([':today' => $today]);
    $ujian_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Data Siswa Total
    $query_total_siswa = "SELECT COUNT(*) as total FROM tb_users WHERE role = 'siswa' AND status = 'aktif'";
    $stmt = $pdo->query($query_total_siswa);
    $total_siswa = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

} catch (PDOException $e) {
    // Jika error, set default values
    $menunggu = $terverifikasi = $ditolak = $total_absensi = 0;
    $hadir = $sakit = $izin = $alpha = 0;
    $ujian_hari_ini = $total_siswa = 0;
}

$test_param = (isset($_GET['test']) && $_GET['test'] == '1') ? '?test=1' : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengawas - SMK N 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, var(--neutral-1) 0%, var(--neutral-2) 100%);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-1) 0%, var(--primary-2) 40%, var(--secondary-1) 100%);
            color: var(--neutral-1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(43, 58, 103, 0.3);
            z-index: 50;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 196, 130, 0.3);
            text-align: center;
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--accent-1);
            background: white;
            padding: 3px;
            margin: 0 auto 10px;
            display: block;
        }

        .sidebar-header-text h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent-1);
            margin: 0;
        }

        .sidebar-header-text p {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
            margin-top: 5px;
        }

        .sidebar-menu {
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 160px);
        }

        .menu-item {
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
            border-left: 4px solid transparent;
            text-decoration: none;
            font-size: 14px;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255, 196, 130, 0.1);
            color: var(--accent-1);
            border-left-color: var(--accent-1);
        }

        .menu-item i {
            width: 24px;
            font-size: 18px;
            text-align: center;
        }

        .menu-subheader {
            padding: 15px 25px 5px 25px;
            color: rgba(255,255,255,0.5);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logout-section {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 196, 130, 0.2);
        }

        .logout-item {
            color: var(--accent-2);
        }

        .logout-item:hover {
            background: rgba(221, 183, 171, 0.1);
            color: var(--accent-2);
            border-left-color: var(--accent-2);
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        /* HEADER */
        .header {
            background: linear-gradient(135deg, #ffffff 0%, var(--neutral-1) 100%);
            padding: 15px 30px;
            border-bottom: 3px solid var(--accent-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 2px 15px rgba(43, 58, 103, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-header {
            height: 45px;
            width: auto;
            border-radius: 8px;
        }

        .header-title h1 {
            font-size: 22px;
            color: var(--primary-1);
            font-weight: 700;
            margin: 0;
        }

        .header-title p {
            color: var(--secondary-1);
            font-size: 12px;
            margin-top: 2px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        /* DATETIME */
        .datetime-display {
            display: flex;
            align-items: center;
            gap: 20px;
            background: linear-gradient(135deg, var(--neutral-1), white);
            padding: 8px 18px;
            border-radius: 40px;
            border: 1px solid var(--neutral-2);
            border-bottom: 2px solid var(--accent-1);
        }

        .date-item, .time-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-item i, .time-item i {
            color: var(--accent-1);
            font-size: 14px;
        }

        .date-text {
            font-weight: 500;
            color: var(--primary-2);
        }

        .time-text {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--warning);
        }

        /* USER INFO */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--accent-1);
        }

        .user-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-1);
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: var(--primary-1);
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: var(--accent-1);
        }

        /* CONTENT AREA */
        .content-area {
            padding: 30px;
        }

        /* WELCOME CARD */
        .welcome-super-card {
            background: linear-gradient(135deg, var(--primary-1) 0%, var(--primary-2) 50%, var(--secondary-1) 100%);
            border-radius: 24px;
            padding: 35px 40px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 20px 35px -10px rgba(43, 58, 103, 0.3);
            border-bottom: 4px solid var(--accent-1);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-super-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,196,130,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .welcome-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 35px;
            border-bottom: 1px solid rgba(255, 196, 130, 0.3);
            padding-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .welcome-logo-besar {
            width: 100px;
            height: 100px;
            border-radius: 20px;
            object-fit: cover;
            border: 4px solid white;
            background: white;
            padding: 5px;
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.2);
        }

        .welcome-text h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }

        .welcome-text p {
            color: rgba(255,255,255,0.9);
            font-size: 15px;
            max-width: 600px;
        }

        /* MENU GRID */
        .welcome-menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .welcome-menu-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px 15px;
            text-align: center;
            border: 1px solid rgba(255, 196, 130, 0.3);
            border-bottom: 3px solid var(--accent-1);
            transition: all 0.3s;
            text-decoration: none;
            color: white;
        }

        .welcome-menu-item:hover {
            background: rgba(255, 196, 130, 0.2);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0,0,0,0.2);
            border-bottom-color: var(--accent-2);
        }

        .welcome-menu-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 196, 130, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            border-bottom: 2px solid var(--accent-1);
        }

        .welcome-menu-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .welcome-menu-desc {
            font-size: 11px;
            color: rgba(255,255,255,0.8);
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 12px 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(43, 58, 103, 0.08);
            border: 1px solid var(--neutral-2);
            border-bottom: 3px solid var(--accent-1);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(43, 58, 103, 0.12);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card:nth-child(1) .stat-value { color: var(--warning); }
        .stat-card:nth-child(2) .stat-value { color: var(--success); }
        .stat-card:nth-child(3) .stat-value { color: var(--danger); }
        .stat-card:nth-child(4) .stat-value { color: var(--primary-1); }

        .stat-label {
            color: var(--secondary-1);
            font-size: 11px;
            margin-top: 4px;
            font-weight: 500;
        }

        /* CHART GRID */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(43, 58, 103, 0.06);
            border: 1px solid var(--neutral-2);
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            box-shadow: 0 6px 16px rgba(43, 58, 103, 0.1);
            transform: translateY(-2px);
        }

        .chart-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--neutral-2);
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-1);
        }

        .chart-title i {
            color: var(--accent-1);
            font-size: 16px;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        /* BAR CHART */
        .chart-bar-item {
            margin-bottom: 14px;
        }

        .chart-bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
            font-weight: 600;
            color: var(--primary-2);
        }

        .chart-bar-label span:first-child {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chart-bar-value {
            font-weight: 700;
            color: var(--primary-1);
            padding: 1px 6px;
            border-radius: 16px;
            font-size: 10px;
        }

        .chart-bar-bg {
            background: var(--neutral-2);
            border-radius: 8px;
            overflow: hidden;
            height: 28px;
        }

        .chart-bar-fill {
            height: 100%;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
            color: white;
            font-size: 10px;
            font-weight: 700;
            transition: width 0.6s ease;
            position: relative;
            overflow: hidden;
        }
        
        .chart-bar-fill::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%, rgba(255,255,255,0.2) 100%);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .chart-bar-percent {
            background: rgba(0,0,0,0.25);
            padding: 2px 5px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
        }

        /* Warna Gradien Bar */
        .bar-hadir { background: linear-gradient(90deg, var(--success), #5a9b82); }
        .bar-sakit { background: linear-gradient(90deg, var(--warning), var(--accent-1)); }
        .bar-izin { background: linear-gradient(90deg, var(--primary-2), var(--secondary-1)); }
        .bar-alpha { background: linear-gradient(90deg, var(--danger), #c97a6a); }
        .bar-menunggu { background: linear-gradient(90deg, var(--warning), var(--accent-1)); }
        .bar-terverifikasi { background: linear-gradient(90deg, var(--success), #5a9b82); }
        .bar-ditolak { background: linear-gradient(90deg, var(--danger), #c97a6a); }

        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid var(--accent-1);
            text-align: center;
            color: var(--primary-2);
            font-size: 11px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../assets/img/logo_sekolah.png" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
            <div class="sidebar-header-text">
                <h2>PENGAWAS</h2>
                <H4>SMK N 8 BANDAR LAMPUNG</H4>
                
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php<?= $test_param ?>" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>

            <div class="menu-subheader">MENU UTAMA</div>
            <a href="monitoring_ujian.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-desktop"></i>
                <span>Monitoring Ujian</span>
            </a>
            <a href="berita_acara.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-file-alt"></i>
                <span>Berita Acara</span>
            </a>
            <a href="absen_pengawas.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-fingerprint"></i>
                <span>Absen Pengawas</span>
            </a>
            <a href="verifikasi_siswa.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-user-check"></i>
                <span>Verifikasi Siswa</span>
            </a>

            <div class="logout-section">
                <a href="../../logout.php" class="menu-item logout-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <img src="../../assets/img/logo_sekolah.png" alt="Logo" class="logo-header" onerror="this.style.display='none'">
                <div class="header-title">
                    <h1>DASHBOARD PENGAWAS</h1>
                    <p>SISTEM MONITORING UJIAN - SMK N 8 BANDAR LAMPUNG</p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="datetime-display">
                    <div class="date-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="date-text" id="currentDate"></span>
                    </div>
                    <div class="time-item">
                        <i class="fas fa-clock"></i>
                        <span class="time-text" id="liveClock"></span>
                        <span class="text-xs text-gray-500">WIB</span>
                    </div>
                </div>
                
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($nama_pengawas) ?></div>
                        <div class="user-role">Pengawas Ujian</div>
                    </div>
                    <img src="../../assets/img/logo_sekolah.png" alt="Logo" class="user-logo" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <!-- CONTENT AREA -->
        <div class="content-area">
            <!-- WELCOME CARD -->
            <div class="welcome-super-card">
                <div class="welcome-header">
                    <img src="../../assets/img/logo_sekolah.png" alt="Logo SMK N 8" class="welcome-logo-besar" onerror="this.style.display='none'">
                    <div class="welcome-text">
                        <h2>Selamat Datang, <?= htmlspecialchars($nama_pengawas) ?>!</h2>
                        <p>Hari ini Anda bertugas sebagai pengawas ujian. Verifikasi absensi siswa yang sudah melakukan absensi.</p>
                    </div>
                </div>
                
                <!-- MENU AKSES CEPAT - 4 KOLOM -->
                <div class="welcome-menu-grid">
                    <a href="monitoring_ujian.php<?= $test_param ?>" class="welcome-menu-item">
                        <div class="welcome-menu-icon"><i class="fas fa-desktop"></i></div>
                        <div class="welcome-menu-title">Monitoring Ujian</div>
                        <div class="welcome-menu-desc">Pantau jalannya ujian</div>
                    </a>
                    
                    <a href="berita_acara.php<?= $test_param ?>" class="welcome-menu-item">
                        <div class="welcome-menu-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="welcome-menu-title">Berita Acara</div>
                        <div class="welcome-menu-desc">Kelola berita acara ujian</div>
                    </a>
                    
                    <a href="absen_pengawas.php<?= $test_param ?>" class="welcome-menu-item">
                        <div class="welcome-menu-icon"><i class="fas fa-fingerprint"></i></div>
                        <div class="welcome-menu-title">Absen Pengawas</div>
                        <div class="welcome-menu-desc">Isi absen dengan kamera</div>
                    </a>

                    <a href="verifikasi_siswa.php<?= $test_param ?>" class="welcome-menu-item">
                        <div class="welcome-menu-icon"><i class="fas fa-user-check"></i></div>
                        <div class="welcome-menu-title">Verifikasi Siswa</div>
                        <div class="welcome-menu-desc">Verifikasi kehadiran siswa</div>
                    </a>
                </div>
            </div>

            <!-- STATISTIK CARD -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $menunggu ?></div>
                    <div class="stat-label">Menunggu Verifikasi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $terverifikasi ?></div>
                    <div class="stat-label">Terverifikasi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $ditolak ?></div>
                    <div class="stat-label">Izin/Sakit</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $total_absensi ?></div>
                    <div class="stat-label">Total Absensi</div>
                </div>
            </div>

            <!-- GRAFIK BAR CHART -->
            <div class="chart-grid">
                <div class="chart-card">
                    <div class="chart-title"><i class="fas fa-chart-simple"></i> Statistik Kehadiran</div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-user-check" style="color: var(--success);"></i> Hadir</span><span class="chart-bar-value"><?= $hadir ?> (<?= $total_absensi > 0 ? round(($hadir/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-hadir" style="width: <?= $total_absensi > 0 ? round(($hadir/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($hadir/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-thermometer-half" style="color: var(--warning);"></i> Sakit</span><span class="chart-bar-value"><?= $sakit ?> (<?= $total_absensi > 0 ? round(($sakit/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-sakit" style="width: <?= $total_absensi > 0 ? round(($sakit/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($sakit/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-envelope" style="color: var(--secondary-1);"></i> Izin</span><span class="chart-bar-value"><?= $izin ?> (<?= $total_absensi > 0 ? round(($izin/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-izin" style="width: <?= $total_absensi > 0 ? round(($izin/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($izin/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-times-circle" style="color: var(--danger);"></i> Alpha</span><span class="chart-bar-value"><?= $alpha ?> (<?= $total_absensi > 0 ? round(($alpha/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-alpha" style="width: <?= $total_absensi > 0 ? round(($alpha/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($alpha/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-title"><i class="fas fa-check-double"></i> Statistik Verifikasi</div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-clock" style="color: var(--warning);"></i> Menunggu</span><span class="chart-bar-value"><?= $menunggu ?> (<?= $total_absensi > 0 ? round(($menunggu/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-menunggu" style="width: <?= $total_absensi > 0 ? round(($menunggu/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($menunggu/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-check-circle" style="color: var(--success);"></i> Terverifikasi</span><span class="chart-bar-value"><?= $terverifikasi ?> (<?= $total_absensi > 0 ? round(($terverifikasi/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-terverifikasi" style="width: <?= $total_absensi > 0 ? round(($terverifikasi/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($terverifikasi/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><span><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Izin/Sakit</span><span class="chart-bar-value"><?= $ditolak ?> (<?= $total_absensi > 0 ? round(($ditolak/$total_absensi)*100) : 0 ?>%)</span></div>
                        <div class="chart-bar-bg"><div class="chart-bar-fill bar-ditolak" style="width: <?= $total_absensi > 0 ? round(($ditolak/$total_absensi)*100) : 0 ?>%;"><span class="chart-bar-percent"><?= $total_absensi > 0 ? round(($ditolak/$total_absensi)*100) : 0 ?>%</span></div></div>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="footer">
                <p>© <?= date('Y') ?>  CIDIKNA. SMK Negeri 8 Bandar Lampung.</p>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = { day: '2-digit', month: 'long', year: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', options);
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.chart-bar-fill');
            bars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => { bar.style.width = width; }, 100);
            });
        });
        
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>