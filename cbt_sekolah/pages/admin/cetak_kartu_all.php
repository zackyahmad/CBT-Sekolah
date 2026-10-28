<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Ambil parameter filter dari URL (dikirim dari kartu_ujian.php)
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk mengambil data kartu ujian berdasarkan filter
$query = "SELECT * FROM tb_kartu_ujian WHERE 1=1";
$params = [];

// Filter pencarian (NIS atau Nama)
if (!empty($search)) {
    $query .= " AND (nis LIKE ? OR nama_siswa LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filter kelas - PERBAIKAN: menangani kelas spesifik dengan benar
if (!empty($kelas)) {
    // Jika filter adalah 'X', 'XI', atau 'XII' (tanpa detail)
    if ($kelas == 'XII' || $kelas == 'XI' || $kelas == 'X') {
        $query .= " AND kelas LIKE ?";
        $params[] = "$kelas%";
    } 
    // Jika filter adalah kelas lengkap (contoh: "X RPL 1", "XI AKL 2", dll)
    else {
        $query .= " AND kelas = ?";
        $params[] = $kelas;
    }
}

$query .= " ORDER BY kelas, nama_siswa ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kartu_ujian = $stmt->fetchAll();

// Untuk debugging (opsional) - hapus atau comment jika sudah production
// echo "<!-- Query: " . $query . " | Kelas: " . $kelas . " | Total: " . count($kartu_ujian) . " -->";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Ujian - <?= !empty($kelas) ? 'Kelas ' . htmlspecialchars($kelas) : 'Semua Kelas' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            background: #e0e0e0;
            padding: 30px;
        }

        .print-container {
            max-width: 500px;
            margin: 0 auto;
        }

        /* Informasi Filter di Atas Halaman (opsional, hanya untuk layar) */
        .filter-info-screen {
            background: #fdf8ed;
            border: 1px solid #8B6914;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 12px;
            color: #333;
        }

        .filter-info-screen strong {
            color: #8B6914;
        }

        /* Kartu Ujian - 1 Kartu per halaman */
        .card-kartu {
            background: white;
            border: 1px solid #000;
            width: 100%;
            max-width: 450px;
            margin: 0 auto 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            page-break-after: always;
            break-after: page;
        }

        /* Kop Surat dalam Kartu */
        .kop-surat {
            text-align: center;
            padding: 15px 12px 10px 12px;
            border-bottom: 2px solid #8B6914;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .logo-kiri, .logo-kanan {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .logo-kiri {
            background: linear-gradient(135deg, #8B6914, #DAA520);
            color: white;
        }

        .logo-kanan {
            background: linear-gradient(135deg, #1a3c5e, #2d5a8c);
            color: white;
        }

        .kop-tengah {
            flex: 1;
        }

        .kop-tengah .provinsi {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #8B6914;
            letter-spacing: 1px;
        }

        .kop-tengah .dinas {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a3c5e;
        }

        .kop-tengah .alamat {
            font-size: 8px;
            color: #666;
            margin-top: 3px;
        }

        .judul-ujian {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 8px;
            background: #fdf8ed;
            border-bottom: 1px solid #e0d5b5;
        }

        /* Body Kartu */
        .body-kartu {
            padding: 15px;
            display: flex;
            gap: 15px;
        }

        .foto-section {
            width: 100px;
            text-align: center;
            flex-shrink: 0;
        }

        .foto-container {
            width: 95px;
            height: 115px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .foto-placeholder {
            text-align: center;
            color: #999;
        }

        .foto-placeholder i {
            font-size: 45px;
            margin-bottom: 5px;
        }

        .foto-placeholder span {
            font-size: 10px;
        }

        .foto-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nis-badge {
            background: #8B6914;
            color: white;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            font-family: monospace;
        }

        .data-section {
            flex: 1;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 11px;
            border-bottom: 1px dotted #eee;
            padding-bottom: 5px;
        }

        .info-label {
            width: 70px;
            font-weight: bold;
            color: #8B6914;
        }

        .info-value {
            flex: 1;
            color: #333;
        }

        .info-value.bold {
            font-weight: bold;
            color: #1a3c5e;
        }

        .login-box {
            background: #fdf8ed;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            border-left: 4px solid #8B6914;
        }

        .login-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #8B6914;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }

        .login-row {
            display: flex;
            gap: 12px;
            font-size: 10px;
        }

        .login-item {
            flex: 1;
        }

        .login-label {
            font-size: 7px;
            color: #666;
            display: block;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .login-value {
            font-weight: bold;
            font-family: monospace;
            background: #8B6914;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            display: inline-block;
            font-size: 10px;
            letter-spacing: 1px;
        }

        .login-value.user {
            background: #1a3c5e;
        }

        /* Footer */
        .footer-kartu {
            background: #fdf8ed;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            border-top: 1px solid #e0d5b5;
        }

        .ttd-area {
            text-align: center;
        }

        .ttd-line {
            width: 80px;
            height: 1px;
            background: #000;
            margin: 5px auto;
        }

        /* Tombol Aksi - hanya untuk layar, tidak muncul saat print */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding: 20px;
            background: white;
            margin-top: 20px;
            position: sticky;
            bottom: 20px;
            z-index: 100;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .btn-cetak, .btn-back {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cetak {
            background: #1a3c5e;
            color: white;
        }

        .btn-back {
            background: #e67e22;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cetak:hover, .btn-back:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }

        /* Print Styles - 1 kartu per halaman */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .action-buttons {
                display: none;
            }
            
            .filter-info-screen {
                display: none;
            }
            
            .card-kartu {
                box-shadow: none;
                border: 1px solid #000;
                margin: 0 auto 0 auto;
                page-break-after: always;
                break-after: page;
            }
            
            .card-kartu:last-child {
                page-break-after: auto;
            }
            
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>

<body>
    <div class="print-container">
        <!-- Informasi Filter (hanya tampil di layar, tidak saat print) -->
        <div class="filter-info-screen">
            <i class="fas fa-filter"></i> 
            <strong>
                <?php 
                if (!empty($kelas) && !empty($search)) {
                    echo "Filter: Kelas " . htmlspecialchars($kelas) . " | Pencarian: " . htmlspecialchars($search);
                } elseif (!empty($kelas)) {
                    echo "Filter: Kelas " . htmlspecialchars($kelas);
                } elseif (!empty($search)) {
                    echo "Filter: Pencarian \"" . htmlspecialchars($search) . "\"";
                } else {
                    echo "Semua Data";
                }
                ?>
            </strong> 
            | Total: <?= count($kartu_ujian) ?> Kartu
        </div>

        <!-- Kartu - 1 per halaman -->
        <?php if (empty($kartu_ujian)): ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
                <i class="fas fa-id-card" style="font-size: 50px; color: #ccc;"></i>
                <p style="margin-top: 20px;">Tidak ada data kartu ujian yang ditemukan dengan filter yang dipilih.</p>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                    Filter: <?= !empty($kelas) ? 'Kelas ' . htmlspecialchars($kelas) : 'Semua Kelas' ?>
                    <?= !empty($search) ? ' | Pencarian: ' . htmlspecialchars($search) : '' ?>
                </p>
                <a href="kartu_ujian.php" class="btn-back" style="display: inline-block; margin-top: 20px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($kartu_ujian as $index => $kartu): ?>
            <div class="card-kartu">
                <!-- Kop Surat dalam Kartu -->
                <div class="kop-surat">
                    <div class="logo-kiri">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <div class="kop-tengah">
                        <div class="provinsi">PEMERINTAH PROVINSI LAMPUNG</div>
                        <div class="dinas">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                        <div class="alamat">Jl. Imam Bonjol 52, Kemiling 35153</div>
                    </div>
                    <div class="logo-kanan">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
                <div class="judul-ujian">SUMATIF TENGAH SEMESTER GENAP</div>
                
                <!-- Body Kartu -->
                <div class="body-kartu">
                    <!-- Foto -->
                    <div class="foto-section">
                        <div class="foto-container">
                            <?php if (!empty($kartu['foto_siswa']) && file_exists("../../uploads/" . $kartu['foto_siswa'])): ?>
                                <img src="../../uploads/<?= $kartu['foto_siswa'] ?>" class="foto-img" alt="Foto">
                            <?php else: ?>
                                <div class="foto-placeholder">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>No Photo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="nis-badge">NIS: <?= htmlspecialchars($kartu['nis']) ?></div>
                    </div>
                    
                    <!-- Data Siswa -->
                    <div class="data-section">
                        <div class="info-row">
                            <span class="info-label">Nama</span>
                            <span class="info-value bold">: <?= htmlspecialchars($kartu['nama_siswa']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kelas</span>
                            <span class="info-value">: <?= htmlspecialchars($kartu['kelas']) ?></span>
                        </div>
                        <?php if (!empty($kartu['jurusan'])): ?>
                        <div class="info-row">
                            <span class="info-label">Jurusan</span>
                            <span class="info-value">: <?= htmlspecialchars($kartu['jurusan']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($kartu['sid'])): ?>
                        <div class="info-row">
                            <span class="info-label">SID</span>
                            <span class="info-value bold">: <?= htmlspecialchars($kartu['sid']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Tempat Lahir</span>
                            <span class="info-value">: <?= htmlspecialchars($kartu['tempat_lahir'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tgl Lahir</span>
                            <span class="info-value">: <?= isset($kartu['tanggal_lahir']) && $kartu['tanggal_lahir'] ? date('d/m/Y', strtotime($kartu['tanggal_lahir'])) : '-' ?></span>
                        </div>
                        
                        <!-- Login Credentials -->
                        <div class="login-box">
                            <div class="login-title">🔑 LOGIN AKUN UJIAN</div>
                            <div class="login-row">
                                <div class="login-item">
                                    <span class="login-label">USERNAME</span>
                                    <span class="login-value user"><?= !empty($kartu['username_ujian']) ? htmlspecialchars($kartu['username_ujian']) : htmlspecialchars($kartu['nis']) ?></span>
                                </div>
                                <div class="login-item">
                                    <span class="login-label">PASSWORD</span>
                                    <span class="login-value"><?= $kartu['password_ujian'] ?? '123456' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="footer-kartu">
                    <div>Bandar Lampung, <?= date('d M Y') ?></div>
                    <div class="ttd-area">
                        <div class="ttd-line"></div>
                        <span>Kepala Sekolah</span>
                    </div>
                    <div>NIP. 19841121 200903 2 003</div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Tombol Aksi -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn-cetak">
                <i class="fas fa-print"></i> Cetak / Simpan PDF
            </button>
            <a href="kartu_ujian.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <script>
        // Opsional: Auto print saat halaman dimuat (uncomment jika ingin otomatis print)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // }
    </script>
</body>

</html>