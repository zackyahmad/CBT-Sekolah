<?php
// File: verifikasi_siswa.php
session_start();

require_once '../../config/koneksi.php';

$koneksi = new mysqli('localhost', 'root', '', 'db_ujian_sekolah');
if ($koneksi->connect_error) die("Koneksi gagal: " . $koneksi->connect_error);
$koneksi->set_charset("utf8");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengawas') {
    header("Location: ../../index.php");
    exit;
}

$nama_pengawas = $_SESSION['nama'] ?? 'Pengawas Ujian';
$test_param = (isset($_GET['test']) && $_GET['test'] == '1') ? '?test=1' : '';
$pesan = '';
$error = '';

// ==============================================
// BUAT TABEL ABSENSI_SISWA JIKA BELUM ADA
// ==============================================
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS absensi_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_siswa VARCHAR(100) NOT NULL,
    kelas VARCHAR(50),
    tanggal DATE NOT NULL,
    jam TIME,
    keterangan VARCHAR(50),
    status_verifikasi VARCHAR(20) DEFAULT 'menunggu',
    surat_sakit VARCHAR(255) NULL,
    verifikasi_oleh VARCHAR(100),
    verifikasi_tanggal DATETIME,
    catatan_verifikasi TEXT,
    created_at DATETIME
)");

// ==============================================
// PROSES VERIFIKASI
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verifikasi'])) {
    $id_absensi        = intval($_POST['id_absensi']);
    $status_verifikasi = $_POST['status_verifikasi'];
    $catatan           = $koneksi->real_escape_string($_POST['catatan'] ?? '');

    // Update status di absensi_siswa
    $update = "UPDATE absensi_siswa SET 
               status_verifikasi = '$status_verifikasi',
               verifikasi_oleh = '$nama_pengawas',
               verifikasi_tanggal = NOW(),
               catatan_verifikasi = '$catatan'
               WHERE id = '$id_absensi'";
    
    if (mysqli_query($koneksi, $update)) {

        // ── Jika DISETUJUI → salin ke tb_absensi untuk rekap admin ──
        if ($status_verifikasi === 'disetujui') {

            // Ambil data absensi yang baru diverifikasi
            $row = mysqli_fetch_assoc(
                mysqli_query($koneksi, "SELECT * FROM absensi_siswa WHERE id = $id_absensi")
            );

            if ($row && $row['id_user']) {
                $id_user   = (int)$row['id_user'];
                $tanggal   = $koneksi->real_escape_string($row['tanggal']);
                $jam       = $koneksi->real_escape_string($row['jam']);

                // Mapping keterangan → status tb_absensi
                $ket = strtolower($row['keterangan']);
                $status_absensi = match($ket) {
                    'hadir' => 'hadir',
                    'sakit' => 'sakit',
                    'izin'  => 'izin',
                    default => 'alpha'
                };

                // Cek apakah sudah ada record di tb_absensi hari itu
                $cek = mysqli_query($koneksi, 
                    "SELECT id_absensi FROM tb_absensi 
                     WHERE id_user = $id_user AND tanggal = '$tanggal'"
                );

                if (mysqli_num_rows($cek) > 0) {
                    // Update jika sudah ada
                    mysqli_query($koneksi,
                        "UPDATE tb_absensi SET status = '$status_absensi'
                         WHERE id_user = $id_user AND tanggal = '$tanggal'"
                    );
                } else {
                    // Insert baru
                    mysqli_query($koneksi,
                        "INSERT INTO tb_absensi (id_user, tanggal, jam_masuk, status, created_at)
                         VALUES ($id_user, '$tanggal', '$jam', '$status_absensi', NOW())"
                    );
                }
            }
        }

        $pesan = "✅ Verifikasi absensi berhasil!";
        echo "<script>setTimeout(function(){ window.location.href='verifikasi_siswa.php$test_param'; }, 1000);</script>";
        exit;
    } else {
        $error = "❌ Gagal verifikasi: " . mysqli_error($koneksi);
    }
}

// ==============================================
// PROSES HAPUS ABSENSI
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_absensi'])) {
    $id_hapus = intval($_POST['id_hapus']);
    
    // Opsional: Hapus file surat sakit fisiknya juga jika ada
    $cek_file = mysqli_query($koneksi, "SELECT surat_sakit FROM absensi_siswa WHERE id = '$id_hapus'");
    if($rf = mysqli_fetch_assoc($cek_file)) {
        if(!empty($rf['surat_sakit']) && file_exists('../../uploads/surat_sakit/' . $rf['surat_sakit'])){
            unlink('../../uploads/surat_sakit/' . $rf['surat_sakit']);
        }
    }

    $query_hapus = "DELETE FROM absensi_siswa WHERE id = '$id_hapus'";
    if (mysqli_query($koneksi, $query_hapus)) {
        $pesan = "✅ Data absensi berhasil dihapus!";
        echo "<script>setTimeout(function(){ window.location.href='verifikasi_siswa.php$test_param'; }, 1000);</script>";
        exit;
    } else {
        $error = "❌ Gagal menghapus data!";
    }
}

// ==============================================
// AMBIL DATA ABSENSI MENUNGGU VERIFIKASI
// ==============================================
$query_menunggu = "SELECT * FROM absensi_siswa 
                   WHERE status_verifikasi = 'menunggu' 
                   ORDER BY tanggal DESC, jam DESC";
$result_menunggu = mysqli_query($koneksi, $query_menunggu);
$data_menunggu = [];
if ($result_menunggu) {
    while ($row = mysqli_fetch_assoc($result_menunggu)) {
        $data_menunggu[] = $row;
    }
}

// ==============================================
// AMBIL DATA RIWAYAT VERIFIKASI
// ==============================================
$query_riwayat = "SELECT * FROM absensi_siswa 
                  WHERE status_verifikasi IN ('disetujui', 'ditolak')
                  ORDER BY verifikasi_tanggal DESC";
$result_riwayat = mysqli_query($koneksi, $query_riwayat);
$data_riwayat = [];
if ($result_riwayat) {
    while ($row = mysqli_fetch_assoc($result_riwayat)) {
        $data_riwayat[] = $row;
    }
}

// ==============================================
// STATISTIK
// ==============================================
$total_menunggu = count($data_menunggu);
$total_disetujui = 0;
$total_ditolak = 0;
foreach ($data_riwayat as $row) {
    if ($row['status_verifikasi'] == 'disetujui') $total_disetujui++;
    else $total_ditolak++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Siswa - SMK N 8 Bandar Lampung</title>
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

        /* SIDEBAR STYLES - GRADASI */
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
            background: linear-gradient(180deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 100%);
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--accent-1);
            background: linear-gradient(135deg, white, var(--neutral-1));
            padding: 3px;
            margin: 0 auto 10px;
            display: block;
        }

        .sidebar-header-text h2 {
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            background: linear-gradient(90deg, rgba(255, 196, 130, 0.15), rgba(255, 196, 130, 0.05));
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
            background: linear-gradient(90deg, rgba(221, 183, 171, 0.15), rgba(221, 183, 171, 0.05));
            color: var(--accent-2);
            border-left-color: var(--accent-2);
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        /* HEADER - GRADASI */
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
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        .datetime {
            background: linear-gradient(135deg, var(--neutral-1), white);
            padding: 8px 18px;
            border-radius: 40px;
            border: 1px solid var(--neutral-2);
            border-bottom: 2px solid var(--accent-1);
            font-size: 13px;
            font-weight: 500;
            color: var(--primary-2);
        }

        .datetime i {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-right: 5px;
        }

        /* CONTENT AREA */
        .content-area {
            padding: 30px;
        }

        /* ALERT */
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid var(--success);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        /* CARD */
        .card {
            background: linear-gradient(135deg, white, var(--neutral-1));
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--neutral-2);
            border-bottom: 3px solid var(--accent-1);
            box-shadow: 0 2px 8px rgba(43, 58, 103, 0.05);
            transition: box-shadow 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 6px 16px rgba(43, 58, 103, 0.1);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--neutral-2);
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-title i {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* BUTTONS */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 196, 130, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--neutral-2), var(--neutral-1));
            color: var(--primary-2);
            padding: 8px 20px;
            border-radius: 30px;
            border: 1px solid var(--neutral-2);
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--neutral-2), #ddd5c5);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #c97a6a);
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(229, 140, 124, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #5a9b82);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(119, 191, 163, 0.4);
        }

        /* TOMBOL LIHAT SURAT */
        .btn-lihat {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #bfdbfe;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-lihat:hover {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(29, 78, 216, 0.2);
        }

        /* STATS CARDS - GRADASI */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, white, var(--neutral-1));
            border-radius: 20px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid var(--neutral-2);
            border-bottom: 3px solid var(--accent-1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(43, 58, 103, 0.12);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 12px;
            color: var(--secondary-1);
            font-weight: 500;
        }

        .bg-yellow { background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--warning); }
        .bg-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: var(--success); }
        .bg-red { background: linear-gradient(135deg, #fee2e2, #fecaca); color: var(--danger); }

        /* TABLE */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, var(--neutral-2), var(--neutral-1));
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: var(--primary-1);
            border-bottom: 2px solid var(--accent-1);
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid var(--neutral-2);
            font-size: 13px;
            color: var(--dark);
            vertical-align: middle;
        }
        
        tr:hover td {
            background: linear-gradient(135deg, rgba(255, 196, 130, 0.08), rgba(255, 196, 130, 0.03));
        }

        /* BADGE */
        .badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-menunggu {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .badge-disetujui {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .badge-ditolak {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        /* AVATAR */
        .avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: linear-gradient(135deg, white, var(--neutral-1));
            border-radius: 24px;
            padding: 25px;
            max-width: 450px;
            width: 90%;
            border-top: 5px solid var(--accent-1);
            box-shadow: 0 20px 40px rgba(43, 58, 103, 0.2);
        }
        
        .modal-content h3 {
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* UTILITIES */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .justify-between { justify-content: space-between; }
        .text-center { text-align: center; }
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .w-full { width: 100%; }
        .text-sm { font-size: 12px; }
        .text-lg { font-size: 18px; }
        .text-2xl { font-size: 24px; }
        .text-green-600 { color: var(--success); }
        .text-red-600 { color: var(--danger); }
        .text-gray-400 { color: var(--secondary-2); }
        .py-8 { padding-top: 32px; padding-bottom: 32px; }
        .px-2 { padding-left: 8px; padding-right: 8px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .rounded-lg { border-radius: 12px; }
        .rounded-full { border-radius: 9999px; }
        .bg-gray-50 { background: var(--neutral-1); }
        .p-3 { padding: 12px; }
        .cursor-pointer { cursor: pointer; }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--neutral-2);
            border-radius: 12px;
            background: white;
            font-family: inherit;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--accent-1);
            box-shadow: 0 0 0 2px rgba(255, 196, 130, 0.2);
        }
        
        input[type="radio"] {
            accent-color: var(--accent-1);
            margin-right: 5px;
        }
        
        label {
            cursor: pointer;
        }
        
        .block {
            display: block;
        }
        
        .font-medium {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../assets/img/logo_sekolah.png" class="sidebar-logo" onerror="this.style.display='none'">
            <div class="sidebar-header-text">
                <h2>PENGAWAS SKAPAN</h2>
                <p>SMK N 8 BANDAR LAMPUNG</p>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php<?= $test_param ?>" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <div class="menu-subheader">VERIFIKASI</div>
            <a href="monitoring_ujian.php<?= $test_param ?>" class="menu-item"><i class="fas fa-desktop"></i> Monitoring Ujian</a>
            <a href="berita_acara.php<?= $test_param ?>" class="menu-item"><i class="fas fa-file-alt"></i> Berita Acara</a>
            <a href="absen_pengawas.php<?= $test_param ?>" class="menu-item"><i class="fas fa-fingerprint"></i> Absen Pengawas</a>
            <a href="verifikasi_siswa.php<?= $test_param ?>" class="menu-item active"><i class="fas fa-user-check"></i> Verifikasi Siswa</a>
            <div class="logout-section">
                <a href="../../logout.php" class="menu-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <img src="../../assets/img/logo_sekolah.png" class="logo-header" onerror="this.style.display='none'">
                <div class="header-title">
                    <h1>VERIFIKASI SISWA</h1>
                    <p>Verifikasi absensi siswa - SMK N 8 Bandar Lampung</p>
                </div>
            </div>
            <div class="header-right">
                <div id="datetime" class="datetime"></div>
            </div>
        </div>
        <div class="content-area">
            
            <?php if ($pesan): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $pesan ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-yellow"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="stat-value"><?= $total_menunggu ?></div>
                        <div class="stat-label">Menunggu Verifikasi</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?= $total_disetujui ?></div>
                        <div class="stat-label">Disetujui</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="fas fa-times-circle"></i></div>
                    <div>
                        <div class="stat-value"><?= $total_ditolak ?></div>
                        <div class="stat-label">Ditolak</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">
                    <i class="fas fa-clock"></i> Daftar Absensi Menunggu Verifikasi 
                    <span class="text-xs" style="background: linear-gradient(135deg, var(--warning), var(--accent-1)); color: white; padding: 2px 10px; border-radius: 20px; margin-left: 10px;"><?= $total_menunggu ?> data</span>
                </div>
                
                <?php if (empty($data_menunggu)): ?>
                    <div class="text-center py-8" style="color: var(--secondary-2);">
                        <i class="fas fa-check-circle fa-3x mb-2 block" style="color: var(--success);"></i>
                        <p>Tidak ada data yang menunggu verifikasi</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Keterangan</th>
                                    <th>Bukti Surat</th> <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($data_menunggu as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="avatar-small"><?= strtoupper(substr($row['nama_siswa'], 0, 1)) ?></div>
                                            <?= htmlspecialchars($row['nama_siswa']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['kelas']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= $row['jam'] ?> WIB</td>
                                    <td class="text-center">
                                        <?php if ($row['keterangan'] == 'Hadir'): ?>
                                            <span class="badge badge-disetujui"><i class="fas fa-check-circle"></i> Hadir</span>
                                        <?php elseif ($row['keterangan'] == 'Sakit'): ?>
                                            <span class="badge badge-menunggu"><i class="fas fa-thermometer-half"></i> Sakit</span>
                                        <?php else: ?>
                                            <span class="badge badge-menunggu"><i class="fas fa-envelope"></i> Izin</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($row['keterangan'] == 'Sakit' && !empty($row['surat_sakit'])): ?>
                                            <a href="../../uploads/surat_sakit/<?= htmlspecialchars($row['surat_sakit']) ?>" target="_blank" class="btn-lihat">
                                                <i class="fas fa-file-image"></i> Lihat Surat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs italic">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <button onclick="openVerifModal(<?= $row['id'] ?>, '<?= addslashes($row['nama_siswa']) ?>', '<?= date('d/m/Y', strtotime($row['tanggal'])) ?>', '<?= $row['jam'] ?>', '<?= $row['keterangan'] ?>')" class="btn-primary">
                                            <i class="fas fa-check-double"></i> Verifikasi
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title"><i class="fas fa-history"></i> Riwayat Verifikasi</div>
                
                <?php if (empty($data_riwayat)): ?>
                    <div class="text-center py-8" style="color: var(--secondary-2);">
                        <i class="fas fa-inbox fa-3x mb-2 block"></i>
                        <p>Belum ada riwayat verifikasi</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Keterangan</th>
                                    <th>Bukti Surat</th> <th>Status</th>
                                    <th>Diverifikasi Oleh</th>
                                    <th>Waktu Verifikasi</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($data_riwayat as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                    <td><?= htmlspecialchars($row['kelas']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= $row['jam'] ?> WIB</td>
                                    <td><?= $row['keterangan'] ?></td>
                                    
                                    <td class="text-center">
                                        <?php if ($row['keterangan'] == 'Sakit' && !empty($row['surat_sakit'])): ?>
                                            <a href="../../uploads/surat_sakit/<?= htmlspecialchars($row['surat_sakit']) ?>" target="_blank" class="btn-lihat">
                                                <i class="fas fa-file-image"></i> Lihat Surat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs italic">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($row['status_verifikasi'] == 'disetujui'): ?>
                                            <span class="badge badge-disetujui"><i class="fas fa-check-circle"></i> Disetujui</span>
                                        <?php else: ?>
                                            <span class="badge badge-ditolak"><i class="fas fa-times-circle"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['verifikasi_oleh'] ?? '-') ?></td>
                                    <td><?= $row['verifikasi_tanggal'] ? date('d/m/Y H:i', strtotime($row['verifikasi_tanggal'])) : '-' ?></td>
                                    <td style="max-width: 200px; word-wrap: break-word;"><?= htmlspecialchars($row['catatan_verifikasi'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                            <button type="submit" name="hapus_absensi" class="btn-danger"><i class="fas fa-trash-alt"></i> Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="verifModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-check-double"></i> Verifikasi Absensi</h3>
                <button onclick="closeVerifModal()" class="text-2xl cursor-pointer" style="color: var(--danger);">&times;</button>
            </div>
            <div class="p-3 rounded-lg mb-4" style="background: linear-gradient(135deg, var(--neutral-2), var(--neutral-1));">
                <p><strong style="color: var(--primary-1);">Nama Siswa:</strong> <span id="modalNama" style="color: var(--dark);"></span></p>
                <p><strong style="color: var(--primary-1);">Tanggal:</strong> <span id="modalTanggal" style="color: var(--dark);"></span></p>
                <p><strong style="color: var(--primary-1);">Jam:</strong> <span id="modalJam" style="color: var(--dark);"></span></p>
                <p><strong style="color: var(--primary-1);">Keterangan:</strong> <span id="modalKeterangan" style="color: var(--dark);"></span></p>
            </div>
            <form method="POST">
                <input type="hidden" name="id_absensi" id="modalId">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--primary-1);">Status Verifikasi</label>
                    <div class="flex gap-4">
                        <label><input type="radio" name="status_verifikasi" value="disetujui" checked> <span class="text-green-600">✅ Disetujui (Sah)</span></label>
                        <label><input type="radio" name="status_verifikasi" value="ditolak"> <span class="text-red-600">❌ Ditolak (Tidak Sah)</span></label>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" style="color: var(--primary-1);">Catatan (Opsional)</label>
                    <textarea name="catatan" rows="3" placeholder="Masukkan catatan verifikasi..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" name="verifikasi" class="btn-primary w-full justify-center">Simpan Verifikasi</button>
                    <button type="button" onclick="closeVerifModal()" class="btn-secondary w-full justify-center">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openVerifModal(id, nama, tanggal, jam, keterangan) {
            document.getElementById('modalId').value = id;
            document.getElementById('modalNama').innerText = nama;
            document.getElementById('modalTanggal').innerText = tanggal;
            document.getElementById('modalJam').innerText = jam + ' WIB';
            document.getElementById('modalKeterangan').innerText = keterangan;
            document.getElementById('verifModal').style.display = 'flex';
        }
        
        function closeVerifModal() {
            document.getElementById('verifModal').style.display = 'none';
        }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('verifModal')) closeVerifModal();
        }
        
        function updateClock() {
            const now = new Date();
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            document.getElementById('datetime').innerHTML = `<i class="fas fa-calendar-alt"></i> ${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()} | <i class="fas fa-clock"></i> ${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}:${now.getSeconds().toString().padStart(2,'0')} WIB`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>