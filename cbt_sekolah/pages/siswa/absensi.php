<?php
// =============================================
// FILE: pages/siswa/absensi.php
// Siswa input absensi → masuk ke verifikasi pengawas
// =============================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../../index.php"); exit;
}

$conn = new mysqli('localhost', 'root', '', 'db_ujian_sekolah');
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
$conn->set_charset("utf8");

$id_siswa   = $_SESSION['id_user'] ?? 0;
$nama_siswa = $_SESSION['nama_lengkap'] ?? '';

// Ambil data kelas siswa
$q = $conn->query("
    SELECT u.*, k.nama_kelas 
    FROM tb_users u 
    LEFT JOIN tb_kelas k ON k.id_kelas = u.id_kelas 
    WHERE u.id_user = $id_siswa LIMIT 1
");
$siswa      = $q ? $q->fetch_assoc() : [];
$nama_kelas = $siswa['nama_kelas'] ?? '-';
$nis        = $siswa['nis'] ?? '-';

$pesan = '';
$error = '';
$tanggal_hari_ini = date('Y-m-d');
$jam_sekarang     = date('H:i:s');

// ── Cek apakah sudah absen hari ini ──
$cek = $conn->query("
    SELECT * FROM absensi_siswa 
    WHERE nama_siswa = '" . $conn->real_escape_string($nama_siswa) . "'
      AND tanggal = '$tanggal_hari_ini'
    ORDER BY id DESC LIMIT 1
");
$sudah_absen  = ($cek && $cek->num_rows > 0);
$data_absen   = $sudah_absen ? $cek->fetch_assoc() : null;

// ── PROSES SUBMIT ABSENSI ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_absensi'])) {
    if ($sudah_absen) {
        $error = 'Anda sudah melakukan absensi hari ini.';
    } else {
        $keterangan  = $_POST['keterangan'] ?? 'Hadir';
        $ket_allowed = ['Hadir', 'Sakit', 'Izin'];
        $nama_surat  = NULL; // Default tanpa surat
        
        if (!in_array($keterangan, $ket_allowed)) {
            $error = 'Keterangan tidak valid.';
        } else {
            // PROSES UPLOAD SURAT JIKA SAKIT
            if ($keterangan === 'Sakit') {
                if (isset($_FILES['surat_sakit']) && $_FILES['surat_sakit']['error'] === 0) {
                    $upload_dir = '../../uploads/surat_sakit/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $file_tmp = $_FILES['surat_sakit']['tmp_name'];
                    $file_name = $_FILES['surat_sakit']['name'];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $valid_ext = ['jpg', 'jpeg', 'png'];
                    
                    if (in_array($ext, $valid_ext)) {
                        $nama_surat = 'surat_' . time() . '_' . uniqid() . '.' . $ext;
                        move_uploaded_file($file_tmp, $upload_dir . $nama_surat);
                    } else {
                        $error = 'Format file surat sakit harus JPG atau PNG.';
                    }
                } else {
                    $error = 'Surat dokter/keterangan sakit wajib diunggah!';
                }
            }

            // LANJUTKAN SIMPAN KE DATABASE JIKA TIDAK ADA ERROR
            if (empty($error)) {
                $nm  = $conn->real_escape_string($nama_siswa);
                $kls = $conn->real_escape_string($nama_kelas);
                $ket = $conn->real_escape_string($keterangan);
                
                // Masukkan nilai NULL jika tidak ada surat (Hadir/Izin)
                $surat_val = $nama_surat ? "'$nama_surat'" : "NULL";

                $insert = $conn->query("
                    INSERT INTO absensi_siswa 
                        (id_user, nama_siswa, kelas, tanggal, jam, keterangan, status_verifikasi, surat_sakit, created_at)
                    VALUES 
                        ($id_siswa, '$nm', '$kls', '$tanggal_hari_ini', '$jam_sekarang', '$ket', 'menunggu', $surat_val, NOW())
                ");

                if ($insert) {
                    $pesan = 'Absensi berhasil dikirim! Menunggu verifikasi pengawas.';
                    $cek2 = $conn->query("SELECT * FROM absensi_siswa WHERE id = " . $conn->insert_id);
                    $data_absen  = $cek2 ? $cek2->fetch_assoc() : null;
                    $sudah_absen = true;
                } else {
                    $error = 'Gagal menyimpan absensi: ' . $conn->error;
                }
            }
        }
    }
}

// ── Riwayat absensi 30 hari terakhir ──
$nm_esc   = $conn->real_escape_string($nama_siswa);
$riwayat  = $conn->query("
    SELECT * FROM absensi_siswa 
    WHERE nama_siswa = '$nm_esc'
    ORDER BY tanggal DESC, id DESC
    LIMIT 30
");
$data_riwayat = [];
if ($riwayat) while ($r = $riwayat->fetch_assoc()) $data_riwayat[] = $r;

// Hitung statistik
$stat = ['hadir'=>0,'sakit'=>0,'izin'=>0,'menunggu'=>0];
foreach ($data_riwayat as $r) {
    if ($r['status_verifikasi'] === 'menunggu') $stat['menunggu']++;
    elseif ($r['keterangan'] === 'Hadir')       $stat['hadir']++;
    elseif ($r['keterangan'] === 'Sakit')        $stat['sakit']++;
    elseif ($r['keterangan'] === 'Izin')         $stat['izin']++;
}

$logo_path = '../../assets/img/logo-smk8.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Absensi Siswa – CBT Sekolah</title>
    
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

        /* Custom Radio Button Styling */
        .absen-radio:checked + .absen-label.hadir { border-color: #10b981; background-color: #ecfdf5; color: #047857; }
        .absen-radio:checked + .absen-label.sakit { border-color: #f59e0b; background-color: #fffbeb; color: #b45309; }
        .absen-radio:checked + .absen-label.izin { border-color: #3b82f6; background-color: #eff6ff; color: #1d4ed8; }
        
        .upload-area { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-slate-800">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <img src="<?= $logo_path ?>" alt="Logo SMK" style="width: 40px; height: 40px; object-fit: contain;" class="h-10 w-10 object-contain" onerror="this.src='https://placehold.co/40x40?text=SMK'">
                    <div class="hidden sm:block flex-col">
                        <span class="text-lg font-extrabold text-slate-800 tracking-tight leading-none block">CBT PORTAL</span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block">SMK N 8 Bandar Lampung</span>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="nav-link text-slate-600 py-5 text-sm transition hover:text-blue-600"><i class="fas fa-home mr-1.5"></i> Dashboard</a>
                    <a href="ujian.php" class="nav-link text-slate-600 py-5 text-sm transition hover:text-blue-600"><i class="fas fa-laptop-code mr-1.5"></i> Ruang Ujian</a>
                    <a href="kartu_ujian.php" class="nav-link text-slate-600 py-5 text-sm transition hover:text-blue-600"><i class="fas fa-id-badge mr-1.5"></i> Kartu Ujian</a>
                    <a href="absensi.php" class="nav-link active py-5 text-sm transition hover:text-blue-600"><i class="fas fa-clipboard-user mr-1.5"></i> Absensi</a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden lg:block bg-slate-100 border border-slate-200 px-4 py-1.5 rounded-full text-sm font-bold text-slate-600 shadow-inner">
                        <i class="far fa-clock text-blue-500 mr-1.5"></i><span id="jam">--:--:-- WIB</span>
                    </div>
                    
                    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-slate-800 leading-tight"><?= htmlspecialchars($nama_siswa) ?></div>
                            <div class="text-xs font-semibold text-blue-600"><?= htmlspecialchars($nama_kelas) ?></div>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-md">
                            <?= strtoupper(substr($nama_siswa, 0, 1)) ?>
                        </div>
                        <button onclick="confirmLogout()" class="ml-2 text-slate-400 hover:text-red-500 transition" title="Logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50 flex justify-around pb-safe">
        <a href="dashboard.php" class="flex flex-col items-center py-3 px-4 text-slate-500 hover:text-blue-600"><i class="fas fa-home text-xl mb-1"></i><span class="text-[10px] font-bold">Beranda</span></a>
        <a href="ujian.php" class="flex flex-col items-center py-3 px-4 text-slate-500 hover:text-blue-600"><i class="fas fa-laptop-code text-xl mb-1"></i><span class="text-[10px] font-bold">Ujian</span></a>
        <a href="kartu_ujian.php" class="flex flex-col items-center py-3 px-4 text-slate-500 hover:text-blue-600"><i class="fas fa-id-badge text-xl mb-1"></i><span class="text-[10px] font-bold">Kartu</span></a>
        <a href="absensi.php" class="flex flex-col items-center py-3 px-4 text-blue-600"><i class="fas fa-clipboard-user text-xl mb-1"></i><span class="text-[10px] font-bold">Absensi</span></a>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mb-16 md:mb-0">
        
        <div class="mb-8">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight"><i class="fas fa-user-check text-blue-600 mr-2"></i> Absensi Kehadiran</h1>
            <p class="text-slate-500 text-sm mt-1">Catat kehadiran Anda hari ini sebelum memulai ujian.</p>
        </div>

        <?php if ($pesan): ?>
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-lg shadow-sm mb-6 flex items-center gap-3">
                <i class="fas fa-check-circle text-xl"></i>
                <span class="font-semibold text-sm"><?= htmlspecialchars($pesan) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-sm mb-6 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <span class="font-semibold text-sm"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                
                <div class="glass-card rounded-2xl p-6 sm:p-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-full -mr-10 -mt-10 z-0"></div>

                    <div class="relative z-10">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                            <i class="far fa-calendar-alt text-blue-500"></i> Status Absensi Hari Ini 
                            <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded-full text-slate-500 ml-2"><?= date('d M Y') ?></span>
                        </h3>

                        <?php if ($sudah_absen && $data_absen): ?>
                            <?php
                                $st  = $data_absen['status_verifikasi'];
                                $ket = $data_absen['keterangan'];
                                
                                $bg_class = $st === 'disetujui' ? 'bg-emerald-50 border-emerald-200' : ($st === 'ditolak' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200');
                                $icon_class = $st === 'disetujui' ? 'text-emerald-500 fa-check-circle' : ($st === 'ditolak' ? 'text-red-500 fa-times-circle' : 'text-amber-500 fa-clock');
                                $title = $st === 'disetujui' ? 'Telah Disetujui Pengawas' : ($st === 'ditolak' ? 'Absensi Ditolak' : 'Menunggu Verifikasi Pengawas');
                            ?>
                            
                            <div class="border-2 rounded-xl p-5 flex flex-col sm:flex-row items-center sm:items-start gap-5 <?= $bg_class ?>">
                                <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0">
                                    <i class="fas <?= $icon_class ?> text-3xl"></i>
                                </div>
                                <div class="text-center sm:text-left flex-1">
                                    <h4 class="text-lg font-bold text-slate-800 mb-1"><?= $title ?></h4>
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4 text-sm text-slate-600 font-medium">
                                        <span class="bg-white px-3 py-1 rounded-md shadow-sm border border-slate-100">Ket: <span class="font-bold text-slate-800"><?= $ket ?></span></span>
                                        <span class="bg-white px-3 py-1 rounded-md shadow-sm border border-slate-100">Jam: <span class="font-bold text-slate-800"><?= substr($data_absen['jam'],0,5) ?> WIB</span></span>
                                    </div>
                                    
                                    <?php if ($ket === 'Sakit' && !empty($data_absen['surat_sakit'])): ?>
                                        <div class="mt-4 text-xs font-bold text-amber-700 bg-amber-100/50 inline-block px-3 py-1.5 rounded-lg">
                                            <i class="fas fa-paperclip mr-1"></i> Bukti Surat Dokter Telah Dilampirkan
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($st === 'ditolak' && !empty($data_absen['catatan_verifikasi'])): ?>
                                        <div class="mt-4 text-sm font-semibold text-red-700 bg-red-100 inline-block px-4 py-2 rounded-lg border border-red-200">
                                            <i class="fas fa-comment-dots mr-1"></i> Catatan Pengawas: <?= htmlspecialchars($data_absen['catatan_verifikasi']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data">
                                <p class="text-sm font-medium text-slate-500 mb-4">Silakan pilih keterangan kehadiran Anda untuk mengikuti ujian hari ini:</p>
                                
                                <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-6">
                                    <div class="relative">
                                        <input type="radio" name="keterangan" value="Hadir" id="hadir" class="absen-radio hidden peer" onchange="toggleSurat()" checked>
                                        <label for="hadir" class="absen-label hadir flex flex-col items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer transition hover:bg-slate-50 text-slate-400 peer-checked:shadow-md">
                                            <i class="fas fa-check-circle text-2xl sm:text-3xl mb-2"></i>
                                            <span class="font-bold text-sm">Hadir</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" name="keterangan" value="Sakit" id="sakit" class="absen-radio hidden peer" onchange="toggleSurat()">
                                        <label for="sakit" class="absen-label sakit flex flex-col items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer transition hover:bg-slate-50 text-slate-400 peer-checked:shadow-md">
                                            <i class="fas fa-thermometer-half text-2xl sm:text-3xl mb-2"></i>
                                            <span class="font-bold text-sm">Sakit</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" name="keterangan" value="Izin" id="izin" class="absen-radio hidden peer" onchange="toggleSurat()">
                                        <label for="izin" class="absen-label izin flex flex-col items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer transition hover:bg-slate-50 text-slate-400 peer-checked:shadow-md">
                                            <i class="fas fa-envelope-open-text text-2xl sm:text-3xl mb-2"></i>
                                            <span class="font-bold text-sm">Izin</span>
                                        </label>
                                    </div>
                                </div>

                                <div id="upload_area" class="hidden upload-area bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
                                    <label class="block text-sm font-bold text-amber-800 mb-2"><i class="fas fa-file-medical mr-1"></i> Upload Surat Dokter (Wajib)</label>
                                    <p class="text-xs text-amber-600 mb-3">Format yang diizinkan: JPG, JPEG, PNG.</p>
                                    <div class="relative w-full border-2 border-dashed border-amber-300 rounded-lg bg-white hover:bg-amber-50/50 transition px-4 py-6 text-center">
                                        <input type="file" name="surat_sakit" id="input_surat" accept="image/jpeg, image/png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <i class="fas fa-cloud-upload-alt text-2xl text-amber-400 mb-2"></i>
                                        <span class="block text-sm font-semibold text-slate-500">Klik untuk memilih file foto</span>
                                        <span class="block text-xs text-slate-400 mt-1" id="file_name_display">Belum ada file terpilih</span>
                                    </div>
                                </div>

                                <button type="submit" name="kirim_absensi" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-200 transition transform hover:-translate-y-0.5 flex items-center justify-center">
                                    <i class="fas fa-paper-plane mr-2"></i> Kirim Absensi Sekarang
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 sm:p-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fas fa-history text-slate-400 mr-2"></i> Riwayat Absensi (30 Hari)</h3>
                    
                    <?php if (!empty($data_riwayat)): ?>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                                        <th class="p-4 font-bold">Tanggal</th>
                                        <th class="p-4 font-bold">Jam</th>
                                        <th class="p-4 font-bold">Keterangan</th>
                                        <th class="p-4 font-bold">Status</th>
                                        <th class="p-4 font-bold hidden sm:table-cell">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100">
                                    <?php foreach ($data_riwayat as $r): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="p-4 font-semibold text-slate-700"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                        <td class="p-4 text-slate-600"><?= substr($r['jam'],0,5) ?></td>
                                        <td class="p-4">
                                            <?php
                                            $k_bg = $r['keterangan']==='Hadir'?'bg-emerald-100 text-emerald-700':($r['keterangan']==='Sakit'?'bg-amber-100 text-amber-700':'bg-blue-100 text-blue-700');
                                            $k_ic = $r['keterangan']==='Hadir'?'fa-check':($r['keterangan']==='Sakit'?'fa-thermometer-half':'fa-envelope');
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold text-xs <?= $k_bg ?>">
                                                <i class="fas <?= $k_ic ?>"></i> <?= $r['keterangan'] ?>
                                            </span>
                                            
                                            <?php if(!empty($r['surat_sakit'])): ?>
                                                <a href="../../uploads/surat_sakit/<?= $r['surat_sakit'] ?>" target="_blank" class="block mt-2 text-[10px] font-bold text-blue-600 hover:text-blue-800 underline">
                                                    <i class="fas fa-paperclip"></i> Lihat Surat
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php
                                            $sv = $r['status_verifikasi'];
                                            $s_bg = $sv==='disetujui'?'bg-emerald-100 text-emerald-700':($sv==='ditolak'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700');
                                            $s_lb = $sv==='disetujui'?'Disetujui':($sv==='ditolak'?'Ditolak':'Menunggu');
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md font-bold text-xs <?= $s_bg ?>">
                                                <?= $s_lb ?>
                                            </span>
                                        </td>
                                        <td class="p-4 hidden sm:table-cell text-xs text-slate-500 italic max-w-xs truncate">
                                            <?= htmlspecialchars($r['catatan_verifikasi'] ?: '-') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="fas fa-clipboard-list text-4xl text-slate-300 mb-3"></i>
                            <p class="text-slate-500 font-medium">Belum ada riwayat absensi yang tersimpan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-4">
                <div class="glass-card rounded-2xl p-6 border-t-4 border-t-blue-500">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider"><i class="fas fa-chart-pie text-blue-500 mr-2"></i> Rekap Kehadiran</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 text-center">
                            <div class="text-3xl font-black text-emerald-600"><?= $stat['hadir'] ?></div>
                            <div class="text-xs font-bold text-emerald-800 mt-1 uppercase">Hadir</div>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100 text-center">
                            <div class="text-3xl font-black text-amber-500"><?= $stat['sakit'] ?></div>
                            <div class="text-xs font-bold text-amber-700 mt-1 uppercase">Sakit</div>
                        </div>
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 text-center">
                            <div class="text-3xl font-black text-blue-500"><?= $stat['izin'] ?></div>
                            <div class="text-xs font-bold text-blue-700 mt-1 uppercase">Izin</div>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 border border-red-100 text-center">
                            <div class="text-3xl font-black text-red-500"><?= $stat['menunggu'] ?></div>
                            <div class="text-xs font-bold text-red-700 mt-1 uppercase text-[10px]">Belum Verif</div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 bg-gradient-to-br from-slate-800 to-slate-900 text-white">
                    <h3 class="text-sm font-bold text-slate-300 mb-2"><i class="fas fa-info-circle mr-1"></i> Informasi Absensi</h3>
                    <ul class="text-xs text-slate-400 space-y-2 mt-4 leading-relaxed">
                        <li class="flex gap-2"><i class="fas fa-check text-emerald-400 mt-0.5"></i> Absensi dilakukan 1x setiap hari ujian.</li>
                        <li class="flex gap-2"><i class="fas fa-check text-emerald-400 mt-0.5"></i> Jika Anda memilih "Sakit", wajib menyertakan foto surat keterangan dokter.</li>
                        <li class="flex gap-2"><i class="fas fa-check text-emerald-400 mt-0.5"></i> Absensi yang sudah dikirim akan dicek dan diverifikasi oleh Pengawas Ujian secara Real-time.</li>
                    </ul>
                </div>
            </div>

        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-xs font-semibold text-slate-400">&copy; <?= date('Y') ?> CIDIKNA - CBT System. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <script>
        // Logika Logout
        function confirmLogout() {
            if (confirm('Apakah Anda yakin ingin keluar dari sistem ujian?')) {
                window.location.href = '../../logout.php';
            }
        }

        // Jam Realtime
        function updateClock() {
            const now = new Date();
            document.getElementById('jam').textContent = 
                String(now.getHours()).padStart(2,'0') + ':' + 
                String(now.getMinutes()).padStart(2,'0') + ':' + 
                String(now.getSeconds()).padStart(2,'0') + ' WIB';
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Logika Tampil/Sembunyi Form Upload Surat
        function toggleSurat() {
            const isSakit = document.getElementById('sakit').checked;
            const areaUpload = document.getElementById('upload_area');
            const inputSurat = document.getElementById('input_surat');
            
            if (isSakit) {
                areaUpload.classList.remove('hidden');
                inputSurat.required = true;
            } else {
                areaUpload.classList.add('hidden');
                inputSurat.required = false;
                inputSurat.value = ''; // Reset
                document.getElementById('file_name_display').textContent = "Belum ada file terpilih";
            }
        }

        // Tampilkan nama file yang dipilih
        document.getElementById('input_surat')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "Belum ada file terpilih";
            document.getElementById('file_name_display').textContent = fileName;
        });
    </script>
</body>
</html>