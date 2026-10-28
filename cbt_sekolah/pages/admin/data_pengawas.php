<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/koneksi.php';

// =============================================
// INISIALISASI KONEKSI DATABASE
// =============================================

// Cek koneksi yang tersedia (PDO atau MySQLi)
global $conn, $pdo, $db;

// Jika menggunakan PDO dari koneksi.php
if (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
} 
// Jika menggunakan MySQLi dari koneksi.php
else if (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
}
// Jika menggunakan variabel lain atau belum ada koneksi
else {
    // Buat koneksi baru
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'db_ujian_sekolah';
    
    try {
        // Coba menggunakan PDO
        $db = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // Jika PDO gagal, coba MySQLi
        $db = new mysqli($host, $user, $password, $database);
        if ($db->connect_error) {
            die("Koneksi gagal: " . $db->connect_error);
        }
    }
}

// Untuk MySQLi style query
$mysqli = null;
if ($db instanceof mysqli) {
    $mysqli = $db;
} else if ($db instanceof PDO) {
    // Untuk kompatibilitas, buat koneksi MySQLi juga
    $mysqli = new mysqli('localhost', 'root', '', 'db_ujian_sekolah');
    if ($mysqli->connect_error) {
        $mysqli = null;
    }
}

// =============================================
// CEK TABEL DAN AMBIL DATA
// =============================================

// Ambil data pengawas
$pengawas = [];
$total_pengawas = 0;
$total_ruangan = 0;
$total_mapel = 0;
$ruangan_list = [];
$mapel_list = [];
$guru_list = [];

try {
    if ($mysqli) {
        // Gunakan MySQLi
        $query = "SELECT 
                    u.id_user,
                    u.username as nip,
                    u.nama_lengkap,
                    u.ruangan as nama_ruangan
                  FROM tb_users u
                  WHERE u.role = 'guru'
                  ORDER BY u.nama_lengkap ASC";
        
        $result = $mysqli->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pengawas[] = $row;
            }
        }
        
        // Hitung statistik
        $stat_result = $mysqli->query("SELECT COUNT(*) as total FROM tb_users WHERE role = 'guru'");
        if ($stat_result) {
            $total_pengawas = $stat_result->fetch_assoc()['total'] ?? 0;
        }
        
        // Ambil daftar ruangan dari tb_ruangan
        $cek_ruangan = $mysqli->query("SHOW TABLES LIKE 'tb_ruangan'");
        if ($cek_ruangan && $cek_ruangan->num_rows > 0) {
            $ruangan_result = $mysqli->query("SELECT COUNT(*) as total FROM tb_ruangan");
            $total_ruangan = $ruangan_result->fetch_assoc()['total'] ?? 0;
            $ruangan_list = $mysqli->query("SELECT * FROM tb_ruangan ORDER BY nama_ruangan ASC");
        }
        
        // Ambil daftar mapel
        $cek_mapel = $mysqli->query("SHOW TABLES LIKE 'tb_mapel'");
        if ($cek_mapel && $cek_mapel->num_rows > 0) {
            $mapel_result = $mysqli->query("SELECT COUNT(*) as total FROM tb_mapel");
            $total_mapel = $mapel_result->fetch_assoc()['total'] ?? 0;
            $mapel_list = $mysqli->query("SELECT * FROM tb_mapel ORDER BY nama_mapel ASC");
        }
        
    } else if ($db instanceof PDO) {
        // Gunakan PDO
        $query = "SELECT 
                    u.id_user,
                    u.username as nip,
                    u.nama_lengkap,
                    u.ruangan as nama_ruangan
                  FROM tb_users u
                  WHERE u.role = 'guru'
                  ORDER BY u.nama_lengkap ASC";
        
        $stmt = $db->query($query);
        $pengawas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hitung statistik
        $total_pengawas = $db->query("SELECT COUNT(*) FROM tb_users WHERE role = 'guru'")->fetchColumn();
        
        // Ambil daftar ruangan
        try {
            $total_ruangan = $db->query("SELECT COUNT(*) FROM tb_ruangan")->fetchColumn();
            $ruangan_list = $db->query("SELECT * FROM tb_ruangan ORDER BY nama_ruangan ASC");
        } catch (PDOException $e) {
            $total_ruangan = 0;
        }
        
        // Ambil daftar mapel
        try {
            $total_mapel = $db->query("SELECT COUNT(*) FROM tb_mapel")->fetchColumn();
            $mapel_list = $db->query("SELECT * FROM tb_mapel ORDER BY nama_mapel ASC");
        } catch (PDOException $e) {
            $total_mapel = 0;
        }
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Tema warna
$navy = '#1A2E40';
$navyLight = '#2A4055';
$sage = '#9CAF88';
$sageLight = '#B5C7A0';
$orange = '#E69A5C';
$orangeLight = '#F5B07C';
$cream = '#F9F5F0';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengawas Ujian - SMKN 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --navy: #1A2E40;
            --navy-light: #2A4055;
            --sage: #9CAF88;
            --sage-light: #B5C7A0;
            --orange: #E69A5C;
            --orange-light: #F5B07C;
            --cream: #F9F5F0;
            --white: #FFFFFF;
        }

        body {
            background: linear-gradient(135deg, var(--cream) 0%, #EAE7DE 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            padding: 1.5rem;
            margin: 0;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .card {
            background: var(--white);
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(26, 46, 64, 0.1);
            border: 1px solid rgba(156, 175, 136, 0.2);
            transition: all 0.3s ease;
        }

        .header-gradient {
            background: linear-gradient(105deg, var(--navy) 0%, var(--navy-light) 40%, var(--sage) 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-card {
            background: var(--white);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(26, 46, 64, 0.1);
            border-left: 4px solid var(--orange);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-left-width: 6px;
            border-left-color: var(--sage);
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--sage-light), var(--sage));
            color: var(--navy);
        }

        .btn-primary {
            background: var(--navy);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }

        .btn-primary:hover {
            background: var(--navy-light);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--sage);
            color: var(--navy);
            padding: 0.6rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }

        .btn-success:hover {
            background: var(--sage-light);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--orange);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-warning:hover {
            background: var(--orange-light);
            color: var(--navy);
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-3px);
            color: white;
        }

        .badge-nip {
            background: rgba(156, 175, 136, 0.2);
            color: var(--navy);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-ruangan {
            background: var(--orange);
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-info {
            background: #6B7280;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .action-btn {
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: white;
            border: none;
            cursor: pointer;
            margin: 0 0.2rem;
        }

        .action-btn.edit {
            background: var(--orange);
        }
        .action-btn.edit:hover {
            background: var(--orange-light);
            color: var(--navy);
            transform: scale(1.1);
        }

        .action-btn.delete {
            background: #E57373;
        }
        .action-btn.delete:hover {
            background: #EF5350;
            transform: scale(1.1);
        }

        .modal-overlay {
            background: rgba(26, 46, 64, 0.6);
            backdrop-filter: blur(4px);
        }

        .input-custom, .select-custom {
            border: 1px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: all 0.2s ease;
        }

        .input-custom:focus, .select-custom:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(230, 154, 92, 0.2);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease forwards;
        }

        .filter-container {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            border: 1px solid rgba(156, 175, 136, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead tr {
            background: linear-gradient(90deg, var(--navy), var(--navy-light));
            color: white;
        }

        table thead th {
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            text-align: left;
        }

        table tbody tr {
            border-bottom: 1px solid rgba(156, 175, 136, 0.2);
        }

        table tbody tr:hover {
            background-color: rgba(156, 175, 136, 0.1);
        }

        table tbody td {
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
            color: #374151;
        }

        @media print {
            .no-print, .btn-primary, .btn-success, .btn-warning, .btn-back, .filter-container, .action-btn, .stat-card {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>

    <div class="main-container animate-fadeInUp">

        <!-- HEADER CARD -->
        <div class="card p-8 mb-6 header-gradient text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <nav class="text-sm flex items-center gap-2 text-white/80">
                        <a href="dashboard.php" class="hover:text-white transition">Dashboard</a>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                        <span class="text-white font-semibold">Data Pengawas Ujian</span>
                    </nav>

                    <a href="dashboard.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white/30">
                            <i class="fas fa-user-shield text-3xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-1">Data Pengawas Ujian</h1>
                            <p class="text-white/90 flex items-center gap-2">
                                <i class="far fa-building"></i>
                                SMK Negeri 8 Bandar Lampung | Tahun Ajaran <?= date('Y') . '/' . (date('Y') + 1) ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button onclick="bukaModalTambah()" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Tambah Pengawas
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIK CARD -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Pengawas</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_pengawas ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Ruangan</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_ruangan ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Mapel</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_mapel ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER DAN PENCARIAN -->
        <div class="filter-container mb-6 no-print">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex-1 min-w-[250px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="text" id="cariPengawas" onkeyup="filterPengawas()" placeholder="Cari nama atau NIP..."
                               class="input-custom pl-10">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="resetFilter()" class="btn-warning">
                        <i class="fas fa-redo-alt"></i> Reset
                    </button>
                    <button onclick="cetakData()" class="btn-primary">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                </div>
            </div>
        </div>

        <!-- TABEL DATA PENGAWAS -->
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4" style="border-color: rgba(156, 175, 136, 0.2);">
                <h2 class="font-bold text-lg" style="color: var(--navy);">
                    <i class="fas fa-list mr-2" style="color: var(--orange);"></i>
                    Daftar Pengawas Ujian
                </h2>
                <span class="badge-nip">
                    <i class="far fa-clock mr-1"></i> Update: <?= date('d/m/Y H:i') ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <?php if ($total_pengawas > 0): ?>
                    <table class="w-full" id="tabelPengawas">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama Pengawas</th>
                                <th>Ruangan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($pengawas as $row):
                            ?>
                                <tr class="pengawas-row">
                                    <td class="font-mono" style="color: var(--orange); font-weight: 600;"><?= $no++ ?></td>
                                    <td>
                                        <span class="badge-nip">
                                            <i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($row['nip'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="font-medium searchable"><?= htmlspecialchars($row['nama_lengkap'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (!empty($row['nama_ruangan'])): ?>
                                            <span class="badge-ruangan">
                                                <i class="fas fa-door-open mr-1"></i> <?= htmlspecialchars($row['nama_ruangan']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-info">
                                                <i class="fas fa-question-circle mr-1"></i> Belum ditentukan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button onclick="bukaModalEdit(<?= $row['id_user'] ?>, '<?= htmlspecialchars($row['nip'] ?? '') ?>', '<?= htmlspecialchars($row['nama_lengkap'] ?? '') ?>', '<?= htmlspecialchars($row['nama_ruangan'] ?? '') ?>')"
                                                class="action-btn edit" title="Edit Pengawas">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button onclick="konfirmasiHapus(<?= $row['id_user'] ?>)"
                                                class="action-btn delete" title="Hapus Pengawas">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="py-20 text-center">
                        <div class="flex flex-col items-center justify-center" style="color: var(--navy-light);">
                            <i class="fas fa-user-shield text-6xl mb-4 opacity-30"></i>
                            <p class="text-lg font-medium">Belum ada data pengawas.</p>
                            <p class="text-sm mt-2 opacity-60">Klik tombol "Tambah Pengawas" untuk menambahkan data</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer Tabel -->
            <?php if ($total_pengawas > 0): ?>
                <div class="border-t px-6 py-4 text-sm flex justify-between items-center" style="background: rgba(156, 175, 136, 0.05);">
                    <div class="text-gray-500">
                        <i class="fas fa-info-circle mr-1" style="color: var(--sage);"></i>
                        Menampilkan <?= $total_pengawas ?> data pengawas ujian
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT PENGAWAS -->
    <div id="modalPengawas" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" style="background: var(--navy); color: white;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h3 id="judulModal" class="text-xl font-bold" style="color: var(--navy);">Tambah Pengawas</h3>
                    <p class="text-xs text-gray-400">Isi data pengawas dengan lengkap</p>
                </div>
                <button onclick="tutupModal()" class="ml-auto text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
            </div>

            <form action="../../proses/pengawas_proses.php" method="POST">
                <input type="hidden" name="id_user" id="id_user">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 mb-2">NIP / Username</label>
                    <input type="text" name="username" id="username" required
                           class="input-custom" placeholder="Contoh: 198001012010011001">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" required
                           class="input-custom" placeholder="Nama lengkap">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Ruangan</label>
                    <select name="ruangan" id="ruangan" class="select-custom">
                        <option value="">-- Pilih Ruangan --</option>
                        <?php if ($ruangan_list): ?>
                            <?php while($r = $ruangan_list->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($r['nama_ruangan']) ?>">
                                    <?= htmlspecialchars($r['nama_ruangan']) ?> (Kap. <?= $r['kapasitas'] ?? '-' ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">Belum ada data ruangan</option>
                        <?php endif; ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Pilih ruangan tempat pengawas bertugas</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="tutupModal()" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-xl font-bold text-sm hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" name="add_pengawas" id="btnSimpan" class="flex-1 text-white py-3 rounded-xl font-bold text-sm shadow-lg transition transform hover:scale-105" style="background: var(--navy);">
                        <i class="fas fa-save mr-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambah() {
            document.getElementById('modalPengawas').classList.remove('hidden');
            document.getElementById('modalPengawas').classList.add('flex');
            document.getElementById('judulModal').innerText = "Tambah Pengawas Baru";
            document.getElementById('btnSimpan').name = "add_pengawas";
            document.getElementById('id_user').value = "";
            document.getElementById('username').value = "";
            document.getElementById('nama_lengkap').value = "";
            document.getElementById('ruangan').value = "";
        }

        function bukaModalEdit(id, nip, nama, ruangan) {
            document.getElementById('modalPengawas').classList.remove('hidden');
            document.getElementById('modalPengawas').classList.add('flex');
            document.getElementById('judulModal').innerText = "Edit Data Pengawas";
            document.getElementById('btnSimpan').name = "edit_pengawas";
            document.getElementById('id_user').value = id;
            document.getElementById('username').value = nip;
            document.getElementById('nama_lengkap').value = nama;
            document.getElementById('ruangan').value = ruangan || "";
        }

        function tutupModal() {
            document.getElementById('modalPengawas').classList.remove('flex');
            document.getElementById('modalPengawas').classList.add('hidden');
        }

        function filterPengawas() {
            let input = document.getElementById("cariPengawas").value.toUpperCase();
            let rows = document.querySelectorAll(".pengawas-row");
            rows.forEach(row => {
                let nama = row.querySelector(".searchable")?.innerText.toUpperCase() || "";
                let nip = row.querySelector(".badge-nip")?.innerText.toUpperCase() || "";
                let match = nama.includes(input) || nip.includes(input);
                row.style.display = match ? "" : "none";
            });
        }

        function resetFilter() {
            document.getElementById("cariPengawas").value = "";
            filterPengawas();
        }

        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Pengawas?',
                text: "Data pengawas akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E69A5C',
                cancelButtonColor: '#1A2E40',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/pengawas_proses.php?hapus=" + id;
                }
            });
        }

        function cetakData() {
            window.print();
        }

        window.onclick = function(event) {
            let modalPengawas = document.getElementById('modalPengawas');
            if (event.target == modalPengawas) {
                tutupModal();
            }
        }

        <?php if (isset($_SESSION['notif'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['notif']['type']; ?>',
                title: '<?= $_SESSION['notif']['title']; ?>',
                text: '<?= $_SESSION['notif']['text']; ?>',
                timer: 2000,
                showConfirmButton: false
            });
        <?php unset($_SESSION['notif']); endif; ?>
    </script>
</body>

</html>