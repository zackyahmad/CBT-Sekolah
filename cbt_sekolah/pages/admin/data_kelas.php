<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Query diperbarui: Hanya mengambil Tingkat dan Nama Kelas
$stmt = $pdo->query("SELECT * FROM tb_kelas ORDER BY tingkat ASC, nama_kelas ASC");
$all_kelas = $stmt->fetchAll();

$kelas_x    = [];
$kelas_xi   = [];
$kelas_xii  = [];

foreach ($all_kelas as $k) {
    if ($k['tingkat'] == 'X') {
        $kelas_x[] = $k;
    } elseif ($k['tingkat'] == 'XI') {
        $kelas_xi[] = $k;
    } elseif ($k['tingkat'] == 'XII') {
        $kelas_xii[] = $k;
    }
}

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
    <title>Data Kelas - SMK Negeri 8 Bandar Lampung</title>
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

        /* Tab styles */
        .tab-container {
            background: white;
            padding: 0.25rem;
            border-radius: 9999px;
            border: 1px solid rgba(156, 175, 136, 0.2);
            display: inline-flex;
        }

        .tab-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .tab-btn.active {
            background: var(--navy);
            color: white;
            box-shadow: 0 4px 10px rgba(26, 46, 64, 0.2);
        }

        .tab-btn:not(.active) {
            background: transparent;
            color: var(--gray);
        }

        .tab-btn:not(.active):hover {
            background: rgba(156, 175, 136, 0.1);
            color: var(--navy);
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
        .badge-tingkat {
            background: rgba(156, 175, 136, 0.2);
            color: var(--navy);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            border: 1px solid rgba(156, 175, 136, 0.3);
            display: inline-block;
        }

        .badge-kelas {
            background: linear-gradient(135deg, var(--orange-light), var(--orange));
            color: white;
            padding: 0.35rem 1.2rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(230, 154, 92, 0.3);
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

        .select-custom {
            border: 1px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            background-color: white;
        }

        .select-custom:focus {
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

        /* Preview kelas */
        .preview-box {
            background: rgba(156, 175, 136, 0.1);
            border-radius: 0.75rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border: 1px dashed var(--sage);
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
                        <span class="text-white font-semibold">Data Kelas</span>
                    </nav>
                    
                    <a href="dashboard.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white/30">
                            <i class="fas fa-school text-3xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-1">Manajemen Rombel</h1>
                            <p class="text-white/90 flex items-center gap-2">
                                <i class="far fa-building"></i>
                                SMK Negeri 8 Bandar Lampung | Tahun Ajaran <?= date('Y') . '/' . (date('Y')+1) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button onclick="bukaModalTambah()" class="btn-success">
                            <i class="fas fa-plus-circle"></i> Tambah Kelas
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card overflow-hidden">
            <div class="p-6 border-b border-gray-100" style="border-color: rgba(156, 175, 136, 0.2);">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <!-- Tab Navigation -->
                    <div class="tab-container">
                        <button onclick="switchTab('x', this)" class="tab-btn active" id="tab-x-btn">KELAS X</button>
                        <button onclick="switchTab('xi', this)" class="tab-btn" id="tab-xi-btn">KELAS XI</button>
                        <button onclick="switchTab('xii', this)" class="tab-btn" id="tab-xii-btn">KELAS XII</button>
                    </div>
                    
                    <!-- Info Total Kelas -->
                    <div class="flex items-center gap-2">
                        <span class="badge-tingkat">
                            <i class="fas fa-layer-group mr-1"></i>
                            Total: <?= count($all_kelas) ?> Kelas
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-0">
                <!-- Konten Tab X -->
                <div id="content-x" class="tab-content block">
                    <?php renderTable($kelas_x, 'X'); ?>
                </div>
                
                <!-- Konten Tab XI -->
                <div id="content-xi" class="tab-content hidden">
                    <?php renderTable($kelas_xi, 'XI'); ?>
                </div>
                
                <!-- Konten Tab XII -->
                <div id="content-xii" class="tab-content hidden">
                    <?php renderTable($kelas_xii, 'XII'); ?>
                </div>
            </div>
            
            <!-- Footer Card -->
            <div class="border-t px-6 py-4 text-sm flex justify-between items-center" style="border-color: rgba(156, 175, 136, 0.2); background: rgba(156, 175, 136, 0.05);">
                <div class="text-gray-500">
                    <i class="fas fa-info-circle mr-1" style="color: var(--sage);"></i>
                    Kelola data rombongan belajar (rombel) SMK Negeri 8 Bandar Lampung
                </div>
                <div>
                    <span class="text-xs" style="color: var(--orange);">
                        <i class="far fa-clock mr-1"></i> Update: <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT KELAS -->
    <div id="modalKelas" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 border relative animate-fadeInUp">
            <!-- Header Modal -->
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" style="background: var(--navy); color: white;">
                    <i class="fas fa-school"></i>
                </div>
                <div>
                    <h3 id="judulModal" class="text-xl font-bold" style="color: var(--navy);">Tambah Rombel</h3>
                    <p class="text-xs text-gray-400">Isi data kelas dengan lengkap</p>
                </div>
                <button onclick="tutupModal()" class="ml-auto text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-2xl"></i>
                </button>
            </div>

            <form action="../../proses/kelas_proses.php" method="POST">
                <input type="hidden" name="id_kelas" id="id_kelas">

                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Pilih Tingkat</label>
                    <select name="tingkat" id="tingkat" onchange="updatePrefix()" required class="select-custom">
                        <option value="">-- Pilih Tingkat --</option>
                        <option value="X">X (Sepuluh)</option>
                        <option value="XI">XI (Sebelas)</option>
                        <option value="XII">XII (Duabelas)</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-600 mb-2">Nama Kelas</label>
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:border-orange-400 transition" style="border-color: #E5E7EB;">
                        <span id="labelPrefix" class="bg-gray-50 px-4 py-3 text-gray-500 font-bold border-r border-gray-200">-</span>
                        <input type="text" name="nama_kelas" id="nama_kelas" required
                            class="flex-1 py-3 px-4 outline-none font-medium text-gray-700"
                            placeholder="contoh: PPLG 1">
                    </div>
                </div>

                <!-- Preview -->
                <div class="preview-box mb-6">
                    <p class="text-xs font-medium text-gray-500 mb-1">Pratinjau:</p>
                    <p id="previewKelas" class="text-lg font-bold" style="color: var(--orange);">-</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="tutupModal()" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-xl font-bold text-sm hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" name="add_kelas" id="btnSimpan" class="flex-1 text-white py-3 rounded-xl font-bold text-sm shadow-lg transition transform hover:scale-105" style="background: var(--navy);">
                        <i class="fas fa-save mr-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk update prefix dan preview
        function updatePrefix() {
            const tingkat = document.getElementById('tingkat').value;
            const label = document.getElementById('labelPrefix');
            const nama = document.getElementById('nama_kelas').value;
            const preview = document.getElementById('previewKelas');

            if (tingkat) {
                label.innerText = tingkat + ' -';
                
                if (nama) {
                    preview.innerText = tingkat + ' - ' + nama.toUpperCase();
                } else {
                    preview.innerText = tingkat + ' - ...';
                }
            } else {
                label.innerText = '-';
                preview.innerText = '-';
            }
        }

        // Event listener untuk input nama
        document.getElementById('nama_kelas').addEventListener('input', updatePrefix);

        // Fungsi switch tab
        function switchTab(tabName, btn) {
            // Sembunyikan semua konten tab
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Tampilkan tab yang dipilih
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Update style semua tombol tab
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active');
            });
            
            // Aktifkan tombol yang diklik
            btn.classList.add('active');
        }

        // Fungsi modal tambah
        function bukaModalTambah() {
            document.getElementById('modalKelas').classList.remove('hidden');
            document.getElementById('modalKelas').classList.add('flex');
            document.getElementById('judulModal').innerText = "Tambah Kelas Baru";
            document.getElementById('btnSimpan').name = "add_kelas";
            document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-save mr-2"></i> Simpan';
            
            // Reset form
            document.getElementById('id_kelas').value = "";
            document.getElementById('tingkat').value = "";
            document.getElementById('nama_kelas').value = "";
            document.getElementById('labelPrefix').innerText = '-';
            document.getElementById('previewKelas').innerText = '-';
        }

        // Fungsi modal edit
        function bukaModalEdit(id, tingkat, nama) {
            document.getElementById('modalKelas').classList.remove('hidden');
            document.getElementById('modalKelas').classList.add('flex');
            document.getElementById('judulModal').innerText = "Edit Rombel";
            document.getElementById('btnSimpan').name = "edit_kelas";
            document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-save mr-2"></i> Update';
            
            // Isi form
            document.getElementById('id_kelas').value = id;
            document.getElementById('tingkat').value = tingkat;
            
            // Hapus prefix dari nama (misal "X-PPLG 1" jadi "PPLG 1")
            const namaBersih = nama.replace(tingkat + '-', '');
            document.getElementById('nama_kelas').value = namaBersih;
            
            // Update prefix dan preview
            updatePrefix();
        }

        // Fungsi tutup modal
        function tutupModal() {
            document.getElementById('modalKelas').classList.remove('flex');
            document.getElementById('modalKelas').classList.add('hidden');
        }

        // Fungsi konfirmasi hapus
        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Kelas?',
                text: "Siswa di kelas ini akan kehilangan rombelnya!",
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
                    window.location.href = "../../proses/kelas_proses.php?hapus=" + id;
                }
            });
        }

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            let modal = document.getElementById('modalKelas');
            if (event.target == modal) {
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

<?php
function renderTable($dataKelas, $tingkat)
{
    if (empty($dataKelas)) {
        echo '<div class="py-20 text-center">
                <div class="flex flex-col items-center justify-center" style="color: var(--navy-light);">
                    <i class="fas fa-school text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Belum ada data kelas ' . $tingkat . '.</p>
                    <p class="text-sm mt-2 opacity-60">Klik tombol "Tambah Kelas" untuk menambahkan data</p>
                </div>
              </div>';
        return;
    }
?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-center w-16">No</th>
                    <th class="text-left">Tingkat</th>
                    <th class="text-left">Nama Kelas</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($dataKelas as $row): ?>
                    <tr>
                        <td class="text-center font-mono" style="color: var(--orange); font-weight: 600;"><?= $no++; ?></td>
                        <td>
                            <span class="badge-tingkat">
                                <i class="fas fa-layer-group mr-1"></i> <?= $row['tingkat']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-kelas">
                                <i class="fas fa-tag mr-1"></i> <?= htmlspecialchars($row['nama_kelas']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button onclick="bukaModalEdit(<?= $row['id_kelas']; ?>, '<?= $row['tingkat']; ?>', '<?= $row['nama_kelas']; ?>')" 
                                    class="action-btn edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button onclick="konfirmasiHapus(<?= $row['id_kelas']; ?>)" 
                                    class="action-btn delete" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php } ?>