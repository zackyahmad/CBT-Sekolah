<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Ambil Data Siswa + Nama Kelasnya (JOIN Table) 
$query = "SELECT u.*, k.nama_kelas, k.tingkat 
          FROM tb_users u 
          LEFT JOIN tb_kelas k ON u.id_kelas = k.id_kelas 
          WHERE u.role = 'siswa' 
          ORDER BY k.tingkat ASC, k.nama_kelas ASC, u.nama_lengkap ASC";
$stmt = $pdo->query($query);
$siswa = $stmt->fetchAll();

// Ambil Data Kelas untuk Dropdown di Modal
$stmt_kelas = $pdo->query("SELECT * FROM tb_kelas ORDER BY tingkat ASC, nama_kelas ASC");
$kelas_list = $stmt_kelas->fetchAll();

// Hitung statistik
$total_siswa = count($siswa);
$total_kelas = $pdo->query("SELECT COUNT(*) FROM tb_kelas")->fetchColumn();
$total_laki = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role='siswa' AND jenis_kelamin='L'")->fetchColumn();
$total_perempuan = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role='siswa' AND jenis_kelamin='P'")->fetchColumn();

// Tema warna: Navy, Hijau Sage, Orange Kalem
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
    <title>Data Siswa - SMKN 8 Bandar Lampung</title>
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
            --gray-light: #F3F4F6;
            --gray: #6B7280;
        }

        body {
            background: linear-gradient(135deg, var(--cream) 0%, #EAE7DE 100%);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 1.5rem;
        }

        /* Container utama tanpa margin untuk sidebar */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* Card styles */
        .card {
            background: var(--white);
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(26, 46, 64, 0.1), 0 8px 10px -6px rgba(26, 46, 64, 0.05);
            border: 1px solid rgba(156, 175, 136, 0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 30px -10px rgba(26, 46, 64, 0.15);
            border-color: var(--sage);
        }

        /* Header gradient */
        .header-gradient {
            background: linear-gradient(105deg, var(--navy) 0%, var(--navy-light) 40%, var(--sage) 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-gradient::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(230,154,92,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        /* Stat card */
        .stat-card {
            background: var(--white);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(26, 46, 64, 0.1);
            border-left: 4px solid var(--orange);
            transition: all 0.3s ease;
            height: 100%;
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
            font-size: 1.25rem;
            background: linear-gradient(135deg, var(--sage-light), var(--sage));
            color: var(--navy);
        }

        /* Button styles */
        .btn-primary {
            background: var(--navy);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--navy-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(26, 46, 64, 0.3);
        }

        .btn-success {
            background: var(--sage);
            color: var(--navy);
            padding: 0.6rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-success:hover {
            background: var(--sage-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(156, 175, 136, 0.4);
        }

        .btn-warning {
            background: var(--orange);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-warning:hover {
            background: var(--orange-light);
            color: var(--navy);
        }

        /* Table styles */
        .table-container {
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(26, 46, 64, 0.1);
        }

        table thead tr {
            background: linear-gradient(90deg, var(--navy), var(--navy-light));
            color: white;
        }

        table thead th {
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        table tbody tr {
            border-bottom: 1px solid rgba(156, 175, 136, 0.2);
            transition: background-color 0.2s ease;
        }

        table tbody tr:hover {
            background-color: rgba(156, 175, 136, 0.1);
        }

        table tbody td {
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            color: #374151;
        }

        /* Badge styles */
        .badge-kelas {
            background: linear-gradient(135deg, var(--sage-light), var(--sage));
            color: var(--navy);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.3);
            display: inline-block;
        }

        .badge-update {
            background: rgba(156, 175, 136, 0.2);
            color: var(--navy);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 500;
            border: 1px solid rgba(156, 175, 136, 0.3);
        }

        /* Action buttons */
        .action-btn {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: white;
            border: none;
            cursor: pointer;
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

        .action-btn.view {
            background: var(--sage);
        }
        .action-btn.view:hover {
            background: var(--sage-light);
            color: var(--navy);
            transform: scale(1.1);
        }

        /* Input styles */
        .input-custom {
            border: 1px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .input-custom:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(230, 154, 92, 0.2);
        }

        .select-custom {
            border: 1px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            background-color: white;
        }

        .select-custom:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(230, 154, 92, 0.2);
        }

        /* Modal styles */
        .modal-overlay {
            background: rgba(26, 46, 64, 0.6);
            backdrop-filter: blur(4px);
        }

        /* Filter section */
        .filter-container {
            background: white;
            border-radius: 1.25rem;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(26, 46, 64, 0.05);
            border: 1px solid rgba(156, 175, 136, 0.2);
        }

        /* Animations */
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

        /* Progress bar for stats */
        .progress-bar {
            height: 0.5rem;
            background: #E5E7EB;
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--orange), var(--orange-light));
            border-radius: 9999px;
            width: 0%;
            transition: width 1s ease;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--cream);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--sage), var(--navy-light));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--orange), var(--sage));
        }
    </style>
</head>

<body>

    <!-- MAIN CONTAINER - Tanpa Sidebar -->
    <div class="main-container animate-fadeInUp">
        
        <!-- HEADER CARD -->
        <div class="card p-8 mb-6 header-gradient text-white relative overflow-hidden">
            <div class="relative z-10">
                <!-- Breadcrumb -->
                <nav class="text-sm mb-4 flex items-center gap-2 text-white/80">
                    <a href="dashboard.php" class="hover:text-white transition">Dashboard</a>
                    <i class="fas fa-chevron-right text-[10px]"></i>
                    <span class="text-white font-semibold">Data Siswa</span>
                </nav>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white/30">
                            <i class="fas fa-users text-3xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-1">Database Siswa</h1>
                            <p class="text-white/90 flex items-center gap-2">
                                <i class="far fa-building"></i>
                                SMK Negeri 8 Bandar Lampung | Tahun Ajaran <?= date('Y') . '/' . (date('Y')+1) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button onclick="bukaModalImport()" class="btn-success">
                            <i class="fas fa-file-excel"></i> Import Excel
                        </button>
                        <button onclick="bukaModalTambah()" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Tambah Siswa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIK CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Siswa</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_siswa ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3">
                    <div class="progress-fill" style="width: 100%;"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Laki-laki</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_laki ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-mars"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3">
                    <div class="progress-fill" style="width: <?= $total_siswa > 0 ? ($total_laki / $total_siswa * 100) : 0 ?>%;"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Perempuan</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_perempuan ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-venus"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3">
                    <div class="progress-fill" style="width: <?= $total_siswa > 0 ? ($total_perempuan / $total_siswa * 100) : 0 ?>%;"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Kelas</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_kelas ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3">
                    <div class="progress-fill" style="width: <?= min(100, ($total_kelas / 20) * 100) ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- FILTER DAN PENCARIAN -->
        <div class="filter-container mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex-1 min-w-[250px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="text" id="cariSiswa" onkeyup="filterSiswa()" placeholder="Cari nama atau NIS..." 
                               class="input-custom pl-10">
                    </div>
                </div>
                <div class="flex gap-2">
                    <select id="filterKelas" onchange="filterSiswa()" class="select-custom min-w-[150px]">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['nama_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="resetFilter()" class="btn-warning">
                        <i class="fas fa-redo-alt"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- TABEL DATA SISWA -->
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4" style="border-color: rgba(156, 175, 136, 0.2);">
                <h2 class="font-bold text-lg" style="color: var(--navy);">
                    <i class="fas fa-list mr-2" style="color: var(--orange);"></i>
                    Daftar Siswa (<?= $total_siswa ?>)
                </h2>
                <span class="badge-update">
                    <i class="far fa-clock mr-1"></i> Update: <?= date('d/m/Y H:i') ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <?php if ($total_siswa > 0): ?>
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>NISN</th>
                            <th>Nama Lengkap</th>
                            <th>JK</th>
                            <th>Kelas</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($siswa as $row): ?>
                        <tr class="siswa-row">
                            <td class="font-mono text-gray-400"><?= $no++ ?></td>
                            <td class="font-mono font-bold" style="color: var(--orange);"><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nisn'] ?: '-') ?></td>
                            <td class="font-medium searchable"><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td>
                                <span class="font-semibold <?= $row['jenis_kelamin'] == 'L' ? 'text-blue-600' : 'text-pink-600' ?>">
                                    <?= $row['jenis_kelamin'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['nama_kelas']): ?>
                                    <span class="badge-kelas">
                                        <?= htmlspecialchars($row['nama_kelas']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-400 py-1 px-3 rounded-lg text-xs italic">
                                        Tanpa Kelas
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                            class="action-btn edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="konfirmasiHapus(<?= $row['id_user']; ?>)" 
                                            class="action-btn delete" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="detail_siswa.php?id=<?= $row['id_user'] ?>" 
                                       class="action-btn view" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="py-20 text-center">
                    <div class="flex flex-col items-center justify-center" style="color: var(--navy-light);">
                        <i class="fas fa-user-slash text-6xl mb-4 opacity-30"></i>
                        <p class="text-lg font-medium">Belum ada data siswa.</p>
                        <p class="text-sm mt-2 opacity-60">Klik tombol "Tambah Siswa" untuk menambahkan data</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer Tabel -->
            <?php if ($total_siswa > 0): ?>
            <div class="border-t px-6 py-4 text-sm flex justify-between items-center" style="border-color: rgba(156, 175, 136, 0.2); background: rgba(156, 175, 136, 0.05);">
                <div class="text-gray-500">
                    <i class="fas fa-info-circle mr-1" style="color: var(--sage);"></i>
                    Menampilkan <span class="font-bold" style="color: var(--navy);"><?= $total_siswa ?></span> data siswa
                </div>
                <div>
                    <button onclick="cetakData()" class="text-sm hover:underline" style="color: var(--orange);">
                        <i class="fas fa-print mr-1"></i> Cetak
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL IMPORT EXCEL (Sage Theme) -->
    <div id="modalImport" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-8 transform transition-all">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl" style="background: var(--sage); color: var(--navy); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold" style="color: var(--navy);">Import Data Siswa</h3>
                    <p class="text-xs text-gray-400">Pastikan format sesuai template</p>
                </div>
                <button onclick="tutupModalImport()" class="ml-auto text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
            </div>

            <div class="mb-6 p-4 rounded-xl" style="background: rgba(156, 175, 136, 0.1); border: 1px solid rgba(156, 175, 136, 0.3);">
                <p class="text-xs mb-2" style="color: var(--navy);"><i class="fas fa-info-circle mr-1"></i> Petunjuk:</p>
                <ul class="text-xs text-gray-600 space-y-1 ml-5 list-disc">
                    <li>Gunakan file .xlsx dari Excel</li>
                    <li>Isi data mulai dari baris ke-2</li>
                    <li>Kolom: NIS, NISN, Nama, JK, Kelas, dll</li>
                </ul>
                <a href="../../proses/download_template_siswa.php" class="mt-3 inline-block font-medium text-sm" style="color: var(--orange);">
                    <i class="fas fa-download mr-1"></i> Download Template
                </a>
            </div>

            <form action="../../proses/import_siswa.php" method="POST" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600 mb-2">Pilih File Excel</label>
                    <input type="file" name="file_siswa" accept=".xlsx,.xls" required
                           class="block w-full text-sm border border-gray-200 rounded-xl p-3 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold" 
                           style="file:background: var(--sage); file:color: var(--navy); hover:file:background: var(--sage-light);">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="tutupModalImport()" class="px-6 py-2.5 text-gray-400 font-medium hover:text-gray-600 transition">
                        Batal
                    </button>
                    <button type="submit" name="import" class="text-white px-6 py-2.5 rounded-xl font-medium transition shadow-lg flex items-center gap-2" style="background: var(--sage); color: var(--navy); hover:background: var(--sage-light);">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT SISWA (Navy Theme) -->
    <div id="modalSiswa" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-8 my-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" style="background: var(--navy); color: white;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3 id="judulModal" class="text-xl font-bold" style="color: var(--navy);">Tambah Siswa Baru</h3>
                        <p class="text-xs text-gray-400">Isi data siswa dengan lengkap</p>
                    </div>
                </div>
                <button onclick="tutupModal()" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
            </div>

            <form action="../../proses/siswa_proses.php" method="POST">
                <input type="hidden" name="id_user" id="id_user">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Informasi Login -->
                    <div class="col-span-2 p-4 rounded-xl mb-2" style="background: rgba(156, 175, 136, 0.1);">
                        <h4 class="font-bold text-sm flex items-center gap-2" style="color: var(--navy);">
                            <i class="fas fa-lock"></i> Informasi Akun
                        </h4>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">NIS (Username)</label>
                        <input type="text" name="username" id="username" required 
                               class="input-custom"
                               placeholder="Contoh: 202312345">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Password</label>
                        <input type="password" name="password" id="password" 
                               class="input-custom" 
                               placeholder="Kosongkan jika tidak diubah">
                        <p class="text-[10px] text-gray-400 mt-1">Minimal 6 karakter</p>
                    </div>

                    <!-- Data Pribadi -->
                    <div class="col-span-2 p-4 rounded-xl mt-2 mb-2" style="background: rgba(230, 154, 92, 0.1);">
                        <h4 class="font-bold text-sm flex items-center gap-2" style="color: var(--navy);">
                            <i class="fas fa-user"></i> Data Pribadi
                        </h4>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" required 
                               class="input-custom"
                               placeholder="Nama lengkap dengan gelar">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">NISN</label>
                        <input type="text" name="nisn" id="nisn" 
                               class="input-custom"
                               placeholder="Nomor Induk Siswa Nasional">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Kelas</label>
                        <select name="id_kelas" id="id_kelas" required class="select-custom">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_list as $k): ?>
                                <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="jenis_kelamin" class="select-custom">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Agama</label>
                        <select name="agama" id="agama" class="select-custom">
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghucu">Konghucu</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="tempat_lahir" 
                               class="input-custom">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="tanggal_lahir" 
                               class="input-custom">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1">Alamat</label>
                        <textarea name="alamat" id="alamat" rows="2" 
                                  class="input-custom"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">No. Telepon</label>
                        <input type="text" name="no_telp" id="no_telp" 
                               class="input-custom">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Email</label>
                        <input type="email" name="email" id="email" 
                               class="input-custom">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8 pt-4 border-t">
                    <button type="button" onclick="tutupModal()" class="px-8 py-3 text-gray-400 font-medium hover:text-gray-600 transition rounded-xl border border-gray-200">
                        Batal
                    </button>
                    <button type="submit" name="add_siswa" id="btnSimpan" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal import
        function bukaModalImport() {
            document.getElementById('modalImport').classList.remove('hidden');
            document.getElementById('modalImport').classList.add('flex');
        }

        function tutupModalImport() {
            document.getElementById('modalImport').classList.remove('flex');
            document.getElementById('modalImport').classList.add('hidden');
        }

        // Fungsi untuk modal tambah siswa
        function bukaModalTambah() {
            document.getElementById('modalSiswa').classList.remove('hidden');
            document.getElementById('modalSiswa').classList.add('flex');
            document.getElementById('judulModal').innerText = "Tambah Siswa Baru";
            document.getElementById('btnSimpan').name = "add_siswa";
            document.querySelector('form').reset();
            document.getElementById('id_user').value = "";
            document.getElementById('password').required = true;
        }

        // Fungsi untuk modal edit siswa
        function bukaModalEdit(data) {
            document.getElementById('modalSiswa').classList.remove('hidden');
            document.getElementById('modalSiswa').classList.add('flex');
            document.getElementById('judulModal').innerText = "Edit Data Siswa";
            document.getElementById('btnSimpan').name = "edit_siswa";
            document.getElementById('password').required = false;

            // Isi form dengan data yang ada
            document.getElementById('id_user').value = data.id_user;
            document.getElementById('username').value = data.username;
            document.getElementById('nama_lengkap').value = data.nama_lengkap;
            document.getElementById('id_kelas').value = data.id_kelas;
            document.getElementById('nisn').value = data.nisn || '';
            document.getElementById('jenis_kelamin').value = data.jenis_kelamin;
            document.getElementById('agama').value = data.agama || 'Islam';
            document.getElementById('tempat_lahir').value = data.tempat_lahir || '';
            document.getElementById('tanggal_lahir').value = data.tanggal_lahir || '';
            document.getElementById('alamat').value = data.alamat || '';
            document.getElementById('no_telp').value = data.no_telp || '';
            document.getElementById('email').value = data.email || '';
        }

        function tutupModal() {
            document.getElementById('modalSiswa').classList.remove('flex');
            document.getElementById('modalSiswa').classList.add('hidden');
        }

        // Fungsi filter siswa
        function filterSiswa() {
            let input = document.getElementById("cariSiswa").value.toUpperCase();
            let filterKelas = document.getElementById("filterKelas").value.toUpperCase();
            let rows = document.querySelectorAll(".siswa-row");
            
            rows.forEach(row => {
                let nama = row.querySelector(".searchable")?.innerText.toUpperCase() || "";
                let kelas = row.querySelector("td:nth-child(6)")?.innerText.toUpperCase() || "";
                
                let matchNama = nama.includes(input);
                let matchKelas = filterKelas === "" || kelas.includes(filterKelas);
                
                row.style.display = matchNama && matchKelas ? "" : "none";
            });
        }

        function resetFilter() {
            document.getElementById("cariSiswa").value = "";
            document.getElementById("filterKelas").value = "";
            filterSiswa();
        }

        // Fungsi hapus dengan konfirmasi
        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Siswa?',
                text: "Data nilai dan riwayat ujian siswa ini juga akan terhapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E69A5C',
                cancelButtonColor: '#1A2E40',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#fff',
                backdrop: `rgba(26, 46, 64, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/siswa_proses.php?hapus=" + id;
                }
            })
        }

        // Fungsi cetak data
        function cetakData() {
            window.print();
        }

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            let modalImport = document.getElementById('modalImport');
            let modalSiswa = document.getElementById('modalSiswa');
            
            if (event.target == modalImport) {
                tutupModalImport();
            }
            if (event.target == modalSiswa) {
                tutupModal();
            }
        }

        // Notifikasi session
        <?php if (isset($_SESSION['notif'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['notif']['type']; ?>',
                title: '<?= $_SESSION['notif']['title']; ?>',
                text: '<?= $_SESSION['notif']['text']; ?>',
                timer: 2000,
                showConfirmButton: false
            });
        <?php unset($_SESSION['notif']);
        endif; ?>

        // Animasi progress bar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.progress-fill').forEach(bar => {
                    bar.style.width = bar.style.width;
                });
            }, 100);
        });
    </script>
</body>

</html>