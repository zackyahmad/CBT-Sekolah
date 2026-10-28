<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Ambil data mapel dengan filter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM tb_mapel 
                           WHERE kode_mapel LIKE :search 
                           OR nama_mapel LIKE :search 
                           ORDER BY nama_mapel ASC");
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM tb_mapel ORDER BY nama_mapel ASC");
}
$mapel = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mata Pelajaran - Admin CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Gradien biru navy, orange, dan hijau soft */
        .gradient-bg {
            background: linear-gradient(135deg, #0A192F 0%, #1E3A5F 30%, #F4A261 60%, #90BE6D 100%);
            background-size: 200% 200%;
            animation: gradientMove 10s ease infinite;
        }
        
        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .table-row-hover:hover {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }
        
        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .back-btn {
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }

        /* Gradien untuk tombol tambah */
        .btn-gradient {
            background: linear-gradient(135deg, #0A192F, #1E3A5F, #F4A261, #90BE6D);
            background-size: 300% 300%;
            animation: btnGradient 5s ease infinite;
            transition: all 0.3s ease;
        }
        
        @keyframes btnGradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .btn-gradient:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(10, 25, 47, 0.3);
        }

        /* Warna aksen */
        .text-navy {
            color: #0A192F;
        }
        
        .text-orange-soft {
            color: #F4A261;
        }
        
        .text-green-soft {
            color: #90BE6D;
        }
        
        .bg-navy {
            background-color: #0A192F;
        }
        
        .bg-orange-soft {
            background-color: #F4A261;
        }
        
        .bg-green-soft {
            background-color: #90BE6D;
        }
        
        .border-navy {
            border-color: #0A192F;
        }
        
        .border-orange-soft {
            border-color: #F4A261;
        }
        
        .border-green-soft {
            border-color: #90BE6D;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Sidebar tidak di-include -->

    <div class="w-full transition-all duration-300">
        <!-- Header dengan gradient biru navy, orange, hijau soft -->
        <header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
            <div class="flex items-center space-x-3">
                <i class="fas fa-book-open text-white text-2xl"></i>
                <h1 class="text-2xl font-bold text-white">Mata Pelajaran</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <span class="text-white text-sm opacity-90">Selamat datang,</span>
                    <p class="text-white font-semibold"><?= $_SESSION['nama']; ?></p>
                </div>
                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
                    <span class="text-white font-bold text-lg"><?= substr($_SESSION['nama'], 0, 1); ?></span>
                </div>
            </div>
        </header>

        <main class="p-6">
            <!-- Tombol Kembali ke Dashboard -->
            <div class="mb-6">
                <a href="dashboard.php" class="back-btn inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 hover:border-[#F4A261] hover:text-[#0A192F] shadow-sm transition-all duration-300 group">
                    <i class="fas fa-arrow-left mr-2 text-sm group-hover:-translate-x-1 transition-transform duration-300"></i>
                    <span class="font-medium">Kembali ke Dashboard</span>
                </a>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Daftar Mata Pelajaran</h2>
                        <p class="text-sm text-gray-500 mt-1">Kelola mata pelajaran yang akan digunakan dalam ujian</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                        <form method="GET" action="" class="flex-1 sm:flex-initial">
                            <div class="relative">
                                <input type="text" 
                                       name="search" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Cari kode/nama mapel..." 
                                       class="w-full sm:w-80 px-4 py-2.5 pl-10 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 text-sm"></i>
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="absolute right-3 top-3.5 text-gray-400 hover:text-[#F4A261] transition">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <button onclick="bukaModalTambah()" 
                                class="btn-gradient text-white font-semibold py-2.5 px-6 rounded-xl flex items-center justify-center shadow-lg transition-all duration-300">
                            <i class="fas fa-plus mr-2"></i> Tambah Mapel
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-y border-gray-100">
                                <th class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                                <th class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Mapel</th>
                                <th class="py-4 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                                <th class="py-4 px-6 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($mapel) > 0): ?>
                                <?php $no = 1;
                                foreach ($mapel as $row): ?>
                                    <tr class="table-row-hover transition duration-300">
                                        <td class="py-4 px-6 text-sm font-medium text-gray-900"><?= $no++; ?></td>
                                        <td class="py-4 px-6">
                                            <span class="px-3 py-1.5 bg-gradient-to-r from-blue-50 to-orange-50 text-[#0A192F] rounded-lg font-mono font-bold text-sm border border-[#F4A261]">
                                                <?= htmlspecialchars($row['kode_mapel']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-700 font-medium">
                                            <?= htmlspecialchars($row['nama_mapel']); ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button onclick="bukaModalEdit(<?= $row['id_mapel']; ?>, '<?= htmlspecialchars(addslashes($row['kode_mapel'])); ?>', '<?= htmlspecialchars(addslashes($row['nama_mapel'])); ?>')" 
                                                        class="action-btn w-9 h-9 rounded-xl bg-yellow-50 text-yellow-600 hover:bg-[#F4A261] hover:text-white flex items-center justify-center transition-all border border-yellow-200 hover:border-[#F4A261]">
                                                    <i class="fas fa-pen text-sm"></i>
                                                </button>
                                                <button onclick="konfirmasiHapus(<?= $row['id_mapel']; ?>)" 
                                                        class="action-btn w-9 h-9 rounded-xl bg-red-50 text-red-600 hover:bg-[#90BE6D] hover:text-white flex items-center justify-center transition-all border border-red-200 hover:border-[#90BE6D]">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="h-24 w-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <i class="fas fa-folder-open text-gray-400 text-4xl"></i>
                                            </div>
                                            <h3 class="text-gray-700 font-semibold text-lg mb-1">Belum ada data mapel</h3>
                                            <p class="text-gray-500 text-sm mb-4">
                                                <?php if (!empty($search)): ?>
                                                    Pencarian "<?= htmlspecialchars($search) ?>" tidak ditemukan
                                                <?php else: ?>
                                                    Klik tombol tambah untuk menambahkan mata pelajaran
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($search)): ?>
                                                <a href="?" class="text-[#0A192F] hover:text-[#F4A261] font-medium text-sm">
                                                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke semua data
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Footer Table -->
                <?php if (count($mapel) > 0): ?>
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
                    <div>
                        <i class="fas fa-info-circle mr-1 text-[#0A192F]"></i>
                        Menampilkan <?= count($mapel) ?> mata pelajaran
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                            <i class="fas fa-circle text-[8px] mr-1"></i> Aktif
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Tambah/Edit Mapel dengan desain lebih menarik -->
    <div id="modalMapel" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <!-- Header Modal dengan gradient baru -->
            <div class="gradient-bg px-6 py-4">
                <h3 id="judulModal" class="text-xl font-bold text-white">Tambah Mapel</h3>
            </div>
            
            <!-- Body Modal -->
            <form action="../../proses/mapel_proses.php" method="POST" class="p-6">
                <input type="hidden" name="id_mapel" id="id_mapel">

                <div class="mb-5">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        <i class="fas fa-code mr-1 text-[#0A192F]"></i> Kode Mata Pelajaran
                    </label>
                    <input type="text" name="kode_mapel" id="kode_mapel" required 
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0A192F] focus:border-[#0A192F] transition uppercase" 
                           placeholder="Contoh: MTK / BINDO">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        <i class="fas fa-book mr-1 text-[#F4A261]"></i> Nama Mata Pelajaran
                    </label>
                    <input type="text" name="nama_mapel" id="nama_mapel" required 
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#F4A261] focus:border-[#F4A261] transition" 
                           placeholder="Contoh: Bahasa Indonesia">
                </div>

                <!-- Footer Modal -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="tutupModal()" 
                            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium">
                        Batal
                    </button>
                    <button type="submit" name="add_mapel" id="btnSimpan" 
                            class="btn-gradient text-white px-6 py-2.5 rounded-xl hover:shadow-lg transition font-medium">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambah() {
            document.getElementById('modalMapel').classList.replace('hidden', 'flex');
            document.getElementById('judulModal').innerText = "Tambah Mapel Baru";
            document.getElementById('btnSimpan').name = "add_mapel";
            document.getElementById('id_mapel').value = "";
            document.getElementById('kode_mapel').value = "";
            document.getElementById('nama_mapel').value = "";
        }

        function bukaModalEdit(id, kode, nama) {
            document.getElementById('modalMapel').classList.replace('hidden', 'flex');
            document.getElementById('judulModal').innerText = "Edit Mapel";
            document.getElementById('btnSimpan').name = "edit_mapel";
            document.getElementById('id_mapel').value = id;
            document.getElementById('kode_mapel').value = kode;
            document.getElementById('nama_mapel').value = nama;
        }

        function tutupModal() {
            document.getElementById('modalMapel').classList.replace('flex', 'hidden');
        }

        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Mapel?',
                text: "Data yang terhubung dengan mapel ini akan ikut terpengaruh!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#90BE6D',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#fff',
                backdrop: `
                    rgba(0,0,0,0.4)
                    left top
                    no-repeat
                `
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/mapel_proses.php?hapus=" + id;
                }
            })
        }

        // Tutup modal jika klik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('modalMapel');
            if (event.target === modal) {
                tutupModal();
            }
        }
    </script>

    <?php if (isset($_SESSION['notif'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['notif']['type']; ?>',
                title: '<?= $_SESSION['notif']['title']; ?>',
                text: '<?= $_SESSION['notif']['text']; ?>',
                timer: 2000,
                showConfirmButton: false,
                background: '#fff',
                confirmButtonColor: '#0A192F',
                backdrop: `
                    rgba(0,0,0,0.4)
                    left top
                    no-repeat
                `
            });
        </script>
    <?php unset($_SESSION['notif']);
    endif; ?>
</body>

</html>