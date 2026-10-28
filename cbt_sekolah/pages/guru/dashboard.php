<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php");
    exit;
}

$user_id      = $_SESSION['id_user'] ?? 0;
$nama_lengkap = $_SESSION['nama_lengkap'] ?? ($_SESSION['nama'] ?? 'Guru');
$username     = $_SESSION['username'] ?? '';
$inisial      = strtoupper(substr($nama_lengkap, 0, 1));

if ($user_id == 0) {
    echo "<div style='padding:20px;background:#fff3cd;border:1px solid #ffc107;margin:20px;border-radius:5px;'>
          <h3>Error: Session tidak valid</h3>
          <p>Silakan <a href='../../index.php'>login kembali</a></p></div>";
    exit;
}

// Koneksi database (MySQLi untuk dashboard)
$conn = new mysqli('localhost', 'root', '', 'db_ujian_sekolah');
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
$conn->set_charset("utf8");

// Juga butuh PDO untuk query tb_soal (tabel admin)
require_once '../../config/koneksi.php';

function formatTanggal($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    $bulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $t = strtotime($tanggal);
    return $t ? date('d', $t).' '.$bulan[(int)date('m',$t)].' '.date('Y',$t) : '-';
}

// ── Statistik ──
$r = $conn->query("SELECT COUNT(*) AS c FROM ujian WHERE guru_id=$user_id");
$total_ujian = $r ? $r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM ujian WHERE guru_id=$user_id AND status='aktif'");
$ujian_aktif = $r ? $r->fetch_assoc()['c'] : 0;

// Total soal dari tb_soal (tabel admin) — DIPERBAIKI
// Total soal berdasarkan ujian milik guru
$ts = $pdo->prepare("
    SELECT COUNT(*)
    FROM tb_soal s
    JOIN ujian u ON s.id_ujian = u.id
    WHERE u.guru_id = ?
");
$ts->execute([$user_id]);
$total_soal = $ts->fetchColumn();

$r = $conn->query("SELECT COUNT(DISTINCT h.siswa_id) AS c FROM hasil_ujian h JOIN ujian u ON h.ujian_id=u.id WHERE u.guru_id=$user_id");
$total_siswa = $r ? $r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT AVG(h.nilai) AS rata FROM hasil_ujian h JOIN ujian u ON h.ujian_id=u.id WHERE u.guru_id=$user_id");
$rata_nilai = '—';
if ($r) { $rata = $r->fetch_assoc()['rata']; if ($rata > 0) $rata_nilai = number_format($rata, 1); }

// Ujian terbaru
$ujian_terbaru = $conn->query("
    SELECT u.id, u.nama_ujian, u.mata_pelajaran, u.tanggal_mulai, u.status,
           COUNT(DISTINCT h.siswa_id) AS peserta
    FROM ujian u LEFT JOIN hasil_ujian h ON h.ujian_id=u.id
    WHERE u.guru_id=$user_id GROUP BY u.id ORDER BY u.created_at DESC LIMIT 5
");

// Chart data
$chart_labels = []; $chart_values = [];
$stat = $conn->query("SELECT u.nama_ujian, AVG(h.nilai) AS avg_nilai FROM ujian u LEFT JOIN hasil_ujian h ON h.ujian_id=u.id WHERE u.guru_id=$user_id GROUP BY u.id ORDER BY u.created_at DESC LIMIT 6");
if ($stat) while ($row = $stat->fetch_assoc()) { $chart_labels[] = $row['nama_ujian']; $chart_values[] = round($row['avg_nilai']??0,1); }

// Tanggal
date_default_timezone_set('Asia/Jakarta');
$hari_id = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$tgl_lengkap  = $hari_id[date('l')].', '.date('d/m/Y');
$jam_sekarang = date('H:i').' WIB';

// Logo
$logo_url = '';
$logo_checks = [
    __DIR__.'/../../assets/img/logo-smk8.png' => '/Aplikasi%20CBT%20Sekolah/cbt_sekolah/assets/img/logo-smk8.png.png',
    __DIR__.'/../../assets/img/logo-smk8.png'      => '/Aplikasi%20CBT%20Sekolah/cbt_sekolah/assets/img/logo-smk8.png',
];
foreach ($logo_checks as $full => $url) {
    if (file_exists($full)) { $logo_url = $url; break; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Dashboard Guru - CBT Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{--primary-1:#2B3A67;--primary-2:#496A81;--secondary-1:#66999B;--secondary-2:#B3AF8F;--accent-1:#FFC482;--accent-2:#DDB7AB;--neutral-1:#F8F4E9;--neutral-2:#E8E1D3;--dark:#2E3B4E;--success:#77BFA3;--warning:#FFB347;--danger:#E58C7C;--sidebar-width:280px;}
        body{background:linear-gradient(135deg,var(--neutral-1) 0%,var(--neutral-2) 100%);font-family:'Inter',sans-serif;color:var(--dark);min-height:100vh;}
        /* SIDEBAR */
        .sidebar{position:fixed;left:0;top:0;width:var(--sidebar-width);height:100%;background:linear-gradient(180deg,var(--primary-1) 0%,var(--primary-2) 50%,var(--secondary-1) 100%);color:white;z-index:1000;box-shadow:4px 0 20px rgba(43,58,103,.25);display:flex;flex-direction:column;}
        .sidebar-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:12px;}
        .sidebar-logo img{width:48px;height:48px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,.1);padding:6px;}
        .logo-placeholder{width:48px;height:48px;background:linear-gradient(135deg,var(--accent-1),var(--accent-2));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--primary-1);}
        .logo-info h3{font-size:16px;font-weight:700;margin-bottom:2px;background:linear-gradient(135deg,#fff,var(--accent-1));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .logo-info p{font-size:10px;opacity:.7;color:var(--neutral-2);}
        .nav-menu{list-style:none;padding:20px 16px;flex:1;}
        .nav-menu li{margin-bottom:6px;}
        .nav-menu a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(255,255,255,.75);text-decoration:none;border-radius:12px;font-weight:500;font-size:14px;transition:all .3s;}
        .nav-menu a i{width:20px;font-size:16px;}
        .nav-menu a:hover{background:rgba(255,255,255,.08);color:var(--accent-1);transform:translateX(4px);}
        .nav-menu a.active{background:linear-gradient(90deg,var(--accent-1),var(--accent-2));color:var(--primary-1);font-weight:600;box-shadow:0 2px 8px rgba(255,196,130,.3);}
        .sidebar-footer{padding:16px;border-top:1px solid rgba(255,255,255,.1);margin-top:auto;}
        .logout-btn{display:flex;align-items:center;gap:12px;padding:12px 16px;background:rgba(255,255,255,.05);border-radius:12px;color:rgba(255,255,255,.8);text-decoration:none;font-weight:500;font-size:14px;transition:all .3s;}
        .logout-btn:hover{background:rgba(229,140,124,.25);color:var(--accent-1);}
        /* MAIN */
        .main-content{margin-left:var(--sidebar-width);min-height:100vh;display:flex;flex-direction:column;}
        .topbar{background:linear-gradient(135deg,#ffffff 0%,var(--neutral-1) 100%);padding:16px 32px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,.03);position:sticky;top:0;z-index:99;border-bottom:2px solid var(--secondary-1);}
        .page-title h1{font-size:22px;font-weight:700;background:linear-gradient(135deg,var(--primary-1),var(--secondary-1));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .page-title p{font-size:12px;color:var(--secondary-2);margin-top:4px;}
        .topbar-right{display:flex;align-items:center;gap:24px;}
        .date{font-size:12px;color:var(--secondary-2);}
        .time{font-size:15px;font-weight:600;background:linear-gradient(135deg,var(--primary-1),var(--secondary-1));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .user-info{display:flex;align-items:center;gap:12px;padding:6px 12px 6px 16px;background:linear-gradient(135deg,var(--neutral-1),var(--neutral-2));border-radius:50px;border:1px solid var(--neutral-2);}
        .user-details{text-align:right;}
        .user-name{font-weight:600;font-size:13px;color:var(--primary-1);}
        .user-role{font-size:10px;color:var(--secondary-2);}
        .user-avatar{width:38px;height:38px;background:linear-gradient(145deg,var(--secondary-1),var(--secondary-2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:white;}
        /* CONTENT */
        .content{padding:28px 32px;flex:1;}
        .welcome-card{background:linear-gradient(135deg,var(--primary-1) 0%,var(--secondary-1) 50%,var(--accent-1) 100%);border-radius:24px;padding:28px 32px;margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;color:white;position:relative;overflow:hidden;}
        .welcome-card::before{content:'';position:absolute;top:-30%;right:-10%;width:200px;height:200px;background:radial-gradient(circle,rgba(255,255,255,.15),transparent);border-radius:50%;}
        .welcome-text h2{font-size:22px;font-weight:700;margin-bottom:6px;}
        .welcome-text p{font-size:13px;opacity:.9;}
        .btn-primary{background:linear-gradient(135deg,var(--accent-1),var(--accent-2));color:var(--primary-1);padding:10px 22px;border-radius:40px;text-decoration:none;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:8px;margin-left:12px;transition:all .3s;}
        .btn-outline{background:transparent;border:1.5px solid rgba(255,255,255,.4);color:white;padding:10px 22px;border-radius:40px;text-decoration:none;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:8px;transition:all .3s;}
        .btn-primary:hover,.btn-outline:hover{transform:translateY(-2px);}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;}
        .stat-card{background:white;border-radius:20px;padding:20px;border:1px solid var(--neutral-2);transition:all .3s;}
        .stat-card:hover{transform:translateY(-4px);box-shadow:0 12px 20px rgba(43,58,103,.08);border-color:var(--secondary-1);}
        .stat-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
        .stat-label{font-size:13px;font-weight:500;color:var(--secondary-2);}
        .stat-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--neutral-1),var(--neutral-2));border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--secondary-1);}
        .stat-value{font-size:30px;font-weight:800;background:linear-gradient(135deg,var(--primary-1),var(--secondary-1));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;}
        .stat-footer{font-size:11px;color:var(--secondary-2);border-top:1px solid var(--neutral-2);padding-top:10px;}
        .card{background:white;border-radius:20px;border:1px solid var(--neutral-2);overflow:hidden;margin-bottom:28px;}
        .card-header{padding:16px 24px;background:linear-gradient(135deg,var(--neutral-1),rgba(248,244,233,.5));border-bottom:1px solid var(--neutral-2);display:flex;justify-content:space-between;align-items:center;}
        .card-title{font-weight:700;font-size:15px;color:var(--primary-1);}
        .card-title i{color:var(--accent-1);margin-right:8px;}
        .btn-link{color:var(--secondary-1);text-decoration:none;font-size:12px;font-weight:500;}
        .btn-link:hover{color:var(--accent-1);}
        .table-wrapper{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:12px 20px;text-align:left;border-bottom:1px solid var(--neutral-2);}
        th{font-size:11px;font-weight:600;color:var(--secondary-2);background:var(--neutral-1);}
        td{font-size:13px;color:var(--dark);}
        .badge{display:inline-block;padding:4px 12px;border-radius:30px;font-size:10px;font-weight:600;}
        .badge-success{background:rgba(119,191,163,.12);color:var(--success);border:1px solid rgba(119,191,163,.3);}
        .badge-warning{background:rgba(255,180,71,.12);color:var(--warning);border:1px solid rgba(255,180,71,.3);}
        .badge-secondary{background:var(--neutral-2);color:var(--secondary-2);}
        .empty-state{text-align:center;padding:48px;color:var(--secondary-2);}
        .empty-icon{font-size:44px;margin-bottom:12px;}
        .chart-container{padding:20px;}
        .footer-copyright{background:linear-gradient(135deg,var(--primary-1),var(--primary-2));color:white;text-align:center;padding:14px 24px;font-size:12px;border-top:1px solid rgba(255,255,255,.1);}
        .footer-copyright p{opacity:.8;}
        @media(max-width:1024px){.sidebar{transform:translateX(-100%)}.main-content{margin-left:0}.stats-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:768px){.stats-grid{grid-template-columns:1fr}.topbar{flex-direction:column;gap:12px}.welcome-card{flex-direction:column;text-align:center;gap:16px}.content{padding:20px}}
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <?php if ($logo_url): ?>
        <img src="<?= $logo_url ?>" alt="Logo SMK"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div class="logo-placeholder" style="display:none"><i class="fas fa-school"></i></div>
        <?php else: ?>
        <div class="logo-placeholder"><i class="fas fa-school"></i></div>
        <?php endif; ?>
        <div class="logo-info">
            <h3>CBT Sekolah</h3>
            <p>SMK Negeri 8 Bandar Lampung</p>
        </div>
    </div>

    <ul class="nav-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="bank_soal.php"><i class="fas fa-database"></i> Bank Soal</a></li>
        <li><a href="monitoring.php"><i class="fas fa-chart-line"></i> Monitoring Ujian</a></li>
        <li><a href="nilai_ujian.php"><i class="fas fa-star"></i> Nilai</a></li>
        <li><a href="setting_ujian.php"><i class="fas fa-calendar-alt"></i> Rencana Ujian</a></li>
        <!-- MENU ABSENSI TELAH DIHAPUS -->
    </ul>

    <div class="sidebar-footer">
        <a href="../../logout.php" class="logout-btn"
           onclick="return confirm('Yakin ingin logout?')">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="topbar">
        <div class="page-title">
            <h1>Dashboard Guru</h1>
            <p>SMK Negeri 8 Bandar Lampung</p>
        </div>
        <div class="topbar-right">
            <div class="datetime">
                <div class="date"><?= $tgl_lengkap ?></div>
                <div class="time" id="liveTime"><?= $jam_sekarang ?></div>
            </div>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($nama_lengkap) ?></div>
                    <div class="user-role"><?= htmlspecialchars($username) ?></div>
                </div>
                <div class="user-avatar"><?= $inisial ?></div>
            </div>
        </div>
    </div>

    <div class="content">
        <!-- WELCOME -->
        <div class="welcome-card">
            <div class="welcome-text">
                <h2>Selamat Datang, <?= htmlspecialchars($nama_lengkap) ?>! 👋</h2>
                <p>Semua sistem berjalan normal. Gunakan menu di samping untuk mengakses fitur utama.</p>
            </div>
            <div>
                <a href="tambah_ujian.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Buat Ujian
                </a>
                <a href="bank_soal.php" class="btn-outline">
                    <i class="fas fa-book"></i> Bank Soal
                </a>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Ujian</span>
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="stat-value"><?= $total_ujian ?></div>
                <div class="stat-footer">Ujian Aktif: <strong><?= $ujian_aktif ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Bank Soal</span>
                    <div class="stat-icon"><i class="fas fa-database"></i></div>
                </div>
                <div class="stat-value"><?= $total_soal ?></div>
                <div class="stat-footer">
                    <a href="bank_soal.php" style="color:var(--accent-1);">Kelola Soal →</a>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Siswa</span>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value"><?= $total_siswa ?></div>
                <div class="stat-footer">Mengerjakan ujian Anda</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Rata-rata Nilai</span>
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="stat-value"><?= $rata_nilai ?></div>
                <div class="stat-footer">Dari seluruh ujian</div>
            </div>
        </div>

        <!-- UJIAN TERBARU -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-clock"></i> Ujian Terbaru</span>
                <a href="setting_ujian.php" class="btn-link">Lihat Semua →</a>
            </div>
            <div class="table-wrapper">
                <?php if ($ujian_terbaru && $ujian_terbaru->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr><th>Nama Ujian</th><th>Mata Pelajaran</th><th>Tanggal</th><th>Peserta</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($ujian = $ujian_terbaru->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ujian['nama_ujian']??'-') ?></strong></td>
                            <td><?= htmlspecialchars($ujian['mata_pelajaran']??'-') ?></td>
                            <td><?= formatTanggal($ujian['tanggal_mulai']??'') ?></td>
                            <td><?= $ujian['peserta']??0 ?> siswa</td>
                            <td>
                                <?php $s=$ujian['status']??'draft';
                                $bc=$s==='aktif'?'badge-success':($s==='selesai'?'badge-secondary':'badge-warning'); ?>
                                <span class="badge <?= $bc ?>"><?= ucfirst($s) ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                    <p>Belum ada ujian yang dibuat</p>
                    <a href="tambah_ujian.php" style="color:var(--accent-1);">Buat ujian pertama →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHART -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-chart-bar"></i> Statistik Nilai Ujian</span>
            </div>
            <div class="chart-container">
                <?php if (!empty($chart_labels)): ?>
                <canvas id="nilaiChart" style="max-height:280px;width:100%;"></canvas>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-chart-pie"></i></div>
                    <p>Belum ada data nilai untuk ditampilkan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="footer-copyright">
        <p>&copy; <?= date('Y') ?> <strong>SMK Negeri 8 Bandar Lampung</strong> - All Rights Reserved.</p>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const day = String(now.getDate()).padStart(2,'0');
    const month = String(now.getMonth()+1).padStart(2,'0');
    document.querySelector('.date').textContent = days[now.getDay()]+', '+day+'/'+month+'/'+now.getFullYear();
    document.getElementById('liveTime').textContent = h+':'+m+' WIB';
}
setInterval(updateClock, 1000);
updateClock();
</script>

<?php if (!empty($chart_labels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('nilaiChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Rata-rata Nilai',
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: 'rgba(102,153,155,0.2)',
            borderColor: '#66999B',
            borderWidth: 2,
            borderRadius: 8,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: '#E8E1D3' } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>