<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

$stmt = $pdo->query("SELECT * FROM tb_users WHERE role = 'guru' ORDER BY nama_lengkap ASC");
$guru = $stmt->fetchAll();

// Hitung statistik
$total_guru = count($guru);
$total_admin = $pdo->query("SELECT COUNT(*) FROM tb_users WHERE role = 'admin'")->fetchColumn();

// Karena kolom 'status' tidak ada, kita set default 0 untuk pengawas
$total_pengawas = 0;

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
    <title>Data Guru - SMKN 8 Bandar Lampung</title>
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
            margin: 0;
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
            cursor: pointer;
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
            cursor: pointer;
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
            font-size: 0.85rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-3px);
        }

        /* Table styles */
        .table-container {
            border-radius: 1.5rem;
            overflow: hidden;
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
            font-size: 0.95rem;
            color: #374151;
        }

        /* Badge styles */
        .badge-nip {
            background: rgba(156, 175, 136, 0.2);
            color: var(--navy);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(156, 175, 136, 0.3);
            display: inline-block;
        }

        .badge-role {
            background: var(--orange);
            color: white;
            padding: 0.15rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 0.5rem;
        }

        /* Action buttons */
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

        /* Modal styles */
        .modal-overlay {
            background: rgba(26, 46, 64, 0.6);
            backdrop-filter: blur(4px);
        }

        .input-custom {
            border: 1px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .input-custom:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(230, 154, 92, 0.2);
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
                <!-- Breadcrumb dan Tombol Kembali -->
                <div class="flex justify-between items-start mb-4">
                    <nav class="text-sm flex items-center gap-2 text-white/80">
                        <a href="dashboard.php" class="hover:text-white transition">Dashboard</a>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                        <span class="text-white font-semibold">Data Guru</span>
                    </nav>
                    
                    <a href="dashboard.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white/30">
                            <i class="fas fa-chalkboard-teacher text-3xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-1">Data Guru</h1>
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
                            <i class="fas fa-plus-circle"></i> Tambah Guru
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
                        <p class="text-sm text-gray-500 mb-1">Total Guru</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_guru ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3" style="height: 0.5rem; background: #E5E7EB; border-radius: 9999px; overflow: hidden;">
                    <div class="progress-fill" style="height: 100%; background: linear-gradient(90deg, var(--orange), var(--orange-light)); width: 100%;"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Admin</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_admin ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3" style="height: 0.5rem; background: #E5E7EB; border-radius: 9999px; overflow: hidden;">
                    <div class="progress-fill" style="height: 100%; background: linear-gradient(90deg, var(--orange), var(--orange-light)); width: <?= $total_guru > 0 ? ($total_admin / $total_guru) * 100 : 0 ?>%;"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Pengawas Ujian</p>
                        <p class="text-3xl font-bold" style="color: var(--navy);"><?= $total_pengawas ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
                <div class="progress-bar mt-3" style="height: 0.5rem; background: #E5E7EB; border-radius: 9999px; overflow: hidden;">
                    <div class="progress-fill" style="height: 100%; background: linear-gradient(90deg, var(--orange), var(--orange-light)); width: 0%;"></div>
                </div>
            </div>
        </div>

        <!-- FILTER DAN PENCARIAN -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6" style="border-color: rgba(156, 175, 136, 0.2);">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex-1 min-w-[250px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="text" id="cariGuru" onkeyup="filterGuru()" placeholder="Cari nama atau NIP..." 
                               class="input-custom pl-10">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="resetFilter()" class="btn-warning">
                        <i class="fas fa-redo-alt"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- TABEL DATA GURU -->
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4" style="border-color: rgba(156, 175, 136, 0.2);">
                <h2 class="font-bold text-lg" style="color: var(--navy);">
                    <i class="fas fa-list mr-2" style="color: var(--orange);"></i>
                    Daftar Guru (<?= $total_guru ?>)
                </h2>
                <span class="badge-nip">
                    <i class="far fa-clock mr-1"></i> Update: <?= date('d/m/Y H:i') ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <?php if ($total_guru > 0): ?>
                <table class="w-full" id="tabelGuru">
                    <thead>
                        <tr>
                            <th class="text-center w-16">No</th>
                            <th class="text-left">NIP / Username</th>
                            <th class="text-left">Nama Lengkap</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($guru as $row): ?>
                        <tr class="guru-row">
                            <td class="text-center font-mono" style="color: var(--orange); font-weight: 600;"><?= $no++ ?></td>
                            <td>
                                <span class="badge-nip">
                                    <i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($row['username']); ?>
                                </span>
                            </td>
                            <td class="font-medium searchable">
                                <?= htmlspecialchars($row['nama_lengkap']); ?>
                                <?php if ($row['role'] == 'admin'): ?>
                                    <span class="badge-role">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button onclick="bukaModalEdit(<?= $row['id_user']; ?>, '<?= $row['username']; ?>', '<?= $row['nama_lengkap']; ?>')" 
                                        class="action-btn edit" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button onclick="konfirmasiHapus(<?= $row['id_user']; ?>)" 
                                        class="action-btn delete" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="py-20 text-center">
                    <div class="flex flex-col items-center justify-center" style="color: var(--navy-light);">
                        <i class="fas fa-chalkboard-teacher text-6xl mb-4 opacity-30"></i>
                        <p class="text-lg font-medium">Belum ada data guru.</p>
                        <p class="text-sm mt-2 opacity-60">Klik tombol "Tambah Guru" untuk menambahkan data</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer Tabel -->
            <?php if ($total_guru > 0): ?>
            <div class="border-t px-6 py-4 text-sm flex justify-between items-center" style="border-color: rgba(156, 175, 136, 0.2); background: rgba(156, 175, 136, 0.05);">
                <div class="text-gray-500">
                    <i class="fas fa-info-circle mr-1" style="color: var(--sage);"></i>
                    Menampilkan <span class="font-bold" style="color: var(--navy);"><?= $total_guru ?></span> data guru
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

    <!-- MODAL IMPORT EXCEL -->
    <div id="modalImport" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-8 transform transition-all">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl" style="background: var(--sage); color: var(--navy); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold" style="color: var(--navy);">Import Data Guru</h3>
                    <p class="text-xs text-gray-400">Pastikan format sesuai template</p>
                </div>
                <button onclick="tutupModalImport()" class="ml-auto text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
            </div>

            <div class="mb-6 p-4 rounded-xl" style="background: rgba(156, 175, 136, 0.1); border: 1px solid rgba(156, 175, 136, 0.3);">
                <p class="text-xs mb-2" style="color: var(--navy);"><i class="fas fa-info-circle mr-1"></i> Petunjuk:</p>
                <ul class="text-xs text-gray-600 space-y-1 ml-5 list-disc">
                    <li>Gunakan file .xlsx atau .csv dari Excel</li>
                    <li>Isi data mulai dari baris ke-2</li>
                    <li>Kolom: NIP, Nama Lengkap</li>
                </ul>
                <a href="data:text/csv;charset=utf-8,NIP,Nama Lengkap%0A19800101,Nama Guru Contoh" download="template_guru.csv" class="mt-3 inline-block font-medium text-sm" style="color: var(--orange);">
                    <i class="fas fa-download mr-1"></i> Download Template
                </a>
            </div>

            <form action="../../proses/import_guru.php" method="POST" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600 mb-2">Pilih File Excel/CSV</label>
                    <input type="file" name="file_guru" accept=".xlsx,.xls,.csv" required
                           class="block w-full text-sm border border-gray-200 rounded-xl p-3 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold" 
                           style="file:background: var(--sage); file:color: var(--navy); hover:file:background: var(--sage-light);">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="tutupModalImport()" class="px-6 py-2.5 text-gray-400 font-medium hover:text-gray-600 transition">
                        Batal
                    </button>
                    <button type="submit" name="import" class="text-white px-6 py-2.5 rounded-xl font-medium transition shadow-lg flex items-center gap-2" style="background: var(--sage); color: var(--navy);">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT GURU -->
    <div id="modalGuru" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" style="background: var(--navy); color: white;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h3 id="judulModal" class="text-xl font-bold" style="color: var(--navy);">Tambah Guru</h3>
                    <p class="text-xs text-gray-400">Isi data guru dengan lengkap</p>
                </div>
                <button onclick="tutupModal()" class="ml-auto text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
            </div>

            <form action="../../proses/guru_proses.php" method="POST">
                <input type="hidden" name="id_user" id="id_user">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 mb-2">NIP / Username</label>
                    <input type="text" name="username" id="username" required 
                           class="input-custom" placeholder="Contoh: 198001012010011001">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" required 
                           class="input-custom" placeholder="Nama lengkap dengan gelar">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Role</label>
                    <select name="role" id="role" class="input-custom">
                        <option value="guru">Guru</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="mb-6" id="divPassword">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Password</label>
                    <input type="password" name="password" id="password" 
                           class="input-custom" placeholder="Kosongkan jika tidak diubah">
                    <p class="text-[10px] text-gray-400 mt-1">Minimal 6 karakter</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="tutupModal()" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-xl font-bold text-sm hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" name="add_guru" id="btnSimpan" class="flex-1 text-white py-3 rounded-xl font-bold text-sm shadow-lg transition transform hover:scale-105" style="background: var(--navy);">
                        <i class="fas fa-save mr-2"></i> Simpan
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

        // Fungsi untuk modal tambah guru
        function bukaModalTambah() {
            document.getElementById('modalGuru').classList.remove('hidden');
            document.getElementById('modalGuru').classList.add('flex');
            document.getElementById('judulModal').innerText = "Tambah Guru Baru";
            document.getElementById('btnSimpan').name = "add_guru";
            document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-save mr-2"></i> Simpan';
            document.getElementById('divPassword').style.display = 'block';
            document.getElementById('password').required = true;
            
            // Reset form
            document.getElementById('id_user').value = "";
            document.getElementById('username').value = "";
            document.getElementById('nama_lengkap').value = "";
            document.getElementById('role').value = "guru";
            document.getElementById('password').value = "";
        }

        // Fungsi untuk modal edit guru
        function bukaModalEdit(id, username, nama) {
            document.getElementById('modalGuru').classList.remove('hidden');
            document.getElementById('modalGuru').classList.add('flex');
            document.getElementById('judulModal').innerText = "Edit Data Guru";
            document.getElementById('btnSimpan').name = "edit_guru";
            document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-save mr-2"></i> Update';
            document.getElementById('divPassword').style.display = 'block';
            document.getElementById('password').required = false;
            
            // Isi form
            document.getElementById('id_user').value = id;
            document.getElementById('username').value = username;
            document.getElementById('nama_lengkap').value = nama;
            document.getElementById('role').value = "guru"; // Default, perlu disesuaikan jika ada data role
            document.getElementById('password').value = "";
        }

        function tutupModal() {
            document.getElementById('modalGuru').classList.remove('flex');
            document.getElementById('modalGuru').classList.add('hidden');
        }

        // Fungsi filter guru
        function filterGuru() {
            let input = document.getElementById("cariGuru").value.toUpperCase();
            let rows = document.querySelectorAll(".guru-row");
            
            rows.forEach(row => {
                let nama = row.querySelector(".searchable")?.innerText.toUpperCase() || "";
                let nip = row.querySelector("td:nth-child(2)")?.innerText.toUpperCase() || "";
                
                let match = nama.includes(input) || nip.includes(input);
                row.style.display = match ? "" : "none";
            });
        }

        function resetFilter() {
            document.getElementById("cariGuru").value = "";
            filterGuru();
        }

        // Fungsi hapus dengan konfirmasi
        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Guru?',
                text: "Data guru akan hilang permanen!",
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
                    window.location.href = "../../proses/guru_proses.php?hapus=" + id;
                }
            });
        }

        // Fungsi cetak data
        function cetakData() {
            window.print();
        }

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            let modalImport = document.getElementById('modalImport');
            let modalGuru = document.getElementById('modalGuru');
            
            if (event.target == modalImport) {
                tutupModalImport();
            }
            if (event.target == modalGuru) {
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
    </script>
</body>

</html>