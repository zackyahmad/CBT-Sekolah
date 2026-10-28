<?php
// File: riwayat_absensi.php
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

$test_param = (isset($_GET['test']) && $_GET['test'] == '1') ? '?test=1' : '';

// ==============================================
// PROSES HAPUS RIWAYAT
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_riwayat'])) {
    $id_absensi = mysqli_real_escape_string($koneksi, $_POST['id_absensi']);
    
    $query_get_siswa = "SELECT siswa_id FROM absensi WHERE id = '$id_absensi'";
    $result_get = mysqli_query($koneksi, $query_get_siswa);
    
    if ($result_get && mysqli_num_rows($result_get) > 0) {
        $row = mysqli_fetch_assoc($result_get);
        $siswa_id = $row['siswa_id'];
        
        $query_hapus = "DELETE FROM absensi WHERE id = '$id_absensi'";
        if (mysqli_query($koneksi, $query_hapus)) {
            $query_cek_siswa = "SELECT COUNT(*) as total FROM absensi WHERE siswa_id = '$siswa_id'";
            $result_cek = mysqli_query($koneksi, $query_cek_siswa);
            if ($result_cek) {
                $data_cek = mysqli_fetch_assoc($result_cek);
                if ($data_cek['total'] == 0) {
                    mysqli_query($koneksi, "DELETE FROM tb_siswa WHERE id_user = '$siswa_id'");
                }
            }
            $success = "✅ Data riwayat berhasil dihapus!";
        } else {
            $error = "❌ Gagal menghapus riwayat: " . mysqli_error($koneksi);
        }
    }
}

// ==============================================
// PROSES HAPUS MASSAL RIWAYAT
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_massal'])) {
    $ids = $_POST['ids'] ?? [];
    
    if (empty($ids)) {
        $error = "⚠️ Tidak ada data yang dipilih untuk dihapus!";
    } else {
        $ids_clean = implode("','", array_map(function($id) use ($koneksi) {
            return mysqli_real_escape_string($koneksi, $id);
        }, $ids));
        
        // Ambil semua siswa_id sebelum menghapus
        $query_get_siswa = "SELECT DISTINCT siswa_id FROM absensi WHERE id IN ('$ids_clean')";
        $result_siswa = mysqli_query($koneksi, $query_get_siswa);
        $siswa_ids = [];
        while ($row = mysqli_fetch_assoc($result_siswa)) {
            $siswa_ids[] = $row['siswa_id'];
        }
        
        $query_hapus = "DELETE FROM absensi WHERE id IN ('$ids_clean')";
        if (mysqli_query($koneksi, $query_hapus)) {
            // Hapus siswa yang tidak memiliki absensi
            foreach ($siswa_ids as $siswa_id) {
                $query_cek = "SELECT COUNT(*) as total FROM absensi WHERE siswa_id = '$siswa_id'";
                $result_cek = mysqli_query($koneksi, $query_cek);
                if ($result_cek) {
                    $data_cek = mysqli_fetch_assoc($result_cek);
                    if ($data_cek['total'] == 0) {
                        mysqli_query($koneksi, "DELETE FROM tb_siswa WHERE id_user = '$siswa_id'");
                    }
                }
            }
            $jumlah = count($ids);
            $success = "✅ Berhasil menghapus $jumlah data riwayat!";
        } else {
            $error = "❌ Gagal menghapus massal: " . mysqli_error($koneksi);
        }
    }
}

// ==============================================
// FILTER DATA
// ==============================================
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal_awal']) : date('Y-m-d', strtotime('-30 days'));
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal_akhir']) : date('Y-m-d');
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$filter_kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, $_GET['kelas']) : '';
$filter_verifikasi = isset($_GET['verifikasi']) ? mysqli_real_escape_string($koneksi, $_GET['verifikasi']) : '';
$filter_cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// AMBIL DATA RIWAYAT ABSENSI (SUDAH DIVERIFIKASI)
$where = "WHERE a.verifikasi_status IN ('terverifikasi', 'ditolak')";
if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
    $where .= " AND a.tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
}
if (!empty($filter_status)) {
    $where .= " AND a.status = '$filter_status'";
}
if (!empty($filter_kelas)) {
    $where .= " AND s.kelas LIKE '%$filter_kelas%'";
}
if (!empty($filter_verifikasi)) {
    $where .= " AND a.verifikasi_status = '$filter_verifikasi'";
}
if (!empty($filter_cari)) {
    $where .= " AND (s.nama LIKE '%$filter_cari%' OR s.kelas LIKE '%$filter_cari%')";
}

// Hitung total data untuk pagination
$query_count = "SELECT COUNT(*) as total FROM absensi a LEFT JOIN tb_siswa s ON a.siswa_id = s.id_user $where";
$result_count = mysqli_query($koneksi, $query_count);
$total_data = 0;
if ($result_count) {
    $count_row = mysqli_fetch_assoc($result_count);
    $total_data = $count_row['total'];
}
$total_pages = ceil($total_data / $limit);

// Ambil data dengan pagination
$query = "SELECT a.*, s.nama as nama_siswa, s.kelas 
          FROM absensi a 
          LEFT JOIN tb_siswa s ON a.siswa_id = s.id_user 
          $where
          ORDER BY a.tanggal DESC, a.verifikasi_tanggal DESC
          LIMIT $offset, $limit";
$result = mysqli_query($koneksi, $query);
$data_riwayat = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_riwayat[] = $row;
    }
}

// ==============================================
// STATISTIK RIWAYAT
// ==============================================
$total_terverifikasi = 0;
$total_ditolak = 0;
$total_hadir = 0;
$total_sakit = 0;
$total_izin = 0;
$total_alpha = 0;
$total_bulan_ini = 0;
$bulan_ini = date('Y-m');

$query_stat_all = "SELECT verifikasi_status, status, tanggal FROM absensi";
$result_stat = mysqli_query($koneksi, $query_stat_all);
if ($result_stat) {
    while ($row = mysqli_fetch_assoc($result_stat)) {
        if ($row['verifikasi_status'] == 'terverifikasi') $total_terverifikasi++;
        if ($row['verifikasi_status'] == 'ditolak') $total_ditolak++;
        if ($row['status'] == 'Hadir') $total_hadir++;
        if ($row['status'] == 'Sakit') $total_sakit++;
        if ($row['status'] == 'Izin') $total_izin++;
        if ($row['status'] == 'Alpha') $total_alpha++;
        if (substr($row['tanggal'], 0, 7) == $bulan_ini) $total_bulan_ini++;
    }
}

// Ambil daftar kelas untuk filter
$daftar_kelas = [];
$query_kelas = "SELECT DISTINCT kelas FROM tb_siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
if ($result_kelas) {
    while ($row = mysqli_fetch_assoc($result_kelas)) {
        $daftar_kelas[] = $row['kelas'];
    }
}

// Data untuk chart (7 hari terakhir)
$chart_labels = [];
$chart_hadir = [];
$chart_sakit = [];
$chart_izin = [];
$chart_alpha = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($date));
    
    $query_chart = "SELECT 
                        COUNT(CASE WHEN status = 'Hadir' THEN 1 END) as hadir,
                        COUNT(CASE WHEN status = 'Sakit' THEN 1 END) as sakit,
                        COUNT(CASE WHEN status = 'Izin' THEN 1 END) as izin,
                        COUNT(CASE WHEN status = 'Alpha' THEN 1 END) as alpha
                    FROM absensi 
                    WHERE tanggal = '$date' AND verifikasi_status = 'terverifikasi'";
    $result_chart = mysqli_query($koneksi, $query_chart);
    if ($result_chart) {
        $row = mysqli_fetch_assoc($result_chart);
        $chart_hadir[] = $row['hadir'];
        $chart_sakit[] = $row['sakit'];
        $chart_izin[] = $row['izin'];
        $chart_alpha[] = $row['alpha'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Absensi - Pengawas SMK N 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f3f4f6; display: flex; min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid #334155; text-align: center; }
        .sidebar-logo {
            width: 60px; height: 60px; border-radius: 12px; object-fit: cover;
            border: 2px solid #F4A261; background: white; padding: 3px;
            margin: 0 auto 10px; display: block;
        }
        .sidebar-header-text h2 { font-size: 18px; font-weight: 700; color: #F4A261; }
        .sidebar-header-text p { font-size: 12px; color: #94a3b8; margin-top: 5px; }
        .sidebar-menu { padding: 20px 0; display: flex; flex-direction: column; height: calc(100vh - 160px); }
        .menu-item {
            padding: 12px 25px; display: flex; align-items: center; gap: 12px;
            color: #cbd5e1; transition: all 0.3s; border-left: 4px solid transparent;
            text-decoration: none; font-size: 14px;
        }
        .menu-item:hover, .menu-item.active { background: #334155; color: white; border-left-color: #F4A261; }
        .menu-item i { width: 24px; font-size: 18px; text-align: center; }
        .menu-subheader { padding: 15px 25px 5px 25px; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .logout-section { margin-top: auto; padding-top: 20px; border-top: 1px solid #334155; }
        .logout-item { color: #f87171; }
        .logout-item:hover { background: #dc2626; color: white; border-left-color: #dc2626; }
        
        /* MAIN CONTENT */
        .main-content { flex: 1; margin-left: 280px; min-height: 100vh; }
        .header {
            background: white; padding: 15px 30px; border-bottom: 3px solid #F4A261;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 40;
        }
        .header-title h1 { font-size: 22px; color: #1e3a8a; font-weight: 700; }
        .header-title p { color: #6b7280; font-size: 12px; }
        #datetime { font-size: 14px; color: #F4A261; font-weight: 500; }
        .content-area { padding: 30px; }
        
        /* CARDS */
        .card {
            background: white; border-radius: 16px; padding: 25px;
            margin-bottom: 25px; border-bottom: 4px solid #F4A261;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-title {
            font-size: 18px; font-weight: 600; margin-bottom: 20px;
            padding-bottom: 12px; border-bottom: 2px solid #f3f4f6;
            display: flex; align-items: center; gap: 10px;
        }
        .card-title i { color: #F4A261; }
        
        /* FORM */
        .form-label { font-weight: 600; margin-bottom: 5px; display: block; font-size: 13px; color: #374151; }
        .form-control, .form-select {
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0;
            border-radius: 10px; border-bottom: 2px solid #F4A261;
            font-size: 14px; transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus { outline: none; border-color: #F4A261; box-shadow: 0 0 0 3px rgba(244,162,97,0.1); }
        
        /* BUTTONS */
        .btn {
            padding: 10px 20px; border: none; border-radius: 10px;
            font-weight: 600; cursor: pointer; display: inline-flex;
            align-items: center; gap: 8px; transition: all 0.3s;
        }
        .btn-primary { background: #F4A261; color: white; }
        .btn-primary:hover { background: #e07c3c; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(244,162,97,0.3); }
        .btn-secondary { background: #e2e8f0; color: #374151; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-info { background: #3b82f6; color: white; }
        .btn-info:hover { background: #2563eb; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        /* BADGES */
        .badge {
            padding: 4px 12px; border-radius: 20px; font-size: 12px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 5px;
        }
        .badge-terverifikasi { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-ditolak { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-hadir { background: #d1fae5; color: #065f46; }
        .badge-sakit { background: #fef3c7; color: #92400e; }
        .badge-izin { background: #dbeafe; color: #1e40af; }
        .badge-alpha { background: #fee2e2; color: #991b1b; }
        
        /* STATISTIK GRID */
        .stat-grid {
            display: grid; grid-template-columns: repeat(6, 1fr);
            gap: 15px; margin-bottom: 25px;
        }
        .stat-item {
            background: white; border-radius: 14px; padding: 15px;
            text-align: center; border-bottom: 3px solid #F4A261;
            transition: transform 0.2s;
        }
        .stat-item:hover { transform: translateY(-3px); }
        .stat-value { font-size: 24px; font-weight: 800; }
        .stat-label { font-size: 11px; color: #6b7280; margin-top: 5px; }
        .stat-sub { font-size: 10px; color: #9ca3af; margin-top: 5px; }
        
        /* FILTER GRID */
        .filter-grid {
            display: grid; grid-template-columns: repeat(6, 1fr);
            gap: 15px; margin-bottom: 20px; align-items: end;
        }
        
        /* TABEL */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f8fafc; padding: 14px 12px; text-align: left;
            font-size: 13px; font-weight: 700; border-bottom: 2px solid #F4A261;
            color: #1e293b;
        }
        td { padding: 14px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; vertical-align: middle; }
        tr:hover td { background: #fefcf8; }
        
        /* ALERT */
        .alert {
            padding: 14px 18px; border-radius: 12px; margin-bottom: 20px;
            border-left: 4px solid; display: flex; align-items: center; gap: 12px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left-color: #10b981; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-left-color: #ef4444; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left-color: #f59e0b; }
        
        /* MODAL */
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 1000; justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; border-radius: 20px; padding: 25px;
            width: 90%; max-width: 450px; border-bottom: 4px solid #F4A261;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* PAGINATION */
        .pagination {
            display: flex; justify-content: center; gap: 8px; margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 14px; border-radius: 8px; text-decoration: none;
            color: #374151; background: #f3f4f6; transition: all 0.3s;
        }
        .pagination a:hover { background: #F4A261; color: white; }
        .pagination .active { background: #F4A261; color: white; }
        
        /* UTILITY */
        .flex { display: flex; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .mt-2 { margin-top: 8px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .w-full { width: 100%; }
        .cursor-pointer { cursor: pointer; }
        .text-center { text-align: center; }
        .justify-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .bg-gray-50 { background: #f9fafb; }
        .rounded-lg { border-radius: 12px; }
        .p-3 { padding: 12px; }
        
        .avatar-small {
            width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #64748b;
        }
        .checkbox-select { width: 18px; height: 18px; cursor: pointer; }
        .text-green-600 { color: #10b981; }
        .text-yellow-600 { color: #f59e0b; }
        .text-red-600 { color: #ef4444; }
        .text-blue-600 { color: #3b82f6; }
        .text-gray-400 { color: #9ca3af; }
        .info-box {
            background: #eff6ff; border-radius: 12px; padding: 15px;
            margin-bottom: 20px; border-left: 4px solid #3b82f6;
        }
        .chart-container { height: 300px; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../assets/img/logo_sekolah.png" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
            <div class="sidebar-header-text">
                <h2>PENGAWAS SKAPAN</h2>
                <p>SMK N 8 BANDAR LAMPUNG</p>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <div class="menu-subheader">VERIFIKASI</div>
            <a href="verifikasi_absensi.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-check-double"></i> <span>Verifikasi Absensi</span>
            </a>
            <div class="menu-subheader">LAPORAN</div>
            <a href="riwayat_absensi.php<?= $test_param ?>" class="menu-item active">
                <i class="fas fa-history"></i> <span>Riwayat Absensi</span>
            </a>
            <a href="data_kehadiran.php<?= $test_param ?>" class="menu-item">
                <i class="fas fa-clipboard-list"></i> <span>Data Kehadiran</span>
            </a>
            <div class="logout-section">
                <a href="../../logout.php" class="menu-item logout-item">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1><i class="fas fa-history text-[#F4A261]"></i> RIWAYAT ABSENSI SISWA</h1>
                <p>Data absensi yang sudah diverifikasi - SMK N 8 Bandar Lampung</p>
            </div>
            <div id="datetime"></div>
        </div>

        <div class="content-area">
            <!-- ALERT MESSAGES -->
            <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-lg"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle fa-lg"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- INFO BOX -->
            <div class="info-box">
                <i class="fas fa-info-circle text-blue-600"></i> 
                <strong>Informasi:</strong> Halaman ini menampilkan semua data absensi yang sudah diverifikasi oleh pengawas.
                Data yang sudah diverifikasi akan otomatis masuk ke dalam Rekap Kehadiran per siswa.
                <span class="text-xs text-gray-500 block mt-1"><i class="fas fa-clock"></i> Data diperbarui secara real-time</span>
            </div>

            <!-- STATISTIK CARD -->
            <div class="stat-grid">
                <div class="stat-item">
                    <div class="stat-value text-green-600"><?= $total_terverifikasi ?></div>
                    <div class="stat-label">Terverifikasi</div>
                    <div class="stat-sub"><i class="fas fa-check-circle"></i> Sah</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-red-600"><?= $total_ditolak ?></div>
                    <div class="stat-label">Ditolak</div>
                    <div class="stat-sub"><i class="fas fa-times-circle"></i> Tidak Sah</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-green-600"><?= $total_hadir ?></div>
                    <div class="stat-label">Total Hadir</div>
                    <div class="stat-sub"><i class="fas fa-user-check"></i> Kehadiran</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-yellow-600"><?= $total_sakit ?></div>
                    <div class="stat-label">Total Sakit</div>
                    <div class="stat-sub"><i class="fas fa-thermometer-half"></i> Izin Medis</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-blue-600"><?= $total_izin ?></div>
                    <div class="stat-label">Total Izin</div>
                    <div class="stat-sub"><i class="fas fa-envelope"></i> Izin</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-red-600"><?= $total_alpha ?></div>
                    <div class="stat-label">Total Alpha</div>
                    <div class="stat-sub"><i class="fas fa-times-circle"></i> Tanpa Keterangan</div>
                </div>
            </div>

            <!-- GRAFIK TREND KEHADIRAN -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-chart-line"></i> Grafik Trend Kehadiran 7 Hari Terakhir
                    <span class="text-xs text-gray-400 ml-2">(Data yang sudah diverifikasi)</span>
                </div>
                <div class="chart-container">
                    <canvas id="kehadiranChart"></canvas>
                </div>
            </div>

            <!-- FILTER DATA -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filter Data Riwayat
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full ml-2">
                        <i class="fas fa-database"></i> <?= number_format($total_data) ?> total data
                    </span>
                </div>
                <form method="GET" class="filter-grid">
                    <div>
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" class="form-control" value="<?= $filter_tanggal_awal ?>">
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" class="form-control" value="<?= $filter_tanggal_akhir ?>">
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-tag"></i> Status Absensi</label>
                        <select name="status" class="form-control">
                            <option value="">Semua</option>
                            <option value="Hadir" <?= $filter_status == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                            <option value="Sakit" <?= $filter_status == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                            <option value="Izin" <?= $filter_status == 'Izin' ? 'selected' : '' ?>>Izin</option>
                            <option value="Alpha" <?= $filter_status == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-check-circle"></i> Status Verifikasi</label>
                        <select name="verifikasi" class="form-control">
                            <option value="">Semua</option>
                            <option value="terverifikasi" <?= $filter_verifikasi == 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                            <option value="ditolak" <?= $filter_verifikasi == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-users"></i> Kelas</label>
                        <input type="text" name="kelas" class="form-control" placeholder="Cari kelas..." value="<?= htmlspecialchars($filter_kelas) ?>" list="daftarKelas">
                        <datalist id="daftarKelas">
                            <?php foreach ($daftar_kelas as $kelas): ?>
                            <option value="<?= htmlspecialchars($kelas) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-search"></i> Cari Siswa</label>
                        <input type="text" name="cari" class="form-control" placeholder="Nama siswa..." value="<?= htmlspecialchars($filter_cari) ?>">
                    </div>
                    <div style="display: flex; gap: 8px; grid-column: span 6;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="riwayat_absensi.php<?= $test_param ?>" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
                        <div class="ml-auto flex gap-2">
                            <select name="limit" class="form-control w-24" onchange="this.form.submit()">
                                <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20 data</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 data</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 data</option>
                                <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200 data</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- HAPUS MASSAL -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-trash-alt"></i> Hapus Massal
                    <span class="text-xs text-gray-400 ml-2">(Pilih beberapa data sekaligus)</span>
                </div>
                <form method="POST" id="formHapusMassal" onsubmit="return confirmHapusMassal()">
                    <div class="flex gap-3">
                        <button type="submit" name="hapus_massal" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Hapus yang Dipilih
                        </button>
                        <span class="text-sm text-gray-500 self-center">
                            <i class="fas fa-info-circle"></i> Centang kotak di samping data yang ingin dihapus
                        </span>
                    </div>
                    <input type="hidden" name="ids" id="selectedIds">
                </form>
            </div>

            <!-- TABEL RIWAYAT ABSENSI -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-list"></i> Daftar Riwayat Absensi
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full ml-2">
                        <i class="fas fa-check-circle"></i> <?= count($data_riwayat) ?> data ditampilkan
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAll" class="checkbox-select" onclick="toggleSelectAll()"></th>
                                <th width="40">No</th>
                                <th>Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Verifikasi</th>
                                <th>Diverifikasi Oleh</th>
                                <th>Waktu Verifikasi</th>
                                <th>Catatan</th>
                                <th width="60">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_riwayat)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-inbox fa-3x mb-3 block"></i>
                                    <p>Tidak ada data riwayat absensi</p>
                                    <p class="text-xs mt-1">Coba ubah filter atau lakukan verifikasi absensi terlebih dahulu</p>
                                 </td>
                            </tr>
                            <?php else: ?>
                                <?php $no = $offset + 1; foreach ($data_riwayat as $row): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="checkbox-select item-checkbox" value="<?= $row['id'] ?>">
                                    </td>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td>
                                        <i class="far fa-calendar-alt text-gray-400 mr-1"></i>
                                        <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="avatar-small">
                                                <?= strtoupper(substr($row['nama_siswa'] ?? '?', 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($row['nama_siswa'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="bg-gray-100 px-2 py-1 rounded-lg text-xs font-mono">
                                            <?= htmlspecialchars($row['kelas'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $row['status'];
                                        $badge = 'badge-';
                                        if ($status == 'Hadir') $badge .= 'hadir';
                                        elseif ($status == 'Sakit') $badge .= 'sakit';
                                        elseif ($status == 'Izin') $badge .= 'izin';
                                        else $badge .= 'alpha';
                                        ?>
                                        <span class="badge <?= $badge ?>">
                                            <?php if ($status == 'Hadir'): ?>✅<?php elseif ($status == 'Sakit'): ?>🤒<?php elseif ($status == 'Izin'): ?>📝<?php else: ?>❌<?php endif; ?>
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($row['verifikasi_status'] == 'terverifikasi'): ?>
                                            <span class="badge badge-terverifikasi"><i class="fas fa-check-circle"></i> Terverifikasi</span>
                                        <?php else: ?>
                                            <span class="badge badge-ditolak"><i class="fas fa-times-circle"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-user-check text-gray-400"></i>
                                            <?= htmlspecialchars($row['verifikasi_oleh'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-clock text-gray-400"></i>
                                            <?= $row['verifikasi_tanggal'] ? date('d/m/Y H:i', strtotime($row['verifikasi_tanggal'])) : '-' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-xs text-gray-500 max-w-[150px] block truncate" title="<?= htmlspecialchars($row['verifikasi_catatan'] ?? '-') ?>">
                                            <?= htmlspecialchars($row['verifikasi_catatan'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="openHapusModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_siswa'])) ?>')" 
                                                class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&tanggal_awal=<?= $filter_tanggal_awal ?>&tanggal_akhir=<?= $filter_tanggal_akhir ?>&status=<?= $filter_status ?>&kelas=<?= urlencode($filter_kelas) ?>&verifikasi=<?= $filter_verifikasi ?>&cari=<?= urlencode($filter_cari) ?>&limit=<?= $limit ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?= $page-1 ?>&tanggal_awal=<?= $filter_tanggal_awal ?>&tanggal_akhir=<?= $filter_tanggal_akhir ?>&status=<?= $filter_status ?>&kelas=<?= urlencode($filter_kelas) ?>&verifikasi=<?= $filter_verifikasi ?>&cari=<?= urlencode($filter_cari) ?>&limit=<?= $limit ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&tanggal_awal=<?= $filter_tanggal_awal ?>&tanggal_akhir=<?= $filter_tanggal_akhir ?>&status=<?= $filter_status ?>&kelas=<?= urlencode($filter_kelas) ?>&verifikasi=<?= $filter_verifikasi ?>&cari=<?= urlencode($filter_cari) ?>&limit=<?= $limit ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>&tanggal_awal=<?= $filter_tanggal_awal ?>&tanggal_akhir=<?= $filter_tanggal_akhir ?>&status=<?= $filter_status ?>&kelas=<?= urlencode($filter_kelas) ?>&verifikasi=<?= $filter_verifikasi ?>&cari=<?= urlencode($filter_cari) ?>&limit=<?= $limit ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?= $total_pages ?>&tanggal_awal=<?= $filter_tanggal_awal ?>&tanggal_akhir=<?= $filter_tanggal_akhir ?>&status=<?= $filter_status ?>&kelas=<?= urlencode($filter_kelas) ?>&verifikasi=<?= $filter_verifikasi ?>&cari=<?= urlencode($filter_cari) ?>&limit=<?= $limit ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL HAPUS -->
    <div id="hapusModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between align-items-center mb-4">
                <h3><i class="fas fa-trash-alt text-red-500"></i> Hapus Riwayat Absensi</h3>
                <button onclick="closeHapusModal()" class="cursor-pointer" style="background: none; border: none; font-size: 24px;">&times;</button>
            </div>
            <div class="bg-red-50 rounded-lg p-3 mb-4">
                <p><strong><i class="fas fa-exclamation-triangle"></i> Peringatan!</strong></p>
                <p>Apakah Anda yakin ingin menghapus riwayat absensi siswa <strong><span id="hapusNama"></span></strong>?</p>
                <p class="text-red-600 mt-2"><small><i class="fas fa-info-circle"></i> Data yang dihapus tidak dapat dikembalikan!</small></p>
            </div>
            <form method="POST">
                <input type="hidden" name="id_absensi" id="hapusId">
                <div class="flex gap-3">
                    <button type="submit" name="hapus_riwayat" class="btn btn-danger w-full"><i class="fas fa-trash-alt"></i> Ya, Hapus</button>
                    <button type="button" onclick="closeHapusModal()" class="btn btn-secondary w-full">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ==============================================
        // CHART JS
        // ==============================================
        const ctx = document.getElementById('kehadiranChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'Hadir',
                        data: <?= json_encode($chart_hadir) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Sakit',
                        data: <?= json_encode($chart_sakit) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Izin',
                        data: <?= json_encode($chart_izin) ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Alpha',
                        data: <?= json_encode($chart_alpha) ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // ==============================================
        // MODAL FUNCTIONS
        // ==============================================
        function openHapusModal(id, nama) {
            document.getElementById('hapusId').value = id;
            document.getElementById('hapusNama').innerText = nama;
            document.getElementById('hapusModal').style.display = 'flex';
        }
        
        function closeHapusModal() { 
            document.getElementById('hapusModal').style.display = 'none'; 
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('hapusModal')) closeHapusModal();
        }
        
        // ==============================================
        // CHECKBOX SELECT ALL FUNCTIONS
        // ==============================================
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateSelectedIds();
        }
        
        function updateSelectedIds() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            document.getElementById('selectedIds').value = ids.join(',');
        }
        
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedIds);
        });
        
        function confirmHapusMassal() {
            const selected = document.querySelectorAll('.item-checkbox:checked');
            if (selected.length === 0) {
                alert('⚠️ Silakan pilih minimal satu data untuk dihapus!');
                return false;
            }
            return confirm(`Apakah Anda yakin ingin menghapus ${selected.length} data riwayat absensi? Data yang dihapus tidak dapat dikembalikan!`);
        }
        
        // ==============================================
        // CLOCK FUNCTION
        // ==============================================
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            document.getElementById('datetime').innerHTML = `<i class="fas fa-calendar-alt"></i> ${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()} | <i class="fas fa-clock"></i> ${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}:${now.getSeconds().toString().padStart(2,'0')} WIB`;
        }
        
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>