<?php
require_once '../../config/koneksi.php';

// Cek apakah ID ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID kartu tidak ditemukan");
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM tb_kartu_ujian WHERE id_kartu = ?");
$stmt->execute([$id]);
$kartu = $stmt->fetch();

if (!$kartu) {
    die("Data kartu ujian tidak ditemukan");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu - <?= htmlspecialchars($kartu['nama_siswa']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #fdf8ed;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Container Kartu */
        .kartu-container {
            max-width: 550px;
            width: 100%;
            margin: 0 auto;
        }

        /* Kartu Ujian - SAMA DENGAN kartu_ujian.php */
        .card-kartu {
            background: white;
            border: 2px solid #1a3c5e;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            overflow: hidden;
        }

        /* Kop Surat */
        .kartu-header {
            padding: 10px 12px;
            background: white;
            border-bottom: 2px solid #8B6914;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .logo-left, .logo-right {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo-left {
            background: linear-gradient(135deg, #8B6914, #DAA520);
        }

        .logo-right {
            background: linear-gradient(135deg, #1a3c5e, #2d5a8c);
        }

        .kop-section {
            flex: 1;
            text-align: center;
        }

        .kop-provinsi {
            font-size: 9px;
            font-weight: 800;
            color: #8B6914;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kop-dinas {
            font-size: 8px;
            font-weight: 700;
            color: #1a3c5e;
            text-transform: uppercase;
        }

        .kop-alamat {
            font-size: 6.5px;
            color: #666;
            margin-top: 2px;
        }

        .judul-ujian {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px;
            background: #fdf8ed;
            border-bottom: 1px solid #e0d5b5;
            color: #1a3c5e;
            letter-spacing: 1px;
        }

        /* Body Kartu */
        .kartu-body {
            padding: 10px 12px;
            background: white;
        }

        .landscape-layout {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        /* Foto Section */
        .foto-section {
            width: 80px;
            text-align: center;
            flex-shrink: 0;
        }

        .foto-container {
            width: 75px;
            height: 90px;
            background: #f5f5f5;
            border: 1.5px solid #e0d5b5;
            border-radius: 8px;
            overflow: hidden;
            margin: 0 auto 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .foto-placeholder {
            text-align: center;
            color: #999;
        }

        .foto-placeholder i {
            font-size: 30px;
            margin-bottom: 3px;
            color: #DAA520;
        }

        .foto-placeholder span {
            font-size: 8px;
        }

        .nis-badge {
            background: #8B6914;
            color: white;
            padding: 3px 6px;
            border-radius: 6px;
            font-size: 8px;
            font-weight: 600;
            font-family: monospace;
            display: inline-block;
        }

        /* Data Section */
        .data-section {
            flex: 1;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 10px;
            margin-bottom: 8px;
        }

        .data-item {
            display: flex;
            align-items: baseline;
            font-size: 9px;
            border-bottom: 1px dotted #e0d5b5;
            padding-bottom: 3px;
        }

        .data-label {
            width: 55px;
            font-weight: 700;
            color: #8B6914;
            flex-shrink: 0;
            font-size: 8px;
        }

        .data-value {
            flex: 1;
            color: #333;
            font-size: 9px;
        }

        .data-value.important {
            font-weight: 700;
            color: #1a3c5e;
        }

        /* Login Section */
        .login-section {
            background: #fdf8ed;
            border-radius: 8px;
            padding: 8px 10px;
            margin-top: 6px;
            border-left: 3px solid #8B6914;
        }

        .login-title {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            color: #8B6914;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .login-items {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .login-item {
            flex: 1;
            min-width: 100px;
        }

        .login-label {
            font-size: 7px;
            font-weight: 700;
            color: #666;
            display: block;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .login-value {
            font-size: 9px;
            font-weight: 700;
            font-family: monospace;
            background: #8B6914;
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            display: inline-block;
            letter-spacing: 0.5px;
        }

        .login-value.username {
            background: #1a3c5e;
        }

        /* Footer */
        .kartu-footer {
            background: #fdf8ed;
            padding: 6px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7px;
            border-top: 1px solid #e0d5b5;
            color: #666;
        }

        .ttd-area {
            text-align: center;
        }

        .ttd-line {
            width: 50px;
            height: 1px;
            background: #333;
            margin: 3px auto;
        }

        /* Print Style - HANYA KARTU YANG DI CETAK */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            /* Sembunyikan tombol */
            .no-print {
                display: none !important;
            }
            
            /* Kartu dicetak tanpa bayangan dan border */
            .card-kartu {
                box-shadow: none;
                border: 1px solid #000;
                margin: 0 auto;
                max-width: 100%;
            }
            
            /* Pastikan tidak ada page break di dalam kartu */
            .card-kartu {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            @page {
                size: A4;
                margin: 0;
            }
            
            /* Reset background untuk print */
            body {
                background: white;
            }
        }

        /* Tombol Cetak - HANYA TAMPIL DI LAYAR */
        .no-print {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 25px;
            margin: 0 8px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-print {
            background: #1a3c5e;
            color: white;
        }

        .btn-close {
            background: #e67e22;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="kartu-container">
        <!-- Kartu Ujian - SAMA PERSIS DENGAN TAMPILAN DI kartu_ujian.php -->
        <div class="card-kartu">
            <!-- Header Kartu: Logo Kiri + Kop Surat Tengah + Logo Kanan -->
            <div class="kartu-header">
                <div class="logo-left">
                    <i class="fas fa-landmark"></i>
                </div>
                <div class="kop-section">
                    <div class="kop-provinsi">PEMERINTAH PROVINSI LAMPUNG</div>
                    <div class="kop-dinas">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                    <div class="kop-alamat">Jl. Imam Bonjol 52, Kemiling 35153</div>
                </div>
                <div class="logo-right">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>
            <div class="judul-ujian">SUMATIF TENGAH SEMESTER GENAP<br>TAHUN PELAJARAN 2024/2025</div>
            
            <!-- Body Kartu -->
            <div class="kartu-body">
                <div class="landscape-layout">
                    <!-- Foto Section -->
                    <div class="foto-section">
                        <div class="foto-container">
                            <?php if (!empty($kartu['foto_siswa']) && file_exists("../../uploads/" . $kartu['foto_siswa'])): ?>
                                <img src="../../uploads/<?= htmlspecialchars($kartu['foto_siswa']) ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div class="foto-placeholder">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>No Photo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="nis-badge">NIS: <?= htmlspecialchars($kartu['nis']) ?></div>
                    </div>
                    
                    <!-- Data Section -->
                    <div class="data-section">
                        <div class="data-grid">
                            <div class="data-item">
                                <span class="data-label">Nama</span>
                                <span class="data-value important"><?= htmlspecialchars($kartu['nama_siswa']) ?></span>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Kelas</span>
                                <span class="data-value"><?= htmlspecialchars($kartu['kelas']) ?></span>
                            </div>
                            <?php if (!empty($kartu['jurusan']) && $kartu['jurusan'] != '-'): ?>
                            <div class="data-item">
                                <span class="data-label">Jurusan</span>
                                <span class="data-value"><?= htmlspecialchars($kartu['jurusan']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="data-item">
                                <span class="data-label">SID</span>
                                <span class="data-value important"><?= htmlspecialchars($kartu['sid'] ?? '-') ?></span>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Tempat Lahir</span>
                                <span class="data-value"><?= htmlspecialchars($kartu['tempat_lahir'] ?? '-') ?></span>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Tgl Lahir</span>
                                <span class="data-value"><?= !empty($kartu['tanggal_lahir']) ? date('d/m/Y', strtotime($kartu['tanggal_lahir'])) : '-' ?></span>
                            </div>
                        </div>
                        
                        <!-- Login Credentials -->
                        <div class="login-section">
                            <div class="login-title">
                                <i class="fas fa-key"></i> Login Akun Ujian
                            </div>
                            <div class="login-items">
                                <div class="login-item">
                                    <span class="login-label">USERNAME</span>
                                    <span class="login-value username"><?= htmlspecialchars($kartu['username_ujian'] ?? $kartu['nis']) ?></span>
                                </div>
                                <div class="login-item">
                                    <span class="login-label">PASSWORD</span>
                                    <span class="login-value"><?= htmlspecialchars($kartu['password_ujian'] ?? '123456') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="kartu-footer">
                <div>Bandar Lampung, <?= date('d F Y') ?></div>
                <div class="ttd-area">
                    <div class="ttd-line"></div>
                    <span>Kepala Sekolah</span>
                </div>
                <div>NIP: 19841121 200903 2 003</div>
            </div>
        </div>
        
        <!-- Tombol Aksi - HANYA TAMPIL DI LAYAR, TIDAK TERCETAK -->
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Cetak Kartu
            </button>
            <button onclick="window.close()" class="btn btn-close">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        // Menampilkan dialog print saat halaman dimuat (opsional, bisa diaktifkan)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // }
    </script>
</body>
</html>