<?php
// File: absen_pengawas.php
session_start();

require_once '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengawas') {
    header("Location: ../../index.php");
    exit;
}

$test_param = (isset($_GET['test']) && $_GET['test'] == '1') ? '?test=1' : '';
$nama_pengawas = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? '';
$id_pengawas = $_SESSION['id_user'] ?? 0;
$today = date('Y-m-d');
$pesan = '';
$error = '';

// Buat folder uploads jika belum ada
$upload_dir = '../../../uploads/absen_pengawas/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Buat tabel absensi_pengawas dengan status verifikasi
$create_table = "CREATE TABLE IF NOT EXISTS absensi_pengawas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    pengawas_id INT(11) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME NULL,
    jam_pulang TIME NULL,
    foto VARCHAR(255) NULL,
    keterangan VARCHAR(50) NULL,
    status_verifikasi ENUM('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
    catatan_admin TEXT NULL,
    diverifikasi_oleh INT(11) NULL,
    diverifikasi_pada DATETIME NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY pengawas_id (pengawas_id)
)";
$pdo->exec($create_table);

// ==============================================
// PROSES UPDATE JAM PULANG
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_jam_pulang'])) {
    $jam_pulang = date('H:i:s');
    $id_absen = intval($_POST['id_absen']);
    
    $stmt_pulang = $pdo->prepare("UPDATE absensi_pengawas SET jam_pulang = ? WHERE id = ? AND pengawas_id = ?");
    if ($stmt_pulang->execute([$jam_pulang, $id_absen, $id_pengawas])) {
        $pesan = "✅ Jam pulang berhasil diupdate!";
        echo "<script>setTimeout(function(){ window.location.href='absen_pengawas.php$test_param'; }, 1000);</script>";
        exit;
    } else {
        $error = "❌ Gagal mengupdate jam pulang!";
    }
}

// ==============================================
// CEK APAKAH SUDAH ABSEN HARI INI
// ==============================================
$stmt_cek = $pdo->prepare("SELECT * FROM absensi_pengawas WHERE pengawas_id = ? AND tanggal = ?");
$stmt_cek->execute([$id_pengawas, $today]);
$data_absen_hari_ini = $stmt_cek->fetch(PDO::FETCH_ASSOC);
$sudah_absen = $data_absen_hari_ini ? true : false;

// ==============================================
// PROSES UPDATE SELFIE ULANG
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_absen'])) {
    $foto_base64 = $_POST['foto_baru'] ?? '';
    $keterangan_baru = $_POST['keterangan_baru'] ?? 'Hadir';
    $id_absen = intval($_POST['id_absen']);
    
    if (empty($foto_base64)) {
        $error = "❌ Silakan ambil foto selfie terlebih dahulu!";
    } else {
        // Hapus foto lama
        $stmt_foto = $pdo->prepare("SELECT foto FROM absensi_pengawas WHERE id = ? AND pengawas_id = ?");
        $stmt_foto->execute([$id_absen, $id_pengawas]);
        $row = $stmt_foto->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['foto'] && file_exists($upload_dir . $row['foto'])) {
            unlink($upload_dir . $row['foto']);
        }
        
        // Simpan foto baru
        $foto_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64));
        $foto_nama = 'absen_' . $id_absen . '_' . time() . '.png';
        file_put_contents($upload_dir . $foto_nama, $foto_data);
        
        // Update database (status verifikasi kembali ke menunggu)
        $stmt_update = $pdo->prepare("UPDATE absensi_pengawas SET foto = ?, keterangan = ?, status_verifikasi = 'menunggu', catatan_admin = NULL, diverifikasi_oleh = NULL, diverifikasi_pada = NULL WHERE id = ? AND pengawas_id = ?");
        if ($stmt_update->execute([$foto_nama, $keterangan_baru, $id_absen, $id_pengawas])) {
            $pesan = "✅ Foto berhasil diperbarui! Menunggu verifikasi ulang dari admin.";
            echo "<script>setTimeout(function(){ window.location.href='absen_pengawas.php$test_param'; }, 1000);</script>";
            exit;
        } else {
            $error = "❌ Gagal memperbarui foto!";
        }
    }
}

// ==============================================
// PROSES SIMPAN ABSEN MASUK
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['absen_masuk'])) {
    $foto_base64 = $_POST['foto'] ?? '';
    $keterangan = $_POST['keterangan'] ?? 'Hadir';
    $jam_masuk = date('H:i:s');
    $nama = trim($_POST['nama'] ?? $nama_pengawas);
    
    if (empty($foto_base64)) {
        $error = "❌ Silakan ambil foto selfie terlebih dahulu!";
    } elseif (empty($nama)) {
        $error = "❌ Silakan isi nama pengawas terlebih dahulu!";
    } elseif (!$sudah_absen) {
        $stmt_insert = $pdo->prepare("INSERT INTO absensi_pengawas (pengawas_id, nama, tanggal, jam_masuk, keterangan, status_verifikasi, created_at) VALUES (?, ?, ?, ?, ?, 'menunggu', NOW())");
        if ($stmt_insert->execute([$id_pengawas, $nama, $today, $jam_masuk, $keterangan])) {
            $id_absen = $pdo->lastInsertId();
            
            $foto_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64));
            $foto_nama = 'absen_' . $id_absen . '_' . time() . '.png';
            file_put_contents($upload_dir . $foto_nama, $foto_data);
            
            // Update nama foto di database
            $stmt_update_foto = $pdo->prepare("UPDATE absensi_pengawas SET foto = ? WHERE id = ?");
            $stmt_update_foto->execute([$foto_nama, $id_absen]);
            
            $_SESSION['nama'] = $nama;
            $_SESSION['nama_lengkap'] = $nama;
            $pesan = "✅ Absen masuk berhasil! Menunggu verifikasi dari admin.";
            echo "<script>setTimeout(function(){ window.location.href='absen_pengawas.php$test_param'; }, 1000);</script>";
            exit;
        } else {
            $error = "❌ Gagal absen.";
        }
    } else {
        $error = "⚠️ Anda sudah melakukan absen masuk hari ini!";
    }
}

// ==============================================
// AMBIL DATA RIWAYAT ABSEN
// ==============================================
$stmt_riwayat = $pdo->prepare("SELECT * FROM absensi_pengawas WHERE pengawas_id = ? ORDER BY tanggal DESC, jam_masuk DESC");
$stmt_riwayat->execute([$id_pengawas]);
$riwayat = $stmt_riwayat->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$total_absen = count($riwayat);
$total_menunggu = 0;
$total_disetujui = 0;
$total_ditolak = 0;

foreach ($riwayat as $row) {
    if ($row['status_verifikasi'] == 'menunggu') $total_menunggu++;
    elseif ($row['status_verifikasi'] == 'disetujui') $total_disetujui++;
    elseif ($row['status_verifikasi'] == 'ditolak') $total_ditolak++;
}

// Fungsi badge status verifikasi
function getStatusVerifikasiBadge($status) {
    switch($status) {
        case 'menunggu':
            return '<span class="badge-menunggu"><i class="fas fa-clock"></i> Menunggu Verifikasi</span>';
        case 'disetujui':
            return '<span class="badge-disetujui"><i class="fas fa-check-circle"></i> Disetujui</span>';
        case 'ditolak':
            return '<span class="badge-ditolak"><i class="fas fa-times-circle"></i> Ditolak</span>';
        default:
            return '<span class="badge-secondary">-</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absen Pengawas - SMK N 8 Bandar Lampung</title>
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

        /* SIDEBAR */
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
        }

        .sidebar-header-text p {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
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
            text-align: center;
        }

        .menu-subheader {
            padding: 15px 25px 5px 25px;
            color: rgba(255,255,255,0.5);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .logout-section {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 196, 130, 0.2);
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
        }

        .header-title h1 {
            font-size: 22px;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        .datetime {
            background: linear-gradient(135deg, var(--neutral-1), white);
            padding: 8px 18px;
            border-radius: 40px;
            border: 1px solid var(--neutral-2);
            font-size: 13px;
            font-weight: 500;
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
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
        }

        /* INFO CARD */
        .info-card {
            background: linear-gradient(135deg, white, var(--neutral-1));
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 25px;
            border: 1px solid var(--neutral-2);
            border-left: 5px solid var(--accent-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .info-title {
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-2), var(--secondary-1));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 12px;
        }
        
        .info-value {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* CARD */
        .card {
            background: linear-gradient(135deg, white, var(--neutral-1));
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--neutral-2);
            border-bottom: 3px solid var(--accent-1);
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

        /* BUTTONS */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
            padding: 10px 24px;
            border-radius: 40px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(255, 196, 130, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--neutral-2), var(--neutral-1));
            color: var(--primary-2);
            padding: 10px 24px;
            border-radius: 40px;
            border: 1px solid var(--neutral-2);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #5a9b82);
            color: white;
            padding: 8px 18px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        /* BADGE VERIFIKASI */
        .badge-menunggu {
            background: #FFB34720;
            color: #FFB347;
            border: 1px solid #FFB347;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-disetujui {
            background: #77BFA320;
            color: #77BFA3;
            border: 1px solid #77BFA3;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-ditolak {
            background: #E58C7C20;
            color: #E58C7C;
            border: 1px solid #E58C7C;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* CAMERA */
        .camera-container {
            background: linear-gradient(135deg, var(--primary-2), var(--secondary-1));
            border-radius: 24px;
            padding: 20px;
            text-align: center;
        }
        
        video {
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            background: #000;
            transform: scaleX(-1);
        }
        
        .foto-preview img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-1);
        }

        /* STATS */
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
        }
        
        .foto-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid var(--accent-1);
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--neutral-2);
            border-radius: 12px;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        
        .form-label {
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 13px;
        }

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
            background: white;
            border-radius: 24px;
            padding: 25px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .modal-img {
            max-width: 100%;
            border-radius: 16px;
            max-height: 400px;
        }
        
        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .justify-center { justify-content: center; }
        .justify-between { justify-content: space-between; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .w-full { width: 100%; }
        .text-sm { font-size: 12px; }
        .py-8 { padding-top: 32px; padding-bottom: 32px; }
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
            <div class="menu-subheader">ABSENSI</div>
            <a href="absen_pengawas.php<?= $test_param ?>" class="menu-item active"><i class="fas fa-fingerprint"></i> Absen Pengawas</a>
            <a href="monitoring_ujian.php<?= $test_param ?>" class="menu-item"><i class="fas fa-desktop"></i> Monitoring Ujian</a>
            <a href="berita_acara.php<?= $test_param ?>" class="menu-item"><i class="fas fa-file-alt"></i> Berita Acara</a>
            <div class="logout-section">
                <a href="../../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>ABSEN PENGAWAS</h1>
                <p>Isi absen kehadiran dengan selfie + verifikasi admin</p>
            </div>
            <div id="datetime" class="datetime"></div>
        </div>
        <div class="content-area">
            
            <?php if ($pesan): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $pesan ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
            <?php endif; ?>

            <!-- INFO CARD -->
            <div class="info-card">
                <div>
                    <div class="info-title"><i class="fas fa-user"></i> Nama Pengawas</div>
                    <div class="info-value"><?= htmlspecialchars($nama_pengawas ?: 'Belum diisi') ?></div>
                </div>
                <div>
                    <div class="info-title"><i class="fas fa-clock"></i> Jam Saat Ini</div>
                    <div class="info-value" id="liveJam">--:--:--</div>
                </div>
                <div>
                    <div class="info-title"><i class="fas fa-calendar-alt"></i> Tanggal</div>
                    <div class="info-value"><?= date('d/m/Y') ?></div>
                </div>
            </div>

            <!-- FORM ABSEN MASUK -->
            <div class="card">
                <div class="card-title"><i class="fas fa-sign-in-alt"></i> Absen Masuk</div>
                
                <?php if ($sudah_absen && $data_absen_hari_ini): ?>
                    <!-- TAMPILAN SUDAH ABSEN -->
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle" style="font-size: 56px; color: var(--success); margin-bottom: 16px;"></i>
                        <p class="text-lg font-medium" style="color: var(--primary-1);">Anda sudah melakukan absen masuk hari ini</p>
                        <p class="text-gray-500">Tanggal: <?= date('d/m/Y') ?></p>
                        <div class="mt-2">
                            <i class="fas fa-clock"></i> Jam Masuk: <?= $data_absen_hari_ini['jam_masuk'] ?? '-' ?> WIB
                        </div>
                        <div class="mt-1">
                            <i class="fas fa-clock"></i> Jam Pulang: <?= $data_absen_hari_ini['jam_pulang'] ?? 'Belum diisi' ?> WIB
                        </div>
                        <div class="mt-2">
                            Status Verifikasi: <?= getStatusVerifikasiBadge($data_absen_hari_ini['status_verifikasi'] ?? 'menunggu') ?>
                        </div>
                        <?php if (!empty($data_absen_hari_ini['foto'])): ?>
                            <div class="mt-4">
                                <img src="../../../uploads/absen_pengawas/<?= $data_absen_hari_ini['foto'] ?>" 
                                     onclick="showFoto('../../../uploads/absen_pengawas/<?= $data_absen_hari_ini['foto'] ?>')" 
                                     style="width: 100px; height: 100px; border-radius: 50%; cursor: pointer; border: 3px solid var(--accent-1);">
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($data_absen_hari_ini['jam_pulang'])): ?>
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="id_absen" value="<?= $data_absen_hari_ini['id'] ?>">
                                <button type="submit" name="update_jam_pulang" class="btn-success">
                                    <i class="fas fa-sign-out-alt"></i> Isi Jam Pulang
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <button onclick="openUpdateModal(<?= $data_absen_hari_ini['id'] ?>)" class="btn-primary mt-4">
                            <i class="fas fa-camera-retro"></i> Selfie Ulang (Ganti Foto)
                        </button>
                        
                        <?php if ($data_absen_hari_ini['status_verifikasi'] == 'ditolak' && !empty($data_absen_hari_ini['catatan_admin'])): ?>
                            <div class="mt-4 p-3 bg-red-50 rounded-lg">
                                <p class="text-sm text-red-600"><i class="fas fa-comment"></i> Catatan Admin: <?= htmlspecialchars($data_absen_hari_ini['catatan_admin']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- FORM ABSEN BARU -->
                    <form method="POST" id="absenForm">
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user"></i> Nama Pengawas</label>
                            <input type="text" name="nama" class="form-input" placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($nama_pengawas) ?>" required>
                        </div>
                        
                        <div class="camera-container">
                            <p class="mb-2"><i class="fas fa-camera"></i> Ambil Foto Selfie</p>
                            <video id="video" autoplay playsinline></video>
                            <canvas id="canvas" style="display:none;"></canvas>
                            <div class="foto-preview mt-3" id="preview"></div>
                            <div class="flex gap-3 justify-center mt-3">
                                <button type="button" id="captureBtn" class="btn-primary"><i class="fas fa-camera"></i> Ambil Foto</button>
                                <button type="button" id="retakeBtn" class="btn-secondary" style="display:none;"><i class="fas fa-undo-alt"></i> Ambil Ulang</button>
                            </div>
                        </div>
                        <input type="hidden" name="foto" id="fotoData">
                        
                        <div class="mt-3 mb-3">
                            <label class="form-label">Keterangan</label>
                            <div class="flex gap-4">
                                <label><input type="radio" name="keterangan" value="Hadir" checked> <span class="text-green-600">✅ Hadir</span></label>
                                <label><input type="radio" name="keterangan" value="Sakit"> <span class="text-yellow-600">🤒 Sakit</span></label>
                                <label><input type="radio" name="keterangan" value="Izin"> <span class="text-blue-600">📝 Izin</span></label>
                            </div>
                        </div>
                        
                        <div class="alert-info p-3 bg-yellow-50 rounded-lg mb-4">
                            <p class="text-sm text-yellow-800"><i class="fas fa-info-circle"></i> Setelah absen, data akan diverifikasi oleh admin. Status verifikasi dapat dilihat di riwayat.</p>
                        </div>
                        
                        <div class="flex gap-3 justify-center">
                            <button type="submit" name="absen_masuk" id="submitBtn" class="btn-primary" disabled><i class="fas fa-save"></i> Kirim Absen (Menunggu Verifikasi)</button>
                            <a href="dashboard.php<?= $test_param ?>" class="btn-secondary">Batal</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- STATISTIK VERIFIKASI -->
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

            <!-- RIWAYAT ABSEN -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-history"></i> Riwayat Absen Pengawas
                    <?php if ($total_absen > 0): ?>
                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus SEMUA data absen?')" class="ml-auto">
                            <button type="submit" name="hapus_semua" class="btn-secondary" style="background: var(--danger); color: white;"><i class="fas fa-trash-alt"></i> Hapus Semua</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_absen > 0): ?>
                    <div class="table-responsive">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Keterangan</th>
                                    <th>Status Verifikasi</th>
                                    <th>Foto</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($riwayat as $row) : ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= $row['jam_masuk'] ?? '-' ?> WIB</td>
                                    <td><?= $row['jam_pulang'] ?? '-' ?> WIB</td>
                                    <td><?= $row['keterangan'] ?></td>
                                    <td><?= getStatusVerifikasiBadge($row['status_verifikasi'] ?? 'menunggu') ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto']) && file_exists($upload_dir . $row['foto'])): ?>
                                            <img src="../../../uploads/absen_pengawas/<?= $row['foto'] ?>" 
                                                 onclick="showFoto('../../../uploads/absen_pengawas/<?= $row['foto'] ?>')" 
                                                 class="foto-thumbnail">
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display: inline;">
                                            <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                            <button type="submit" name="hapus_absen" class="btn-secondary" style="background: var(--danger); color: white; padding: 4px 10px;"><i class="fas fa-trash-alt"></i> Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Belum ada riwayat absen</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL UPDATE SELFIE -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <h3 class="text-lg font-bold mb-3"><i class="fas fa-camera-retro"></i> Selfie Ulang</h3>
            <div class="camera-container" style="padding: 15px;">
                <video id="updateVideo" autoplay playsinline style="width:100%; transform:scaleX(-1); border-radius:12px;"></video>
                <canvas id="updateCanvas" style="display:none;"></canvas>
                <div id="updatePreview" class="mt-2"></div>
                <button type="button" id="updateCaptureBtn" class="btn-primary mt-2 w-full">Ambil Foto</button>
                <button type="button" id="updateRetakeBtn" class="btn-secondary mt-2 w-full" style="display:none;">Ambil Ulang</button>
            </div>
            <div class="mt-3">
                <label class="form-label">Keterangan</label>
                <select id="updateKeterangan" class="form-input">
                    <option value="Hadir">✅ Hadir</option>
                    <option value="Sakit">🤒 Sakit</option>
                    <option value="Izin">📝 Izin</option>
                </select>
            </div>
            <div class="flex gap-3 justify-center mt-4">
                <button id="updateSubmitBtn" class="btn-primary" disabled>Simpan Perubahan</button>
                <button onclick="closeUpdateModal()" class="btn-secondary">Batal</button>
            </div>
        </div>
    </div>

    <!-- MODAL FOTO -->
    <div id="fotoModal" class="modal">
        <div class="modal-content">
            <img id="modalFoto" src="" class="modal-img">
            <button onclick="closeFotoModal()" class="btn-secondary mt-3 w-full">Tutup</button>
        </div>
    </div>

    <script>
        // KAMERA ABSEN BARU
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const submitBtn = document.getElementById('submitBtn');
        const fotoData = document.getElementById('fotoData');
        const preview = document.getElementById('preview');
        let stream = null;
        
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
            } catch (err) {
                alert('Tidak dapat mengakses kamera: ' + err.message);
            }
        }
        
        captureBtn.addEventListener('click', () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const foto = canvas.toDataURL('image/png');
            fotoData.value = foto;
            if (stream) stream.getTracks().forEach(track => track.stop());
            video.style.display = 'none';
            preview.innerHTML = `<img src="${foto}"><p class="text-sm mt-2" style="color: var(--success);">✅ Foto berhasil diambil</p>`;
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-flex';
            submitBtn.disabled = false;
        });
        
        retakeBtn.addEventListener('click', async () => {
            video.style.display = 'block';
            preview.innerHTML = '';
            startCamera();
            captureBtn.style.display = 'inline-flex';
            retakeBtn.style.display = 'none';
            fotoData.value = '';
            submitBtn.disabled = true;
        });
        
        startCamera();
        
        // UPDATE MODAL
        let updateStream = null;
        let updateFotoData = '';
        let currentUpdateId = null;
        
        function openUpdateModal(id) {
            currentUpdateId = id;
            document.getElementById('updateModal').style.display = 'flex';
            startUpdateCamera();
            updateFotoData = '';
            document.getElementById('updatePreview').innerHTML = '';
            document.getElementById('updateSubmitBtn').disabled = true;
        }
        
        async function startUpdateCamera() {
            try {
                if (updateStream) updateStream.getTracks().forEach(track => track.stop());
                updateStream = await navigator.mediaDevices.getUserMedia({ video: true });
                document.getElementById('updateVideo').srcObject = updateStream;
            } catch (err) { alert('Tidak dapat mengakses kamera'); }
        }
        
        document.getElementById('updateCaptureBtn').addEventListener('click', () => {
            const videoEl = document.getElementById('updateVideo');
            const canvasEl = document.getElementById('updateCanvas');
            const context = canvasEl.getContext('2d');
            canvasEl.width = videoEl.videoWidth;
            canvasEl.height = videoEl.videoHeight;
            context.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);
            updateFotoData = canvasEl.toDataURL('image/png');
            if (updateStream) updateStream.getTracks().forEach(track => track.stop());
            videoEl.style.display = 'none';
            document.getElementById('updatePreview').innerHTML = `<img src="${updateFotoData}" style="width:80px; height:80px; border-radius:50%; border: 3px solid var(--accent-1);">`;
            document.getElementById('updateCaptureBtn').style.display = 'none';
            document.getElementById('updateRetakeBtn').style.display = 'inline-flex';
            document.getElementById('updateSubmitBtn').disabled = false;
        });
        
        document.getElementById('updateRetakeBtn').addEventListener('click', async () => {
            const videoEl = document.getElementById('updateVideo');
            videoEl.style.display = 'block';
            document.getElementById('updatePreview').innerHTML = '';
            startUpdateCamera();
            document.getElementById('updateCaptureBtn').style.display = 'inline-flex';
            document.getElementById('updateRetakeBtn').style.display = 'none';
            updateFotoData = '';
            document.getElementById('updateSubmitBtn').disabled = true;
        });
        
        document.getElementById('updateSubmitBtn').addEventListener('click', () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="update_absen" value="1">
                <input type="hidden" name="id_absen" value="${currentUpdateId}">
                <input type="hidden" name="foto_baru" value="${updateFotoData}">
                <input type="hidden" name="keterangan_baru" value="${document.getElementById('updateKeterangan').value}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
        
        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
            if (updateStream) updateStream.getTracks().forEach(track => track.stop());
        }
        
        function showFoto(src) {
            document.getElementById('modalFoto').src = src;
            document.getElementById('fotoModal').style.display = 'flex';
        }
        
        function closeFotoModal() {
            document.getElementById('fotoModal').style.display = 'none';
        }
        
        function updateClock() {
            const now = new Date();
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            document.getElementById('datetime').innerHTML = `<i class="fas fa-calendar-alt"></i> ${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()} | <i class="fas fa-clock"></i> ${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}:${now.getSeconds().toString().padStart(2,'0')} WIB`;
            document.getElementById('liveJam').innerHTML = `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}:${now.getSeconds().toString().padStart(2,'0')} WIB`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>