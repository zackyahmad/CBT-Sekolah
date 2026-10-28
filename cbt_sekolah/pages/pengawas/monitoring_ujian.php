<?php
// File: monitoring_ujian.php
session_start();

require_once '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengawas') {
    header("Location: ../../index.php");
    exit;
}

$nama_pengawas = $_SESSION['nama'] ?? 'Pengawas Ujian';
$test_param = (isset($_GET['test']) && $_GET['test'] == '1') ? '?test=1' : '';

// ==============================================
// 1. AMBIL MASTER KELAS UNTUK DROPDOWN
// ==============================================
$stmt_kelas = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC");
$master_kelas = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// ==============================================
// 2. PROSES FILTER KELAS & AMBIL DATA SISWA
// ==============================================
$kelas_terpilih = $_GET['kelas'] ?? '';
$data_siswa = [];
$total_siswa = 0;
$total_selesai = 0;
$total_belum = 0;
$mapel_default = 'Belum ada ujian aktif'; // Default jika tidak ada jadwal ujian

if (!empty($kelas_terpilih)) {
    
    // MENCARI NAMA MAPEL OTOMATIS: Cek ujian apa yang aktif untuk kelas ini hari ini
    $stmt_mapel = $pdo->prepare("
        SELECT m.nama_mapel 
        FROM tb_ujian_kelas uk
        JOIN tb_kelas k ON uk.id_kelas = k.id_kelas
        JOIN tb_ujian u ON uk.id_ujian = u.id_ujian
        JOIN tb_mapel m ON u.id_mapel = m.id_mapel
        WHERE k.nama_kelas = ? AND (u.status_ujian = 'aktif' OR u.status_ujian = 'Aktif' OR DATE(u.tanggal_ujian) = CURDATE())
        LIMIT 1
    ");
    $stmt_mapel->execute([$kelas_terpilih]);
    $mapel_aktif = $stmt_mapel->fetchColumn();
    
    if ($mapel_aktif) {
        $mapel_default = $mapel_aktif;
    }

    // QUERY UTAMA: Tarik data siswa dan hasil ujiannya
    $query = "
        SELECT u.id_user, u.nama_lengkap as nama, u.nis, k.nama_kelas as kelas,
               h.id_hasil, h.waktu_selesai, m.nama_mapel
        FROM tb_users u
        JOIN tb_kelas k ON u.id_kelas = k.id_kelas
        LEFT JOIN tb_hasil_ujian h ON h.id_user = u.id_user AND DATE(h.waktu_selesai) = CURDATE()
        LEFT JOIN tb_ujian uj ON h.id_ujian = uj.id_ujian
        LEFT JOIN tb_mapel m ON uj.id_mapel = m.id_mapel
        WHERE k.nama_kelas = ? AND u.role = 'siswa'
        ORDER BY u.nama_lengkap ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$kelas_terpilih]);
    $data_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung Statistik
    $total_siswa = count($data_siswa);
    foreach ($data_siswa as $siswa) {
        if (!empty($siswa['id_hasil'])) {
            $total_selesai++;
        } else {
            $total_belum++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Ujian - SMK N 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if(!empty($kelas_terpilih)): ?>
        <meta http-equiv="refresh" content="30;url=monitoring_ujian.php?kelas=<?= urlencode($kelas_terpilih) ?>">
    <?php endif; ?>

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
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, var(--neutral-1) 0%, var(--neutral-2) 100%); display: flex; min-height: 100vh; }

        /* SIDEBAR STYLES */
        .sidebar { width: 280px; background: linear-gradient(135deg, var(--primary-1) 0%, var(--primary-2) 40%, var(--secondary-1) 100%); color: var(--neutral-1); position: fixed; height: 100vh; overflow-y: auto; box-shadow: 4px 0 20px rgba(43, 58, 103, 0.3); z-index: 50; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255, 196, 130, 0.3); text-align: center; background: linear-gradient(180deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 100%); }
        .sidebar-logo { width: 60px; height: 60px; border-radius: 12px; object-fit: cover; border: 2px solid var(--accent-1); background: linear-gradient(135deg, white, var(--neutral-1)); padding: 3px; margin: 0 auto 10px; display: block; }
        .sidebar-header-text h2 { font-size: 18px; font-weight: 700; background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0; }
        .sidebar-header-text p { font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 5px; }
        .sidebar-menu { padding: 20px 0; display: flex; flex-direction: column; height: calc(100vh - 160px); }
        .menu-item { padding: 12px 25px; display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.8); transition: all 0.3s; border-left: 4px solid transparent; text-decoration: none; font-size: 14px; }
        .menu-item:hover, .menu-item.active { background: linear-gradient(90deg, rgba(255, 196, 130, 0.15), rgba(255, 196, 130, 0.05)); color: var(--accent-1); border-left-color: var(--accent-1); }
        .menu-item i { width: 24px; font-size: 18px; text-align: center; }
        .menu-subheader { padding: 15px 25px 5px 25px; color: rgba(255,255,255,0.5); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .logout-section { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255, 196, 130, 0.2); }
        .logout-item { color: var(--accent-2); }
        .logout-item:hover { background: linear-gradient(90deg, rgba(221, 183, 171, 0.15), rgba(221, 183, 171, 0.05)); color: var(--accent-2); border-left-color: var(--accent-2); }

        /* MAIN CONTENT */
        .main-content { flex: 1; margin-left: 280px; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #ffffff 0%, var(--neutral-1) 100%); padding: 15px 30px; border-bottom: 3px solid var(--accent-1); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 40; box-shadow: 0 2px 15px rgba(43, 58, 103, 0.1); }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .logo-header { height: 45px; width: auto; border-radius: 8px; }
        .header-title h1 { font-size: 22px; background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; margin: 0; }
        .header-title p { color: var(--secondary-1); font-size: 12px; margin-top: 2px; }
        .datetime { background: linear-gradient(135deg, var(--neutral-1), white); padding: 8px 18px; border-radius: 40px; border: 1px solid var(--neutral-2); border-bottom: 2px solid var(--accent-1); font-size: 13px; font-weight: 500; color: var(--primary-2); }
        .datetime i { background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-right: 5px; }

        .content-area { padding: 30px; }

        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: linear-gradient(135deg, white, var(--neutral-1)); border-radius: 20px; padding: 18px 20px; display: flex; align-items: center; gap: 15px; border: 1px solid var(--neutral-2); border-bottom: 3px solid var(--accent-1); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(43, 58, 103, 0.12); }
        .stat-icon { width: 52px; height: 52px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-value { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .stat-label { font-size: 12px; color: var(--secondary-1); font-weight: 500; }
        .bg-blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: var(--primary-2); }
        .bg-yellow { background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--warning); }
        .bg-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: var(--success); }

        /* CARD & FORMS */
        .card { background: linear-gradient(135deg, white, var(--neutral-1)); border-radius: 20px; padding: 25px; margin-bottom: 25px; border: 1px solid var(--neutral-2); border-bottom: 3px solid var(--accent-1); box-shadow: 0 2px 8px rgba(43, 58, 103, 0.05); }
        .card-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--neutral-2); display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        
        .filter-select { padding: 10px 15px; border-radius: 10px; border: 1px solid var(--neutral-2); outline: none; font-weight: 600; color: var(--primary-1); min-width: 250px; background: white; }
        .btn-filter { background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); color: white; padding: 10px 20px; border-radius: 10px; border: none; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(255, 196, 130, 0.4); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { background: linear-gradient(135deg, var(--neutral-2), var(--neutral-1)); padding: 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--primary-1); border-bottom: 2px solid var(--accent-1); }
        td { padding: 12px; border-bottom: 1px solid var(--neutral-2); font-size: 13px; color: var(--dark); vertical-align: middle; }
        tr:hover td { background: linear-gradient(135deg, rgba(255, 196, 130, 0.08), rgba(255, 196, 130, 0.03)); }

        .badge { padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .badge-aktif { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-selesai { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        .avatar-small { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: white; }
        .mapel-badge { background: white; border: 1px solid var(--accent-1); color: var(--primary-1); padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 11px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../assets/img/logo-smk8.png" class="sidebar-logo">
            <div class="sidebar-header-text">
                <h2>PENGAWAS SKAPAN</h2>
                <p>SMK N 8 BANDAR LAMPUNG</p>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php<?= $test_param ?>" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <div class="menu-subheader">MENU UTAMA</div>
            <a href="monitoring_ujian.php<?= $test_param ?>" class="menu-item active"><i class="fas fa-desktop"></i> Monitoring Ujian</a>
            <a href="berita_acara.php<?= $test_param ?>" class="menu-item"><i class="fas fa-file-alt"></i> Berita Acara</a>
            <a href="absen_pengawas.php<?= $test_param ?>" class="menu-item"><i class="fas fa-fingerprint"></i> Absen Pengawas</a>
            <a href="verifikasi_siswa.php<?= $test_param ?>" class="menu-item"><i class="fas fa-user-check"></i> Verifikasi Siswa</a>
            <div class="logout-section">
                <a href="../../logout.php" class="menu-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <img src="../../assets/img/logo-smk8.png" class="logo-header">
                <div class="header-title">
                    <h1>MONITORING UJIAN REAL-TIME</h1>
                    <p>Pantau status pengerjaan ujian siswa secara otomatis</p>
                </div>
            </div>
            <div class="header-right">
                <div id="datetime" class="datetime"></div>
            </div>
        </div>
        
        <div class="content-area">
            
            <div class="card">
                <form method="GET" class="flex items-end gap-4">
                    <?php if(isset($_GET['test'])): ?>
                        <input type="hidden" name="test" value="1">
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Pilih Kelas yang Dipantau</label>
                        <select name="kelas" class="filter-select" required>
                            <option value="">-- Tampilkan Semua Kelas --</option>
                            <?php foreach($master_kelas as $mk): ?>
                                <option value="<?= $mk['nama_kelas'] ?>" <?= ($kelas_terpilih == $mk['nama_kelas']) ? 'selected' : '' ?>>
                                    Kelas <?= htmlspecialchars($mk['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter"><i class="fas fa-search mr-2"></i> Tampilkan Data</button>
                </form>
            </div>

            <?php if (!empty($kelas_terpilih)): ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-value"><?= $total_siswa ?></div>
                            <div class="stat-label">Total Siswa di Kelas <?= htmlspecialchars($kelas_terpilih) ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-yellow"><i class="fas fa-spinner fa-spin"></i></div>
                        <div>
                            <div class="stat-value"><?= $total_belum ?></div>
                            <div class="stat-label">Belum Selesai / Sedang Ujian</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-value"><?= $total_selesai ?></div>
                            <div class="stat-label">Selesai Mengumpulkan</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">
                        <span><i class="fas fa-desktop mr-2 text-[#FFC482]"></i> Status Ujian Kelas: <?= htmlspecialchars($kelas_terpilih) ?></span>
                        <span class="text-xs text-gray-400 font-normal"><i class="fas fa-sync fa-spin mr-1"></i> Auto-refresh tiap 30 detik</span>
                    </div>
                    
                    <?php if (empty($data_siswa)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-users-slash fa-3x mb-3 block opacity-50"></i>
                            <p class="font-bold">Tidak ada data siswa</p>
                            <p class="text-sm mt-1">Belum ada siswa yang terdaftar di kelas ini pada database.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="text-center w-12">No</th>
                                        <th>Identitas Siswa</th>
                                        <th class="text-center">Kelas</th>
                                        <th class="text-center">Mata Pelajaran</th>
                                        <th class="text-center">Waktu Selesai</th>
                                        <th class="text-center">Status Ujian HARI INI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $no = 1; 
                                        foreach ($data_siswa as $siswa): 
                                        // Gunakan nama_mapel dari database jika ada, jika tidak gunakan mapel kelas yg aktif hari ini
                                        $mapel_ditampilkan = !empty($siswa['nama_mapel']) ? $siswa['nama_mapel'] : $mapel_default;
                                    ?>
                                    <tr>
                                        <td class="text-center font-bold text-gray-400"><?= $no++ ?></td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="avatar-small"><?= strtoupper(substr($siswa['nama'], 0, 1)) ?></div>
                                                <div>
                                                    <div class="font-bold text-[#2B3A67]"><?= htmlspecialchars($siswa['nama']) ?></div>
                                                    <div class="text-xs text-gray-500">NIS: <?= htmlspecialchars($siswa['nis']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center font-bold"><?= htmlspecialchars($siswa['kelas']) ?></td>
                                        
                                        <td class="text-center">
                                            <span class="mapel-badge">
                                                <?= htmlspecialchars($mapel_ditampilkan) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <?php if (!empty($siswa['waktu_selesai'])): ?>
                                                <span class="text-sm font-bold text-[#66999B]"><i class="fas fa-clock mr-1 text-[#FFC482]"></i> <?= date('H:i', strtotime($siswa['waktu_selesai'])) ?> WIB</span>
                                            <?php else: ?>
                                                <span class="text-gray-300">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (empty($siswa['id_hasil'])): ?>
                                                <span class="badge badge-aktif"><i class="fas fa-pen ml-1"></i> Sedang Mengerjakan</span>
                                            <?php else: ?>
                                                <span class="badge badge-selesai"><i class="fas fa-check-circle ml-1"></i> Sudah Mengumpulkan</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="text-center py-16">
                    <img src="../../assets/img/logo-smk8.png" class="w-32 h-32 mx-auto mb-6 opacity-80 grayscale">
                    <h2 class="text-2xl font-bold text-[#2B3A67] mb-2">Pusat Monitoring Ujian</h2>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">Silakan pilih kelas pada form di atas untuk memantau status ujian siswa secara real-time.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
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