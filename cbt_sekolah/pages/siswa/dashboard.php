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

// Ambil data siswa dari tb_users
$id_siswa = $_SESSION['id_user'];
$query_siswa = "SELECT * FROM tb_users WHERE id_user = '$id_siswa' AND role = 'siswa'";
$result_siswa = mysqli_query($conn, $query_siswa);
$siswa = mysqli_fetch_assoc($result_siswa);

// Prioritaskan Nama Lengkap, jika kosong otomatis pakai Username akun
$nama = !empty($siswa['nama_lengkap']) ? $siswa['nama_lengkap'] : ($siswa['username'] ?? 'Siswa');
$nis = $siswa['nis'] ?? '-';
$id_kelas = $siswa['id_kelas'] ?? 0;

// Ambil nama kelas
$query_kelas = "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = '$id_kelas'";
$result_kelas = mysqli_query($conn, $query_kelas);
$kelas_data = mysqli_fetch_assoc($result_kelas);
$kelas = $kelas_data['nama_kelas'] ?? '-';

/* ===============================
   DATA DASHBOARD DARI DATABASE
=================================*/

// Total ujian untuk kelas siswa
$query_total = "SELECT COUNT(DISTINCT u.id_ujian) as total 
                FROM tb_ujian u 
                JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian 
                WHERE uk.id_kelas = '$id_kelas' 
                AND u.status_ujian = 'aktif'";
$result_total = mysqli_query($conn, $query_total);
$total_ujian = mysqli_fetch_assoc($result_total)['total'] ?? 0;

// Ujian yang sudah dikerjakan (selesai)
$query_selesai = "SELECT COUNT(DISTINCT id_ujian) as selesai 
                  FROM tb_hasil_ujian 
                  WHERE id_user = '$id_siswa'"; 
$result_selesai = mysqli_query($conn, $query_selesai);
$selesai_ujian = mysqli_fetch_assoc($result_selesai)['selesai'] ?? 0;

// Ujian yang belum dikerjakan
$belum_ujian = max(0, $total_ujian - $selesai_ujian);

// Hitung persentase progress
$persentase_progress = ($total_ujian > 0) ? round(($selesai_ujian / $total_ujian) * 100) : 0;

// Ujian hari ini
$tanggal_hari_ini = date('Y-m-d');
$query_hari_ini = "SELECT COUNT(*) as hari_ini 
                   FROM tb_ujian u
                   JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
                   WHERE uk.id_kelas = '$id_kelas' 
                   AND u.tanggal_ujian = '$tanggal_hari_ini'
                   AND u.status_ujian = 'aktif'";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$jadwal_hari = mysqli_fetch_assoc($result_hari_ini)['hari_ini'] ?? 0;

// Ambil jadwal ujian terdekat
$query_jadwal = "SELECT u.*, m.nama_mapel
                 FROM tb_ujian u
                 JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
                 JOIN tb_mapel m ON u.id_mapel = m.id_mapel
                 WHERE uk.id_kelas = '$id_kelas' 
                 AND u.status_ujian = 'aktif'
                 AND u.tanggal_ujian >= '$tanggal_hari_ini'
                 ORDER BY u.tanggal_ujian ASC, u.jam_mulai ASC
                 LIMIT 5";
$result_jadwal = mysqli_query($conn, $query_jadwal);
$jadwal_ujian = [];
while ($row = mysqli_fetch_assoc($result_jadwal)) {
    $jadwal_ujian[] = $row;
}

// Ambil semua tanggal ujian untuk kalender
$query_tanggal = "SELECT DISTINCT u.tanggal_ujian as tanggal 
                  FROM tb_ujian u
                  JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
                  WHERE uk.id_kelas = '$id_kelas'
                  AND u.status_ujian = 'aktif'";
$result_tanggal = mysqli_query($conn, $query_tanggal);
$tanggal_ujian_list = [];
while ($row = mysqli_fetch_assoc($result_tanggal)) {
    $tanggal_ujian_list[] = $row['tanggal'];
}

$logo_path = '../../assets/img/logo-smk8.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Portal Siswa - <?= htmlspecialchars($nama) ?></title>
    
    <!-- Menggunakan Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .nav-link.active { color: #2563eb; font-weight: 700; border-bottom: 2px solid #2563eb; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; }
        .calendar-day { font-size: 11px; font-weight: 700; color: #64748b; padding-bottom: 8px; }
        .calendar-date { font-size: 13px; font-weight: 600; padding: 6px; border-radius: 8px; transition: 0.2s; }
        .calendar-date.today { background: #2563eb; color: white; }
        .calendar-date.has-exam { background: #fef08a; color: #854d0e; }
    </style>
</head>
<body class="text-slate-800">

    <!-- TOP NAVIGATION BAR MODERN -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <!-- PENGAMAN UKURAN LOGO (style="width:40px; height:40px;") -->
                    <img src="<?= $logo_path ?>" alt="Logo SMK" style="width: 40px; height: 40px; object-fit: contain;" class="h-10 w-10 object-contain" onerror="this.src='https://placehold.co/40x40?text=SMK'">
                    <div class="hidden sm:block flex-col">
                        <span class="text-lg font-extrabold text-slate-800 tracking-tight leading-none block">CBT PORTAL</span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block">SMK N 8 Bandar Lampung</span>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="nav-link active py-5 text-sm transition hover:text-blue-600"><i class="fas fa-home mr-1.5"></i> Dashboard</a>
                    <a href="ujian.php" class="nav-link text-slate-600 py-5 text-sm transition hover:text-blue-600"><i class="fas fa-laptop-code mr-1.5"></i> Ruang Ujian</a>
                    <a href="kartu_ujian.php" class="nav-link text-slate-600 py-5 text-sm transition hover:text-blue-600"><i class="fas fa-id-badge mr-1.5"></i> Kartu Ujian</a>
                    <a href="absensi.php" class="nav-link text-slate-600 py-5 text-sm transition hover:text-blue-600"><i class="fas fa-clipboard-user mr-1.5"></i> Absensi</a>
                </div>

                <!-- Right Side (Time & Profile) -->
                <div class="flex items-center gap-4">
                    <div class="hidden lg:block bg-slate-100 border border-slate-200 px-4 py-1.5 rounded-full text-sm font-bold text-slate-600 shadow-inner">
                        <i class="far fa-clock text-blue-500 mr-1.5"></i><span id="jam">--:--:-- WIB</span>
                    </div>
                    
                    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-slate-800 leading-tight"><?= htmlspecialchars($nama) ?></div>
                            <div class="text-xs font-semibold text-blue-600"><?= htmlspecialchars($kelas) ?></div>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-md">
                            <?= strtoupper(substr($nama, 0, 1)) ?>
                        </div>
                        <button onclick="confirmLogout()" class="ml-2 text-slate-400 hover:text-red-500 transition" title="Logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- MOBILE MENU (Bottom Navigation style for smaller screens) -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50 flex justify-around pb-safe">
        <a href="dashboard.php" class="flex flex-col items-center py-3 px-4 text-blue-600"><i class="fas fa-home text-xl mb-1"></i><span class="text-[10px] font-bold">Beranda</span></a>
        <a href="ujian.php" class="flex flex-col items-center py-3 px-4 text-slate-500 hover:text-blue-600"><i class="fas fa-laptop-code text-xl mb-1"></i><span class="text-[10px] font-bold">Ujian</span></a>
        <a href="kartu_ujian.php" class="flex flex-col items-center py-3 px-4 text-slate-500 hover:text-blue-600"><i class="fas fa-id-badge text-xl mb-1"></i><span class="text-[10px] font-bold">Kartu</span></a>
    </div>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mb-16 md:mb-0">
        
        <!-- HERO WELCOME BANNER -->
        <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 rounded-3xl p-8 sm:p-10 text-white shadow-xl mb-8 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
            <!-- Decorative background elements -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white opacity-5 mix-blend-overlay"></div>
            <div class="absolute bottom-0 right-32 -mb-12 w-40 h-40 rounded-full bg-white opacity-10 mix-blend-overlay"></div>

            <div class="relative z-10 w-full md:w-2/3">
                <span class="inline-block py-1 px-3 rounded-full bg-white/20 backdrop-blur-sm text-xs font-bold tracking-wider mb-4 border border-white/20">TAHUN PELAJARAN AKTIF</span>
                <h1 class="text-3xl sm:text-4xl font-extrabold mb-2 tracking-tight">Halo, <?= htmlspecialchars($nama) ?>! 👋</h1>
                <p class="text-blue-100 text-sm sm:text-base mb-6 max-w-lg">Persiapkan dirimu dengan baik. Periksa jadwal ujian di bawah dan pastikan koneksi internet stabil sebelum mulai mengerjakan soal.</p>
                
                <a href="ujian.php" class="inline-flex items-center justify-center bg-white text-blue-700 hover:bg-blue-50 font-bold py-3 px-6 rounded-xl transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-play-circle mr-2 text-lg"></i> Mulai Ujian Sekarang
                </a>
            </div>

            <div class="relative z-10 w-full md:w-1/3 bg-black/20 backdrop-blur-md rounded-2xl p-5 border border-white/10">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-blue-100">Progres Ujianmu</span>
                    <span class="text-sm font-bold text-white"><?= $persentase_progress ?>%</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-black/30 rounded-full h-2.5 mb-4">
                    <div class="bg-gradient-to-r from-green-400 to-emerald-300 h-2.5 rounded-full shadow-[0_0_10px_rgba(52,211,153,0.5)] transition-all duration-1000" style="width: <?= $persentase_progress ?>%"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-black text-white"><?= $selesai_ujian ?></div>
                        <div class="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Telah Selesai</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-white"><?= $belum_ujian ?></div>
                        <div class="text-[10px] font-bold text-blue-200 uppercase tracking-wider">Belum Dikerjakan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK STATS GRID -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:border-blue-200 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl shadow-inner"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="text-2xl font-black text-slate-800 leading-none"><?= $total_ujian ?></div>
                    <div class="text-xs font-semibold text-slate-500 mt-1">Total Mapel</div>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:border-orange-200 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl shadow-inner"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="text-2xl font-black text-slate-800 leading-none"><?= $belum_ujian ?></div>
                    <div class="text-xs font-semibold text-slate-500 mt-1">Sisa Ujian</div>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:border-emerald-200 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shadow-inner"><i class="fas fa-check-double"></i></div>
                <div>
                    <div class="text-2xl font-black text-slate-800 leading-none"><?= $selesai_ujian ?></div>
                    <div class="text-xs font-semibold text-slate-500 mt-1">Telah Lulus</div>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:border-purple-200 transition">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl shadow-inner"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <div class="text-2xl font-black text-slate-800 leading-none"><?= $jadwal_hari ?></div>
                    <div class="text-xs font-semibold text-slate-500 mt-1">Ujian Hari Ini</div>
                </div>
            </div>
        </div>

        <!-- MAIN GRID CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT: JADWAL TERDEKAT (TAKES 2 COLUMNS) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between pb-2 border-b-2 border-slate-200">
                    <h3 class="text-lg font-black text-slate-800"><i class="fas fa-clipboard-list text-blue-600 mr-2"></i> Daftar Ujian Aktif & Terdekat</h3>
                    <a href="ujian.php" class="text-sm font-bold text-blue-600 hover:text-blue-800">Lihat Semua <i class="fas fa-arrow-right text-xs ml-1"></i></a>
                </div>

                <?php if (count($jadwal_ujian) > 0): ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($jadwal_ujian as $jadwal): 
                            $is_today = ($jadwal['tanggal_ujian'] == $tanggal_hari_ini);
                        ?>
                            <div class="glass-card rounded-2xl p-5 border-l-4 <?= $is_today ? 'border-l-blue-600 bg-blue-50/30' : 'border-l-slate-300' ?> hover:shadow-md transition group">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-xl <?= $is_today ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center text-xl shrink-0">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-lg leading-tight group-hover:text-blue-700 transition"><?= htmlspecialchars($jadwal['nama_mapel']) ?></h4>
                                            <div class="flex flex-wrap items-center gap-3 mt-2 text-xs font-semibold text-slate-500">
                                                <span class="flex items-center"><i class="far fa-calendar-alt mr-1.5"></i> <?= date('d M Y', strtotime($jadwal['tanggal_ujian'])) ?></span>
                                                <span class="flex items-center"><i class="far fa-clock mr-1.5"></i> <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?> WIB</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="w-full sm:w-auto text-right">
                                        <?php if ($is_today): ?>
                                            <a href="kerjakan_ujian.php?id_ujian=<?= $jadwal['id_ujian'] ?>" class="block w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-sm transition shadow-md shadow-blue-200">
                                                Kerjakan
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-block py-1.5 px-3 bg-slate-100 text-slate-600 font-bold text-xs rounded-lg border border-slate-200">
                                                <i class="fas fa-hourglass-half mr-1 text-orange-500"></i> Menunggu Jadwal
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="glass-card rounded-3xl p-10 text-center border-dashed border-2 border-slate-200">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-mug-hot text-3xl text-slate-300"></i>
                        </div>
                        <h4 class="text-lg font-bold text-slate-700 mb-1">Belum Ada Ujian Terdekat</h4>
                        <p class="text-sm text-slate-500 max-w-sm mx-auto">Saat ini tidak ada ujian aktif untuk kelas Anda. Silakan istirahat atau pelajari materi pelajaran selanjutnya.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: MINIMAL CALENDAR & INFO -->
            <div class="space-y-6">
                
                <!-- Identitas Cepat -->
                <div class="glass-card rounded-2xl p-6 border-t-4 border-t-indigo-500">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Kartu Identitas Peserta</h3>
                    <div class="flex items-center gap-4 mb-4">
                        <!-- PENGAMAN UKURAN FOTO -->
                        <img src="https://placehold.co/60x60/2563eb/white?text=<?= strtoupper(substr($nama, 0, 2)) ?>" style="width: 56px; height: 56px; object-fit: cover;" class="w-14 h-14 rounded-lg object-cover shadow-sm">
                        <div>
                            <div class="font-bold text-slate-800 leading-tight"><?= htmlspecialchars($nama) ?></div>
                            <div class="text-xs text-slate-500 mt-1">NIS: <?= htmlspecialchars($nis) ?></div>
                        </div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Ruang / Kelas</div>
                        <div class="font-bold text-slate-700"><i class="fas fa-door-open text-indigo-500 mr-2"></i> <?= htmlspecialchars($kelas) ?></div>
                    </div>
                </div>

                <!-- Modern Minimal Calendar -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-bold text-slate-800"><i class="far fa-calendar-check text-blue-600 mr-2"></i> Kalender Ujian</h3>
                        <div class="flex gap-2">
                            <button onclick="prevMonth()" class="w-7 h-7 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition"><i class="fas fa-chevron-left text-xs"></i></button>
                            <button onclick="nextMonth()" class="w-7 h-7 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition"><i class="fas fa-chevron-right text-xs"></i></button>
                        </div>
                    </div>
                    <div class="text-center font-bold text-blue-700 mb-4 text-sm" id="monthYear"></div>
                    
                    <div class="calendar-grid mb-2">
                        <div class="calendar-day">M</div><div class="calendar-day">S</div><div class="calendar-day">S</div>
                        <div class="calendar-day">R</div><div class="calendar-day">K</div><div class="calendar-day">J</div>
                        <div class="calendar-day">S</div>
                    </div>
                    <div class="calendar-grid" id="calendarDates"></div>

                    <div class="flex items-center gap-4 mt-6 pt-4 border-t border-slate-100 justify-center">
                        <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span><span class="text-[10px] font-bold text-slate-500">Hari ini</span></div>
                        <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-300"></span><span class="text-[10px] font-bold text-slate-500">Ada Ujian</span></div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-200 bg-white mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-xs font-semibold text-slate-400">&copy; <?= date('Y') ?> CIDIKNA - CBT System. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <script>
        function confirmLogout() {
            if (confirm('Apakah Anda yakin ingin keluar dari sistem ujian?')) {
                window.location.href = '../../logout.php';
            }
        }

        // Live Clock
        function updateClock() {
            const now = new Date();
            document.getElementById("jam").innerHTML = 
                now.getHours().toString().padStart(2, '0') + ":" + 
                now.getMinutes().toString().padStart(2, '0') + ":" + 
                now.getSeconds().toString().padStart(2, '0') + " WIB";
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Minimal Calendar Logic
        let currentDate = new Date();
        let jadwalUjian = <?= json_encode($tanggal_ujian_list) ?>;

        function renderCalendar(date) {
            const monthYear = document.getElementById("monthYear");
            const calendarDates = document.getElementById("calendarDates");

            const year = date.getFullYear();
            const month = date.getMonth();

            const firstDay = new Date(year, month, 1).getDay();
            const lastDate = new Date(year, month + 1, 0).getDate();

            const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

            monthYear.innerHTML = months[month] + " " + year;
            calendarDates.innerHTML = "";

            for (let i = 0; i < firstDay; i++) {
                calendarDates.innerHTML += "<div></div>";
            }

            let today = new Date();
            
            for (let i = 1; i <= lastDate; i++) {
                let isToday = (i === today.getDate() && month === today.getMonth() && year === today.getFullYear());
                let dateStr = year + "-" + String(month + 1).padStart(2, '0') + "-" + String(i).padStart(2, '0');
                let hasUjian = jadwalUjian.includes(dateStr);

                let classes = "calendar-date ";
                if (isToday) classes += "today shadow-md shadow-blue-200";
                else if (hasUjian) classes += "has-exam shadow-sm";
                else classes += "text-slate-600 hover:bg-slate-100";

                calendarDates.innerHTML += `<div class="${classes}">${i}</div>`;
            }
        }

        function prevMonth() { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate); }
        function nextMonth() { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate); }
        
        renderCalendar(currentDate);
    </script>
</body>
</html>