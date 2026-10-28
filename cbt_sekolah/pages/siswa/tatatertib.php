<?php
session_start();

// Cek login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'siswa') {
    header("Location: ../../index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Koneksi database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'db_ujian_sekolah';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil data siswa
$id_siswa = $_SESSION['id_user'];
$query_siswa = "SELECT * FROM tb_users WHERE id_user = '$id_siswa' AND role = 'siswa'";
$result_siswa = mysqli_query($conn, $query_siswa);
$siswa = mysqli_fetch_assoc($result_siswa);

$nama = $siswa['nama_lengkap'];
$nis = $siswa['nis'];

// Ambil data kartu ujian untuk foto
$query_kartu = "SELECT * FROM tb_kartu_ujian WHERE nis = '$nis'";
$result_kartu = mysqli_query($conn, $query_kartu);
$kartu = mysqli_fetch_assoc($result_kartu);

// Ambil semua tata tertib yang aktif (status = 'Aktif')
// Urutkan berdasarkan kategori: Ujian, Siswa, Guru, Umum
$query_tatatertib = "SELECT * FROM tb_tata_tertib 
                     WHERE status = 'Aktif' 
                     ORDER BY FIELD(kategori, 'Ujian', 'Siswa', 'Guru', 'Umum'), id DESC";
$result_tatatertib = mysqli_query($conn, $query_tatatertib);
$tatatertib_list = [];
while ($row = mysqli_fetch_assoc($result_tatatertib)) {
    $tatatertib_list[] = $row;
}

// Kelompokkan berdasarkan kategori
$tatatertib_grouped = [];
foreach ($tatatertib_list as $item) {
    $kategori = $item['kategori'];
    if (!isset($tatatertib_grouped[$kategori])) {
        $tatatertib_grouped[$kategori] = [];
    }
    $tatatertib_grouped[$kategori][] = $item;
}

// Ikon untuk setiap kategori
$icon_map = [
    'Ujian' => 'fas fa-file-alt',
    'Siswa' => 'fas fa-user-graduate',
    'Guru' => 'fas fa-chalkboard-teacher',
    'Umum' => 'fas fa-building'
];

// Warna untuk setiap kategori
$color_map = [
    'Ujian' => '#E58C7C',
    'Siswa' => '#77BFA3',
    'Guru' => '#FFB347',
    'Umum' => '#66999B'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tata Tertib - CBT <?= htmlspecialchars($nama) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
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
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--neutral-1) 0%, var(--neutral-2) 100%);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-1) 0%, var(--primary-2) 100%);
            color: white;
            padding: 25px 20px;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logo h2 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo h4 {
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
        }

        .logo p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .user-profile {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .user-profile h5 {
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .user-profile p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .sidebar a i {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar a.text-danger:hover {
            background: var(--danger);
            color: white;
        }

        /* MAIN */
        .main {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 25px 35px;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .school-logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .topbar h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .time-box {
            background: white;
            padding: 12px 24px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            font-size: 14px;
            font-weight: 600;
            text-align: right;
            color: var(--primary-1);
        }

        /* PAGE TITLE */
        .page-title {
            margin-bottom: 25px;
        }

        .page-title h2 {
            font-size: 24px;
            color: var(--primary-1);
            font-weight: 700;
        }

        .page-title p {
            color: #64748b;
            font-size: 14px;
            margin-top: 5px;
        }

        /* INFO BOX */
        .info-box {
            background: linear-gradient(135deg, var(--neutral-1), var(--neutral-2));
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid var(--accent-1);
        }

        .info-box i {
            font-size: 24px;
            color: var(--warning);
        }

        .info-box p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        /* TATA TERTIB CONTENT */
        .tatatertib-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .tatatertib-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .tatatertib-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(43, 58, 103, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            padding: 18px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header i {
            font-size: 28px;
            color: var(--accent-1);
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        .card-header .badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: auto;
            color: white;
        }

        .card-body {
            padding: 25px;
        }

        .tatatertib-isi {
            line-height: 1.8;
            color: var(--dark);
            font-size: 14px;
            white-space: pre-line;
        }

        .tatatertib-isi p {
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            padding: 20px;
            border-top: 1px solid var(--neutral-2);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
                transition: 0.3s;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
                color: white;
                width: 42px;
                height: 42px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
        }

        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .card-header {
                flex-wrap: wrap;
            }
            .card-header .badge {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Menu Toggle untuk Mobile -->
<div class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <h2>CBT</h2>
        <h4>SMK N 8 BANDAR LAMPUNG</h4>
        <p>Sistem Ujian Online</p>
    </div>

    <div class="user-profile">
        <h5><?= htmlspecialchars($nama) ?></h5>
        <p>NIS: <?= htmlspecialchars($nis) ?></p>
    </div>

    <a href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="tatatertib.php" class="active">
        <i class="fas fa-gavel"></i> Tata Tertib
    </a>
    <a href="kartu_ujian.php">
        <i class="fas fa-id-card"></i> Kartu Ujian
    </a>
    <a href="absensi.php">
        <i class="fas fa-check-circle"></i> Absensi
    </a>
    <a href="ujian.php">
        <i class="fas fa-pen"></i> Ujian
    </a>
    <a href="javascript:void(0)" onclick="confirmLogout()" style="color: var(--danger);">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- MAIN -->
<div class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <img src="../../assets/img/logo_sekolah.png" 
                 alt="Logo Sekolah" 
                 class="school-logo"
                 onerror="this.src='https://placehold.co/55x55/2B3A67/white?text=SMK'">
            <h1>Tata Tertib</h1>
        </div>
        <div class="time-box" id="jam"></div>
    </div>

    <!-- PAGE TITLE -->
    <div class="page-title">
        <h2><i class="fas fa-gavel"></i> Peraturan & Tata Tertib</h2>
        <p>Berikut adalah peraturan dan tata tertib yang harus dipatuhi selama proses pembelajaran dan ujian berlangsung</p>
    </div>

    <!-- INFO BOX -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <p>Tata tertib ini dapat berubah sewaktu-waktu sesuai dengan kebijakan sekolah. Pastikan Anda selalu membaca dan mematuhi peraturan yang berlaku.</p>
    </div>

    <!-- TATA TERTIB CONTENT -->
    <div class="tatatertib-container">
        <?php if (!empty($tatatertib_grouped)): ?>
            <?php foreach ($tatatertib_grouped as $kategori => $items): ?>
                <?php 
                $icon = $icon_map[$kategori] ?? 'fas fa-gavel';
                $kategori_display = ($kategori == 'Ujian') ? 'Tata Tertib Ujian' : (($kategori == 'Siswa') ? 'Tata Tertib Siswa' : (($kategori == 'Guru') ? 'Tata Tertib Guru' : 'Tata Tertib Umum'));
                ?>
                <div class="tatatertib-card">
                    <div class="card-header">
                        <i class="<?= $icon ?>"></i>
                        <h3><?= htmlspecialchars($kategori_display) ?></h3>
                        <span class="badge">Berlaku</span>
                    </div>
                    <div class="card-body">
                        <?php foreach ($items as $index => $item): ?>
                            <?php if (!empty($item['judul'])): ?>
                                <h4 style="color: var(--primary-1); margin-bottom: 15px; font-size: 16px; font-weight: 600;">
                                    <?= htmlspecialchars($item['judul']) ?>
                                </h4>
                            <?php endif; ?>
                            <div class="tatatertib-isi">
                                <?= nl2br(htmlspecialchars($item['deskripsi'])) ?>
                            </div>
                            <?php if ($index < count($items) - 1): ?>
                                <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--neutral-2);">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Jika tidak ada data tata tertib -->
            <div class="tatatertib-card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>Informasi</h3>
                </div>
                <div class="card-body">
                    <div class="tatatertib-isi" style="text-align: center; padding: 40px;">
                        <i class="fas fa-file-alt" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                        <p>Belum ada tata tertib yang ditetapkan.</p>
                        <p style="font-size: 12px; margin-top: 10px;">Silahkan hubungi administrator sekolah.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> CIDIKA_SMKN8BANDARLAMPUNG
    </div>
</div>

<script>
// Fungsi toggle sidebar untuk mobile
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Konfirmasi logout
function confirmLogout() {
    if (confirm('Apakah Anda yakin ingin keluar dari sistem?')) {
        window.location.href = '../../logout.php';
    }
}

// Jam digital
function updateClock() {
    let now = new Date();
    let jam = now.getHours().toString().padStart(2, '0');
    let menit = now.getMinutes().toString().padStart(2, '0');
    let detik = now.getSeconds().toString().padStart(2, '0');

    document.getElementById("jam").innerHTML = jam + ":" + menit + ":" + detik + " WIB";
}

setInterval(updateClock, 1000);
updateClock();

// Close sidebar saat klik di luar (mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');

    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove('open');
        }
    }
});
</script>

</body>
</html>